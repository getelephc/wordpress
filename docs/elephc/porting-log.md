# WordPress native core experiment

## Baseline, 2026-11-13

- WordPress: 7.1.4, downloaded from the official versioned release archive.
- Archive SHA-256: `881e53a4681ff2cd911651b11cccdede5ae477ddf0765af5d6a1a8b219a1d835`.
- Compiler: installed Elephc 0.27.8 Linux x86_64 SDK.
- Runtime: native Linux host tools, PHP 8.5.12 reference interpreter.
- PHP compilation profile: `--php-version 8.4`; HTTP adds `--web`.
- Native dependency: PCRE2 10.47, committed manifest and lockfile.
- Database: none. No WordPress configuration, installation, migration or
  remote database is involved.

## Native build and launch

The project uses Elephc directly on the host, as explicitly requested by the
user. The build script resolves `ELEPHC_BIN` or `elephc` on `PATH`, prepares
the verified sources, installs locked PCRE2 and compiles the CLI and HTTP
entry points with the PHP 8.4 compiler profile.

```bash
./scripts/build-elephc.sh
./scripts/serve-elephc.sh
```

The server runs in the foreground on `http://127.0.0.1:9000/` with one worker
and a 64 KiB body limit. The executable can also be launched directly with
custom `--listen` options. PHP is used only by the reference checks, selected
through `PHP_BIN` or `php` on `PATH`.

The complete compiler SDK, C toolchain, Python 3.12 or newer and `patch` are
native build prerequisites. The optional machine-wide build queue is reused
when installed. Source-only reproduction copies `.elephc-version` along with
the maintained source files so elvm selects the pinned compiler in the export.

## Scope

The first port exercises a complete, useful WordPress subsystem rather than a
captured response. The Gutenberg block serialization parser consumes runtime
input and returns the same five-field block structures as WordPress.

```text
raw document
  -> original WordPress regex with adapted capture transport
  -> WP_Block_Parser::parse / proceed / next_token
  -> WP_Block_Parser_Frame stack
  -> WP_Block_Parser_Block objects and array conversion
  -> JSON response or CLI output
```

The HTTP router, health response and demonstration HTML are explicit adapters.
They do not invoke WordPress routing, themes, block rendering, plugin discovery,
the REST API or `WP_Query`. Later extensions below add core hooks and content
formatting without bootstrapping the CMS.

## Original-source diagnostics

`elephc/probes/full-wordpress.php` requires the original `wp-blog-header.php`.
Its first compilation error is in `wp-load.php:50`: the include resolver does
not consider the conditionally defined `ABSPATH` constant known.

`elephc/probes/shortinit.php` predefines `ABSPATH` and `SHORTINIT`, then requires
the original `wp-settings.php`. This advances to `wp-includes/compat-utf8.php`,
where `goto` at lines 140, 167, 518, 527 and 534, and the label at line 539,
are rejected. Additional parser recovery diagnostics accompany them.
This probe is compile-only and never executes WordPress startup.

`elephc/probes/blocks.php` isolates the three unmodified parser classes. It
reports 11 front-end diagnostics: a constructor used in `array_push` on a
property receiver, object accesses after untyped stack reads, and the
five-argument `preg_match` call.

After addressing those diagnostics, code generation exposed a separate
untyped-property storage error in `WP_Block_Parser_Block::__construct`.
Explicit property and constructor types resolved that error.

## Compatibility changes

Three file-level patches are applied only to generated copies:

1. Declare parser document, offset and collection storage, block field types,
   and the frame's concrete block/offset fields.
2. Store a newly constructed frame in a local before appending it to the
   stack. Narrow values read from the stack with `instanceof` guards before
   accessing their properties.
3. Match the remaining document using Elephc's supported three-argument
   `preg_match` and numbered captures. Recover the absolute byte position with
   `offset + strpos(remaining_document, full_match)`.

The upstream regex has no start anchor or lookbehind, so searching a suffix
preserves its match context for these delimiters. Optional slash/namespace/
attribute captures are recognized by their nonempty strings. All parser state
machine branches remain present, including best-effort malformed-input recovery.

The patched classes also run on ordinary PHP. The differential check verifies
that the adaptation itself agrees with upstream before comparing native output.
Explicit types narrow the public API to the string documents and parser-owned
constructor calls used by this profile. Repeated substring copies may change
performance, so this experiment makes no speed claim.

## Verification

The maintained corpus contains 22 documents. The checker compares each with
unmodified PHP, patched PHP and the native CLI. It then sends every document
twice through one native HTTP worker, followed by route, header, HEAD, error
and body-limit checks, for 62 counted HTTP checks.

Cases include empty input, freeform HTML, paragraphs, attributes with scalar and
structured values, custom namespaces, void blocks, multiple siblings, Unicode
byte offsets, deep nesting, missing/mismatched closers and malformed JSON.
The PHP reference reuses a parser object across the corpus; the HTTP round also
checks request isolation and that `Allow` headers do not leak.

The source-only check excludes downloaded sources, generated core files and
executables, then downloads, verifies, patches, compiles and repeats the checks.
It reuses the installed compiler SDK and native package cache. Its temporary
directory is removed on success or failure.

Build logs and diagnostic output are under ignored `elephc/build/`.
The block-parser-only baseline produced 1,757,528-byte CLI and
3,894,208-byte HTTP executables on the native host.

## Actions and filters extension, 2026-11-15

The compiled source inventory now contains six core files and five patches.
The added files are `plugin.php`, `class-wp-hook.php` and the unchanged
`class-wp-filter-sentinel.php`. There are three executables: the block parser
CLI, the hooks CLI and the combined HTTP server.

The original hook API first failed to parse the comma-separated static
declaration in `wp_register_plugin_realpath`. That filesystem helper is outside
the hooks profile. Nine deprecated-hook, plugin-filesystem and plugin-lifecycle
functions now fail explicitly rather than pulling unrelated subsystems into
the compiled graph. Their original bodies remain in the patch diff.

