# Required Elephc patch

The native WordPress result uses the complete patch in
[`wordpress-main.patch`](wordpress-main.patch), applied to Elephc revision
`f20729406216d21ce99841cf4636b771db9874dd`.
Its SHA-256 is
`494a3fdf054c04dbf860de008ee030ec85bb0d0e9a2fbfe1eb75e1ef9d60de1f`.
This repository contains the changes needed by the verified build; an unpatched
SDK at that base revision is insufficient.

The patch covers include-loaded callable descriptors and activation, finite
callable analysis, inherited and virtual method indexing, reference and ownership
handling, generated argument forwarding, native property access and bounded
large-program assembly. Regression tests for compiler changes are included in
the patch. It applies to the compiler checkout, separately from the complete
WordPress source patches in `patches/full-source/`.

The exact compiler, SDK archive and runtime identities used for the recorded
result are in [`toolchain.json`](../../elephc/full-source/toolchain.json).
The compiler version banner alone does not identify this patched SDK. A different
local binary must receive its own PHP/native verification.

Use Rust 1.98.1, the tested upstream CI toolchain for this source build.
Build from a separate, clean source checkout at the exact revision:

```bash
# Run from this WordPress repository. The destination must not already exist.
gh repo clone illegalstudio/elephc elephc/build/full-source/compiler -- --no-checkout
git -C elephc/build/full-source/compiler checkout --detach \
  f20729406216d21ce99841cf4636b771db9874dd
git -C elephc/build/full-source/compiler apply --check \
  "$PWD/patches/compiler/wordpress-main.patch"
git -C elephc/build/full-source/compiler apply \
  "$PWD/patches/compiler/wordpress-main.patch"
(
  cd elephc/build/full-source/compiler
  CARGO_PROFILE_RELEASE_OPT_LEVEL=1 \
  CARGO_PROFILE_RELEASE_DEBUG=line-tables-only \
    cargo build --release --locked -j 1 -p elephc --bin elephc
)
```

The recorded Actions SDK had no downloadable artifacts when checked on
2026-11-23. Build the matching bridge
outputs from this checkout using the crate inventory in the pinned
[upstream build action](https://github.com/illegalstudio/elephc/blob/f20729406216d21ce99841cf4636b771db9874dd/.github/actions/build-release-artifacts/action.yml):

```bash
(
  cd elephc/build/full-source/compiler
  cargo build --release --locked -j 1 \
    -p elephc-tls -p elephc-pdo -p elephc-crypto -p elephc-openssl \
    -p elephc-bcmath -p elephc-gmp -p elephc-random -p elephc-mbstring \
    -p elephc-iconv -p elephc-phar -p elephc-tz -p elephc-image \
    -p elephc-probe -p elephc-instr -p elephc-web -p elephc-pcntl \
    -p elephc-xml -p elephc-tidy -p elephc-fileinfo -p elephc-tokenizer \
    -p elephc-magician -p elephc-reference -p elephc-curl -p elephc-zend
  cargo build --release --locked -j 1 -p elephc-magician --features curl \
    --target-dir target/elephc-magician-curl-release-build
  cp target/elephc-magician-curl-release-build/release/libelephc_magician.a \
    target/release/libelephc_magician_curl.a
  printf '\n' > target/release/elephc-pdo-features.txt
)
mkdir -p elephc/build/full-source/sdk-local
cp elephc/build/full-source/compiler/target/release/elephc \
  elephc/build/full-source/compiler/target/release/libelephc_*.a \
  elephc/build/full-source/compiler/target/release/elephc-pdo-features.txt \
  elephc/build/full-source/sdk-local/
export ELEPHC_BIN="$PWD/elephc/build/full-source/sdk-local/elephc"
"$ELEPHC_BIN" --print-capabilities
```

The bridge recipe follows the upstream release build. It has not been used to
replace the recorded CI runtime archives in this experiment. Local Rust and native
build dependencies must satisfy the pinned upstream source. Reconstructed SDK
bytes have their own provenance and require the WordPress controls again.
Keep runtime archives beside the compiler as regular files. Runtime archives
represented by symbolic links are rejected by Elephc's SDK validation.

Source build identities include the base revision, this patch's checksum, the
Rust version and build flags. A locally built compiler's binary checksum can
differ across build paths and toolchains. Runtime archives must retain the
verified base SDK identities.

## Verify the resulting SDK with WordPress

From this WordPress repository, with `ELEPHC_BIN` selecting the complete SDK:

```bash
python3 scripts/prepare-wordpress.py
python3 scripts/test-wordpress-controls.py --compiler "$ELEPHC_BIN"
ELEPHC_IR_OPT=off python3 scripts/test-wordpress-controls.py --compiler "$ELEPHC_BIN"
./scripts/build-wordpress.sh
python3 scripts/test-wordpress-bootstrap.py \
  --native elephc/build/full-source/bootstrap
```

The source-only reproduction command and current execution boundaries are in the
[repository README](../../README.md). The WordPress port includes explicit
compatibility fixtures for remaining SDK limitations. Compiling the source graph
does not establish full HTTP, administrative or plugin support.
