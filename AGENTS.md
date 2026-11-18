# Project conventions

- The primary experiment starts from the complete original WordPress archive,
  following the Tempest/Laravel full-file patch workflow. Maximize actual native
  WordPress execution through reviewable compatibility patches and explicit
  fixtures. Use `scripts/prepare-wordpress.py`, `patches/full-source/` and
  `elephc/full-source/fixtures/`. Do not extract selected function ranges for
  this profile. Keep compiler traversal and verified native behavior distinct.
- The earlier subsystem profiles cover blocks, hooks, shortcodes, attribute
  KSES, caching, lists, URLs, sanitization and bounded REST objects. Their
  boundaries below apply to those profiles and do not limit the primary port.
- Run application code, Elephc and PHP reference checks directly on the host.
  Use native tools. The full-source fixture uses isolated in-memory SQLite;
  the subsystem profiles have no database. Never connect the fixture to a
  shared database.
- Use `127.0.0.1:9000` as the default local HTTP listener.
- The PHP-hosted hybrid experiment under `experiments/php-hybrid/` uses an
  isolated SQLite fixture. Its documentation, reports and tools describe only
  the current implementation and PHP controls, without legacy hybrid variants.
- Keep repository scripts portable: use tools on `PATH` without personal paths,
  agent-specific helpers or automatic workload scheduling. Apply workstation
  scheduling policies outside the repository scripts.
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
- URL operations process UTF-8/NUL-free strings and bounded JSON query data.
  Keep protocol policy/order, display/raw contexts and parser result distinctions.
  HTTP transport, remote-request validation, PHP object queries and INI defaults
  remain outside this profile. Keep the NUL regex diagnostic separate.
- Text sanitization accepts UTF-8/NUL-free strings. Preserve original field and
  textarea whitespace, percent removal, recursive class fallback/filter context,
  entity normalization and empty/null color results. Non-string coercion and
  charset recovery remain outside this profile.
- Error objects use bounded request-local JSON operation sequences. Preserve
  original code defaults, empty/null data and history, merge/export ordering,
  snapshot independence and action context. PHP object data, subclasses and direct
  property mutation remain outside the profile.
- HTTP/REST response objects preserve status normalization, header/link ordering,
  sparse link keys, metadata, identity and original error conversion. The HTTP
  endpoint returns an inspection envelope; full REST routing/authentication and
  handler execution remain outside the profile.
- REST requests preserve canonical headers, active-source precedence, null/presence
  distinctions, lazy JSON flags, ArrayAccess and isolated permalink policy. Budget
  checks and peek must not force parsing. Required/callback phases use the original
  lifecycle with a finite source-defined manifest and request-local traces. Preserve
  partial sanitization and error aggregation. Bounded schemas use the original
  dispatcher and scalar/array/object validators, at most 64 schema nodes and eight
  nested schema levels. Preserve required-list precedence, numeric array key policy,
  nested error paths, value independence and fixed English locale behavior.
  Boolean uniqueItems uses original recursive stabilization and serialization;
  preserve value type distinctions, property-order independence and error precedence.
  Scalar list coercion, combining/multi-type schemas, enum/formats/patterns,
  automatic schema sanitization, form decoding and full JSON Schema remain boundaries.
  URL discovery, authentication and dispatch remain explicit boundaries.
- The earlier complete WordPress compile probes preserve release-SDK failure
  diagnostics. Keep them separate from the verified subsystem profiles and the
  new complete-source port on the main SDK.
- Keep provenance, boundaries and verified results current in the README,
  porting log and Code Journal. Never use em dashes.
