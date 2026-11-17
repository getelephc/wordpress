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
| `functions.php` | Select/type four original list wrappers, `wp_suspend_cache_addition` and two query builders |
| `http.php` | Three selected URL parser/component helpers with explicit array/scalar boundaries |
| `class-wp-error.php` | Typed properties/methods, uniform mixed maps, local bucket updates and explicit string-zero emptiness |
| `load.php` | Select/type `is_wp_error`, bounded integer `absint` and original JSON media-type predicate |
| `class-wp-http-response.php` | Typed data/status/headers, uniform header storage and local updates |
| `rest-api/class-wp-rest-response.php` | Typed inherited response API, local link updates and explicit array/message casts |
| `rest-api/class-wp-rest-request.php` | Typed request state, local source/header updates, ArrayAccess, original callback lifecycle with descriptors and explicit form/general-schema/URL boundaries |
| `rest-api.php` | Select/type response ensuring, error conversion, integer/boolean predicates, boolean sanitization and five scalar schema validators |
| `class-wp-list-util.php` | Typed array records, uniform result maps, local sort updates, method callback descriptors and explicit object-record boundaries |
| `blocks.php` | Five selected serialization functions, typed recursion, explicit string emptiness and a JSON encoder boundary |
| `formatting.php` | Typed formatting values, concrete regex cache, builtin bridges and closure newline-preservation callback |

`class-wp-filter-sentinel.php` is copied without changes. Its manifest entry
has no patch; original and generated source hashes must match.

The formatting entry lists 24 selected functions and the blocks entry lists
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

The URL profile selects esc_url and its aliases, _deep_replace and relative-link
conversion from formatting, three URL parsing helpers from http.php, and two
recursive query builders from functions.php. Entity/protocol processing reuses
KSES. The deep replacement loop detects convergence instead of using the
unsupported str_replace count argument; array searches use the existing string
bridge. Empty regex subjects are guarded and protocol selection uses an explicit
first-value loop. Query separators are explicit; PHP object data and INI lookup
are outside the profile. Original PHP uses original helpers with the same fixed
protocol policy and request data. The application rejects NUL before regex calls.

The text profile adds eleven original formatting helpers: sanitize_key,
sanitize_html_class, esc_html, wp_pre_kses_less_than and its callback,
wp_strip_all_tags, sanitize_text_field, sanitize_textarea_field,
_sanitize_text_fields, sanitize_hex_color and sanitize_hex_color_no_hash.
String signatures and return casts bound the public profile. UTF-8 validation
and existing entity escaping replace the wider charset/bootstrap graph; regex
calls reuse the guarded KSES adapters and a typed less-than callback. The
non-scalar strip-tags warning/translation path is an explicit exception outside
the string profile. Original tag stripping, whitespace/percent handling, color
patterns and filter argument ordering remain. The original-PHP oracle uses
unmodified formatting/charset functions with the same adapter input boundaries.

The JSON sanitizer adapter sends each operation directly to an encoder with a
`mixed` parameter. A shared switch-result local loses null color values on the
pinned SDK. The separate `sanitize-null-result` diagnostic preserves that
failure and its direct-core/mixed-encoder controls. Original color functions
retain their nullable string return types; no invalid/empty result substitution
is performed in the adapter.

The error profile retains the complete original WP_Error class and selects
is_wp_error from load.php (alongside the response profile's absint helper). Constructor maps use uniform mixed storage so integer
code 0 followed by a negative key does not trigger a list-to-map storage change.
Local bucket updates preserve native ownership; method boundaries and extracted
message/history arrays have explicit types. Code emptiness explicitly includes
string zero and nonempty-data handling excludes it, preserving original PHP
behavior on the pinned SDK. The original isset history rules, message order,
protected copy method and action calls remain. The JSON adapter bounds operations,
state and events without replacing the original copying algorithms.

The response profile retains the full WP_HTTP_Response and WP_REST_Response
classes with their inheritance. Header/links use uniform mixed maps and local
bucket updates; typed casts make link sets, callbacks, metadata and error records
explicit. The original CURIE filter, header concatenation, link filtering/key
policy and error algorithms remain. Integer absint is selected from the existing
load module within the adapter's bounded status domain. rest_ensure_response
and rest_convert_error_to_response enter the selected rest-api module; the original
status reducer, message formatting and reverse-conversion behavior are preserved.
The adapter validates error-body shape before calling the original as_error API.

The bounded request class preserves its original header normalization, active
parameter order, null/presence rules, lazy JSON parsing and ArrayAccess methods.
Uniform mixed maps and local updates adapt source/header storage. Static header
canonicalization is called with static:: because the pinned checker rejects
$this-> syntax for static methods. Content-type compact/trim mapping uses explicit
values and a typed closure. Permalink policy comes from the adapter; original PHP
uses the normal pre-option filter. Native JSON errors retain the verified 400
constant and fixed English label. The pure media-type regex loses only its
memoization to avoid the pinned static-empty-array initialization failure.
Automatic schema sanitization and from_url become explicit exceptions; the
original URL-encoded parse_str branch is an explicit boundary after its normal
guards. These unsupported original bodies remain visible in the patch diff.

The request JSON error-message bridge removes only the SDK's intentional
near-location diagnostic suffix before placing it into the original WordPress
error payload. Decoder codes and other error fields remain. The original PHP
oracle uses normal option filters, a fixed English gettext fixture and the
original WP_Http constant definition without executing a transport.

The original has_valid_params and sanitize_params bodies retain required checks,
callback order, exact-false/error handling, partial successful writes and aggregated
messages/details. Callback arguments resolve through WordPressHookCallbacks;
error narrowing, uniform detail maps and local source updates adapt native storage.
The fixed English fixture supplies the original validation messages. The selected
REST module also includes rest_is_integer, rest_is_boolean and rest_sanitize_boolean.
The integer predicate returns false before regex matching for NUL strings, preserving
PHP rejection despite the pinned matcher limitation. The JSON adapter validates
at most 64 argument rules and permits only source-defined callback names; general
schema keywords and arbitrary discovery remain explicit boundaries. Callback rules,
wrappers and the bounded trace are application fixtures, separate from core logic.

Five selected scalar validators retain original type checks, numeric limits,
exclusive-bound combinations, floating multiple checks and UTF-8 string lengths.
Typed numeric/array casts adapt their signatures. Fixed English translation and
number formatting replace the locale bootstrap; the PHP oracle supplies the same
locale and UTF-8 encoding through original helpers. The original pattern branch
is an explicit exception. Application preflight accepts only the documented scalar
keywords and bounds, and a finite dispatcher selects the original validator.
This does not import the general schema dispatcher or automatic schema sanitizer.
Direct reference cases additionally compare the selected helpers with the original
full schema dispatcher. Preserve original error order and unchanged input values.
