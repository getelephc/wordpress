# WordPress on Elephc

This experiment starts from the **complete original WordPress 7.1.4 distribution**
and ports its real bootstrap to Elephc using full-file compatibility patches and
explicit fixtures, following the sibling Tempest and Laravel experiments.
The primary build does not extract function ranges or use a PHP-hosted extension.

**Current status:** the complete original and patched bootstrap agree under PHP,
including `wp_loaded`, theme setup, 116 registered core blocks and the contents
of 109 original block patterns. Native platform
and UTF-8 controls pass. The complete WordPress native bootstrap is still being
ported and has not completed native verification. Source preparation and compiler
traversal are not native execution coverage.

## Requirements

Run directly on a Linux x86_64 host, without Docker or DDEV:

- A complete Elephc SDK, including its runtime archives. The tested main revision
  is `22654308233a43f96ff4ae2482e08afe61fe956e`, with PHP compilation profile 8.4.
  The release banner alone reads `0.27.8`; use the revision and hashes in
  [toolchain.json](elephc/full-source/toolchain.json) to identify this SDK.
- Python 3.12 or newer, Bash, `patch`, a C compiler/linker and GNU binutils.
  The tested development SDK requires glibc 2.39 or newer.
- The libsodium development library and headers available to the linker.
- PHP 8.4 or newer with PDO SQLite and mbstring for the reference checks.
- Internet access for the pinned WordPress archive and locked native dependencies.

Use `elephc` on `PATH` or set `ELEPHC_BIN` to the executable in another complete
SDK. No version manager or workstation-specific helper is required.
`.elephc-version` records the tested main revision.

## Obtain the tested development SDK

The SDK metadata names a GitHub Actions artifact rather than a versioned release.
With the GitHub CLI authenticated, download and verify that exact artifact:

```bash
mkdir -p elephc/build/full-source/sdk
# Keep the compiler and its runtime archives in the same directory.
gh run download 44272348788 --repo illegalstudio/elephc \
  --name elephc-dev-linux-x86_64-22654308233a43f96ff4ae2482e08afe61fe956e-1 \
  --dir elephc/build/full-source/sdk
python3 - <<'PYSDK'
import hashlib
from pathlib import Path
archive = Path('elephc/build/full-source/sdk/elephc-dev-linux-x86_64-22654308233a43f96ff4ae2482e08afe61fe956e.tar.gz')
assert hashlib.sha256(archive.read_bytes()).hexdigest() == '1792606248d5f33ccf5b33c9c3e3ade9462ce43bcdb0be29bfb564ec9fce6eef'
PYSDK
tar -xzf elephc/build/full-source/sdk/elephc-dev-linux-x86_64-22654308233a43f96ff4ae2482e08afe61fe956e.tar.gz \
  -C elephc/build/full-source/sdk
export ELEPHC_BIN="$PWD/elephc/build/full-source/sdk/elephc"
```

Actions artifacts have a retention period. If this artifact is unavailable, a
newer SDK needs its own verification and provenance before being described as
tested here. Another complete SDK can always be selected with `ELEPHC_BIN`.

## Reproduce the source and PHP baseline

```bash
python3 scripts/prepare-wordpress.py
python3 scripts/prepare-wordpress.py --check
./scripts/build-wordpress.sh --php-reference
```

Preparation verifies all 3,782 original dependency files against the official
archive, copies the complete tree to `elephc/build/full-source/wordpress`, applies
the [full-file patch series](patches/full-source/README.md), and adds the explicit
[fixtures](elephc/full-source/fixtures). Original files in `upstream/` are never
edited. Original, patch and resulting file hashes are checked; generated source
inventories are written to `elephc/build/full-source/source-manifest.json`.

The PHP comparison executes the original `wp-load.php` and `wp-settings.php`
from a temporary copy of the verified distribution, then the complete patched
counterpart. Both receive the same explicit `wp-config.php` fixture. Both use an isolated **SQLite database in memory** containing
site options. It never connects to a database server. The fixture selects the
original Twenty Twenty-Five theme, English locale and no installed plugins.
There is no persisted database or captured WordPress HTML response. The probe
also renders static and dynamic blocks through original `do_blocks`; a future
native executable accepts another block document as its first argument.