`plugin.php` initializes fresh hook tables. Explicit signatures preserve
mixed filter values, nullable hook-name defaults and argument arrays.
Array construction/merge replaces an unsupported mixed-value `array_unshift`
path. Table removal uses typed local copies before publishing the updated
table, and the original callback identity algorithm uses a typed object-ID
adapter after its existing object checks.

`WP_Hook` retains its dispatch and active-priority resorting algorithms.
Elephc cannot maintain PHP's hidden array pointer on an object property or
nested array element, so the profile stores each active iteration's position
explicitly. The same logical current/next/previous/end/reset operations drive
priority insertion, removal and recursive dispatch. `foreach` over `WP_Hook`
also uses an explicit cursor with compatible Iterator/ArrayAccess signatures.
The `next()` adapter returns `void`, following the interface contract.
Construction and full reset briefly insert and remove a string key to select
associative callback storage before sparse integer priorities are inserted.
Without this adaptation the native array exposed empty slots between priorities
through `array_keys`, which the Iterator comparison caught.

Direct string callback dispatch to application functions in the included demo
graph failed at runtime on the pinned SDK, while closure and method descriptors
worked. Application function names are therefore registered explicitly with
`WordPressHookCallbacks::register(name, function_name(...))`. Resolution stores
the descriptor in the callback bucket after the original name has supplied
the WordPress identity key. Duplicate registration, priority matching and
removal continue to use that original key. This is a finite application
manifest, not general plugin discovery.

The hook corpus contains 31 scenarios, compared independently with unmodified
PHP, patched PHP and the native hooks CLI. It includes stable equal-priority
ordering, zero/negative priorities, accepted arguments, removal, recursion,
live insertion/removal, `all`, action context/counters, mixed values,
closure captures, callable forms, Iterator access and sentinel identity.
Together with the existing 22 block fixtures, repeated request checks total
132 HTTP checks through one native worker.

`GET /hooks` runs the default priority scenario, with other cases selectable
by the `case` query parameter. `POST /filter` sends runtime request text through
real WordPress filters. The default listener remains `127.0.0.1:9000`.

Ordinary argument-array forwarding is tested, including the `_ref_array` APIs.
Reference-bound argument arrays, preinitialized hook globals, arbitrary plugin
loading and direct mutation of the public callback table are not claimed.
Closure captures by reference are covered independently.

The expanded native host executables are 1,761,624 bytes for block parsing,
3,799,656 bytes for hooks and 21,461,120 bytes for the combined HTTP server.

## Shortcodes and content extension, 2026-11-16

The source graph adds the original `shortcodes.php` and seven selected functions
from `formatting.php`: `wpautop`, `wp_html_split`, `get_html_split_regex`,
`wp_replace_in_html_tags`, `_autop_newline_preservation_helper`,
`shortcode_unautop` and `wp_spaces_regexp`. Eight original files and seven
patches now define the graph. The formatting patch applies to the full original
file, whose result has a separate hash; ordered byte ranges and per-function
hashes select the generated module, which also has its own checksum. Build-time
selection uses Python and does not require PHP.

The content pipeline registers `wpautop` and `shortcode_unautop` at priority 10,
then a wrapper around `do_shortcode(content, true)` at priority 11. Demo callbacks
supply greeting, box, upper-case, counter and context behavior. Registration,
attribute parsing, regex matching, escaped tags, nesting and filter dispatch
remain core WordPress code. This is an explicit pipeline, not the complete
WordPress default `the_content` stack; it does not invoke `do_blocks` or themes.

`ignore_html=true` prevents shortcode expansion inside HTML attributes, comments
and CDATA. The alternate attribute-expansion branch would require KSES; this
profile raises an explicit exception when that path is reached. It does not
pretend to sanitize HTML. Invalid registrations and callbacks raise exceptions
without importing translations or WordPress's notice subsystem.

Compatibility changes make regex results, callback descriptors and the HTML
split regex cache concrete. Small adapters cover the pinned SDK's missing
`preg_quote` and `stripcslashes`, array-search string replacement, string-valued
replacement maps and value-based intersection of tag lists. The original
shortcode and formatting regexes remain visible and unchanged. Replacement
backreferences use literal single-quoted strings joined with newlines because
the pinned compiler incorrectly interpolated numeric `$1` tokens in the original
double-quoted replacement strings. Content-filter wrappers expose concrete
application descriptors while retaining the core callback identity names.

The renderer is exposed by `elephc/runtime/render MODE INPUT` and by `GET` or
`POST /render`, defaulting to the pipeline. CLI output is exact rendered text;
diagnostic modes return JSON. HTTP accepts a raw body and selects the mode with
`?mode=...`. The listener remains `127.0.0.1:9000`, without a database.

The empty-input cases exposed a native `preg_replace` crash. A symbolized
focused run traced it through `__rt_preg_replace_ret_linux_x86_64`,
`__rt_mixed_from_value_string` and `__rt_str_persist_copy`, with an invalid
string length. `shortcode_parse_atts` returns its normal empty array before
normalizing an empty input, and `shortcode_unautop` returns empty text before
its final replacement. The latter guard stays after regex construction so
`wp_spaces_regexp` filter initialization is retained.

Verification now compares 22 block fixtures, 31 hook scenarios and 70 rendering
fixtures against unmodified PHP, patched PHP and the native CLIs. It then
performs 282 HTTP checks through one worker, including repeated requests,
response types, HEAD, method errors and request-body limits. The complete
source-only reproduction also passed with a fresh WordPress download.
Native CLI stderr and server logs contain no runtime warnings or errors.

The native host executables are 1,761,624 bytes for parsing, 3,799,656 bytes
for hooks, 4,391,784 bytes for rendering and 22,505,088 bytes for HTTP. These
sizes are observations of this SDK and source graph, not performance results.

## Gutenberg serialization extension, 2026-11-16

