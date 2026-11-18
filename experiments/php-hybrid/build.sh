#!/usr/bin/env bash
set -euo pipefail
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$root"
python3 scripts/prepare-elephc.py --check
python3 experiments/php-hybrid/prepare-extended.py
(
    cd elephc/build/php-hybrid
    elephc native install --locked
    elephc --php-version 8.5 --emit cdylib --heap-size=33554432 wp_extended.php
)
python3 experiments/php-hybrid/build-adapter.py
python3 experiments/php-hybrid/route-extended.py
php -d extension="$root/elephc/build/php-hybrid/wp_elephc_extended.so" experiments/php-hybrid/verify.php > elephc/build/php-hybrid/verification.json
python3 experiments/php-hybrid/check-corpus.py
