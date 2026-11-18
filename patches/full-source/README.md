# Complete-source WordPress patches

This series starts from the complete WordPress archive pinned in
`elephc/upstream.json`. It does not use the function ranges in the subsystem
manifest. Every prepared PHP file retains its complete original file structure.

`manifest.json` records one target per patch, its reason, and SHA-256 checksums
for the original file, patch and resulting complete file. The categories are:

- `compatibility`: a syntax or storage rewrite intended to preserve behavior.
- `finite-profile`: a source-loading manifest or an explicitly unsupported
  behavior. Read the reason before treating the corresponding API as supported.

Reconstruct and verify the complete build tree:

```bash
python3 scripts/prepare-wordpress.py
python3 scripts/prepare-wordpress.py --check
```

Preparation compares every original file with the pinned archive and refuses
modified dependencies, divergent patches or a divergent prepared tree. It copies
the entire distribution to `elephc/build/full-source/wordpress`, applies patches,
and adds the explicit files in `elephc/full-source/fixtures`. A fixture cannot
overwrite an original distribution file. The generated `source-manifest.json`
contains the original and prepared checksum inventories.

To develop a patch, edit the generated copy and record it:

```bash
python3 scripts/record-wordpress-patch.py wp-includes/example.php \
  --kind compatibility --reason 'Explain the compiler constraint and behavior.'
```

`php scripts/lower-wordpress-syntax.php GENERATED_FILE` is a development helper
for splitting local static declarations and expressing inline HTML as literal `echo`
statements. It uses PHP tokens, preserves the template text, and does not remove
functions. Review its complete diff before recording a patch. Do not run this
helper against `upstream/`. The `--html-only` mode leaves static declarations
untouched and is used for the complete default-theme pattern files.

Its optional `--object-callbacks` mode uses object handles in callback arrays.
This mode requires a `finite-profile` entry: callbacks that replace an object
argument by reference need an explicit adapter and are outside the current
fixture. Ordinary mutations of the object's properties preserve identity.

Compiler traversal, successful PHP execution and successful native execution are
different results. A full copied tree or a parsed dependency is not evidence
that WordPress has executed natively.