The compiled graph adds five selected functions from `wp-includes/blocks.php`:
`serialize_block_attributes`, `strip_core_block_namespace`,
`get_comment_delimited_block_content`, `serialize_block` and `serialize_blocks`.
The inventory is now nine original files with eight patches and five native
executables. The same full-file, selected-range and final-module checks used for
formatting also verify the block module. Preparation still uses Python only.

The core algorithms retain recursive child ordering, null placeholders in
`innerContent`, comment construction, namespace removal and comment-safe JSON
attribute escaping. Types make array reads and recursive calls concrete;
`serialize_blocks` loops over the list explicitly rather than dispatching a
string callback through generic `array_map`. Core's `empty(block_content)` is
expressed as comparisons with both the empty string and `"0"`, preserving PHP
behavior where the pinned SDK's native `empty` only recognized string length.

The `wp_json_encode` call is replaced by an explicit JSON encoder bridge for
encodable UTF-8 attributes. Failed encoding raises an exception; charset repair
and the related UTF-8 bootstrap graph remain outside this profile. The original
PHP oracle loads unmodified `functions.php`, its `option.php` dependency and
`blocks.php`, without executing database or CMS bootstrap operations. Their
source checksums are pinned alongside the compilation dependencies.

`elephc/runtime/serialize` accepts a mode and optional input argument. Otherwise
it reads stdin using `stream_get_contents(STDIN)`, with diagnostics through
`fwrite(STDERR, ...)` and exit status 2. The default `blocks` mode serializes a
JSON list such as the result of `/parse`. `roundtrip` accepts a raw document,
and `attributes` exposes the core's comment-safe JSON serialization.
`POST /serialize` exposes the same modes and returns exact text with a
`text/plain; charset=utf-8` content type. Input errors return HTTP 400.
The host listener remains `127.0.0.1:9000`.

The adapter validates the five block fields, list shapes, chunk types and the
one-to-one correspondence of null markers with children, with a maximum of
64 block levels. Non-array attributes retain core normalization to an empty
array. To edit saved HTML, update `innerContent`; `innerHTML` is unused parser
metadata during serialization. Nested JSON objects are decoded to associative
arrays consistently with the block parser.

WordPress serialization is a normalization step. In the original core, content
`"0"` becomes a void block and malformed nested documents can change structure
across successive parse/serialize passes. The checker compares serialized text,
reparsed trees and the next serialization independently against original PHP.
It does not assume preservation of source bytes or idempotence.

Verification passed for 58 serialization fixtures and 22 invalid-input cases,
including both sides of the 64-level block limit. Every serialized document,
reparsed tree and subsequent serialization agrees with original PHP. The full
corpus now covers 22 parser documents, 31 hook scenarios, 70 rendering fixtures
and 58 serialization fixtures, with 566 HTTP checks through a repeated worker.
CLI stdin and stderr are tested. A fresh source-only export rebuilt all five
executables and passed the same checks; its temporary tree was removed.

The serializer executable is 1,857,624 bytes and the combined HTTP executable
is 22,638,976 bytes on the native host. Other profile sizes are unchanged from
the previous extension. These sizes are observations, not speed claims.

## KSES attribute extension, 2026-11-16

This profile selects 21 functions from `kses.php`, plus the original global
declaration and HTML entity-name table. `esc_attr` is the eighth selected
formatting function. Ten original files and nine patches now define the native
graph, with a sixth executable for KSES primitives. The original entity-table
initializer is wrapped in a typed getter and restored by setup for every
operation: the first HTTP request succeeds without this adapter, but later
requests encounter a cleared ordinary global on the pinned worker.
The manifest records source
segments as well as function ranges, checking the full patched file, every
selected fragment and the final generated module.

WordPress 7.1's whole-document `wp_kses` graph calls `wp_kses_hair`, which uses
`WP_HTML_Tag_Processor`. This extension instead ports the distinct
`wp_kses_attr_parse`/`wp_kses_hair_parse` path and `wp_kses_one_attr`, with core
protocol and entity normalization. It does not replace modern HTML parsing with
an older regex implementation. The style branch requires `WP_HTML_Decoder`
and `safecss_filter_attr`; CSS remains an explicit unsupported branch.

The application supplies a fixed policy for `a`, `img`, `div`, `span` and `p`,
common/data attributes, selected anchor/image attributes, valueless download
and image widths from 1 to 4096. Protocols are `http`, `https`, `mailto`, `ftp`
and `feed`. The same policy configures the original PHP oracle. It is decoded
into uniform mixed-value maps because the pinned SDK cannot merge the original
boolean-valued policy map with heterogeneous attribute limits.

The UTF-8 escape adapter retains original KSES entity normalization and HTML
4.01 quote behavior. The pinned `htmlspecialchars` rejects its fourth
`double_encode` argument. Within the fixed core entity table, the adapter
retains recognized entities, re-encodes `apos`, and escapes quote/angle syntax
characters. Core attribute-escape, allowed-HTML and URI-attribute hooks remain
in the selected graph. The oracle uses unmodified core formatting, compatibility
and UTF-8 helpers; its normal option filter supplies blog_charset=UTF-8 before
any database path is reached.

The shortcode HTML attribute branch is restored. Regex callbacks use closure
adapters; a per-call object counts quoted-attribute matches without depending on
the unsupported five-argument regex callback form. Explicit `html-shortcodes`
and `html-pipeline` modes enable this branch; default modes keep ignore_html=true.
As in original WordPress, quoted expansions are passed through one-attribute
KSES, unquoted/name-position expansions retain the unfiltered-HTML behavior,
and rejected expansions may keep the original shortcode text. These modes do
not sanitize an HTML document.

`elephc/runtime/kses` and POST `/kses` expose attribute parsing, one-attribute
filtering, protocol cleanup, entity normalization, numeric byte decoding and
attribute escaping. Parser modes return JSON and decode mode returns raw bytes;
other modes return text. UTF-8 is required, and NUL is excluded from HTML
attribute/shortcode inputs. Protocol/entity helpers have explicit binary-slice
adapters and empty-subject guards for the pinned native regex runtime.
Whole-document HTML filtering, CSS, XML entity normalization and charset
recovery remain outside this profile, with explicit API errors.

