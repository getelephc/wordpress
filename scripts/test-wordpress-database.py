#!/usr/bin/env python3
"""Compare complete WordPress SQL files using an isolated SQLite fixture."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"


def run(command, *, timeout=120):
    result = subprocess.run(command, cwd=OUT, capture_output=True, text=True, timeout=timeout)
    if result.returncode:
        raise SystemExit(result.stdout + result.stderr)
    return result.stdout


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--compiler", default=os.environ.get("ELEPHC_BIN", "elephc"))
    args = parser.parse_args()
    compiler = shutil.which(args.compiler)
    if not compiler:
        parser.error("A complete Elephc SDK is required")
    compiler = str(Path(compiler).resolve())
    subprocess.run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT, check=True)
    report_path = OUT / "database-verification.json"
    report_path.unlink(missing_ok=True)
    for name in ("elephc.toml", "elephc.lock"):
        shutil.copyfile(ROOT / "elephc/full-source" / name, OUT / name)
    run([compiler, "native", "install", "--locked"])
    support = (ROOT / "tests/full-source/database-support.php").read_text().removeprefix("<?php")
    with tempfile.TemporaryDirectory(prefix=".database-control-", dir=OUT) as directory:
        work = Path(directory)
        for native in (False, True):
            label = "native" if native else "reference"
            platform = "aot-platform.lfc" if native else "aot-php-platform.php"
            source = f"<?php\nrequire __DIR__ . '/../wordpress/{platform}';\n"
            source += "require_once __DIR__ . '/../wordpress/aot-arrays.php';\n" + support
            if native:
                source += "wp_aot_bootstrap_mark('WP_DEBUG',true);wp_aot_bootstrap_mark('WP_DEBUG_DISPLAY',true);\n"
            tree = "../wordpress" if native else "../../../../upstream/wordpress"
            for name in ("plugin.php", "class-wp-error.php", "class-wpdb.php"):
                source += f"require __DIR__ . '/{tree}/wp-includes/{name}';\n"
            source += "require __DIR__ . '/../wordpress/wp-content/db.php';\n"
            source += "require __DIR__ . '/../../../../tests/full-source/database-cases.php';\n"
            (work / f"{label}.php").write_text(source)
        expected = json.loads(run([os.environ.get("PHP_BIN", "php"), str(work / "reference.php")]))
        command = [compiler, "--php-version", "8.4", "--with-regex", "--link", "sodium", str(work / "native.php")]
        if os.environ.get("ELEPHC_IR_OPT") == "off":
            command.insert(1, "--ir-opt=off")
        run(command)
        actual = json.loads(run([str(work / "native")], timeout=15))
        if json.dumps(actual, sort_keys=True) != json.dumps(expected, sort_keys=True):
            raise SystemExit(json.dumps({"php": expected, "native": actual}, indent=2))
        comparisons = sum(len(row) if isinstance(row, list) else 1 for row in actual)
        report = {"schema": 1, "groups": len(actual), "comparisons": comparisons,
                  "matches_original_php": True, "complete_wordpress_files": ["wp-includes/plugin.php", "wp-includes/class-wp-error.php", "wp-includes/class-wpdb.php"],
                  "database": "sqlite::memory:", "ir_optimization": "off" if os.environ.get("ELEPHC_IR_OPT") == "off" else "on",
                  "compiler_sha256": hashlib.sha256(Path(compiler).read_bytes()).hexdigest(),
                  "source_manifest_sha256": hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest(),
                  "cases_sha256": hashlib.sha256((ROOT / "tests/full-source/database-cases.php").read_bytes()).hexdigest(),
                  "support_sha256": hashlib.sha256((ROOT / "tests/full-source/database-support.php").read_bytes()).hexdigest(),
                  "harness_sha256": hashlib.sha256(Path(__file__).read_bytes()).hexdigest()}
        report_path.write_text(json.dumps(report, indent=2) + "\n")
    print(f"PASS: complete WordPress SQL files, {comparisons} comparisons with original PHP", flush=True)


if __name__ == "__main__":
    main()
