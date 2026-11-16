#!/usr/bin/env python3
"""Compare native blocks, hooks and rendered content with unmodified WordPress."""

import http.client
import json
import os
from pathlib import Path
import signal
import socket
import subprocess
import tempfile
import time
import urllib.parse


ROOT = Path(__file__).resolve().parent.parent


def run_output(command):
    result = subprocess.run(command, cwd=ROOT, capture_output=True, text=True, check=True, timeout=20)
    assert not result.stderr, f"Unexpected diagnostics from {command[0]}: {result.stderr}"
    return result.stdout


def run_json(command):
    return json.loads(run_output(command))


def main():
    fixtures = json.loads((ROOT / "tests/fixtures.json").read_text())
    php = os.environ.get("PHP_BIN", "php")
    reference = run_json([php, "tests/reference.php", "upstream"])
    patched = run_json([php, "tests/reference.php", "profile"])
    assert patched == reference, "Patched PHP differs from original WordPress"

    for fixture in fixtures:
        actual = run_json([str(ROOT / "elephc/runtime/parse"), fixture["document"]])
        assert actual == reference[fixture["name"]], f"Native CLI differs: {fixture['name']}\n{actual}"
    print(f"PASS: {len(fixtures)} fixtures match upstream PHP in patched PHP and native CLI.")

    hook_input = 'WordPress 🐘'
    hook_fixtures = json.loads((ROOT / "tests/hooks-fixtures.json").read_text())
    hook_reference = run_json([php, "tests/hooks-reference.php", "upstream"])
    hook_patched = run_json([php, "tests/hooks-reference.php", "profile"])
    assert hook_patched == hook_reference, "Patched hooks differ from original WordPress"
    for scenario in hook_fixtures:
        actual = run_json([str(ROOT / "elephc/runtime/hooks"), scenario, hook_input])
        assert actual == hook_reference[scenario], f"Native hook CLI differs: {scenario}\n{actual}"
    print(f"PASS: {len(hook_fixtures)} hook scenarios match upstream PHP in patched PHP and native CLI.")

    render_fixtures = json.loads((ROOT / "tests/render-fixtures.json").read_text())
    render_reference = run_json([php, "tests/render-reference.php", "upstream"])
    render_patched = run_json([php, "tests/render-reference.php", "profile"])
    assert render_patched == render_reference, "Patched rendering differs from original WordPress"
    for fixture in render_fixtures:
        actual = run_output([str(ROOT / "elephc/runtime/render"), fixture["mode"], fixture["input"]])
        assert actual == render_reference[fixture["name"]], f"Native rendering CLI differs: {fixture['name']}\n{actual!r}"
    print(f"PASS: {len(render_fixtures)} render fixtures match upstream PHP in patched PHP and native CLI.")

    with socket.socket() as listener:
        listener.bind(("127.0.0.1", 0))
        port = listener.getsockname()[1]

    def request(method, path, body=None):
        connection = http.client.HTTPConnection("127.0.0.1", port, timeout=5)
        try:
            connection.request(method, path, body=body, headers={"Content-Type": "text/plain; charset=utf-8"})
            response = connection.getresponse()
            return response.status, {name.lower(): value for name, value in response.getheaders()}, response.read()
        finally:
            connection.close()

    with tempfile.TemporaryFile(dir=ROOT / "elephc/build") as log:
        process = subprocess.Popen(
            [str(ROOT / "elephc/runtime/server"), "--listen", f"127.0.0.1:{port}", "--workers", "1", "--max-body-size", "65536"],
            cwd=ROOT, stdout=log, stderr=log, start_new_session=True,
        )
        try:
            for _ in range(100):
                if process.poll() is not None:
                    log.seek(0)
                    raise AssertionError(f"Native server stopped: {log.read().decode()}")
                try:
                    request("GET", "/health")
                    break
                except OSError:
                    time.sleep(0.05)
            else:
                raise AssertionError("Native server startup timed out")

            checks = 0
            for _ in range(2):
                for fixture in fixtures:
                    status, headers, body = request("POST", "/parse", fixture["document"].encode())
                    assert status == 200, (fixture["name"], status, body)
                    assert headers["content-type"].startswith("application/json"), headers
                    actual = json.loads(body)
                    assert actual == reference[fixture["name"]], f"Native HTTP differs: {fixture['name']}\n{actual}"
                    checks += 1
                for scenario in hook_fixtures:
                    query = urllib.parse.urlencode({"case": scenario, "value": hook_input})
                    status, headers, body = request("GET", "/hooks?" + query)
                    assert status == 200, (scenario, status, body)
                    assert json.loads(body) == hook_reference[scenario], f"Native hook HTTP differs: {scenario}"
                    checks += 1
                for fixture in render_fixtures:
                    query = urllib.parse.urlencode({"mode": fixture["mode"]})
                    status, headers, body = request("POST", "/render?" + query, fixture["input"].encode())
                    assert status == 200, (fixture["name"], status, body)
                    actual = body.decode()
                    assert actual == render_reference[fixture["name"]], f"Native rendering HTTP differs: {fixture['name']}\n{actual!r}"
                    expected_type = "application/json" if fixture["mode"] in ("attributes", "inspect", "context", "registration") else "text/html"
                    assert headers["content-type"].startswith(expected_type), headers
                    checks += 1
                status, _, body = request("GET", "/render")
                assert status == 200 and b'Hello Elephc' in body and b'<div class="box">' in body
                status, _, body = request("HEAD", "/render")
                assert status == 200 and body == b""
                status, headers, body = request("PUT", "/render")
                assert status == 405 and headers["allow"] == "GET, HEAD, POST"
                status, _, body = request("POST", "/render?mode=unknown", b"input")
                assert status == 400 and json.loads(body)["error"] == "Unknown render mode"
                status, _, body = request("POST", "/render", b"x" * 65537)
                assert status == 413
                checks += 5
                status, _, body = request("POST", "/filter", hook_input.encode())
                assert status == 200 and json.loads(body) == hook_reference["priority"]
                status, _, body = request("GET", "/hooks?case=unknown")
                assert status == 400 and json.loads(body)["error"] == "Unknown hook scenario"
                status, headers, body = request("GET", "/filter")
                assert status == 405 and headers["allow"] == "POST"
                status, _, body = request("HEAD", "/hooks")
                assert status == 200 and body == b""
                checks += 4
                status, headers, body = request("GET", "/")
                assert status == 200 and b"WordPress blocks." in body
                assert headers["content-type"].startswith("text/html")
                status, _, body = request("GET", "/blocks?probe=1")
                demo = json.loads(body)
                assert status == 200 and demo[0]["blockName"] == "core/group"
                assert len(demo[0]["innerBlocks"]) == 2
                status, _, body = request("GET", "/health")
                assert status == 200 and json.loads(body)["database"] is False
                status, _, body = request("HEAD", "/")
                assert status == 200 and body == b""
                status, _, body = request("GET", "/missing")
                assert status == 404 and json.loads(body)["error"] == "Not Found"
                status, headers, body = request("GET", "/parse")
                assert status == 405 and headers["allow"] == "POST"
                status, headers, body = request("POST", "/blocks", b"ignored")
                assert status == 405 and headers["allow"] == "GET, HEAD"
                status, headers, body = request("GET", "/health")
                assert status == 200 and "allow" not in headers
                status, _, body = request("POST", "/parse", b"x" * 65537)
                assert status == 413
                checks += 9
            log.flush()
            log.seek(0)
            diagnostics = log.read()
            assert b"Warning:" not in diagnostics and b"Fatal error:" not in diagnostics, diagnostics.decode(errors="replace")
            print(f"PASS: {checks} HTTP checks, including repeated requests, HEAD, 404, 405 and body limit.")
        finally:
            try:
                os.killpg(process.pid, signal.SIGTERM)
            except ProcessLookupError:
                pass
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(process.pid, signal.SIGKILL)
                process.wait(timeout=5)


if __name__ == "__main__":
    main()