Verification passed for all 87 KSES fixtures and 19 additional HTML rendering
fixtures against original PHP, patched PHP and native CLI output. The complete
corpus covers 22 parser documents, 31 hook scenarios, 89 rendering fixtures and
58 serialization fixtures, with 22 invalid serialization inputs and 13 new
KSES/HTML boundary inputs (12 through CLI, all 13 through HTTP). All 812 HTTP
checks pass across repeated requests in one worker. A fresh source-only export
rebuilt all six executables and passed the same checks; its temporary tree was
removed. Native runtime output contains no PHP warnings or fatal errors.

The KSES executable is 3,844,712 bytes, rendering is 5,053,032 bytes and the
combined HTTP executable is 23,300,480 bytes on the native host. The parser,
serializer and hooks sizes are unchanged. These are build observations, not
performance claims.

## Object cache extension, 2026-11-16

The cache profile adds the original `WP_Object_Cache` class, its 21 `wp_cache_*`
wrappers and the original `wp_suspend_cache_addition` function selected from
`functions.php`. The native graph now has 13 original files, 12 compatibility
patches and seven executables. The original class and API remain visible in
full-file patches; the suspension helper has full-source and selected-function
provenance hashes.

The adapter supplies an isolated site context to the constructor. Original PHP
receives the identical context through `is_multisite` and `get_current_blog_id`.
The checked algorithms retain add/replace existence tests, null-versus-missing
handling, the by-reference found flag, hit/miss counters, bulk results, numeric
increments/decrements, global groups and blog prefixes. This does not bootstrap
WordPress site management or load a persistent cache drop-in.

Cache buckets and bulk results use uniform mixed maps. The pinned SDK otherwise
fails its storage contract when setting numeric key 0 and then -2 in the same
bucket, after a successful read of key "0". Decoding an empty JSON object supplies
the uniform map contract; updates copy a local bucket before assigning it back
to the object. Explicit checks for empty string and "0" preserve PHP default
group behavior. `flush_group` retains core's literal group handling.

The application accepts a JSON object with at most 256 operations and optional
site configuration. Each invocation initializes a fresh core cache and resets
addition suspension. JSON objects become associative arrays. Key/group NUL bytes,
invalid keys, non-encodable numeric values and invalid operation fields raise
explicit input errors. Object cloning, magic property access, HTML statistics
and deprecated reset are excluded with explicit native boundaries. The original
core's unused expire/force arguments and no-op close/non-persistent group methods
are preserved. Within a sequence the state is shared; the next HTTP request starts
fresh. The API returns operation results and the actual core hit/miss counters.

All 56 cache scenarios agree with unmodified PHP, patched PHP and native CLI,
including stored false/null, mixed numeric keys, float/exponent counters, integer
overflow, suspension, bulk operations, global groups and simulated blog switching.
All 38 invalid inputs return explicit errors. The full suite retains all previous
corpora and passes 1,006 HTTP checks, including adjacent requests that verify cache
and suspension isolation. A source-only export rebuilt all seven executables and
passed the same checks, then removed its temporary tree. The build and full
verification ran through the machine-wide queue.

The native cache executable is 1,423,232 bytes and the combined server is
24,094,336 bytes. Existing standalone profiles are unchanged. These measurements
are build observations rather than performance claims.

## List utility extension, 2026-11-16

The profile adds the original `WP_List_Util` class and four original wrappers:
`wp_list_filter`, `wp_filter_object_list`, `wp_list_pluck` and `wp_list_sort`.
The selected `functions.php` module now contains those wrappers and the existing
cache suspension helper. A shared bootstrap loads the list class dependency for
both the cache and list entry points. The native graph now has 14 original files,
13 patches and eight executables. Source/full-module/range hashes are retained.

The class keeps its filter matching, field/index extraction, ordering argument
normalization and comparator logic for array records. Result maps use uniform
mixed storage, and the array constructor/properties/call boundaries have explicit
types. PHP object-record paths and invalid-row translation notices are outside
the JSON profile and fail explicitly. The application validates selected pluck
fields before core calls, avoiding undefined-field diagnostics.

The pinned SDK checks the typed source but rejects native `uasort` code generation
for PHP type Mixed in `WP_List_Util::sort` (instruction 204). A stable merge-sort
bridge uses the unchanged private core comparator through a first-class method
descriptor. It preserves stable ties and the requested key policy. This is an
adaptation of the sorting backend, not a performance-equivalence claim.

The CLI and POST `/lists` accept an object with up to 256 array records. Operations
filter, filter-pluck, pluck and sort call the original wrappers; chain mode runs
up to 16 operations through one original utility instance. Responses include
original input, final output and intermediate chain results. Comparisons accept
scalars/null and exclude NUL text; field/index keys are integers or strings.
Nested arrays may be extracted but cannot be compared. PHP object records and
database/query execution remain outside the profile. Core array filtering uses
array_key_exists, so present null fields can match whereas absent fields cannot;
this differs from the original class's isset-based PHP object branch.

All 56 scenarios agree with unmodified PHP, patched PHP and native CLI, including
AND/OR/NOT and loose scalar matching, false/null, numeric keys, duplicate/missing
indices, numeric strings, stable ties, multiple fields, key preservation and
unchanged original/intermediate arrays in chains. All 32 invalid inputs return
explicit errors. The complete check retains every previous corpus and passes
1,188 HTTP checks. A fresh source-only export rebuilt all eight executables and
passed the same suite, then removed its temporary tree. Native CLI and worker
runtime output has no PHP warnings or fatal errors.

The list executable is 584,576 bytes, cache is 1,426,304 bytes and the combined
server is 24,521,088 bytes. The cache now shares the selected functions bootstrap.
These are build observations, without speed claims.

## URL string extension, 2026-11-16

