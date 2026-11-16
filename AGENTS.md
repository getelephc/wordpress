# Project conventions

- This is a bounded WordPress core port to Elephc, covering blocks, hooks,
  shortcodes and selected formatting with native CLI and HTTP profiles.
  Keep the scope explicit.
- Run application code, Elephc and PHP reference checks directly on the host.
  This project intentionally uses native tools and has no database.
- Use `127.0.0.1:9000` as the default local HTTP listener.
- Use the machine-wide build queue for native builds and clean-room checks.
  `scripts/build-elephc.sh` and `scripts/test-clean-room.py` use the installed
  host queue when available.
- Downloaded WordPress sources in `upstream/` are original dependencies.
  Do not edit them. Maintain compatibility rewrites in `patches/`, including
  all original, patch and resulting source checksums.
- The formatting module selects verified function ranges after patching the
  complete source file. Maintain the full-source, range and final-module hashes.
- `elephc/runtime/core/` and executables are generated. Rebuild with
  `scripts/prepare-elephc.py` and `scripts/build-elephc.sh`.
- Validate changes against unmodified PHP with `scripts/test-elephc.py` and
  use source-only reproduction when source preparation or patches change.
- Keep named application callbacks in the explicit `WordPressHookCallbacks`
  manifest. Direct closures and method callables use native descriptors.
- The content pipeline keeps HTML-attribute shortcode expansion disabled.
  Preserve its explicit KSES boundary rather than silently simulating sanitization.
- The complete WordPress compile probes intentionally fail on the pinned SDK.
  Keep their diagnostics separate from the verified parser profile.
- Keep provenance, boundaries and verified results current in the README,
  porting log and Code Journal. Never use em dashes.