The comparison also checks nested Customizer setting operations and deprecated
template-theme transformations against unmodified WordPress. Its report is
`elephc/build/full-source/bootstrap-verification.json`.

## Native controls and bootstrap build

```bash
./scripts/build-wordpress.sh --controls
./scripts/build-wordpress.sh --check
./scripts/build-wordpress.sh
```

`--controls` compiles and runs focused native tests against ordinary PHP, using
shared case files in `tests/full-source/`. They cover libsodium bridge primitives,
binary string spans, PHP version ordering, complete core UTF-8 source files and
in-memory PDO SQLite and reference updates through first-class callables,
closures, method arrays and invokable objects. Results are recorded in
`elephc/build/full-source/controls.json` only after every comparison passes.
The Sodium bridge calls the real system library. The original WordPress Sodium
polyfill supplies the crypto reference when host PHP lacks that extension.

`--check` attempts the complete native WordPress bootstrap. The default command
also compiles and links it, producing `elephc/build/full-source/bootstrap` after a
successful build. **The complete build remains unverified.** The main-SDK check was stopped after
747 seconds without a final verdict; the focused compiler probes identify
additional unsupported behavior. A native CMS executable or HTTP site is not available from this port
yet. After a successful build, require native parity explicitly:

```bash
python3 scripts/test-wordpress-bootstrap.py \
  --native elephc/build/full-source/bootstrap
```

The local URL stored in the fixture is `http://127.0.0.1:9000`. This fixture does
not start a server.

Reproduce the verified controls from an export containing only maintained source
files and the pinned original archive:

```bash
python3 scripts/test-wordpress-clean-room.py --compiler "${ELEPHC_BIN:-elephc}"
```

The export reconstructs the entire dependency tree, runs the PHP bootstrap
comparisons and compiles the native controls, then removes itself. The portable combined report remains at
`elephc/build/full-source/clean-room-verification.json`. It does not
claim that the complete native WordPress bootstrap works.

## Patch policy and boundaries

Every patch targets a complete original file and records its reason and hashes.
Mechanical rewrites preserve mixed HTML as literal `echo` statements and split
static declarations. Finite-profile changes declare closed source loaders,
unsupported transports or reference-alias restrictions in the manifest. Read
those reasons before treating an API as supported.

The first fixture covers single-site initialization, in-memory site options,
the original default theme and no external network activity. The SQL fixture is
not a complete WordPress database schema. Arbitrary plugins, PHP catalogs,
custom PHP templates, runtime-loaded PHP files, installation and upgrades need
additional source manifests or adapters. RSS XML tree references and feed-link
alias maps are explicitly unavailable in this fixture. Full administrative,
frontend and REST request handling has not been verified natively.

The [compiler probes](elephc/full-source/probes/README.md) isolate unsupported
array references and a failure to invoke included functions by their string name
on the tested SDK. First-class callable controls pass; this does not resolve
WordPress string callbacks.

The [current verification snapshot](docs/elephc/full-source-status.json) records
307 complete-file patches, five fixtures, the PHP reference results, 19,209
passing native comparisons and the incomplete full check.

The [porting log](docs/elephc/porting-log.md) records verified results and current
blockers. No percentage of WordPress is attributed to native execution merely
because its source is copied, patched or parsed.

## Other experiments

The [native subsystem profiles](docs/elephc/subsystem-profiles.md) remain separate
working examples for blocks, hooks, content, KSES, caching, list utilities, URLs,
sanitization and bounded REST objects. Their selected-function manifests and
released SDK requirements belong to those profiles.

The [PHP hybrid experiment](experiments/php-hybrid/README.md) runs WordPress under
PHP with selected algorithms routed through an Elephc extension. Its current
measurements describe that implementation, independently of this complete-source
native port.

WordPress is an original dependency under its own license. This repository's
code is covered by [LICENSE](LICENSE).
