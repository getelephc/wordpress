#!/usr/bin/env bash
set -euo pipefail

root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$root"
if [[ ! -x elephc/build/full-source/bootstrap-web ]]; then
    printf 'Build the native WordPress HTTP server with ./scripts/build-wordpress.sh --web first.\n' >&2
    exit 1
fi
exec elephc/build/full-source/bootstrap-web --listen "127.0.0.1:${WORDPRESS_WEB_PORT:-9000}" --workers 1 --max-body-size 65536 "$@"
