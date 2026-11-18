#!/usr/bin/env bash
set -euo pipefail

root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$root"
if [[ ${1:-} == --help || ${1:-} == -h ]]; then
    printf 'Usage: ./scripts/build-elephc.sh\nBuild WordPress block parsing, serialization, hooks, content rendering, attribute KSES, object cache, list utilities, URL helpers, sanitization, error objects, response objects, request objects and HTTP profiles with native Elephc.\n'
    exit 0
fi
if [[ $# != 0 ]]; then
    printf 'Unknown build argument: %s\n' "$1" >&2
    exit 1
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
"$compiler" --php-version 8.4 serialize.php 2>&1 | tee ../build/compile-serialize.log
"$compiler" --php-version 8.4 hooks.php 2>&1 | tee ../build/compile-hooks.log
"$compiler" --php-version 8.4 render.php 2>&1 | tee ../build/compile-render.log
"$compiler" --php-version 8.4 kses.php 2>&1 | tee ../build/compile-kses.log
"$compiler" --php-version 8.4 cache.php 2>&1 | tee ../build/compile-cache.log
"$compiler" --php-version 8.4 lists.php 2>&1 | tee ../build/compile-lists.log
"$compiler" --php-version 8.4 urls.php 2>&1 | tee ../build/compile-urls.log
"$compiler" --php-version 8.4 sanitize.php 2>&1 | tee ../build/compile-sanitize.log
"$compiler" --php-version 8.4 errors.php 2>&1 | tee ../build/compile-errors.log
"$compiler" --php-version 8.4 responses.php 2>&1 | tee ../build/compile-responses.log
"$compiler" --php-version 8.4 requests.php 2>&1 | tee ../build/compile-requests.log
"$compiler" --php-version 8.4 --web server.php 2>&1 | tee ../build/compile-web.log
printf 'Built native block parser, serializer, hooks, rendering, KSES, object cache, lists, URLs, sanitization, error objects, response objects, request objects and HTTP core profiles.\n'
