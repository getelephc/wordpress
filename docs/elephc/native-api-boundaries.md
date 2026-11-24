# Current native API and fixture boundaries

The complete-source port preserves original WordPress capability checks and
algorithms. Unsupported native PHP APIs pass through `wp_aot_native_api` and
raise explicit errors when reached. The [manifest](../../elephc/full-source/native-boundaries.json)
records API names, call sites and bounded native replacements. The PHP reference
continues to call real PHP functions. Optional capability guards inspect the
original API names and do not advertise unavailable features.

The verified targets are the isolated native CLI bootstrap and bounded native
HTTP rendering of the homepage, three seeded posts and five runtime documents.
The HTTP entry uses the original initialization, Latest Posts block and post and
content APIs with request isolation. Full theme request dispatch, administration,
authentication, arbitrary plugins, persistent storage and network requests remain
outside that proof. This includes original paths that need authentication cookie state, password
hashing, some file/compression/XML operations or unrestricted introspection.
Compiler traversal does not count these paths as executed native coverage.

## Explicit runtime fixtures

The [fixture directory](../../elephc/full-source/fixtures/) supplies:

- A single-site configuration using the bundled default theme and English locale,
  without installed plugins or a shared database.
- In-memory SQLite containing site options and empty taxonomy tables. The HTTP
  entry adds three published posts to the otherwise empty post table per request.
  Original SQL and theme-style queries execute against this finite environment.
- Checked views for original class contracts and finite native reflection,
  property and constant inventories. Unknown metadata paths fail explicitly.
- Explicit array, binary, regex, HTML/entity and query helpers, exercised by the
  corresponding original-PHP/native controls.
- Distinct PHP and native platform implementations. PHP-only dynamic property
  operations stay outside native storage inference. Native unsupported paths
  throw instead of returning invented values.

The source, fixture and metadata hashes are verified during preparation. Original
files under `upstream/` are never edited. Required Elephc compiler changes are in
[`wordpress-main.patch`](../../patches/compiler/wordpress-main.patch); complete
WordPress compatibility changes are in [`patches/full-source/`](../../patches/full-source/README.md).

## Bounded native replacements

`uniqid` uses the native microsecond clock and CSPRNG with the PHP 8.4 timestamp
and optional decimal entropy format. Its pinned source identity is recorded in
the API manifest. Controls compare deterministic formatting and generated value
invariants. Prefixes are NUL-free strings and the entropy flag is Boolean; weak
argument coercion, thread-local parity and the PHP fallback after CSPRNG failure
are outside the fixture.

`hash_hmac_algos` derives its catalog from actual native hash capabilities,
excluding entries rejected by native HMAC. The crypto controls compare the
canonical names, ordering, digests, binary values, long keys and rejection paths.
Direct uppercase algorithm names remain an SDK boundary.

Hook invocation maps the original `strip_tags` string to a first-class native
descriptor while retaining callback registration and removal identity. Other
callbacks retain their original identities. Complete hook-file controls verify
named, closure and method callbacks with mutation and nested execution.

Bootstrap constant inventories preserve original definitions and first values.
Typed views cover known runtime strings and time units; they do not implement
unrestricted dynamic constant introspection. The static block path inventory
retains every original bundled registrar. The lazy pattern loader retains all
109 original default-theme files.

The complete Style Engine and capability-mapping controls load whole original
files with finite collaborators. Unused collaborators throw. Concrete protected
callbacks retain their original implementations, and capability switch exits
still reach the original final filter. The component controls do not establish
all stylesheet, authorization or database paths.

## Differential controls

The 47 primary groups compare native behavior with unmodified PHP. Complete-file
components include hooks, capabilities, styles, block context, Theme JSON, query
cache keys, wrapper attributes, pattern registration and mailer objects. Their
support fixtures and cases live together under
[`tests/full-source/`](../../tests/full-source/).

The native bootstrap comparison separately checks original initialization,
complete application-probe state and all five runtime documents. Source-only
reproduction repeats preparation and controls from maintained files; `--native`
also rebuilds the full executable and repeats actual native parity. The current
result is recorded in [full-source-status.json](full-source-status.json).
