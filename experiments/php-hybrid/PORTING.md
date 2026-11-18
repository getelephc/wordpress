# PHP hybrid implementation notes

WordPress runs in PHP with selected algorithms supplied by an Elephc shared library and a Zend extension. The bounded native CLI/HTTP profile and its patches are separate. Generated files and the isolated SQLite fixture live under `elephc/build/php-hybrid/`.

| Component | Responsibility | Verification |
| --- | --- | --- |
| `source-index.php`, `source-tools.py` | Extract verified source ranges and lower internal methods to typed free functions. Native class records retain state and constructors. | Parser fixtures, object-operation corpora and complete pages. |
| `prepare-extended.py`, `extended-runtime.php` | Assemble the native graph, specialize fixed callbacks, format finite error templates and provide dispatch/context. Initialize the immutable entity table once per PHP request. | Source checksums, compiler checks, exact profiling and module hashes. |
| `object-protocol.py` | Retain native records by request-local handles. Pure getters return values; mutations return changed properties. Preserve self-transfer aliases. | Error history, response inheritance, lazy request flags, direct PHP mutations and handle lifetime. |
| `adapter.c`, `abi-guard.S` | Call the generated C ABI from Zend, reset native state per request, preserve SysV callee-saved registers and count actual native entries. | Internal-function reflection, direct comparisons, ABI probe and repeated HTTP requests. |
| `fast-exports.php`, `fast-adapter.h` | Transfer typed scalar/string values without a generic JSON envelope. Project numeric-array key types and execute the original native predicate. | Original-PHP comparisons, opaque values and recovery after a caught native exception. |
| `host-bridge.php`, `host-objects.php` | Preserve PHP callbacks and opaque payload identity. WeakMap snapshots resynchronize native class views after direct PHP mutations. Native code owns ordinary cache operations. | Object identity, cache/API interoperability, cloning and nested-hook controls. |
| `route-extended.py` | Inject delegation only into the generated site copy. PHP controls restore byte-identical upstream core files. | Source hashes and full HTML/REST JSON equality. |
| `corpus.php`, `extra-cases.php`, `verify.php` | Compare operation corpora within a complete WordPress bootstrap and check bridge-specific behavior. | 1049 operation cases and 129 direct extension comparisons. |
| `bench.py`, `coverage.py`, `report.py` | Rotate PHP, extension-loaded/PHP and hybrid modes. Preserve timings and distinguish compiled, routed and observed code. Publish the current measurement snapshot. | 252 measured responses with identical hashes, per-entry counters and fallback counts. |
| `profile-native.py`, `profile-report.py` | Replay current native calls with Elephc exact monitoring and optional assertions. | One entity-table initialization and at most four request imports in the recorded REST trace. |

Native source comes from checksum-verified prepared modules and the selected original wp_parse_list function. Provenance records original, prepared, selected-range and generated-module hashes. Dependencies under `upstream/` are never edited.

The pinned cdylib checker requires internal methods to be lowered to free functions. Two fixed serializer templates use equivalent concatenation; finite error templates use an explicit formatter; shortcode quoting uses a direct typed loop. Typed error-payload helpers preserve array values. CURIE filtering preserves the fixed base list and adds the PHP-filtered records. Intermediate callback-sensitive operations use explicit PHP fallbacks; terminal filters retain their original arguments.

Every native ABI entry, lifecycle operation and error accessor uses the SysV register-preserving guard. The ABI probe targets the current library. Scalar exports return values directly and are checked through the separate last-status accessor. The integration is Linux x86_64 NTS and sequential only.

Build, benchmark and profiling scripts use public tools on PATH. Preparation creates the output directory and copies the repository's dependency manifest and lockfile; the build installs locked PCRE2 explicitly. A fresh temporary project with a restricted PATH compiled the native library, linked and called the extension, and ran exact profiling without personal helpers or a required version manager.

See README.md and results.json for current coverage, measurements and boundaries. Generated raw captures remain under `elephc/build/php-hybrid/`. Profiling includes instrumentation and generic diagnostic transport; HTTP and microbenchmarks disable profiling and use typed paths. Retained-at-return counters do not establish leaks. The hybrid is currently slower than normal PHP.
