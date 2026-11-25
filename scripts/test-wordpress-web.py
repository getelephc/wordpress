#!/usr/bin/env python3
"""Compare native HTTP pages with the complete original WordPress PHP rendering."""

import argparse
from contextlib import contextmanager
import difflib
import hashlib
from html import escape
from html.parser import HTMLParser
import json
import os
from pathlib import Path
import re
import resource
import shutil
import signal
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"
ENTRY = ROOT / "elephc/full-source/web.php"
FIXTURES = ROOT / "elephc/full-source/fixtures"
CASES = ROOT / "tests/full-source/bootstrap-render-cases.json"
COMPLEX_POST = "post-1004"
ASSETS = {
    "/wp-includes/css/dist/block-library/style.min.css": "text/css",
    "/wp-includes/images/w-logo-blue.png": "image/png",
}
POSTS = {
    1004: ("Gutenberg blocks in depth", "This paragraph sits inside the nested columns."),
    1003: ("From SQLite to the page", "SQLite stores the three posts"),
    1002: ("How WordPress renders blocks", "WordPress generates this HTML"),
    1001: ("A first WordPress post", "This post comes from the WordPress posts table."),
}


def sha(path):
    with path.open("rb") as source:
        return hashlib.file_digest(source, "sha256").hexdigest()


def request(url, method="GET"):
    try:
        response = urllib.request.urlopen(urllib.request.Request(url, method=method), timeout=90)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.status, response.headers, response.read()


class LatestPostsLinks(HTMLParser):
    def __init__(self):
        super().__init__()
        self.hrefs = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "a" and "wp-block-latest-posts__post-title" in attrs.get("class", "").split():
            self.hrefs.append(attrs.get("href", ""))


def post_links(body):
    html = body.decode("utf-8")
    if '<html lang="en">' not in html or 'Compiled with Elephc' not in html:
        raise SystemExit("The homepage is missing its English language or masthead")
    if '<h2 class="wp-block-heading">Latest posts</h2>' not in html:
        raise SystemExit("The homepage is missing its latest-posts heading")
    links = LatestPostsLinks()
    links.feed(html)
    expected = [f"http://127.0.0.1:9000/?p={post_id}" for post_id in POSTS]
    if links.hrefs != expected:
        raise SystemExit(f"Expected WordPress to generate four ordered post links, got {links.hrefs}")
    return ["/?" + urllib.parse.urlsplit(link).query for link in links.hrefs]


@contextmanager
def web_server(command, port, log_path, *, env=None):
    with log_path.open("w") as log:
        server = subprocess.Popen(
            command, cwd=ROOT, env=env, stdout=log, stderr=subprocess.STDOUT, start_new_session=True,
            preexec_fn=lambda: resource.setrlimit(resource.RLIMIT_CORE, (0, 0)),
        )
        try:
            deadline = time.monotonic() + 30
            while True:
                if server.poll() is not None:
                    raise SystemExit("Web server exited:\n" + log_path.read_text())
                try:
                    with socket.create_connection(("127.0.0.1", port), timeout=1):
                        break
                except OSError:
                    if time.monotonic() >= deadline:
                        raise SystemExit(f"The web server did not listen on port {port} within 30 seconds")
                    time.sleep(0.1)
            yield f"http://127.0.0.1:{port}"
            if server.poll() is not None:
                raise SystemExit("The web server did not survive repeated requests")
        finally:
            if server.poll() is None:
                os.killpg(server.pid, signal.SIGTERM)
                try:
                    server.wait(timeout=15)
                except subprocess.TimeoutExpired:
                    os.killpg(server.pid, signal.SIGKILL)
                    server.wait()


@contextmanager
def php_reference_server():
    with tempfile.TemporaryDirectory(prefix=".web-php-reference-", dir=OUT) as temporary:
        directory = Path(temporary)
        wordpress = directory / "wordpress"
        shutil.copytree(ROOT / "upstream/wordpress", wordpress)
        for path in FIXTURES.rglob("*"):
            if path.is_file():
                target = wordpress / path.relative_to(FIXTURES)
                if target.exists():
                    raise SystemExit(f"Web reference fixture would overwrite original WordPress: {target.name}")
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copyfile(path, target)
        entry = directory / "web.php"
        entry.write_text(ENTRY.read_text().replace('/wordpress/aot-platform.lfc', '/wordpress/aot-php-platform.php'))
        with socket.socket() as probe:
            probe.bind(("127.0.0.1", 0))
            port = probe.getsockname()[1]
        command = [os.environ.get("PHP_BIN", "php"), "-d", "display_errors=stderr",
                   "-S", f"127.0.0.1:{port}", "-t", str(directory), str(entry)]
        env = dict(os.environ)
        env.pop("PHP_CLI_SERVER_WORKERS", None)
        log_path = OUT / "web-php-reference.log"
        with web_server(command, port, log_path, env=env) as base_url:
            yield base_url
        if re.search(r"PHP (Fatal error|Warning|Notice|Deprecated|Parse error)", log_path.read_text()):
            raise SystemExit("Unexpected stock PHP diagnostics:\n" + log_path.read_text())