Ten original helpers extend the string profile: esc_url, esc_url_raw,
sanitize_url, _deep_replace and wp_make_link_relative from formatting.php;
wp_parse_url and two component helpers from http.php; build_query and
_http_build_query from functions.php. The native graph now has 15 original
files, 14 patches and nine executables. Formatting selects 13 functions,
functions.php selects seven, and the new HTTP module selects only the three
parser helpers. Complete patched files, source ranges and generated hashes
remain verified; HTTP request transports are excluded.

The original escape pipeline preserves the existing fixed protocol policy,
explicit protocol overrides/order, entity normalization, display/raw contexts,
relative URLs, bracket escaping and clean_url hooks. Scheme selection uses an
explicit first-value loop. The deep replacement loop detects convergence in
place of the unsupported str_replace count argument, retaining repeated CR/LF
percent-sequence removal. Existing array-search replacement adapters are reused.
Whitespace-only and relative empty inputs bypass the pinned empty-regex hazard.

The parser retains original placeholder handling for relative/schemeless URLs
and distinguishes false failures, absent null components and empty strings.
The native parse_url/urlencode primitives were first checked against PHP on
credentials, IPv6, invalid ports, query colons and empty queries. Query builders
retain recursive bracket keys, boolean/null conversion and empty child segments.
Unencoded build_query and encoded _http_build_query remain distinct. Separators
are explicit, and PHP object data/INI separator lookup are excluded. The JSON
adapter limits queries to 256 fields per level and 16 nested levels below root.

All URL text, protocol names, query keys/string values and separators require
UTF-8 without NUL. An initial protocol validator exposed a pinned matcher gap:
the maintained regex-nul-subject probe reports length 12 in both PHP and native,
but anchored ASCII-letter matching returns 0 in PHP and 1 natively for a subject
containing NUL. The URL adapter checks NUL before invoking that regex. The
standalone diagnostic is kept outside verified application builds and was
reproduced separately with locked PCRE2 10.47 through the build queue.

All 103 URL cases match unmodified PHP, patched PHP and native CLI; all 28
invalid inputs return explicit errors. The complete suite retains every prior
corpus and passes 1,456 repeated-worker HTTP checks. A source-only export rebuilt
all nine executables and passed the same suite, then removed its temporary tree.
CLI stdin, protocol filtering, hook isolation, recursion limits, routing, MIME
types and body limits are covered. Runtime output has no PHP warnings or fatal
errors. No URL operation performs network requests, redirects or database access.

The URL executable is 4,469,864 bytes and the combined server is 24,752,000 bytes.
These are native build observations rather than speed claims.

## Text and identifier sanitization extension, 2026-11-16

Eleven additional original formatting functions extend the profile:
`sanitize_text_field`, `sanitize_textarea_field`, `_sanitize_text_fields`,
`wp_strip_all_tags`, `wp_pre_kses_less_than` and its callback, `esc_html`,
`sanitize_key`, `sanitize_html_class`, `sanitize_hex_color` and
`sanitize_hex_color_no_hash`. Formatting now selects 24 functions. The graph
still uses 15 original files and 14 patches; the new `sanitize` CLI brings the
native executables to ten. Complete source, selected-range and module hashes
remain verified. Original downloaded sources are unchanged.

The original core keeps tag/script/style removal, whitespace normalization,
repeated percent removal, incomplete less-than handling, entity normalization,
class fallback and color patterns. String arguments, explicit result types,
existing guarded regex adapters and a typed less-than callback bound the graph.
UTF-8 validation and fixed entity escaping replace the broader charset/bootstrap
dependencies. Non-scalar warning/translation paths are excluded by the string
input profile. The original-PHP oracle still loads unmodified formatting and
charset helpers, using the existing UTF-8 option filter without a database.

The CLI and POST `/sanitize` accept a JSON object with required `text`.
Operations are field (default), textarea, strip-tags, less-than, html, key,
class, hex and hex-no-hash. Optional `remove_breaks`, `fallback` and diagnostic
`filtered` fields expose the relevant core behavior. Diagnostic filters are
cleared for every operation. Recursive class fallback retains its original
filter context; colors preserve empty-string versus null results. All text
must be UTF-8 without NUL. Non-string coercion, charset recovery, full HTML KSES,
CSS and template-context validation remain outside this profile.

Query mutation was considered first, but the pinned SDK reports
`Undefined function: parse_str` in both checking and compilation of the separate
`query-parse` probe. `wp_parse_str`, `add_query_arg`, `remove_query_arg` and the
string branch of `wp_parse_args` remain deferred. Compiler task `7d0aa07a`
requests retesting the primitive on current main; WordPress task `1792335e`
tracks resuming that dependency graph. The negative probe is excluded from
verified application builds.

The first native verification exposed a dispatcher result-storage difference:
an invalid color became an empty string after string/nullable results joined
in one switch local. The maintained `sanitize-null-result` probe compares the
old dispatcher with direct-core and explicit-mixed-encoder controls. PHP returns
null for all three; native loses null only through the old dispatcher. A simple
nullable-string return control matches PHP. Returning each operation directly
through an encoder with a `mixed` parameter preserves the result; original
color signatures stay nullable strings. Compiler task `57ac10b4` requests
retesting current main. The negative runtime diagnostic remains separate from
verified application builds.

All 123 sanitizer cases agree with unmodified PHP, patched PHP and native CLI,
including Unicode/whitespace, malformed HTML, script/style content, nested
percent sequences, entities, class recursion and filter arguments. All 17
invalid inputs return explicit errors. The complete suite retains every prior
corpus and passes 1,742 repeated-worker HTTP checks. Fresh source-only
reproduction rebuilt all ten executables and passed the same suite, then
removed its export. CLI stdin, UTF-8/NUL boundaries, request isolation, routing,
MIME types and body limits are covered. Runtime logs have no PHP warnings or
fatal errors.

The sanitizer executable is 4,109,416 bytes and the combined server is
24,917,120 bytes. These are build observations, without speed claims.

## Error object extension, 2026-11-16

