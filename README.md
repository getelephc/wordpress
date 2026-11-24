<p align="center">
  <a href="https://github.com/illegalstudio/elephc"><img src="https://raw.githubusercontent.com/getelephc/laravel/7a8d4627eba19c6f32ef95c91c058ae6a8846e12/assets/elephc-logo-mark.png" width="130" alt="Elephc logo"></a>
</p>

<p align="center">
  <a href="https://wordpress.org"><img src="https://raw.githubusercontent.com/WordPress/WordPress/7.1.4/wp-admin/images/w-logo-blue.png" width="80" alt="WordPress logo"></a>
</p>

<h1 align="center">WordPress 7 on Elephc</h1>

<p align="center">
  <em>WordPress blocks, rendered by an ahead-of-time compiled core.</em>
</p>

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-GPL--2.0--or--later-FF7A1A?style=flat-square" alt="License: GPL-2.0-or-later"></a>
  <a href="https://x.com/nahime0"><img src="https://img.shields.io/badge/Follow-%40nahime0-FF7A1A?style=flat-square&amp;logo=x&amp;logoColor=white" alt="Follow @nahime0 on X"></a>
</p>

<p align="center">
  <strong>WordPress 7.1.4 &middot; 1,103 WordPress patches &middot; 15 runtime fixtures &middot; 2 Elephc SDK patches &middot; native web and CLI</strong>
</p>

<p align="center">
  An experimental port that starts from the complete WordPress release and compiles the bootstrap and content renderer with <a href="https://github.com/illegalstudio/elephc">Elephc</a>. Each HTTP request renders its block document live in the native executable.
</p>

<p align="center">
  <a href="https://elephc.dev"><strong>Official Website</strong></a>
</p>

---

> [!IMPORTANT]
> This experiment verifies an isolated bootstrap and content-rendering path. A
> full WordPress site, including theme routing and administration, is outside
> the current profile.

## Current status

- The pinned WordPress 7.1.4 archive contains 3,782 original files, including
  1,512 PHP files. Preparation verifies the complete inventory and applies the
  [patch manifest](patches/full-source/manifest.json), which records original,
  patch and resulting file checksums for each target.
- The native CLI bootstrap registers 116 core blocks and matches the original
  PHP result, including 109 original theme pattern hashes. An independent
  source-only export rebuilt the CLI and repeated the parity checks.
- The native HTTP binary serves `/` and `/index.php` on port 9000. All 14 HTTP
  checks passed, including exact original-PHP content parity for the default
  page and five documents, repeated requests, HEAD and error responses.
- All 47 native control groups passed 230,504 comparisons in each IR mode.
  The [verification snapshot](docs/elephc/full-source-status.json) records the
  compiler, build and test identities.

## Patch and fixture inventory

| Layer | Count | Purpose |
| --- | ---: | --- |
| [WordPress source patches](patches/full-source/README.md) | 1,103 | 639 compatibility rewrites and 464 finite-profile adaptations, each applied to a complete original file |
| [Runtime fixtures](elephc/full-source/fixtures/) | 15 | Local configuration, in-memory SQLite, bootstrap probes and bounded PHP compatibility helpers |
| [Elephc SDK patches](patches/compiler/README.md) | 2 | Compiler and linker-scale adaptations in `wordpress-main.patch`, followed by the request-global storage fix in `wordpress-web.patch` |
| [HTTP entrypoint](elephc/full-source/web.php) | 1 | Application adapter that invokes the bootstrap and serves the rendered content |

## Estimated WordPress code exercised

The default page exercises approximately **1.9%** of the distributed PHP source
lines on the original-PHP reference path (12,028 of 635,648 lines). Across the
default and all five test documents, the estimate is **2.3%** (14,562 lines),
with at least one line reached in 444 of the 1,512 original PHP files.

The estimate uses PHP 8.5.12's `phpdbg` opcode log for the unmodified WordPress
7.1.4 bootstrap fixture, including its inspection probes and shutdown. It counts
distinct original file/line pairs with opcode hits, divided by all physical
lines in the archive's PHP files, including comments and blanks. Fixtures and
non-PHP assets are excluded. All six traced renders matched the PHP baseline.
This is a reference-path estimate for the native fixture. Native verification
compares outputs, while an Elephc line-coverage measurement would require
instrumenting the native executable.

## Reproduce the web build

Run directly on a Linux x86_64 host with the following tools and libraries.

- Python 3.12 or newer, Bash, `patch`, a C compiler/linker and GNU binutils.
  The tested development SDK requires glibc 2.39 or newer.
- LLVM `ld.lld` on `PATH`, or its executable selected with `WORDPRESS_LLD`.
  The build can also discover `rust-lld` through an active `rustc` on `PATH`.
- The libsodium development library and headers available to the linker.
- PHP 8.4 or newer with PDO SQLite and mbstring for the reference checks.
- Internet access for the pinned WordPress archive and locked native packages.