def php_reference_pages(base_url, cases):
    def fetch(path):
        status, _, body = request(base_url + path)
        if status != 200:
            raise SystemExit(f"Original PHP failed for {path} with HTTP {status}: {body[:1000]!r}")
        return body

    pages = {"default": ("/", fetch("/"))}
    for name, document in cases.items():
        path = "/?" + urllib.parse.urlencode({"document": document})
        pages[name] = (path, fetch(path))
    for post_id, path in zip(POSTS, post_links(pages["default"][1]), strict=True):
        body = fetch(path)
        title, excerpt = POSTS[post_id]
        html = body.decode("utf-8")
        if f"<h1>{title}</h1>" not in html or excerpt not in html or 'href="/">All posts</a>' not in html:
            raise SystemExit(f"Original PHP did not render the title, body and back link of article {post_id}")
        pages[f"post-{post_id}"] = (path, body)
    return pages


class PostContent(HTMLParser):
    """Keep source spans for block hashes and normalized tokens for the diff."""

    block_classes = {"wp-block-" + name: name for name in
                     ("columns", "column", "heading", "list", "image", "quote", "separator")}
    void = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta",
            "param", "source", "track", "wbr"}
    volatile = {"nonce", "_wpnonce", "data-nonce", "data-timestamp", "data-cache-buster"}
    volatile_query = {"_", "_wpnonce", "ver", "timestamp", "cache-bust", "cacheBust"}

    def __init__(self, body):
        super().__init__()
        self.html = body.decode("utf-8")
        self.line_offsets = [0] + [match.end() for match in re.finditer("\n", self.html)]
        self.stack = []
        self.lines = []
        self.blocks = []
        self.found = False
        self.feed(self.html)
        self.close()
        if not self.found or self.stack:
            raise ValueError("Missing or incomplete post-content container")

    def source_offset(self):
        line, column = self.getpos()
        return self.line_offsets[line - 1] + column

    def start(self, tag, attrs, *, closes=False):
        values = dict(attrs)
        if not self.stack:
            if tag == "div" and values.get("class") == "post-content":
                if self.found:
                    raise ValueError("Multiple post-content containers")
                self.found = True
                self.stack.append((tag, None))
            return

        classes = (values.get("class") or "").split()
        name = next((self.block_classes[value] for value in classes if value in self.block_classes), None)
        name = name or {"p": "paragraph", "li": "list-item"}.get(tag)
        block = None
        start = self.source_offset()
        if name:
            block = {"name": "core/" + name, "depth": len(self.stack) - 1, "start": start}
            self.blocks.append(block)
        normalized = []
        for key, value in sorted(attrs, key=lambda attr: attr[0]):
            if key in self.volatile or (tag == "time" and key == "datetime"):
                continue
            if value is None:
                normalized.append(" " + key)
                continue
            if key in {"href", "src", "poster"} and "?" in value:
                url = urllib.parse.urlsplit(value)
                query = [pair for pair in url.query.split("&")
                         if urllib.parse.unquote_plus(pair.split("=", 1)[0]) not in self.volatile_query]
                value = urllib.parse.urlunsplit(url._replace(query="&".join(query)))
            normalized.append(f' {key}="{escape(value, quote=True)}"')
        self.lines.append("<" + tag + "".join(normalized) + ">\n")
        if closes or tag in self.void:
            if block:
                self.finish_block(block, start + len(self.get_starttag_text()))
        else:
            self.stack.append((tag, block))

    def handle_starttag(self, tag, attrs):
        self.start(tag, attrs)

    def handle_startendtag(self, tag, attrs):
        self.start(tag, attrs, closes=True)

    def handle_endtag(self, tag):
        if not self.stack:
            return
        if self.stack[-1][0] != tag:
            raise ValueError(f"Unexpected closing tag in post content: {tag}")
        _, block = self.stack.pop()
        if self.stack:
            self.lines.append(f"</{tag}>\n")
        if block:
            self.finish_block(block, self.html.index(">", self.source_offset()) + 1)

    def handle_data(self, data):
        if self.stack and data.strip():
            value = re.sub(r"\b\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?\b",
                           "[timestamp]", data)
            self.lines.append(escape(value, quote=False) + "\n")

    def handle_comment(self, data):
        if self.stack:
            self.lines.append(f"<!--{data}-->\n")

    def finish_block(self, block, end):
        fragment = self.html[block.pop("start"):end].encode("utf-8")
        block["sha256"] = hashlib.sha256(fragment).hexdigest()


