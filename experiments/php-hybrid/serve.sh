#!/usr/bin/env bash
set -euo pipefail
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
mode=${1:-hybrid}
if [[ $mode != php && $mode != loaded-off && $mode != hybrid ]]; then
    printf 'Usage: %s [php|loaded-off|hybrid]\n' "$0" >&2
    exit 2
fi
args=(-d opcache.enable_cli=1 -d opcache.jit=disable -d opcache.jit_buffer_size=0)
if [[ $mode != php ]]; then
    args+=(-d "extension=$root/elephc/build/php-hybrid/wp_elephc_extended.so")
fi
export HYBRID_MODE=$mode
python3 - <<'PY'
import socket
with socket.socket() as check:
    check.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1)
    check.bind(('127.0.0.1',9000))
PY
if [[ $mode == hybrid ]]; then
    python3 "$root/experiments/php-hybrid/route-extended.py"
else
    python3 "$root/experiments/php-hybrid/route-extended.py" --original
fi
exec php "${args[@]}" -S 127.0.0.1:9000 -t "$root/elephc/build/php-hybrid/wordpress" "$root/experiments/php-hybrid/router.php"
