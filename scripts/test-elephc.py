#!/usr/bin/env python3
"""Compare native blocks, serialization, hooks, content, KSES, cache, lists, URLs, sanitization, error/response/request objects with original PHP."""

import http.client
import base64
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


def run_output(command, input=None):
    result = subprocess.run(command, cwd=ROOT, input=input, capture_output=True, check=True, timeout=20)
    assert not result.stderr, f"Unexpected diagnostics from {command[0]}: {result.stderr}"
    return result.stdout.decode('utf-8')


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

    serialize_fixtures = json.loads((ROOT / "tests/serialize-fixtures.json").read_text())
    serialize_errors = json.loads((ROOT / "tests/serialize-errors.json").read_text())
    serialize_reference = run_json([php, "tests/serialize-reference.php", "upstream"])
    serialize_patched = run_json([php, "tests/serialize-reference.php", "profile"])
    assert serialize_patched == serialize_reference, "Patched serialization differs from original WordPress"
    serializer = str(ROOT / "elephc/runtime/serialize")
    for fixture in serialize_fixtures:
        expected = serialize_reference[fixture["name"]]
        actual = run_output([serializer, fixture["mode"], fixture["input"]])
        assert actual == expected["text"], f"Native serialization CLI differs: {fixture['name']}\n{actual!r}"
        parsed = run_json([str(ROOT / "elephc/runtime/parse"), actual])
        assert parsed == expected["parsed"], f"Native serialization reparse differs: {fixture['name']}"
        again = run_output([serializer, "blocks"], json.dumps(parsed, ensure_ascii=False).encode())
        assert again == expected["reserialized"], f"Native serialization normalization differs: {fixture['name']}"
    for fixture in serialize_errors:
        result = subprocess.run([serializer, fixture["mode"], fixture["input"]], cwd=ROOT, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        assert result.stderr.startswith(b"Serialization error: ") and b"Fatal error" not in result.stderr and b"Warning:" not in result.stderr, (fixture["name"], result.stderr)
    nested_document = next(f["document"] for f in fixtures if f["name"] == "nested")
    parsed_input = run_output([str(ROOT / "elephc/runtime/parse"), nested_document]).encode()
    assert run_output([serializer], parsed_input) == serialize_reference["document-nested"]["text"]
    assert run_output([serializer, "roundtrip"], nested_document.encode()) == serialize_reference["document-nested"]["text"]
    print(f"PASS: {len(serialize_fixtures)} serialization fixtures, reparsing, stdin and {len(serialize_errors)} invalid-input cases.")

    kses_fixtures = json.loads((ROOT / "tests/kses-fixtures.json").read_text())
    kses_errors = json.loads((ROOT / "tests/kses-errors.json").read_text())
    kses_reference = run_json([php, "tests/kses-reference.php", "upstream"])
    assert run_json([php, "tests/kses-reference.php", "profile"]) == kses_reference, "Patched KSES differs from original PHP"
    for fixture in kses_fixtures:
        command = [str(ROOT / "elephc/runtime/kses"), fixture["mode"]]
        data = None
        if "\0" in fixture["input"]:
            data = fixture["input"].encode()
        else:
            command.extend([fixture["input"], fixture.get("element", "a")])
        result = subprocess.run(command, input=data, capture_output=True, check=True, timeout=20)
        assert not result.stderr and result.stdout == base64.b64decode(kses_reference[fixture["name"]]), (fixture["name"], result.stdout, result.stderr)
    error_checks = 0
    for fixture in kses_errors:
        data = base64.b64decode(fixture["input_base64"])
        target = fixture.get("target", "kses")
        if target == "render" and b"\0" in data:
            continue  # Native argv cannot contain NUL; HTTP exercises this boundary.
        command = [os.fsencode(ROOT / "elephc/runtime" / target), fixture["mode"].encode()]
        stdin = data if target == "kses" else None
        if target == "render":
            command.append(data)
        result = subprocess.run(command, input=stdin, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        prefix = b"KSES error: " if target == "kses" else b"Rendering error: "
        assert result.stderr.startswith(prefix) and b"Warning:" not in result.stderr and b"Fatal error" not in result.stderr, (fixture["name"], result.stderr)
        error_checks += 1
    print(f"PASS: {len(kses_fixtures)} KSES fixtures match original PHP; {error_checks} CLI boundary checks.")

    cache_fixtures = json.loads((ROOT / "tests/cache-fixtures.json").read_text())
    cache_errors = json.loads((ROOT / "tests/cache-errors.json").read_text())
    cache_reference = run_json([php, "tests/cache-reference.php", "upstream"])
    assert run_json([php, "tests/cache-reference.php", "profile"]) == cache_reference, "Patched cache differs from original PHP"
    cache = str(ROOT / "elephc/runtime/cache")
    for fixture in cache_fixtures:
        actual = run_json([cache, fixture["input"]])
        assert actual == cache_reference[fixture["name"]], (fixture["name"], actual, cache_reference[fixture["name"]])
    assert json.loads(run_output([cache], cache_fixtures[0]["input"].encode())) == cache_reference[cache_fixtures[0]["name"]]
    for fixture in cache_errors:
        data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
        result = subprocess.run([cache], input=data, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        assert result.stderr.startswith(b"Cache error: ") and b"Fatal error" not in result.stderr and b"Warning:" not in result.stderr, (fixture["name"], result.stderr)
    print(f"PASS: {len(cache_fixtures)} object cache scenarios match original PHP; stdin and {len(cache_errors)} CLI boundary checks.")

    list_fixtures = json.loads((ROOT / "tests/lists-fixtures.json").read_text())
    list_errors = json.loads((ROOT / "tests/lists-errors.json").read_text())
    list_reference = run_json([php, "tests/lists-reference.php", "upstream"])
    assert run_json([php, "tests/lists-reference.php", "profile"]) == list_reference, "Patched list utilities differ from original PHP"
    lists = str(ROOT / "elephc/runtime/lists")
    for fixture in list_fixtures:
        actual = run_json([lists, fixture["input"]])
        assert actual == list_reference[fixture["name"]], (fixture["name"], actual, list_reference[fixture["name"]])
    assert json.loads(run_output([lists], list_fixtures[0]["input"].encode())) == list_reference[list_fixtures[0]["name"]]
    for fixture in list_errors:
        data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
        result = subprocess.run([lists], input=data, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        assert result.stderr.startswith(b"List error: ") and b"Fatal error" not in result.stderr and b"Warning:" not in result.stderr, (fixture["name"], result.stderr)
    print(f"PASS: {len(list_fixtures)} list scenarios match original PHP; stdin and {len(list_errors)} CLI boundary checks.")

    url_fixtures = json.loads((ROOT / "tests/urls-fixtures.json").read_text())
    url_errors = json.loads((ROOT / "tests/urls-errors.json").read_text())
    url_reference = run_json([php, "tests/urls-reference.php", "upstream"])
    assert run_json([php, "tests/urls-reference.php", "profile"]) == url_reference, "Patched URL helpers differ from original PHP"
    urls = str(ROOT / "elephc/runtime/urls")
    for fixture in url_fixtures:
        actual = run_json([urls, fixture["input"]])
        assert actual == url_reference[fixture["name"]], (fixture["name"], actual, url_reference[fixture["name"]])
    assert json.loads(run_output([urls], url_fixtures[0]["input"].encode())) == url_reference[url_fixtures[0]["name"]]
    for fixture in url_errors:
        data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
        result = subprocess.run([urls], input=data, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        assert result.stderr.startswith(b"URL error: ") and b"Fatal error" not in result.stderr and b"Warning:" not in result.stderr, (fixture["name"], result.stderr)
    print(f"PASS: {len(url_fixtures)} URL cases match original PHP; stdin and {len(url_errors)} CLI boundary checks.")

    sanitize_fixtures = json.loads((ROOT / "tests/sanitize-fixtures.json").read_text())
    sanitize_errors = json.loads((ROOT / "tests/sanitize-errors.json").read_text())
    sanitize_reference = run_json([php, "tests/sanitize-reference.php", "upstream"])
    assert run_json([php, "tests/sanitize-reference.php", "profile"]) == sanitize_reference, "Patched sanitizers differ from original PHP"
    sanitize = str(ROOT / "elephc/runtime/sanitize")
    for fixture in sanitize_fixtures:
        actual = run_json([sanitize, fixture["input"]])
        assert actual == sanitize_reference[fixture["name"]], (fixture["name"], actual, sanitize_reference[fixture["name"]])
    assert json.loads(run_output([sanitize], sanitize_fixtures[0]["input"].encode())) == sanitize_reference[sanitize_fixtures[0]["name"]]
    for fixture in sanitize_errors:
        data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
        result = subprocess.run([sanitize], input=data, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        assert result.stderr.startswith(b"Sanitization error: ") and b"Fatal error" not in result.stderr and b"Warning:" not in result.stderr, (fixture["name"], result.stderr)
    print(f"PASS: {len(sanitize_fixtures)} sanitization cases match original PHP; stdin and {len(sanitize_errors)} CLI boundary checks.")

    error_fixtures = json.loads((ROOT / "tests/errors-fixtures.json").read_text())
    error_boundaries = json.loads((ROOT / "tests/errors-errors.json").read_text())
    error_reference = run_json([php, "tests/errors-reference.php", "upstream"])
    assert run_json([php, "tests/errors-reference.php", "profile"]) == error_reference, "Patched error objects differ from original PHP"
    errors = str(ROOT / "elephc/runtime/errors")
    for fixture in error_fixtures:
        actual = run_json([errors, fixture["input"]])
        assert actual == error_reference[fixture["name"]], (fixture["name"], actual, error_reference[fixture["name"]])
    assert json.loads(run_output([errors], error_fixtures[0]["input"].encode())) == error_reference[error_fixtures[0]["name"]]
    for fixture in error_boundaries:
        data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
        result = subprocess.run([errors], input=data, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        assert result.stderr.startswith(b"Error profile: ") and b"Fatal error" not in result.stderr and b"Warning:" not in result.stderr, (fixture["name"], result.stderr)
    print(f"PASS: {len(error_fixtures)} error-object cases match original PHP; stdin and {len(error_boundaries)} CLI boundary checks.")

    response_fixtures = json.loads((ROOT / "tests/responses-fixtures.json").read_text())
    response_errors = json.loads((ROOT / "tests/responses-errors.json").read_text())
    response_reference = run_json([php, "tests/responses-reference.php", "upstream"])
    assert run_json([php, "tests/responses-reference.php", "profile"]) == response_reference, "Patched response objects differ from original PHP"
    responses = str(ROOT / "elephc/runtime/responses")
    for fixture in response_fixtures:
        actual = run_json([responses, fixture["input"]])
        assert actual == response_reference[fixture["name"]], (fixture["name"], actual, response_reference[fixture["name"]])
    assert json.loads(run_output([responses], response_fixtures[0]["input"].encode())) == response_reference[response_fixtures[0]["name"]]
    for fixture in response_errors:
        data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
        result = subprocess.run([responses], input=data, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        assert result.stderr.startswith(b"Response profile: ") and b"Fatal error" not in result.stderr and b"Warning:" not in result.stderr, (fixture["name"], result.stderr)
    print(f"PASS: {len(response_fixtures)} response-object cases match original PHP; stdin and {len(response_errors)} CLI boundary checks.")

    request_fixtures = json.loads((ROOT / "tests/requests-fixtures.json").read_text())
    request_errors = json.loads((ROOT / "tests/requests-errors.json").read_text())
    request_reference = run_json([php, "tests/requests-reference.php", "upstream"])
    assert run_json([php, "tests/requests-reference.php", "profile"]) == request_reference, "Patched request objects differ from original PHP"
    requests_cli = str(ROOT / "elephc/runtime/requests")
    for fixture in request_fixtures:
        actual = run_json([requests_cli, fixture["input"]])
        assert actual == request_reference[fixture["name"]], (fixture["name"], actual, request_reference[fixture["name"]])
    assert json.loads(run_output([requests_cli], request_fixtures[0]["input"].encode())) == request_reference[request_fixtures[0]["name"]]
    for fixture in request_errors:
        data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
        result = subprocess.run([requests_cli], input=data, capture_output=True, timeout=20)
        assert result.returncode == 2 and not result.stdout, (fixture["name"], result)
        assert result.stderr.startswith(b"Request profile: ") and b"Fatal error" not in result.stderr and b"Warning:" not in result.stderr, (fixture["name"], result.stderr)
    print(f"PASS: {len(request_fixtures)} request-object cases match original PHP; stdin and {len(request_errors)} CLI boundary checks.")

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
                    try:
                        status, headers, body = request("POST", "/render?" + query, fixture["input"].encode())
                    except Exception as error:
                        raise AssertionError(f"Native render request failed: {fixture['name']}") from error
                    assert status == 200, (fixture["name"], status, body)
                    actual = body.decode()
                    assert actual == render_reference[fixture["name"]], f"Native rendering HTTP differs: {fixture['name']}\n{actual!r}"
                    expected_type = "application/json" if fixture["mode"] in ("attributes", "inspect", "context", "registration") else "text/html"
                    assert headers["content-type"].startswith(expected_type), headers
                    checks += 1
                for fixture in serialize_fixtures:
                    query = urllib.parse.urlencode({"mode": fixture["mode"]})
                    status, headers, body = request("POST", "/serialize?" + query, fixture["input"].encode())
                    expected = serialize_reference[fixture["name"]]
                    assert status == 200 and body.decode() == expected["text"], (fixture["name"], status, body)
                    assert headers["content-type"].startswith("text/plain"), headers
                    status, _, parsed_body = request("POST", "/parse", body)
                    assert status == 200 and json.loads(parsed_body) == expected["parsed"], fixture["name"]
                    checks += 2
                for fixture in serialize_errors:
                    query = urllib.parse.urlencode({"mode": fixture["mode"]})
                    status, headers, body = request("POST", "/serialize?" + query, fixture["input"].encode())
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str) and "allow" not in headers, headers
                    checks += 1
                status, _, body = request("POST", "/serialize", parsed_input)
                assert status == 200 and body.decode() == serialize_reference["document-nested"]["text"]
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/serialize")
                    assert status == 405 and headers["allow"] == "POST", (method, status, headers)
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/serialize", b"x" * 65537)
                assert status == 413
                checks += 4
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
                for fixture in kses_fixtures:
                    query = urllib.parse.urlencode({"mode": fixture["mode"], "element": fixture.get("element", "a")})
                    status, headers, body = request("POST", "/kses?" + query, fixture["input"].encode())
                    assert status == 200 and body == base64.b64decode(kses_reference[fixture["name"]]), (fixture["name"], status, body)
                    mime = "application/octet-stream" if fixture["mode"] == "decode" else ("application/json" if fixture["mode"] in ("attributes", "tag-attributes") else "text/plain")
                    assert headers["content-type"].startswith(mime), headers
                    checks += 1
                for fixture in kses_errors:
                    route = "/render" if fixture.get("target") == "render" else "/kses"
                    query = urllib.parse.urlencode({"mode": fixture["mode"]})
                    status, headers, body = request("POST", route + "?" + query, base64.b64decode(fixture["input_base64"]))
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str), fixture["name"]
                    checks += 1
                status, _, body = request("POST", "/kses", b'title="Text"')
                assert status == 200 and body == b'title="Text"'
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/kses")
                    assert status == 405 and headers["allow"] == "POST"
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/kses", b"x" * 65537)
                assert status == 413
                checks += 4
                for fixture in cache_fixtures:
                    try:
                        status, headers, body = request("POST", "/cache", fixture["input"].encode())
                    except Exception as error:
                        raise AssertionError(f"Native cache request failed: {fixture['name']}") from error
                    assert status == 200 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert json.loads(body) == cache_reference[fixture["name"]], (fixture["name"], body, cache_reference[fixture["name"]])
                    checks += 1
                for fixture in cache_errors:
                    data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
                    status, headers, body = request("POST", "/cache", data)
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str), fixture["name"]
                    checks += 1
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/cache")
                    assert status == 405 and headers["allow"] == "POST"
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/cache", b"x" * 65537)
                assert status == 413
                checks += 3
                for fixture in list_fixtures:
                    try:
                        status, headers, body = request("POST", "/lists", fixture["input"].encode())
                    except Exception as error:
                        raise AssertionError(f"Native list request failed: {fixture['name']}") from error
                    assert status == 200 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert json.loads(body) == list_reference[fixture["name"]], (fixture["name"], body, list_reference[fixture["name"]])
                    checks += 1
                for fixture in list_errors:
                    data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
                    status, headers, body = request("POST", "/lists", data)
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str), fixture["name"]
                    checks += 1
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/lists")
                    assert status == 405 and headers["allow"] == "POST"
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/lists", b"x" * 65537)
                assert status == 413
                checks += 3
                for fixture in url_fixtures:
                    try:
                        status, headers, body = request("POST", "/urls", fixture["input"].encode())
                    except Exception as error:
                        raise AssertionError(f"Native URL request failed: {fixture['name']}") from error
                    assert status == 200 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert json.loads(body) == url_reference[fixture["name"]], (fixture["name"], body, url_reference[fixture["name"]])
                    checks += 1
                for fixture in url_errors:
                    data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
                    status, headers, body = request("POST", "/urls", data)
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str), fixture["name"]
                    checks += 1
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/urls")
                    assert status == 405 and headers["allow"] == "POST"
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/urls", b"x" * 65537)
                assert status == 413
                checks += 3
                for fixture in sanitize_fixtures:
                    try:
                        status, headers, body = request("POST", "/sanitize", fixture["input"].encode())
                    except Exception as error:
                        raise AssertionError(f"Native sanitization request failed: {fixture['name']}") from error
                    assert status == 200 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert json.loads(body) == sanitize_reference[fixture["name"]], (fixture["name"], body, sanitize_reference[fixture["name"]])
                    checks += 1
                for fixture in sanitize_errors:
                    data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
                    status, headers, body = request("POST", "/sanitize", data)
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str), fixture["name"]
                    checks += 1
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/sanitize")
                    assert status == 405 and headers["allow"] == "POST"
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/sanitize", b"x" * 65537)
                assert status == 413
                checks += 3
                for fixture in error_fixtures:
                    try:
                        status, headers, body = request("POST", "/errors", fixture["input"].encode())
                    except Exception as error:
                        raise AssertionError(f"Native error-object request failed: {fixture['name']}") from error
                    assert status == 200 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert json.loads(body) == error_reference[fixture["name"]], (fixture["name"], body, error_reference[fixture["name"]])
                    checks += 1
                for fixture in error_boundaries:
                    data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
                    status, headers, body = request("POST", "/errors", data)
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str), fixture["name"]
                    checks += 1
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/errors")
                    assert status == 405 and headers["allow"] == "POST"
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/errors", b"x" * 65537)
                assert status == 413
                checks += 3
                for fixture in response_fixtures:
                    try:
                        status, headers, body = request("POST", "/responses", fixture["input"].encode())
                    except Exception as error:
                        raise AssertionError(f"Native response-object request failed: {fixture['name']}") from error
                    assert status == 200 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert json.loads(body) == response_reference[fixture["name"]], (fixture["name"], body, response_reference[fixture["name"]])
                    checks += 1
                for fixture in response_errors:
                    data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
                    status, headers, body = request("POST", "/responses", data)
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str), fixture["name"]
                    checks += 1
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/responses")
                    assert status == 405 and headers["allow"] == "POST"
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/responses", b"x" * 65537)
                assert status == 413
                checks += 3
                for fixture in request_fixtures:
                    try:
                        status, headers, body = request("POST", "/requests", fixture["input"].encode())
                    except Exception as error:
                        raise AssertionError(f"Native request-object request failed: {fixture['name']}") from error
                    assert status == 200 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert json.loads(body) == request_reference[fixture["name"]], (fixture["name"], body, request_reference[fixture["name"]])
                    checks += 1
                for fixture in request_errors:
                    data = base64.b64decode(fixture["input_base64"]) if "input_base64" in fixture else fixture["input"].encode()
                    status, headers, body = request("POST", "/requests", data)
                    assert status == 400 and headers["content-type"].startswith("application/json"), (fixture["name"], status, body)
                    assert isinstance(json.loads(body)["error"], str), fixture["name"]
                    checks += 1
                for method in ("GET", "HEAD"):
                    status, headers, body = request(method, "/requests")
                    assert status == 405 and headers["allow"] == "POST"
                    if method == "HEAD":
                        assert body == b""
                status, _, body = request("POST", "/requests", b"x" * 65537)
                assert status == 413
                checks += 3
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
        except Exception:
            log.flush()
            log.seek(0)
            print(log.read().decode(errors="replace"))
            raise
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
