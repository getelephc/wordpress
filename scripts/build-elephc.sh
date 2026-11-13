#!/usr/bin/env bash
set -euo pipefail

root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$root"
if [[ ${1:-} == --help || ${1:-} == -h ]]; then
    printf 'Usage: ./scripts/build-elephc.sh\nBuild the pinned CLI and HTTP parser profiles with native Elephc.\n'
    exit 0
fi
if [[ $# != 0 ]]; then
    printf 'Unknown build argument: %s\n' "$1" >&2
    exit 1
fi
build_lock=${BUILD_LOCK_BIN:-$HOME/.codex/skills/me-build-lock/scripts/build-lock}
if [[ -x $build_lock && -z ${ME_BUILD_LOCK_HELD:-} ]]; then
    exec "$build_lock" run --agent codex --label 'wordpress: build parser profiles' -- "$root/scripts/build-elephc.sh"
fi

compiler=$(command -v "${ELEPHC_BIN:-elephc}") || {
    printf 'Elephc not found. Install the SDK or set ELEPHC_BIN to its executable.\n' >&2
    exit 1
}
"$compiler" --version
python3 scripts/prepare-elephc.py
python3 scripts/prepare-elephc.py --check
mkdir -p elephc/build
cd elephc/runtime
"$compiler" native install --locked 2>&1 | tee ../build/native.log
"$compiler" --php-version 8.4 --check server.php 2>&1 | tee ../build/check.log
"$compiler" --php-version 8.4 parse.php 2>&1 | tee ../build/compile-cli.log
"$compiler" --php-version 8.4 --web server.php 2>&1 | tee ../build/compile-web.log
printf 'Built native CLI and HTTP parser profiles.\n'