def complex_post_parity(reference_body, native_body, artifacts):
    artifacts.mkdir(parents=True, exist_ok=True)
    (artifacts / "stock-php.html").write_bytes(reference_body)
    (artifacts / "native.html").write_bytes(native_body)
    reference = PostContent(reference_body)
    native = PostContent(native_body)
    for name, content in (("stock-php", reference), ("native", native)):
        (artifacts / f"{name}-normalized.html").write_text("".join(content.lines))
    diff = "".join(difflib.unified_diff(reference.lines, native.lines, "stock-php", "native"))
    (artifacts / "content.diff").write_text(diff)
    blocks = []
    for index in range(max(len(reference.blocks), len(native.blocks))):
        expected = reference.blocks[index] if index < len(reference.blocks) else None
        actual = native.blocks[index] if index < len(native.blocks) else None
        blocks.append({"index": index + 1, "stock_php": expected, "native": actual, "matches": expected == actual})
    report = {"normalized_html_matches": not diff, "raw_html_matches": reference_body == native_body, "blocks": blocks}
    (artifacts / "block-parity.json").write_text(json.dumps(report, indent=2) + "\n")
    failures = [f'{block["index"]} ({(block["stock_php"] or block["native"])["name"]})'
                for block in blocks if not block["matches"]]
    return report, failures


def verify_complex_post_reference(body):
    content = PostContent(body)
    shape = [(row["depth"], row["name"].removeprefix("core/")) for row in content.blocks]
    expected = [
        (0, "columns"), (1, "column"), (2, "paragraph"), (1, "column"), (2, "columns"),
        (3, "column"), (4, "paragraph"), (3, "column"), (4, "heading"), (4, "list"),
        (5, "list-item"), (5, "list-item"), (1, "column"), (2, "paragraph"),
        (0, "image"), (0, "quote"), (1, "paragraph"), (0, "separator"),
    ]
    if shape != expected or not re.search(rb"<figcaption\b[^>]*>The WordPress logo bundled with the release\.</figcaption>", body):
        raise SystemExit(f"Stock PHP did not render the expected block tree and caption: {shape}")


