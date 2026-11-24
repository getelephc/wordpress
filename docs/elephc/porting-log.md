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
| Prepared source manifest | `c19cbe6d850639389b67fd665a288f491afcf6dfbb929d538db2423bb75d00c5` |
| Elephc base revision | `f20729406216d21ce99841cf4636b771db9874dd` |
| Elephc compatibility patch | `494a3fdf054c04dbf860de008ee030ec85bb0d0e9a2fbfe1eb75e1ef9d60de1f` |
| Verified compiler executable | `2f75c59ebe654fcf86e481d990ed6428ef462cfdabc005e3060166efb323a9fc` |
| Ordinary native executable | `cf8296ba1dc808e3070c2cab814587ce0790641894f201d0a3e3f48e21440dbf` |
| Source-only native executable | `682acf48d0a61e6f5598059e7a9acf69f5cef39d38e2ee01dfc322ee26c6e110` |

The required compiler changes are versioned in
[`patches/compiler/wordpress-main.patch`](../../patches/compiler/wordpress-main.patch).
[SDK instructions](../../patches/compiler/README.md) reconstruct that compiler
from the pinned base. A locally reconstructed SDK has its own binary identities
and requires the WordPress controls again.

## Verified execution

The ordinary public build compiles and links the complete graph in
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
no installed plugins and isolated SQLite in memory. Full HTTP request handling,
administration, authentication, arbitrary plugins, persistence and network
operations remain outside this proof. The configured local URL is
`http://127.0.0.1:9000`; the executable does not start a server.
