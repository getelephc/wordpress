#!/usr/bin/env bash
set -euo pipefail

root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$root"
mode=${1:-build}
case "$mode" in
    --help|-h)
        printf 'Usage: ./scripts/build-wordpress.sh [--check|--controls|--php-reference]\nBuild the complete-source WordPress bootstrap or verify its original-PHP controls.\n'
        exit 0
        ;;
    build|--check|--controls|--php-reference) ;;
    *) printf 'Unknown mode: %s\n' "$mode" >&2; exit 2 ;;
esac
if [[ $# -gt 1 ]]; then
    printf 'Expected at most one mode.\n' >&2
    exit 2
fi
python3 scripts/prepare-wordpress.py
python3 scripts/prepare-wordpress.py --check
if [[ "$mode" == --php-reference ]]; then
    exec python3 scripts/test-wordpress-bootstrap.py
fi
compiler=$(command -v "${ELEPHC_BIN:-elephc}") || {
    printf 'Elephc SDK not found. Set ELEPHC_BIN or install the SDK on PATH.\n' >&2
    exit 1
}
compiler=$(python3 -c 'from pathlib import Path; import sys; print(Path(sys.argv[1]).resolve())' "$compiler")
"$compiler" --version
if [[ "$mode" == --controls ]]; then
    exec python3 scripts/test-wordpress-controls.py --compiler "$compiler"
fi
out="$root/elephc/build/full-source"
cp elephc/full-source/elephc.toml "$out/elephc.toml"
cp elephc/full-source/elephc.lock "$out/elephc.lock"
printf "<?php\nrequire __DIR__ . '/wordpress/aot-probe.php';\n" > "$out/bootstrap.php"
cd "$out"
"$compiler" native install --locked 2>&1 | tee native.log
if [[ "$mode" == --check ]]; then
    "$compiler" --php-version 8.4 --check bootstrap.php 2>&1 | tee bootstrap-check.log
    exit 0
fi
"$compiler" --php-version 8.4 --link sodium bootstrap.php 2>&1 | tee bootstrap-build.log
printf 'Built elephc/build/full-source/bootstrap\n'
