# Patches for the complete-source WordPress port

- [`compiler/`](compiler/README.md) contains the required Elephc compatibility and
  scaling patch, pinned base revision and complete SDK build instructions.
- [`full-source/`](full-source/README.md) contains the WordPress compatibility
  series. Every patch applies to a complete original file. Its manifest records
  the original, patch and resulting source hashes.

Runtime fixtures are in `elephc/full-source/fixtures/`. Prepare and check the
complete original distribution with `scripts/prepare-wordpress.py`. Original
sources under `upstream/` are immutable generated dependencies. The primary
profile does not extract selected function ranges.
