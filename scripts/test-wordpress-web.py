#!/usr/bin/env python3
"""Compare native HTTP pages with the complete original WordPress PHP rendering."""

import argparse
import hashlib
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
POSTS = {
    1003: ("Da SQLite alla pagina", "SQLite conserva i tre articoli"),
    1002: ("Come vengono renderizzati i blocchi", "Il core costruisce questo HTML"),
    1001: ("Un primo articolo su WordPress", "Questo articolo arriva dalla tabella"),
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
    if "Ultimi articoli" not in body.decode("utf-8"):
        raise SystemExit("The homepage is missing its latest-posts heading")
    links = LatestPostsLinks()
    links.feed(body.decode("utf-8"))
    expected = [f"http://127.0.0.1:9000/?p={post_id}" for post_id in POSTS]
    if links.hrefs != expected:
        raise SystemExit(f"Expected WordPress to generate three ordered post links, got {links.hrefs}")
    return ["/?" + urllib.parse.urlsplit(link).query for link in links.hrefs]


def php_reference_pages(cases):
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
        php_driver = '''
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = $argv[2];
            $_SERVER['PHP_SELF'] = '/index.php';
            $_SERVER['HTTP_HOST'] = '127.0.0.1:9000';
            parse_str(parse_url($argv[2], PHP_URL_QUERY) ?? '', $_GET);
            require $argv[1];
            fwrite(STDERR, 'STATUS ' . http_response_code());
        '''

        def fetch(path):
            completed = subprocess.run(
                [os.environ.get("PHP_BIN", "php"), "-d", "display_errors=stderr", "-r", php_driver, str(entry), path],
                cwd=ROOT, capture_output=True, timeout=30,
            )
            if completed.returncode or completed.stderr != b"STATUS 200":
                raise SystemExit(f"Original PHP failed for {path}:\n{completed.stderr.decode(errors='replace')}")
            return completed.stdout

        pages = {"default": ("/", fetch("/"))}
        for name, document in cases.items():
            path = "/?" + urllib.parse.urlencode({"document": document})
            pages[name] = (path, fetch(path))
        for post_id, path in zip(POSTS, post_links(pages["default"][1]), strict=True):
            body = fetch(path)
            title, excerpt = POSTS[post_id]
            html = body.decode("utf-8")
            if f"<h1>{title}</h1>" not in html or excerpt not in html or 'href="/">Tutti gli articoli' not in html:
                raise SystemExit(f"Original PHP did not render the title, body and back link of article {post_id}")
            pages[f"post-{post_id}"] = (path, body)
    return pages


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--native", type=Path, default=OUT / "bootstrap-web")
    parser.add_argument("--port", type=int, default=9000)
    parser.add_argument("--reference-only", action="store_true", help="Verify the web fixture under original WordPress PHP")
    args = parser.parse_args()
    cases = json.loads(CASES.read_text())
    if args.reference_only:
        subprocess.run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT, check=True)
        pages = php_reference_pages(cases)
        print(f"PASS: {len(pages)} original PHP web pages, including all three linked posts")
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

    pages = php_reference_pages(cases)
    base_url = f"http://127.0.0.1:{args.port}"
    results = []
    with (OUT / "web-test-server.log").open("w") as log:
        server = subprocess.Popen(
            [str(binary), "--listen", f"127.0.0.1:{args.port}", "--workers", "1",
             "--max-body-size", "65536"],
            cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, start_new_session=True,
            preexec_fn=lambda: resource.setrlimit(resource.RLIMIT_CORE, (0, 0)),
        )
        try:
            deadline = time.monotonic() + 30
            while True:
                if server.poll() is not None:
                    raise SystemExit("Native server exited:\n" + (OUT / "web-test-server.log").read_text())
                try:
                    with socket.create_connection(("127.0.0.1", args.port), timeout=1):
                        break
                except OSError:
                    if time.monotonic() >= deadline:
                        raise SystemExit("The native server did not listen within 30 seconds")
                    time.sleep(0.1)

            sequence = [*pages, "default", "nested-unicode", "navigation-names", "default"]
            for name in sequence:
                path, expected = pages[name]
                status, headers, body = request(base_url + path)
                if (status != 200 or not headers.get("Content-Type", "").startswith("text/html")
                        or headers.get("X-WordPress-Execution") != "elephc-native"
                        or not body.startswith(b"<!doctype html>")):
                    raise SystemExit(f"Invalid native HTML response for {name}: {status}\n{body[:2000]!r}")
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
                results.append({"path": path, "method": method, "status": status})
            if server.poll() is not None:
                raise SystemExit("The native master did not survive repeated requests")
        finally:
            if server.poll() is None:
                os.killpg(server.pid, signal.SIGTERM)
                try:
                    server.wait(timeout=15)
                except subprocess.TimeoutExpired:
                    os.killpg(server.pid, signal.SIGKILL)
                    server.wait()

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
        "note": "Live HTML rendered by the original WordPress bootstrap and SQLite-backed post APIs inside native HTTP requests.",
    }
    report_path.write_text(json.dumps(report, indent=2) + "\n")
    print(f"PASS: {len(results)} native HTTP checks; complete original-PHP rendering parity", flush=True)


if __name__ == "__main__":
    main()
