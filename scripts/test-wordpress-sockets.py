#!/usr/bin/env python3
"""Compare the native socket adapter with PHP using an isolated loopback peer."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import threading

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--compiler", default=os.environ.get("ELEPHC_BIN", "elephc"))
    args = parser.parse_args()
    compiler = shutil.which(args.compiler)
    if not compiler:
        parser.error("A complete Elephc SDK is required")
    compiler = str(Path(compiler).resolve())
    OUT.mkdir(parents=True, exist_ok=True)
    report_path = OUT / "socket-verification.json"
    report_path.unlink(missing_ok=True)
    subprocess.run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT, check=True, capture_output=True, text=True)
    for name in ("elephc.toml", "elephc.lock"):
        shutil.copyfile(ROOT / "elephc/full-source" / name, OUT / name)
    subprocess.run([compiler, "native", "install", "--locked"], cwd=OUT, check=True, capture_output=True, text=True)
    stop = threading.Event()
    errors = []
    with socket.socket() as listener:
        listener.bind(("127.0.0.1", 0))
        listener.listen()
        listener.settimeout(0.2)

        def serve():
            while not stop.is_set():
                try:
                    connection, _ = listener.accept()
                except socket.timeout:
                    continue
                except OSError:
                    break
                try:
                    with connection:
                        connection.settimeout(3)
                        request = b""
                        while b"\n" not in request and len(request) < 64:
                            part = connection.recv(64)
                            if not part:
                                break
                            request += part
                        if request != b"probe\n":
                            raise AssertionError(f"Unexpected loopback request: {request!r}")
                        connection.sendall(b"socket-control\n")
                except Exception as error:
                    errors.append(str(error))

        thread = threading.Thread(target=serve, daemon=True)
        thread.start()
        try:
            with tempfile.TemporaryDirectory(prefix=".socket-control-", dir=OUT) as directory:
                work = Path(directory)
                port = listener.getsockname()[1]
                common = f"define('SOCKET_TEST_PORT', {port});\n"
                common += "require __DIR__ . '/../../../../tests/full-source/socket-cases.php';\n"
                native = "<?php\nrequire __DIR__ . '/../wordpress/aot-platform.lfc';\n"
                native += "function socket_open_call(mixed $host,mixed $port,mixed &$code,mixed &$message,mixed $timeout): mixed {return wp_aot_fsockopen($host,$port,$code,$message,$timeout);}\n"
                native += "function socket_write_call(mixed $stream,string $text): mixed {return wp_aot_fwrite($stream,$text);}\nfunction socket_read_call(mixed $stream): mixed {return wp_aot_fgets($stream);}\n"
                reference = "<?php\nfunction socket_open_call(mixed $host,mixed $port,mixed &$code,mixed &$message,mixed $timeout): mixed {return fsockopen($host,$port,$code,$message,$timeout);}\n"
                reference += "function socket_write_call(mixed $stream,string $text): mixed {return fwrite($stream,$text);}\nfunction socket_read_call(mixed $stream): mixed {return fgets($stream);}\n"
                (work / "native.php").write_text(native + common)
                (work / "reference.php").write_text(reference + common)
                command = [compiler, "--php-version", "8.4", "--with-regex", "--link", "sodium", str(work / "native.php")]
                if os.environ.get("ELEPHC_IR_OPT") == "off":
                    command.insert(1, "--ir-opt=off")
                subprocess.run(command, cwd=OUT, check=True, capture_output=True, text=True, timeout=120)
                expected = subprocess.run([os.environ.get("PHP_BIN", "php"), str(work / "reference.php")], check=True, capture_output=True, text=True, timeout=15)
                actual = subprocess.run([str(work / "native")], check=True, capture_output=True, text=True, timeout=15)
                expected_value, actual_value = json.loads(expected.stdout), json.loads(actual.stdout)
                if actual_value != expected_value or errors:
                    raise SystemExit(json.dumps({"php": expected_value, "native": actual_value, "peer_errors": errors}, indent=2))
                report = {"schema": 1, "groups": len(actual_value), "comparisons": sum(map(len, actual_value)),
                          "matches_original_php": True, "cases": actual_value,
                          "compiler_sha256": hashlib.sha256(Path(compiler).read_bytes()).hexdigest(),
                          "source_manifest_sha256": hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest(),
                          "cases_sha256": hashlib.sha256((ROOT / "tests/full-source/socket-cases.php").read_bytes()).hexdigest()}
                report_path.write_text(json.dumps(report, indent=2) + "\n")
        finally:
            stop.set()
            thread.join(timeout=4)
    print("PASS: loopback socket conversions, error outputs and payload exchange", flush=True)


if __name__ == "__main__":
    try:
        main()
    except subprocess.CalledProcessError as error:
        raise SystemExit((error.stdout or "") + (error.stderr or "")) from error