The graph adds the complete original `WP_Error` class and selects the original
`is_wp_error` helper from `load.php`. It now uses 17 original files and 16
patches; formatting still selects 24 functions. The new `errors` CLI brings
native executables to eleven. The class retains message/code ordering, latest
data and chronological history, protected copying, merge/export and original
actions. Full-file, selected-function and generated hashes remain verified.
The broader loader/CMS and REST graphs are excluded.

Typed properties and method boundaries, uniform mixed maps and local bucket
updates adapt native storage without replacing core algorithms. The history
array is extracted with an explicit cast/coalesce to avoid reassigning a
never-inferred empty list. Code emptiness explicitly includes string zero and
nonempty-data handling excludes it. These preserve PHP behavior on the pinned
SDK. The native core probe agrees with PHP on numeric key 0 followed by -2,
null/false history, merge/export and error recognition.

The CLI and POST `/errors` accept request-local JSON operation sequences.
Operations create, add, add-data, remove, get-codes/get-code/get-messages,
get-message/get-data/get-all-data, has-errors, is-error, merge/export, snapshot
and check-value call the original core. Transfers copy `source` into `target`;
mutations return null. Responses include earlier operation results, final
snapshots and optional original action events. Hook callbacks observe the
actual object after insertion. Each invocation resets its object pool, hooks
and trace, and starts with an empty `main` object.

The profile allows 256 operations and 16 named objects. Codes are integer/string
PHP keys; names/messages and string codes exclude NUL. Data is associative JSON,
with 256 fields per level and a depth limit of 16; data keys exclude NUL and
string data values retain it. Aggregate state (messages, latest-data slots and
core history results) and hook events are bounded at 1,024 entries. State growth
is checked after each operation, including self-copying. Invalid input produces
CLI exit 2 or HTTP 400. PHP object data, subclasses, dynamic properties and
direct property mutation are outside the profile.

Original empty-code defaults and null history behavior are preserved. Explicit
add_data accepts false/zero/empty values; original isset guards omit previous or
current null from history. Orphan data without a message code is readable but
is not transferred. A zero code after a nonempty first code can copy that first
code's history again, as the original query/default rules require. Earlier
snapshots and source/target objects retain independent arrays after mutations.
The original-PHP oracle loads unmodified error, load and plugin sources without
a database.

All 62 scenarios agree with original PHP, patched PHP and native CLI, including
constructor/add empty-data rules, numeric and falsey codes, null history, orphan
data, self-copying, transfer independence, nested JSON/NUL data and real action
arguments. All 38 invalid inputs fail explicitly, covering type/encoding,
operation/object/field/depth limits and excessive state/event growth. The full
suite retains every previous corpus and passes 1,948 repeated-worker HTTP checks.
Fresh source-only reproduction rebuilds all eleven executables and repeats the
same suite, then removes its export. Runtime CLI and worker logs have no PHP
warnings or fatal errors.

The errors executable is 2,945,160 bytes and the combined server is 25,331,328
bytes. These are build observations, without speed claims.

## HTTP and REST response extension, 2026-11-16

The graph adds complete original WP_HTTP_Response and WP_REST_Response classes,
including their inheritance. The selected load module now contains absint and
is_wp_error. A new selected rest-api module contains rest_ensure_response and
rest_convert_error_to_response. The graph has 20 original files, 19 patches and
twelve executables. Full patched sources, ordered helper ranges and generated
module checksums remain verified; upstream files are unchanged.

Response properties/methods have explicit storage contracts. Header maps and
link buckets use uniform mixed storage with local updates. Link sets, record
messages, nullable metadata and callback captures have explicit casts. The
original header replacement/comma behavior, link filtering/key policy, CURIE
filter, status reducer, inheritance and conversion algorithms remain. absint
uses a bounded integer status contract. The native core probe agrees with PHP
on inherited methods, sparse links, header formatting, metadata, object identity
and error conversion. No new compiler failure was identified.

The responses CLI and POST `/responses` accept JSON with kind rest (default)
or http, optional constructor data/status/headers, and up to 256 operations.
Operations inspect or mutate data/status/headers, explicitly call jsonSerialize,
manage links and route/handler metadata, obtain CURIEs, detect errors and convert
them. ensure retains the actual REST object or upgrades the HTTP base object;
ensure-json wraps JSON, while ensure-error preserves WP_Error identity. Nested
error operations reuse the bounded error profile and an isolated main object.
convert-error records both the converted response and its original reverse
conversion. Earlier snapshots retain independent values.

The endpoint returns a JSON inspection envelope with transport status 200 for
valid inputs. The nested WordPress object retains its status and header data.
Status inputs and numeric error-status candidates are bounded to -9999..9999,
including original absint normalization and nonstandard object status values.
Header text excludes NUL/CR/LF; other metadata excludes NUL. JSON data retains
256-field/16-level bounds. Headers and individual bulk-link insertions allow
256 entries, accumulated links 1,024, and optional CURIE filter records 256.
Error-status array bodies passed to as_error require complete code/message/data
records, and converting an empty error is rejected before original undefined
index behavior. Invalid input returns CLI exit 2 or HTTP 400.

The original conversion chooses the last numeric status in the first code's
history; statuses under other codes do not replace it. Previous values appear
as additional_data, but as_error does not restore that history. Repeated
messages can duplicate the latest data in reverse conversion. Tests compare
both original stages separately. Link removal preserves numeric holes; null,
empty and string-zero href removal clears the relation. Header case and order,
reserved href attribute behavior and CURIE filter isolation are preserved.

All 74 response scenarios agree with original PHP, patched PHP and native CLI.
All 48 invalid inputs fail explicitly. The complete suite retains prior corpora
and passes 2,198 repeated-worker HTTP checks. Fresh source-only reproduction
rebuilds all twelve executables and repeats the suite, then removes its export.
Runtime CLI and HTTP logs contain no PHP warnings or fatal errors. The original
PHP oracle loads unmodified response classes, error/load/plugin sources,
list utility/functions and REST helpers without a database.

