#!/usr/bin/env python3
"""Compare the native CLI and HTTP parser with unmodified WordPress under PHP."""

import http.client
import json
import os
from pathlib import Path
import signal
import socket
import subprocess
import tempfile
import time


ROOT = Path(__file__).resolve().parent.parent


def run_json(command):
    result = subprocess.run(command, cwd=ROOT, capture_output=True, text=True, check=True, timeout=20)
    return json.loads(result.stdout)


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
