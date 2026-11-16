# Core compatibility patches

Each patch mirrors its WordPress target under `wp-includes/`.
`manifest.json` records the original, patch-file and patched-file SHA-256
hashes. The upstream archive and diagnostic source hashes are recorded
separately in `elephc/upstream.json`.

Run `python3 scripts/prepare-elephc.py` to reconstruct the generated tree.
Run it with `--check` to verify existing sources without downloading or writing.
A changed original, patch or generated file causes verification to fail.
Repeated preparation starts from the same original sources and is idempotent.

| Target | Changes |
| --- | --- |
| `class-wp-block-parser-block.php` | Explicit public property and constructor types for the five block fields |
| `class-wp-block-parser-frame.php` | Typed block reference, integer offsets and nullable leading offset |
| `class-wp-block-parser.php` | Typed parser storage and input, frame guards, append via a local, three-argument regex capture and byte-offset reconstruction |
| `class-wp-hook.php` | Typed hook storage, explicit iteration cursors, callback descriptor resolution, safe table updates and Iterator signatures |
| `plugin.php` | Fresh hook globals, typed values/arguments, object identity adapter, table updates and explicit exceptions for nine APIs outside the hooks profile |
| `shortcodes.php` | Typed parser values, closure adapters for regex callbacks, builtin bridges and the selected HTML attribute path |
| `kses.php` | Selected attribute/protocol/entity helpers, original entity table, explicit CSS/XML boundaries and regex adapters |
| `class-wp-object-cache.php` | Typed cache storage, uniform mixed maps, local bucket updates, explicit site context and JSON-profile boundaries |
| `cache.php` | Local class include, typed API, explicit global instance initialization/guards and deprecated reset boundary |
| `functions.php` | Select/type four original list wrappers and `wp_suspend_cache_addition` |
| `class-wp-list-util.php` | Typed array records, uniform result maps, local sort updates, method callback descriptors and explicit object-record boundaries |
| `blocks.php` | Five selected serialization functions, typed recursion, explicit string emptiness and a JSON encoder boundary |
| `formatting.php` | Typed formatting values, concrete regex cache, builtin bridges and closure newline-preservation callback |

`class-wp-filter-sentinel.php` is copied without changes. Its manifest entry
has no patch; original and generated source hashes must match.

The formatting entry lists eight selected functions and the blocks entry lists
five. The KSES entry uses source segments for its global declarations, original
entity table and 21 functions. Apply each small patch to its complete original file and verify `patched_source_sha256`, then select
the recorded byte ranges in order. Each range has its own hash; the assembled
PHP module must match `patched_sha256`. Source preparation performs these steps
using Python only. When changing a patch or its selected source ranges,
recompute the ranges and all affected hashes from the resulting full file.
The original regexes and function bodies remain visible and reviewable.

When changing a patch, regenerate its hash and resulting source hash in the
manifest, then run the differential fixtures and source-only reproduction.
Keep the original state machine and regular expression visible in the patch.
Do not silently remove parser branches to make compilation pass.

The typed constructors are intended for calls made by the parser, not the
entire loosely typed public API of the original WordPress classes. Token
matching currently copies the remaining document at each step, so it should
not be assumed to retain upstream performance characteristics.

The hook profile retains WordPress's dispatch and active-priority resorting
algorithms. Hidden PHP array pointers are replaced by explicit cursor state,
which also tracks recursive execution. Named application callbacks use
`WordPressHookCallbacks` to resolve a finite source-defined descriptor manifest
while retaining WordPress's original callback identity key.
Original plugin filesystem, lifecycle and deprecated-hook helper bodies are
preserved in the patch diff and replaced by explicit exceptions in this profile.

The block serializer retains core comment construction, attribute escaping and
recursive child/chunk ordering. Its JSON bridge supports encodable UTF-8 values;
encoding failures raise an exception instead of importing charset recovery.
The content emptiness branch explicitly checks both the empty string and `"0"`
to retain PHP behavior on the pinned native SDK. `serialize_blocks` uses an
explicit loop to avoid dispatching a function name through generic `array_map`.

The KSES graph deliberately selects the attribute parser rather than replacing
modern `wp_kses_hair`, which uses the HTML API. The policy has no style attribute;
a caller that enables style encounters an explicit CSS boundary. UTF-8 escaping
uses core entity normalization and HTML 4.01 quote behavior because the pinned
native htmlspecialchars builtin lacks its fourth double_encode argument.
Original PHP reference checks load original formatting/charset helpers and
short-circuit the blog_charset option to UTF-8 through its normal filter, without
a database. Regex adapters preserve supported binary entity/protocol slices
and avoid the pinned empty-subject replacement crash. A getter retains the original
entity table and restores it for each operation after native HTTP global resets.
The existing shortcode
HTML branch is restored with typed callbacks and a per-call match counter.

The object cache retains core add/replace/get/delete, counter, group, bulk and
suspension behavior. Uniform mixed maps avoid a pinned native storage-contract
failure when key 0 is followed by key -2; updates use local buckets before
assigning class properties. The adapter supplies the constructor site context.
Original PHP gets the same context through its normal `is_multisite` and
`get_current_blog_id` dependencies. JSON values avoid PHP object cloning;
magic access, HTML statistics and deprecated reset throw explicit exceptions.
The original cache's ignored expiration/force arguments and non-persistence
are preserved. Per-request initialization also clears addition suspension.

The list profile retains the original filtering, plucking and comparator logic
for array records. JSON objects are decoded associatively. Result maps use
uniform mixed storage. The pinned backend rejects `uasort` for generic record
arrays, so a stable merge-sort bridge calls the unchanged core comparator
through a first-class method descriptor and retains the requested key policy. Scalar string zero ordering
retains PHP empty behavior. Object-record branches and invalid-row translation
notices become explicit exceptions. The JSON adapter validates required pluck
fields and scalar comparison domains before core calls. The selected functions
module is shared with cache setup; `functions-bootstrap.php` loads its list
class dependency for both entry points.