The response executable is 3,976,072 bytes and the combined server is
25,950,336 bytes. These are build observations, without speed claims. Full REST
routing, request validation/authentication, handler execution, embedded rendering
and the complete CMS remain outside this response-object profile.

## Bounded REST request extension, 2026-11-17

The graph adds WP_REST_Request with a bounded header/parameter/JSON profile.
The selected load module gains the original wp_is_json_media_type predicate.
There are 21 generated core files, 20 patches and thirteen executables. Full
source, helper-range and generated checksums remain verified. The original
WP_Http source is additionally checksum-verified for its BAD_REQUEST constant
used by the PHP oracle; its transport graph is not compiled into native code.

Typed properties, signatures and ArrayAccess methods provide storage contracts.
Source/header maps use uniform mixed storage and local bucket updates. Content
metadata uses an explicit four-field map and typed trim callback. Static header
canonicalization uses late-bound static:: syntax because the pinned checker
rejects the original instance call to a static method. The JSON media predicate
retains its exact original regex but elides its pure static memoization: the
pinned EIR backend rejects initializing Array(Never) in a Mixed static local.
Both limitations have maintained minimal diagnostics and compiler follow-ups.

The requests CLI and POST `/requests` accept explicit method/route/attributes,
headers, raw body and URL/GET/POST/FILES/default source maps, plus at most 256
operations. Methods and canonical headers, source-specific getters/setters,
merged/individual lookup, presence checks, real ArrayAccess operators and lazy
JSON parsing call the original core. The source-defined adapter subclass exposes
protected order, parse result and raw state for inspection. Budget checks and
peek read state without forcing parsing; snapshots run the normal getters.
File values remain metadata and no file operations are performed.

The option context is explicit and request-local. Native code reads the adapter's
permalink policy while original PHP uses the normal pre-option filter, avoiding
a database. The original class's English JSON error label is supplied by a fixed
locale fixture; native code retains that verified label and constant 400. The
SDK decoder adds an intentional near-location suffix to diagnostic text. A
small request bridge strips only that suffix from WordPress json_error_message,
retaining the decoder code and the complete original WP_Error data. A standalone
raw diagnostic preserves the SDK/PHP difference; it is not a claim that the
intentional diagnostic extension is itself a compiler defect.

The profile preserves header case/hyphen canonicalization, value lists, source
priority, numeric keys, default handling and null/presence distinctions.
get_param skips null while merged get_params can retain it; has_param and
ArrayAccess isset differ for null-only sources. set_param updates active
non-default sources and otherwise writes into the first ordered source.
Changing a content header does not invalidate an existing JSON parse attempt;
set_body resets the flags and JSON slot. Invalid JSON checks return the original
rest_invalid_json error details while getters can still return null/fallbacks.
The outer inspection call uses HTTP 200 for valid modeled operations.

Inputs have bounded JSON source/value arguments (256 fields, 16 levels), request
state has at most 256 fields per source, and header names/value lists allow 256
entries each. Body text is UTF-8 without NUL; JSON body objects/lists/null and
original string-zero empty-body behavior are covered. Other scalar JSON bodies
are excluded. Profile input errors return CLI exit 2 or HTTP 400. Form decoding
for non-POST raw bodies, schema validation/sanitization, URL discovery, locale
bootstrap, authentication and handler/route dispatch remain explicit boundaries.
Their original bodies remain visible in the patch; unsupported APIs throw.

All 70 scenarios agree with unmodified PHP, patched PHP and native CLI. All 45
input boundaries fail explicitly. The complete suite retains every prior corpus
and passes 2,434 repeated-worker HTTP checks. Fresh source-only reproduction
rebuilds thirteen executables and repeats the suite, then removes its export.
Native CLI and HTTP logs contain no PHP warnings or fatal errors. The request
executable is 4,265,832 bytes and the combined server is 26,806,656 bytes. These
are build observations without speed claims.

Compiler tasks `ad219f8e` and `bb834c02` request retesting
the two hard compilation failures on current main before retiring adapters.

## REST request callback lifecycle, 2026-11-17

The bounded request class now retains original has_valid_params and sanitize_params
for required fields and explicit callbacks. The selected REST module adds original
rest_is_integer, rest_is_boolean and rest_sanitize_boolean. There are still 21 core
files, 20 patches and thirteen executables. Complete patched sources, helper ranges,
fragments and generated modules retain verified hashes; upstream remains untouched.

Lifecycle methods use explicit types, uniform invalid-parameter/detail maps and
WP_Error narrowing. Named callback invocation resolves native descriptors through
the existing finite manifest. Successful sanitization updates fresh current source
buckets. The original required checks, source traversal, callback ordering, exact
false/error handling and aggregation algorithms remain. Fixed English validation
messages match the original-PHP locale fixture. The integer helper rejects NUL
strings before the unchanged regex to preserve PHP behavior on the pinned matcher.
Automatic rest_parse_request_arg dispatch remains an explicit general-schema boundary.

The existing requests CLI and POST `/requests` accept optional rules for at most
64 fields and route_validate. Rules replace attributes.args with required flags
and named source-defined validation/sanitization callbacks. Raw attributes permit
only the same bounded callback fields when a phase runs. validate and sanitize
operations explicitly call the original methods; callback-events exposes their
ordered key/value and actual request method/route arguments. Trace state resets
per invocation and is limited to 1,024 events. Wrappers, rule selection and trace
inspection are application fixtures, separate from core schema logic.

Required checks precede callbacks, optional null skips validation, and callback
null/zero results pass while exact false and WP_Error fail. The route callback
runs after successful parameter validation. Sanitization visits every active
source, including defaults, retains partial successful updates and aggregates
original error messages/details. Duplicate source failures and callback error
history retain original conversion behavior. Sanitization without argument rules
leaves JSON parsing lazy. Error inspection exposes both WP_Error and its converted
REST response, keeping the transport inspection envelope at HTTP 200.

