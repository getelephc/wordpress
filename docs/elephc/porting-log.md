# Current complete-source WordPress result

The repository retains the complete-source WordPress 7.1.4 port and its
reproduction workflow. All 3,782 original archive files remain immutable.
The preparation applies 1,103 complete-file compatibility patches and adds
fifteen explicit runtime fixtures. No selected WordPress function ranges are
extracted.

## Verified identities

| Input | SHA-256 or revision |
| --- | --- |
| WordPress archive | `881e53a4681ff2cd911651b11cccdede5ae477ddf0765af5d6a1a8b219a1d835` |
| Prepared source manifest for the recorded CLI and controls | `c19cbe6d850639389b67fd665a288f491afcf6dfbb929d538db2423bb75d00c5` |
| Current prepared source manifest for HTTP | `22837b12a147e6e3ae01a5723370202d02b193e960bf5788651e253a33b8b567` |
| Elephc base revision | `f20729406216d21ce99841cf4636b771db9874dd` |
| Elephc compatibility patch | `56d32d2e2293e10176da0b8eddd8d207077c4461f3752fcbd4b0d4de5b0844bb` |
| Verified compiler executable | `2f75c59ebe654fcf86e481d990ed6428ef462cfdabc005e3060166efb323a9fc` |
| Ordinary native executable | `cf8296ba1dc808e3070c2cab814587ce0790641894f201d0a3e3f48e21440dbf` |
| Source-only native executable | `682acf48d0a61e6f5598059e7a9acf69f5cef39d38e2ee01dfc322ee26c6e110` |

The required compiler changes are versioned in
[`patches/compiler/wordpress-main.patch`](../../patches/compiler/wordpress-main.patch).
[SDK instructions](../../patches/compiler/README.md) reconstruct that compiler
from the pinned base. A locally reconstructed SDK has its own binary identities
and requires the WordPress controls again.

## Verified execution

The ordinary public CLI build compiles and links the complete graph in
2172.99 seconds. It emits 17,756,316,802 assembly bytes,
a 4,221,808,432-byte combined object and 2,109,009,393 code bytes.
Allocated contents retain SHA-256 `12c9fd825d5e2d24a2a6c40154db9d1625e36c644c075f72864da1ea7298cbe9`
after section placement. Function folding remains disabled.
These numbers describe build correctness and resource use, not a runtime benchmark.

The actual native CLI bootstrap and all five runtime documents match the full
original-PHP inspection states without unexpected runtime diagnostics. The cases
cover empty content, nested Unicode blocks, a dynamic title, video shortcode and
navigation label uniqueness. The state includes 116 registered core blocks,
109 pattern hashes and the complete application probes.

All 47 native control groups pass 230,504 comparisons with IR optimization on.
A separate export containing maintained source and the verified original archive
passes all 47 groups with IR optimization off, performs another ordinary complete
native build and repeats bootstrap/document parity. Its export is removed.
The [verification snapshot](full-source-status.json) binds the reports to their
source, compiler, test-input, harness and linker identities.

## Native HTTP rendering

The public `scripts/build-wordpress.sh --web` build uses the complete prepared
core and the separate `elephc/full-source/web.php` entry. It finishes in
2588.13 seconds with compiler
`5017cabe244cfe49dc7af7620218bc422a2143c115fd398ea5f8f727864a23cc` and executable
`88a2186f9ee4386c853a5a2289c1d6b7640dff83c9b8b0afc0be909e3731509c`.
The linker preserves allocated bytes and disables function folding.

The entry executes the original `wp-load.php` and `wp-settings.php` for each
request and seeds four English posts in request-local SQLite. The homepage
renders the original Latest Posts block, which queries those rows through
`WP_Query` and generates plain WordPress permalinks. The article route uses
`get_post()` and the core block and shortcode pipeline. The diagnostic probes
remain on the separate CLI path. `scripts/serve-wordpress.sh` starts one worker
on `127.0.0.1:9000` with request isolation.

The HTTP harness compares the homepage, all five documents and the four linked
posts with stock PHP. It also checks repeated requests, HEAD, 404, 405, invalid
input and both local assets used by post 1004. Stock PHP serves the reference
pages from a temporary copy of the original archive with the same fixtures.
All 23 HTTP checks and the retained native CLI parity checks passed.

Post 1004 uses the WordPress 7.1.4 Gutenberg serializer. Its 18 rendered blocks,
including the nested columns and captioned image, have matching SHA-256 hashes.
The raw HTTP bodies match and the normalized content diff is empty. Headless
Chromium found zero differing pixels at both 390 and 1280 pixels wide.

Two retained whole-file patches preserve the nonempty WordPress query path.
`class-wp-query.php` converts result rows through direct `get_post()` calls with
the original `OBJECT` and `raw` defaults and preserves result keys. The Latest
Posts block passes its live loop post to the title and permalink APIs, avoiding
implicit lookups through the native `GLOBALS` view.

The additional `patches/compiler/wordpress-web.patch` coerces boxed request-global
replacements into the native hash layout. It unboxes through an indexed view and
uses the runtime heap kind to pass through a hash or rebuild an indexed array.
Its five-target IR/assembly test,
both-IR native HTTP test and five packaged-SDK reproducers pass. The SDK uses
Rust 1.99.1 and retains all 25 base runtime archives. An independent maintained-file
export reconstructs the current core and passes all ten original-PHP web pages.
An independent reconstruction applied both compiler patches to the pinned SDK
source. English sample strings changed in two of the 69 patched files, both
regression tests. The SDK's compiler and runtime sources are byte-identical
to the previous reconstruction. Both translated SDK fixtures pass with IR
optimization on and off. The seven WordPress control groups with translated
samples also pass 17,165 primary comparisons against original PHP.

## Current adaptations

- Complete original PHP files receive explicit native storage types, checked
  contract views and loader paths. Original algorithms and optional branches
  remain reviewable in their complete-file patches.
- Runtime fixtures provide in-memory SQLite, finite reflection and constant
  metadata, explicit array operations and typed native API boundaries.
- Hook iteration uses explicit cursor state with mutation and recursion checks.
  Named callback identities and original filter/action behavior are retained.
- Theme JSON uses a local immutable settings-schema snapshot and non-null key
  presence checks. Its complete-class control has 330 comparisons per IR mode.
- Query cache-key traversal captures a local database snapshot around the original
  recursive walk, retaining fresh global reads around it. Its complete-class
  control has 360 comparisons per IR mode.
- Wrapper attribute merge callbacks have explicit string arguments and results
  after the original caller normalization. The complete file has 409 comparisons
  per IR mode, including zero values, Unicode, escaping and input independence.

The CLI fixture selects the original Twenty Twenty-Five theme, English locale,
no installed plugins and isolated SQLite in memory. The HTTP entry uses the same
fixture. Full theme routing, administration, authentication, arbitrary plugins,
persistence and network operations remain outside this proof. The CLI prints
JSON; the separate web executable serves HTML on `http://127.0.0.1:9000`.
