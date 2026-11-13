#!/usr/bin/env bash
set -euo pipefail

root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$root"
python3 scripts/prepare-elephc.py --check
compiler=$(command -v "${ELEPHC_BIN:-elephc}") || {
    printf 'Elephc not found. Install the SDK or set ELEPHC_BIN to its executable.\n' >&2
    exit 1
}
mkdir -p elephc/build
status=0
for probe in full-wordpress shortinit blocks; do
    printf '\nChecking unmodified WordPress: %s\n' "$probe"
    if ! "$compiler" --php-version 8.4 --check "elephc/probes/$probe.php" > "elephc/build/upstream-$probe.log" 2>&1; then
        status=1
    fi
    cat "elephc/build/upstream-$probe.log"
done
exit "$status"
