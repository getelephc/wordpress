# Project conventions

- This is a bounded WordPress core port to Elephc, covering block parsing and
  serialization, hooks, shortcodes, attribute KSES, object caching, list utilities and formatting
  through native CLI and HTTP profiles. Keep the scope explicit.
- Run application code, Elephc and PHP reference checks directly on the host.
  This project intentionally uses native tools and has no database.
- Use `127.0.0.1:9000` as the default local HTTP listener.
- Use the machine-wide build queue for native builds and clean-room checks.
  `scripts/build-elephc.sh` and `scripts/test-clean-room.py` use the installed
  host queue when available.
- Downloaded WordPress sources in `upstream/` are original dependencies.
  Do not edit them. Maintain compatibility rewrites in `patches/`, including
  all original, patch and resulting source checksums.
- Selected modules use verified function ranges or source segments after
  patching complete original files. Maintain the full-source, range and final-module hashes.
- Serialization accepts validated block trees with UTF-8 JSON attributes. Keep
  charset recovery and complete block rendering outside the profile explicit.
- `elephc/runtime/core/` and executables are generated. Rebuild with
  `scripts/prepare-elephc.py` and `scripts/build-elephc.sh`.
- Validate changes against unmodified PHP with `scripts/test-elephc.py` and
  use source-only reproduction when source preparation or patches change.
- Keep named application callbacks in the explicit `WordPressHookCallbacks`
  manifest. Direct closures and method callables use native descriptors.
- The default content pipeline keeps HTML-attribute expansion disabled. Explicit
  HTML modes use the selected KSES allowlist. Whole-document KSES, CSS, XML
  entity normalization and charset recovery remain outside this profile.
- Object caching is request-local and accepts JSON values. Preserve core group,
  found flag and suspension behavior. Object cloning, persistent drop-ins, direct
  property access, HTML cache statistics and deprecated reset are explicit boundaries.
- List operations use JSON array records and scalar/null comparisons. Preserve
  PHP comparison, key and sorting behavior; validate selected pluck fields. PHP
  object records and database queries remain outside this profile.
- The complete WordPress compile probes intentionally fail on the pinned SDK.
  Keep their diagnostics separate from the verified parser profile.
- Keep provenance, boundaries and verified results current in the README,
  porting log and Code Journal. Never use em dashes.
