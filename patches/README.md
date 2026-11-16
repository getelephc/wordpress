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
| `shortcodes.php` | Typed parser values, closure adapters for regex callbacks, builtin bridges and explicit attribute-expansion boundary |
| `blocks.php` | Five selected serialization functions, typed recursion, explicit string emptiness and a JSON encoder boundary |
| `formatting.php` | Typed formatting values, concrete regex cache, builtin bridges and closure newline-preservation callback |

`class-wp-filter-sentinel.php` is copied without changes. Its manifest entry
has no patch; original and generated source hashes must match.

The formatting entry lists seven selected functions and the blocks entry lists
five. Apply each small patch to its complete original file and verify `patched_source_sha256`, then select
the recorded byte ranges in order. Each range has its own hash; the assembled
PHP module must match `patched_sha256`. Source preparation performs these steps
using Python only. When changing either patch or its selected functions,
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
