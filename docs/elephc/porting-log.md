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

## Follow-up direction

A broader CMS experiment should address the UTF-8 compatibility loader's
control flow and repeat the `SHORTINIT` diagnostic to discover the next real
blocker. Database-backed bootstrap, plugin discovery, themes, KSES and arbitrary
block rendering require separate verified profiles. Current subsystem coverage
does not establish their compatibility.
