#!/usr/bin/env bash
set -euo pipefail

root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$root"
if [[ ! -x elephc/runtime/server ]]; then
    printf 'Build the parser profile with ./scripts/build-elephc.sh first.\n' >&2
    exit 1
fi
exec elephc/runtime/server --listen 127.0.0.1:9000 --workers 1 --max-body-size 65536 "$@"
