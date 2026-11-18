#!/usr/bin/env python3
"""Compile full-source platform and UTF-8 controls and compare with original PHP."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"


def run(command, *, cwd=OUT, timeout=120):
    result = subprocess.run(command, cwd=cwd, capture_output=True, timeout=timeout)
    if result.returncode:
        raise SystemExit(result.stdout.decode(errors="replace") + result.stderr.decode(errors="replace"))
    return result.stdout


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--compiler", default=os.environ.get("ELEPHC_BIN", "elephc"))
    args = parser.parse_args()
    (OUT / "controls.json").unlink(missing_ok=True)
    compiler = shutil.which(args.compiler)
    if not compiler:
        parser.error("Compiler not found. Provide a complete Elephc SDK on PATH or with --compiler.")
    compiler = str(Path(compiler).resolve())
    php = os.environ.get("PHP_BIN", "php")
    run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT)
    for name in ("elephc.toml", "elephc.lock"):
        shutil.copyfile(ROOT / "elephc/full-source" / name, OUT / name)
    run([compiler, "native", "install", "--locked"])
    report = {"schema": 1, "compiler_version": run([compiler, "--version"]).decode().strip(),
              "compiler_sha256": hashlib.sha256(Path(compiler).read_bytes()).hexdigest(),
              "source_manifest_sha256": hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest(),
              "controls": {}}
    for name in ("crypto", "span", "version", "utf8", "sql", "callable"):
        native = "<?php\nrequire __DIR__ . '/wordpress/aot-platform.lfc';\n"
        reference = "<?php\n"
        if name == "crypto":
            reference += "require __DIR__ . '/../../../upstream/wordpress/wp-includes/sodium_compat/autoload.php';\n"
        if name == "utf8":
            for source in ("compat-utf8.php", "utf8.php"):
                native += f"require __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
            sink = "function _deprecated_function(string $name, string $version, ?string $replacement = null): void {}\n"
            native += sink
            reference += sink
        cases = f"require __DIR__ . '/../../../tests/full-source/{name}-cases.php';\n"
        (OUT / f"control-{name}.php").write_text(native + cases)
        (OUT / f"reference-{name}.php").write_text(reference + cases)
        expected = run([php, str(OUT / f"reference-{name}.php")])
        log = run([compiler, "--php-version", "8.4", "--link", "sodium", f"control-{name}.php"])
        (OUT / f"control-{name}.log").write_bytes(log)
        actual = run([str(OUT / f"control-{name}")], timeout=10)
        expected_value = json.loads(expected)
        actual_value = json.loads(actual)
        if json.dumps(expected_value, sort_keys=True) != json.dumps(actual_value, sort_keys=True):
            (OUT / f"control-{name}-expected.json").write_bytes(expected)
            (OUT / f"control-{name}-actual.json").write_bytes(actual)
            raise SystemExit(f"{name} control differs from original PHP")
        values = actual_value
        count = sum(len(row) if isinstance(row, list) else 1 for row in values)
        report["controls"][name] = {"cases": count, "matches_original_php": True,
                                    "cases_sha256": hashlib.sha256((ROOT / f"tests/full-source/{name}-cases.php").read_bytes()).hexdigest()}
        print(f"PASS: {name}, {count} comparisons with original PHP", flush=True)
    (OUT / "controls.json").write_text(json.dumps(report, indent=2) + "\n")


if __name__ == "__main__":
    main()
