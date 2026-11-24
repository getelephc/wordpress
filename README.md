# WordPress on Elephc

This repository contains one experiment: compiling the complete original
WordPress 7.1.4 source distribution with Elephc. Reviewable full-file patches and
explicit fixtures preserve the original dependency archive and its checksums.

The verified result is an isolated **native CLI bootstrap**. The bootstrap and
five runtime documents match unmodified WordPress under PHP, including nested
Unicode blocks, dynamic titles, navigation names and a video shortcode. The
state includes 116 registered core blocks and 109 original pattern hashes.
All 47 native control groups pass 230,504 comparisons in each IR mode.
A separate source-only export also rebuilt the executable and repeated parity.

There is no WordPress HTTP entrypoint yet. The fixture URL is
`http://127.0.0.1:9000`; running the executable does not start a web server.

## Requirements

Run directly on a Linux x86_64 host, without Docker or DDEV:

- A complete Elephc SDK, including its runtime archives. The tested main revision
  is `f20729406216d21ce99841cf4636b771db9874dd`, with PHP compilation profile 8.4
  and the maintained [compiler compatibility patch](patches/compiler/README.md).
  The release banner alone reads `0.27.8`; use the revision and hashes in
  [toolchain.json](elephc/full-source/toolchain.json) to identify this SDK.
- Python 3.12 or newer, Bash, `patch`, a C compiler/linker and GNU binutils.
  The tested development SDK requires glibc 2.39 or newer.
- LLVM `ld.lld` on `PATH`, or its executable selected with `WORDPRESS_LLD`.
  The build can also discover `rust-lld` through an active `rustc` on `PATH`.
  The [section-placement compatibility linker](scripts/wordpress-link.py)
  preserves generated instructions and distinct function addresses, and records
  its allocated-content checksums in `elephc/build/full-source/bootstrap-link.json`.
- The libsodium development library and headers available to the linker.
- PHP 8.4 or newer with PDO SQLite and mbstring for the reference checks.
- Internet access for the pinned WordPress archive and locked native dependencies.
  The complete retained Requests graph also links locked Curl and its SSH, HTTP/2,
  TLS and compression dependencies through the SDK native package manager.
  The retained fileinfo bridge uses locked libmagic 5.46 with its embedded magic
  database.

The measured complete build reaches about 35 GiB of compiler resident memory
and produces 17.76 GB of assembly, plus the object and executable. The 512 MiB
heap setting applies to the compiled program.

Use `elephc` on `PATH` or set `ELEPHC_BIN` to the executable in another complete
SDK. No version manager or workstation-specific helper is required.
`.elephc-version` records the tested main revision.

## Obtain the tested development SDK

The SDK metadata records the original GitHub Actions artifact and its checksums.
That run no longer exposes downloadable artifacts, as checked on 2026-11-23.
Use the [pinned compiler and bridge source-build instructions](patches/compiler/README.md)
to construct a complete local SDK from the same base revision and maintained
compiler patch. Keep its compiler and runtime archives together, then set
`ELEPHC_BIN` to that compiler.

A source-built SDK has its own binary identities. The hashes in
`toolchain.json` identify the SDK used for the recorded results; they do not
describe every local build of that source. Run the PHP/native controls for your
SDK before relying on those results. Another complete SDK can also be selected
with `ELEPHC_BIN` and needs its own verification.

## Required Elephc changes

The verified build uses Elephc revision
`f20729406216d21ce99841cf4636b771db9874dd` plus the versioned
[`patches/compiler/wordpress-main.patch`](patches/compiler/wordpress-main.patch).
The unpatched base SDK does not reproduce this result.

The [compiler instructions](patches/compiler/README.md) explain how to check out
that revision, apply the patch and assemble a complete SDK. The patch contains
the compiler compatibility, ownership and large-program scaling changes.
[`toolchain.json`](elephc/full-source/toolchain.json) records the base revision,
patch checksum, compiler identity, runtime archive provenance and build flags.
A reconstructed SDK has its own binary hashes and must pass the controls.

These compiler changes are separate from the WordPress compatibility patches in
[`patches/full-source/`](patches/full-source/README.md). No sibling checkout or
personal filesystem path is required by the repository scripts.