def screenshot_parity(browser, native_url, php_url, artifacts):
    results = []
    with tempfile.TemporaryDirectory(prefix=".browser-", dir=artifacts) as profile:
        for width in (390, 1280):
            height = 1320 if width == 390 else 1200
            paths = {}
            for name, url in (("stock-php", php_url), ("native", native_url)):
                path = artifacts / f"{name}-{width}.png"
                path.unlink(missing_ok=True)
                command = [
                    str(browser.resolve()), "--headless", "--disable-gpu", "--hide-scrollbars",
                    "--force-device-scale-factor=1", "--disable-background-networking",
                    f"--user-data-dir={profile}", f"--window-size={width},{height}", f"--screenshot={path}", url,
                ]
                result = subprocess.run(command, capture_output=True, text=True, timeout=90)
                if result.returncode or not path.is_file():
                    raise SystemExit(f"Headless screenshot failed for {name} at {width}px:\n{result.stderr}")
                dimensions = subprocess.check_output(["magick", "identify", "-format", "%wx%h", path], text=True)
                if dimensions != f"{width}x{height}":
                    raise SystemExit(f"Unexpected screenshot dimensions for {path}: {dimensions}")
                paths[name] = path
            diff = artifacts / f"pixel-diff-{width}.png"
            diff.unlink(missing_ok=True)
            comparison = subprocess.run(
                ["magick", "compare", "-metric", "AE", paths["stock-php"], paths["native"], diff],
                capture_output=True, text=True, timeout=30,
            )
            if comparison.returncode not in (0, 1) or not diff.is_file():
                raise SystemExit("Screenshot comparison failed: " + comparison.stderr)
            changed = int(comparison.stderr.split()[0])
            results.append({"width": width, "height": height, "different_pixels": changed, "diff": diff.name})
            print(f"{width}px screenshot comparison: {changed} different pixels", flush=True)
    return results


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--native", type=Path, default=OUT / "bootstrap-web")
    parser.add_argument("--port", type=int, default=9000)
    parser.add_argument("--reference-only", action="store_true", help="Verify the web fixture under original WordPress PHP")
    parser.add_argument("--artifacts", type=Path, default=OUT / "complex-post-parity")
    parser.add_argument("--browser", type=Path, help="Headless Chromium executable for screenshot parity (requires ImageMagick)")
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    cases = json.loads(CASES.read_text())
    if args.reference_only:
        subprocess.run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT, check=True)
        with php_reference_server() as base_url:
            pages = php_reference_pages(base_url, cases)
            verify_complex_post_reference(pages[COMPLEX_POST][1])
        print(f"PASS: {len(pages)} original PHP web pages, including all four linked posts")
        return
    binary = args.native.resolve()
    if not binary.is_file():
        parser.error("Build the native WordPress server with scripts/build-wordpress.sh --web")
    with binary.open("rb") as source:
        if source.read(4) != b"\x7fELF":
            parser.error("The native server must be an ELF executable")
    with socket.socket() as probe:
        try:
            probe.bind(("127.0.0.1", args.port))
        except OSError:
            parser.error(f"Port {args.port} is already occupied; select a free --port")
    source_hash = sha(OUT / "source-manifest.json")
    report_path = OUT / "web-verification.json"
    report_path.unlink(missing_ok=True)
    build = json.loads((OUT / "bootstrap-web-build.json").read_text())
    if (build["in_progress"] or build["exit_code"] != 0 or not build["web"]
            or not build["inputs_unchanged"] or build["native_sha256"] != sha(binary)
            or build["source_manifest_sha256"] != source_hash):
        raise SystemExit("The web executable does not match its completed native build")

    # Recreate the reference from unmodified WordPress, retaining CLI parity when available.
    reference = ["python3", "scripts/test-wordpress-bootstrap.py"]
    cli = OUT / "bootstrap"
    if cli.is_file():
        reference += ["--native", str(cli)]
    subprocess.run(reference, cwd=ROOT, check=True)
    baseline = json.loads((OUT / "bootstrap-verification.json").read_text())
    if baseline["source_manifest_sha256"] != source_hash:
        raise SystemExit("The PHP reference uses different prepared sources")
    link = json.loads((OUT / "bootstrap-web-link.json").read_text())
    if (not link["linked"] or link["function_folding"]
            or link["source_manifest_sha256"] != source_hash
            or link["allocated_contents_sha256"] != link["placed_allocated_contents_sha256"]):
        raise SystemExit("The web executable lacks matching content-preserving linker provenance")
    entry = OUT / "bootstrap-web.php"
    if sha(entry) != sha(ENTRY):
        raise SystemExit("Rebuild the server after changing the maintained HTTP entrypoint")
    if build["entry_sha256"] != sha(entry) or link["entry_sha256"] != sha(entry):
        raise SystemExit("The web build and link refer to another HTTP entrypoint")

    log_path = OUT / "web-test-server.log"
    env = dict(os.environ)
    env["WORDPRESS_WEB_PORT"] = str(args.port)
    command = [str(ROOT / "scripts/serve-wordpress.sh")]
    if binary != (OUT / "bootstrap-web").resolve():
        command = [str(binary), "--listen", f"127.0.0.1:{args.port}", "--workers", "1",
                   "--max-body-size", "65536"]
    results = []
    with php_reference_server() as reference_url:
        pages = php_reference_pages(reference_url, cases)
        verify_complex_post_reference(pages[COMPLEX_POST][1])
        with web_server(command, args.port, log_path, env=env) as base_url:
            sequence = [COMPLEX_POST, *(name for name in pages if name != COMPLEX_POST), "default", "nested-unicode", "navigation-names", "default"]
            for name in sequence:
                path, expected = pages[name]
                status, headers, body = request(base_url + path)
                if name == COMPLEX_POST:
                    artifacts.mkdir(parents=True, exist_ok=True)
                    (artifacts / "stock-php.html").write_bytes(expected)
                    (artifacts / "native.html").write_bytes(body)
                if (status != 200 or not headers.get("Content-Type", "").startswith("text/html")
                        or headers.get("X-WordPress-Execution") != "elephc-native"
                        or not body.startswith(b"<!doctype html>")):
                    raise SystemExit(f"Invalid native HTML response for {name}: {status}\n{body[:2000]!r}")
                if name == COMPLEX_POST:
                    complex_report, failed_blocks = complex_post_parity(expected, body, artifacts)
                    if args.browser:
                        complex_report["screenshots"] = screenshot_parity(
                            args.browser, base_url + path, reference_url + path, artifacts
                        )
                        (artifacts / "block-parity.json").write_text(json.dumps(complex_report, indent=2) + "\n")
                    if failed_blocks or not complex_report["normalized_html_matches"]:
                        raise SystemExit(f"Complex post differs at blocks {', '.join(failed_blocks)}. See {artifacts}")
                    if any(row["different_pixels"] for row in complex_report.get("screenshots", ())):
                        raise SystemExit(f"Complex post screenshots differ. See {artifacts}")
                    print(f"PASS: {len(complex_report['blocks'])} complex-post block hashes and normalized HTML", flush=True)
                if body != expected:
                    (OUT / "web-native-mismatch.json").write_text(json.dumps(
                        {"case": name, "php": expected.decode("utf-8"), "native": body.decode("utf-8")}, indent=2) + "\n")
                    raise SystemExit("Native HTTP rendering differs from original PHP: " + name)
                results.append({"case": name, "status": status, "matches_original_php": True,
                                "html_sha256": hashlib.sha256(body).hexdigest()})
                print("PASS: native HTTP WordPress rendering matches original PHP: " + name, flush=True)

            for path, method, expected_status in [
                ("/", "HEAD", 200), ("/missing", "GET", 404), ("/", "POST", 405),
                ("/?document=%00", "GET", 400), ("/?p=9999", "GET", 404),
                ("/?p=bad", "GET", 400), ("/?p=1001&document=", "GET", 400),
            ]:
                status, headers, body = request(base_url + path, method)
                if status != expected_status or (method == "HEAD" and body):
                    raise SystemExit(f"Unexpected HTTP boundary result: {method} {path}: {status}")
                if method == "POST" and headers.get("Allow") != "GET, HEAD":
                    raise SystemExit("The method guard lost its Allow header")
                if path == "/?p=9999" and body != b"Post not found\n":
                    raise SystemExit("The missing-post response lost its English message")
                results.append({"path": path, "method": method, "status": status})
            for path, content_type in ASSETS.items():
                php_status, php_headers, php_body = request(reference_url + path)
                status, headers, body = request(base_url + path)
                upstream_bytes = (ROOT / "upstream/wordpress" / path.removeprefix("/")).read_bytes()
                if (status != 200 or php_status != 200 or not headers["Content-Type"].startswith(content_type)
                        or not php_headers["Content-Type"].startswith(content_type) or body != php_body or body != upstream_bytes):
                    raise SystemExit(f"Block fixture asset differs from the original WordPress archive: {path}")
                results.append({"path": path, "status": status, "matches_original_php": True,
                                "asset_sha256": hashlib.sha256(body).hexdigest()})

    diagnostics = (OUT / "web-test-server.log").read_text()
    if re.search(r"fatal error|uncaught|segmentation fault|sigsegv|panicked|double free", diagnostics, re.I):
        raise SystemExit("Unexpected native web diagnostics:\n" + diagnostics[-4000:])

    report = {
        "schema": 1, "verified": True, "native_server": True, "php_subprocess_serving": False,
        "source_manifest_sha256": source_hash, "compiler_sha256": link["compiler_sha256"],
        "native_sha256": sha(binary), "entry_sha256": sha(entry), "harness_sha256": sha(Path(__file__)),
        "build_driver_sha256": build["driver_sha256"], "runtime_diagnostics_clean": True,
        "cases_sha256": sha(CASES),
        "web_isolation": "request", "workers": 1, "requests": results,
        "cli_native_also_verified": baseline["native_verified"],
        "complex_post": complex_report,
        "note": "Live HTML rendered by the original WordPress bootstrap and SQLite-backed post APIs inside native HTTP requests.",
    }
    report_path.write_text(json.dumps(report, indent=2) + "\n")
    print(f"PASS: {len(results)} native HTTP checks; complete original-PHP rendering parity", flush=True)


if __name__ == "__main__":
    main()
