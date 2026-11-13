# Parser compatibility patches

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

When changing a patch, regenerate its hash and resulting source hash in the
manifest, then run the differential fixtures and source-only reproduction.
Keep the original state machine and regular expression visible in the patch.
Do not silently remove parser branches to make compilation pass.

The typed constructors are intended for calls made by the parser, not the
entire loosely typed public API of the original WordPress classes. Token
matching currently copies the remaining document at each step, so it should
not be assumed to retain upstream performance characteristics.