The new corpus contains 102 original-PHP/profile/native cases and 36 invalid
inputs. It covers integer/boolean edge values, numeric parameter keys, malformed
JSON, required/null behavior, phase and source order, callback returns, partial
updates, route context, attribute replacement and the exact/exceeded event limit.
The complete suite retains all earlier cases and passes 2,710 HTTP checks through
one repeated worker. Fresh source-only reproduction rebuilds thirteen executables
and repeats the same checks, removing its export. No new compiler failure was
identified. Form decoding, general JSON Schema, URL discovery, authentication,
handler dispatch and the complete CMS remain outside the profile.

## Scalar REST schema validators, 2026-11-17

The selected REST module adds the original null, boolean, number, integer and
string validators. It now contains ten helpers. The graph retains 21 core files,
20 patches and thirteen executables; no upstream file is edited. Full patched
source, ordered helper ranges, fragments and final module hashes remain verified.

The methods retain original numeric type checks, minimum/maximum combinations,
exclusive flags, floating multiple tests and integer error precedence. Explicit
number casts provide native fmod contracts. String validation retains original
UTF-8 code-point length checks with an explicit encoding. Fixed English plural
selection and number formatting replace locale bootstrap in that helper; original
PHP uses the equivalent English fixture and original number_format_i18n with no
locale object. The unsupported pattern branch throws explicitly. The general
schema dispatcher and sanitizer are not compiled into this profile.

The requests CLI and POST `/requests` gain validate: schema with a schema rule.
The factory stores profile_schema alongside the source-defined callback; phase
preflight checks their pairing. A bounded dispatcher calls the selected original
validator. Numeric schemas accept minimum, maximum, exclusiveMinimum,
exclusiveMaximum and positive multipleOf; constraints are finite numbers within
+/- 10^12. String schemas accept integer minLength/maxLength from 0 to 1,000,000.
Null/boolean schemas accept only type. Unknown keywords, arrays/objects, unions,
enum, formats, patterns and combining schemas fail explicitly. Validation leaves
values unchanged and explicit sanitizers retain their previous behavior.

The scalar-check operation validates a supplied JSON value directly, including
null values that request callbacks skip. It preserves original helper errors,
which have no HTTP status metadata and therefore convert to status 500. The
request lifecycle aggregates callback failures under status 400. Both are
inspection results with transport status 200. Direct values retain existing
256-field/16-level JSON bounds and parameter names exclude NUL.

The 163 new cases cover original type/coercion rules, all exclusive-bound
combinations, decimal multipliers and floating remainder behavior, numeric keys,
UTF-8 accents/emoji/combining marks/NUL, singular/plural/grouped length messages,
request aggregation, unchanged values, source priority and trace isolation.
Reference checks additionally compare specialized helpers with the complete
original schema dispatcher for supported inputs. All 38 invalid inputs fail
explicitly. The complete native and fresh source-only suites retain every prior
case and pass 3,112 HTTP checks through one repeated worker. The export is removed
after rebuilding all thirteen executables. General JSON Schema, automatic schema
sanitization, database bootstrap and full dispatch remain outside the profile.

## Recursive array and object schema validation, 2026-11-17

The selected REST graph adds its original schema dispatcher, array/object
validators and four collection predicates/normalizers. The functions module
adds original wp_is_numeric_array, retaining array_all with a typed native
closure. There are now seventeen REST helpers and eight functions.php helpers,
with the same 21 core files, 20 patches and thirteen executables. Complete
patched sources, ordered function ranges, fragments and final modules retain
verified checksums; downloaded upstream sources are unchanged.

The core dispatcher now reaches all seven single-type validators. Explicit
exceptions replace unsupported combining/multi-type, enum and format paths.
Object and array validators preserve required/property/item traversal, count
checks, first-error propagation and nested parameter paths. Required-property
lists take precedence over legacy property-level boolean flags, including an
empty required list. Required properties recognize present null, then run the
child schema. Object additionalProperties supports booleans or another schema.
Array keys must be numeric; sparse and negative keys are accepted and a local
normalized copy supplies consecutive indices for validation errors. Inputs stay
unchanged. Object validation retains decoded arrays/maps and empty-string
objects under original WordPress semantics.

Explicit types, local array casts and the existing fixed English plural/number
bridge supply native contracts. PHP object/JsonSerializable paths and scalar
list coercion are explicit boundaries. Pattern properties and unique-item
checks remain outside the selected graph. The application fixture is renamed
schema-support.php and now validates recursive metadata with at most 64 schema
nodes including root and eight nested levels below root. Property maps and
required lists allow at most 64 entries; collection count limits are integers
from 0 to 256. Value data retains existing 256-field/16-level JSON limits.

The requests CLI and POST `/requests` gain schema-check, with scalar-check kept
as an alias. Existing schema callbacks use the same recursive guard and original
dispatcher. Scalar reference controls now independently compare that dispatcher
with the original specialized helpers. Previously unsupported array/object
type-only boundary cases are narrowed to the still-unsupported unique-item and
pattern-property keywords; their boundary count is retained.

The new corpus contains 74 original-PHP/profile/native scenarios and 39 invalid
inputs. It covers nested paths, null presence, required-list precedence,
additional-property rules, sparse numeric keys, item/property ordering, count
limits, unchanged values, HTTP isolation and exact/exceeded schema budgets.
Every prior corpus remains. Full native and fresh source-only reproduction
each pass 3,338 HTTP checks through one repeated worker; the temporary export
is removed after rebuilding all thirteen executables. Automatic schema
sanitization, scalar list coercion, enum/formats/patterns, combining/multi-type
schemas and full CMS dispatch remain outside the profile.

## Follow-up direction

A broader CMS experiment should address the UTF-8 compatibility loader's
control flow and repeat the `SHORTINIT` diagnostic to discover the next real
blocker. Database-backed bootstrap, plugin discovery, themes, whole-document KSES,
CSS and arbitrary block rendering require separate verified profiles. Current
subsystem coverage does not establish their compatibility.