First assemble the complete SDK from Elephc revision
`f20729406216d21ce99841cf4636b771db9874dd` (version `0.27.8`) plus the two SDK
patches. The original CI SDK artifact was unavailable on 2026-11-23. The
[compiler and bridge build recipe](patches/compiler/README.md) shows how to
build the SDK from source. [`toolchain.json`](elephc/full-source/toolchain.json)
records both compiler identities and the 25 runtime archives used in the CLI
and web proofs. A locally rebuilt SDK has its own binary identities and requires
verification.

```bash
export ELEPHC_BIN=/path/to/complete-sdk/elephc
./scripts/build-wordpress.sh --web
./scripts/serve-wordpress.sh
```

The wrapper prepares and checks the complete WordPress tree and installs the
locked native dependencies. It compiles with `--php-version 8.4`, `--web` and
`--web-isolation request`, with IR optimization off and a 512 MiB runtime heap.
Generated sources, reports and binaries go under `elephc/build/full-source/`.
`elephc` on `PATH` also works when it belongs to the complete SDK.

The recorded web build took about 40 minutes and produced a 2.24 GB executable.
Budget about 35 GiB of compiler memory and enough disk space for 17.7 GB of
assembly plus linker intermediates. Cold native dependency setup can add several
minutes.

Open [http://127.0.0.1:9000](http://127.0.0.1:9000). `/` and `/index.php` accept
GET and HEAD. Rerun the serve script to start an already-built binary.

Try a different block document without recompiling.

```bash
curl --get --data-urlencode 'document=<!-- wp:paragraph --><p>Ciao WordPress</p><!-- /wp:paragraph -->' http://127.0.0.1:9000/
```

The optional document is limited to 16,384 UTF-8 bytes without NUL.

## Compile the CLI bootstrap

```bash
./scripts/build-wordpress.sh
./elephc/build/full-source/bootstrap '<!-- wp:paragraph --><p>Ciao</p><!-- /wp:paragraph -->'
```

The CLI prints JSON with the HTML in its `rendered` field. Its executable and
build reports are separate from the web build.

## How the port works

Preparation copies the original archive and applies the full-file patch series
and fixtures in the generated tree. The original checksums remain intact. The
[section-placement linker](scripts/wordpress-link.py) preserves allocated bytes
and distinct function addresses in the large generated program.

```text
Elephc HTTP listener
  -> web.php validates the route, method and optional block document
  -> aot-probe.php runs original wp-load.php and wp-settings.php
  -> WordPress renders do_blocks() and do_shortcode()
  -> an output-buffer callback wraps the rendered content in HTML
```

The listener uses one worker and a fresh handler process per request. Each
handler pays the cost of the bootstrap and inspection probes, recreating the
in-memory SQLite database before rendering. The fixture selects Twenty
Twenty-Five, English locale and an empty plugin list.

## Verify

Check the archive and patch integrity, compare the compiled CLI with PHP, and
test HTTP on a separate port.

```bash
python3 scripts/prepare-wordpress.py --check
python3 scripts/test-wordpress-bootstrap.py \
  --native elephc/build/full-source/bootstrap
python3 scripts/test-wordpress-web.py --port 9001
```

The HTTP test starts and stops its own native server and checks exact rendered
content, repeated requests, HEAD, missing paths, rejected methods and NUL input.
For a new SDK, install the locked dependencies before running the control suites.

```bash
"$ELEPHC_BIN" native install --locked --manifest-path elephc/full-source/elephc.toml
python3 scripts/test-wordpress-controls.py --compiler "$ELEPHC_BIN"
ELEPHC_IR_OPT=off python3 scripts/test-wordpress-controls.py --compiler "$ELEPHC_BIN"
python3 scripts/test-wordpress-linker.py
```

Reconstruct the source tree in a temporary export and verify the controls.
Add `--native` for a fresh CLI build and bootstrap parity check.

```bash
python3 scripts/test-wordpress-clean-room.py --compiler "$ELEPHC_BIN"
ELEPHC_IR_OPT=off python3 scripts/test-wordpress-clean-room.py \
  --native --compiler "$ELEPHC_BIN"
```

## Current boundaries

Full theme request routing, administration, authentication, arbitrary plugins,
network operations and a persistent database are outside the verified profile.
Calls to unsupported APIs fail at explicit boundaries, and the control suites
use finite collaborators for paths outside the fixture.

## Documentation

- [Porting log and verified execution](docs/elephc/porting-log.md)
- [Machine-readable verification snapshot](docs/elephc/full-source-status.json)
- [Elephc patches and SDK build recipe](patches/compiler/README.md)
- [WordPress patch format and maintenance](patches/full-source/README.md)
- [Native API boundaries](docs/elephc/native-api-boundaries.md)
- [Original archive version and checksums](elephc/upstream.json)

WordPress sources retain their upstream GPL-2.0-or-later license. See
[`LICENSE`](LICENSE) and the original archive's `license.txt`.