## Prepare and build

Run the SDK recipe first, or select another complete SDK explicitly:

```bash
export ELEPHC_BIN=/path/to/complete-sdk/elephc
python3 scripts/prepare-wordpress.py
python3 scripts/prepare-wordpress.py --check
"$ELEPHC_BIN" native install --locked --manifest-path elephc/full-source/elephc.toml
./scripts/build-wordpress.sh --php-reference
./scripts/build-wordpress.sh
```

Preparation downloads the pinned archive if needed, verifies every original
file, copies the complete distribution and applies all 1,103 full-file patches.
It adds fifteen runtime fixtures without overwriting original dependency files.
Original sources in `upstream/` remain unchanged. Generated sources and binaries
are written under `elephc/build/full-source/` and are ignored by Git.

The initial native dependency installation can build the locked C libraries and
take several minutes on a cold cache. Complete it before running the control
suites, whose individual commands have bounded timeouts.

Run the native executable:

```bash
./elephc/build/full-source/bootstrap
./elephc/build/full-source/bootstrap '<!-- wp:paragraph --><p>Ciao</p><!-- /wp:paragraph -->'
```

It returns a JSON inspection state whose `rendered` field contains the rendered
content. It initializes the original `wp-load.php` and `wp-settings.php` using
the bundled Twenty Twenty-Five theme, English locale, no installed plugins and
an isolated SQLite database in memory. Nothing connects to a database server.
The database and application probes are explicit fixtures, not a captured HTML
response or a persistent WordPress installation.

## Verify

Compare the original PHP distribution, patched PHP and actual native output:

```bash
python3 scripts/test-wordpress-bootstrap.py \
  --native elephc/build/full-source/bootstrap
python3 scripts/test-wordpress-controls.py --compiler "$ELEPHC_BIN"
ELEPHC_IR_OPT=off python3 scripts/test-wordpress-controls.py --compiler "$ELEPHC_BIN"
python3 scripts/test-wordpress-linker.py
```

Reconstruct the source tree from maintained files and the verified archive:

```bash
python3 scripts/test-wordpress-clean-room.py --compiler "$ELEPHC_BIN"
ELEPHC_IR_OPT=off python3 scripts/test-wordpress-clean-room.py \
  --native --compiler "$ELEPHC_BIN"
```

The first command verifies the PHP reference and all 47 native control groups.
`--native` additionally performs another complete native build and compares the
bootstrap and all five documents. Each temporary export is removed afterward.
The controls include whole original component files with finite collaborators;
unused collaborator paths throw explicitly. A successful compile or `--check`
alone is not proof that all WordPress execution paths work.

The native build uses PHP profile 8.4, IR optimization off, GC statistics and a
512 MiB runtime heap. The portable linker preserves allocated bytes and disables
function folding. Reports include compiler, source, harness and test-input
checksums. The [verification snapshot](docs/elephc/full-source-status.json) records
the last complete result; generated local reports remain under the build tree.
The [porting log](docs/elephc/porting-log.md) explains its current adaptations.

## Scope and repository layout

Full HTTP request handling, administration, authentication, arbitrary plugins,
network operations and a persistent database remain outside the verified CLI
fixture. Unsupported native APIs retain explicit failure boundaries documented
in [native API boundaries](docs/elephc/native-api-boundaries.md). Source file
counts are provenance, not percentages of executed WordPress code. This profile
makes no runtime performance claim.

| Path | Purpose |
| --- | --- |
| `elephc/upstream.json` | Pinned original WordPress archive and checksums |
| `elephc/full-source/` | Runtime fixtures, locked native dependencies and provenance |
| `patches/compiler/` | Required Elephc patch and SDK build instructions |
| `patches/full-source/` | Complete WordPress file patches and original/patch/result hashes |
| `scripts/` | Preparation, native build, patch maintenance and verification |
| `tests/full-source/` | Bootstrap inputs and all 47 native control groups |
| `docs/elephc/` | Current verified result, boundaries and property audit |

WordPress sources retain their upstream GPL-2.0-or-later license. See
[`LICENSE`](LICENSE) and the original archive's `license.txt`.
