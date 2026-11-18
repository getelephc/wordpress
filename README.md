# WordPress on Elephc

A native AOT experiment with WordPress blocks, serialization, hooks, content, attribute KSES, object caching, list utilities, URLs, text sanitization, errors and HTTP/REST request/response objects.
WordPress **7.1.4**, Elephc **0.27.8**, PHP profile **8.4**, Linux x86_64.

The experiment builds native CLIs for blocks, serialization, hooks, content,
attributes, caching, lists, URLs, text sanitization, errors and HTTP/REST request/response
objects, plus an HTTP server. It uses 21 original core files with 20 compatibility
patches. Selected modules contain 24 formatting functions, five
block serializers, 21 KSES functions, eight helpers from `functions.php`, three
URL parsing helpers from `http.php`, three helpers from `load.php`, and nineteen REST
helpers from `rest-api.php`. Documents, JSON block trees, content, cache/list
records, URLs and request/error/response sequences are processed at runtime.
The HTTP adapter, callback manifest and demo callbacks belong to this experiment.

This is a core subsystem port. The complete CMS bootstrap, database, plugin
discovery, themes, block rendering and `wp-admin` are outside the current profile.

## PHP hybrid experiment

The [PHP hybrid experiment](experiments/php-hybrid/README.md) runs the complete
WordPress front controller with selected core algorithms compiled into a native
PHP extension. It uses an isolated SQLite fixture, a Zend adapter and Elephc
cdylib output. The [current measurements](experiments/php-hybrid/results.json)
compare ordinary PHP, PHP with the extension loaded and native routing enabled.
The native subsystem profile described below remains database-free.

## Requirements

Build and run directly on the host. The verified target is Linux x86_64.
You need:

- The complete Elephc 0.27.8 SDK, including its runtime archives, on `PATH`.
  You can install it directly or optionally use
  [elvm](https://github.com/illegalstudio/elvm) with `elvm install 0.27.8`;
  the repository's `.elephc-version` records the selected version.
- Bash, Python 3.12 or newer, `patch`, a C compiler/linker (`cc`) and GNU
  binutils (`as`, `ar`, `strip`). On Arch Linux these tools are provided by
  `base-devel`, `python` and `patch`; on Debian/Ubuntu use `build-essential`,
  `binutils`, `python3` and `patch` with a sufficiently recent Python.
- Internet access for the first WordPress download and PCRE2 installation.
- PHP 8.4 or newer with `mbstring` only for the differential reference tests. PHP is not
  required to compile or run the native executables.

## Build with Elephc

From the repository root:

```bash
elephc --version
./scripts/build-elephc.sh
```

The script verifies the downloaded sources and patches, installs the locked
PCRE2 dependency, and produces `elephc/runtime/parse`, `elephc/runtime/hooks`,
`elephc/runtime/render`, `elephc/runtime/serialize`, `elephc/runtime/kses`,
`elephc/runtime/cache`, `elephc/runtime/lists`, `elephc/runtime/urls`,
`elephc/runtime/sanitize`, `elephc/runtime/errors`, `elephc/runtime/responses`, `elephc/runtime/requests` and `elephc/runtime/server`.
It uses `elephc` from `PATH`; set
`ELEPHC_BIN=/absolute/path/to/elephc` to select another complete SDK.
The scripts run directly with the documented tools on `PATH` and require no
agent helpers or workstation-specific scheduler.

The equivalent direct Elephc commands are:

```bash
python3 scripts/prepare-elephc.py
(
  cd elephc/runtime
  elephc native install --locked
  elephc --php-version 8.4 --check server.php
  elephc --php-version 8.4 parse.php
  elephc --php-version 8.4 serialize.php
  elephc --php-version 8.4 hooks.php
  elephc --php-version 8.4 render.php
  elephc --php-version 8.4 kses.php
  elephc --php-version 8.4 cache.php
  elephc --php-version 8.4 lists.php
  elephc --php-version 8.4 urls.php
  elephc --php-version 8.4 sanitize.php
  elephc --php-version 8.4 errors.php
  elephc --php-version 8.4 responses.php
  elephc --php-version 8.4 requests.php
  elephc --php-version 8.4 --web server.php
)
```

The PHP profile is an explicit compiler setting. It does not require the
reference PHP interpreter to be the same version.

## Run locally

```bash
./scripts/serve-elephc.sh
```

Open <http://127.0.0.1:9000/>. The script starts the native server in the
foreground with one worker and a 64 KiB request body limit. Stop it with
`Ctrl+C`, and restart it after rebuilding.

You can also run the executable directly and choose a different port:

```bash
./elephc/runtime/server --listen 127.0.0.1:9001 --workers 1 --max-body-size 65536
```

The demo, parsed blocks, hooks, rendered content and health endpoints are `/`,
`/blocks`, `/hooks`, `/render` and `/health`. `/serialize`, `/kses`, `/cache`, `/lists`, `/urls`, `/sanitize`, `/errors`, `/responses` and `/requests`
accept POST only.
No database or background service manager is required.

Parse your own document:

```bash
curl -sS http://127.0.0.1:9000/parse \
  -H 'Content-Type: text/plain; charset=utf-8' \
  --data-binary '<!-- wp:paragraph {"align":"center"} --><p>Hello!</p><!-- /wp:paragraph -->'
```

The result is the WordPress block structure:

```json
[{"blockName":"core/paragraph","attrs":{"align":"center"},"innerBlocks":[],"innerHTML":"<p>Hello!</p>","innerContent":["<p>Hello!</p>"]}]
```

Use the native CLI directly:

```bash
./elephc/runtime/parse '<!-- wp:separator /-->'
```

## Gutenberg serialization

The native serializer uses five selected WordPress core functions:
`serialize_block_attributes`, `strip_core_block_namespace`,
`get_comment_delimited_block_content`, `serialize_block` and `serialize_blocks`.
It reconstructs a comment-delimited WordPress document from the block tree.

```bash
./elephc/runtime/parse '<!-- wp:paragraph {"align":"left"} --><p>Hello</p><!-- /wp:paragraph -->' > blocks.json
# Edit the JSON tree, for example changing attrs.align to "right".
./elephc/runtime/serialize < blocks.json
curl -sS http://127.0.0.1:9000/serialize \
  -H 'Content-Type: application/json' --data-binary @blocks.json
```

The CLI defaults to `blocks` mode. It reads its input from stdin, or from its
second argument when provided. `POST /serialize` also defaults to a JSON block
list and returns the exact serialized document as `text/plain; charset=utf-8`.
Both interfaces support `roundtrip` mode to parse and serialize a raw document:

```bash
./elephc/runtime/serialize roundtrip '<!-- wp:separator /-->'
curl -sS 'http://127.0.0.1:9000/serialize?mode=roundtrip' \
  --data-binary '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->'
./elephc/runtime/serialize attributes '{"label":"a--b <tag> & \"quote\""}'
```

`attributes` mode returns the core's JSON encoding with substitutions for
characters that could interfere with HTML comments. The JSON list input uses
the same five fields returned by `/parse`: `blockName`, `attrs`, `innerBlocks`,
`innerHTML` and `innerContent`. The serializer reads `innerContent`, replacing
each null marker with the corresponding child from `innerBlocks` in order.
When editing HTML, update `innerContent`; `innerHTML` is parser metadata and
is not used to reconstruct the document.

The adapter checks required fields, content types, list layout, matching child
markers and a maximum of 64 block levels. Invalid input or an unknown mode
returns HTTP `400`; the CLI writes a diagnostic to stderr and exits with `2`.
JSON attributes must be encodable UTF-8 data. The native profile uses an
explicit JSON encoder bridge and raises an error on encoding failure;
WordPress's charset-recovery path in `wp_json_encode` is outside the profile.
The PHP oracle loads the unmodified core encoder and serialization functions.

Serialization follows WordPress normalization. Comment whitespace and JSON
formatting can change, and empty blocks become self-closing. Core considers
the content string `"0"` empty. Malformed nested documents can also change
structure across successive parse/serialize passes. Tests compare each stage
with original WordPress rather than assuming lossless or idempotent output.

## Actions and filters

The hooks profile runs WordPress's `WP_Hook` and the core plugin API. It
supports registration, priorities, callback removal, nested hooks, recursive
filters, mutation during an active hook, action/filter counters and the
`all` hook. The native profile initializes fresh hook tables; it does not
load an installed plugin directory.

```bash
./elephc/runtime/hooks priority 'Hello WordPress'
./elephc/runtime/hooks recursive 'Hello WordPress'
curl -sS http://127.0.0.1:9000/hooks
curl -sS 'http://127.0.0.1:9000/hooks?case=closure-capture&value=Hello'
curl -sS http://127.0.0.1:9000/filter --data-binary 'Hello WordPress'
```

The default pipeline registers filters at priorities 5, 10 and 20, returning
`Hello WordPress:early:middle:late`. The full scenario inventory is in
[`tests/hooks-fixtures.json`](tests/hooks-fixtures.json).

Closures, captured closures, first-class callables, instance/static methods,
invokable objects and the tested builtin `strtoupper` run through native
callable descriptors. Register application function names explicitly in the
AOT callback manifest before using string callbacks:

```php
function my_title_filter(string $title): string {
    return $title . '!';
}

WordPressHookCallbacks::register('my_title_filter', my_title_filter(...));
add_filter('the_title', 'my_title_filter');
$title = apply_filters('the_title', 'Hello WordPress');
```

This registration belongs in the source entry point or its included application
code, followed by a rebuild. It makes the function descriptor available to
WordPress while retaining the original callback name as the registration and
removal key. Direct closure callbacks do not need this name registration.

The source API also exposes `apply_filters_ref_array` and
`do_action_ref_array`; forwarding ordinary argument arrays is tested. Passing
reference-bound argument arrays into callbacks is outside this profile.
Closures that capture their own variables by reference are tested separately.

Nine unrelated APIs fail explicitly: deprecated-hook wrappers,
`plugin_basename`, `wp_register_plugin_realpath`, `plugin_dir_path`,
`plugin_dir_url`, and activation/deactivation/uninstall hook registration.
Preinitialized hook globals before bootstrap, arbitrary plugin discovery and
direct mutation of the public callback table are outside the profile.
`WP_Hook` iteration through `foreach` is tested; its `next()` adapter follows
the `Iterator` interface's `void` return contract.

## Shortcodes and content rendering

The renderer runs the core shortcode engine and an explicit content-filter
pipeline: `wpautop` at priority 10, `shortcode_unautop` at priority 10, and
`do_shortcode` through a wrapper at priority 11. This is the selected pipeline,
not WordPress's complete default filter stack. It does not run `do_blocks`,
themes, whole-document KSES or installed plugins.

```bash
./elephc/runtime/render pipeline 'Welcome [greet name="Elephc"]'
./elephc/runtime/render shortcodes '[box]Hello [greet name="Ada"][/box]'
./elephc/runtime/render autop $'First paragraph\n\nSecond paragraph'
curl -sS http://127.0.0.1:9000/render
curl -sS http://127.0.0.1:9000/render --data-binary 'Welcome [greet name="Elephc"]'
```

The default pipeline example returns:

```html
<p>Welcome <span class="greeting">Hello Elephc</span></p>
```

The source-defined demo registers `greet`, `upper`, `box`, `counter`, `context`
and several literal-tag aliases for regex checks. The core performs tag
selection, regex matching, attribute parsing, escaping, nesting and hook
dispatch. The demo callbacks supply their output; `greet` escapes its name
with PHP's `htmlspecialchars`.

`GET /render` renders the demo. `POST /render` accepts a raw UTF-8 body. Select
a mode with `?mode=shortcodes`, `autop`, `autop-no-br`, `strip` or one of the
diagnostic modes listed in `tests/render-fixtures.json`. The CLI takes the mode
first and input second, and writes the exact rendered output. Diagnostic
`attributes`, `inspect`, `context` and `registration` modes produce JSON.

The default shortcode modes use `ignore_html=true`: shortcode-looking text
inside HTML attributes, comments and CDATA is not expanded. The explicit
`html-shortcodes` and `html-pipeline` modes enable the selected KSES attribute
path described below. Invalid
tag registration and invalid callbacks raise an exception instead of using
WordPress's translation/notice subsystem.

The renderer formats content and expands its registered shortcodes. It is not
an HTML sanitizer. Same-tag nesting retains WordPress's original regex behavior;
callbacks that process enclosed content explicitly invoke the core again.

## KSES attribute profile

The selected KSES helpers parse attribute lists, normalize HTML entities and URL
protocols, escape attribute text, and filter one attribute against an explicit
allowlist. They do not run `wp_kses` on a whole HTML document. WordPress 7.1 uses
`WP_HTML_Tag_Processor` for that graph; the full HTML API, CSS filtering and XML
entity normalization remain outside this profile.

```bash
./elephc/runtime/kses protocol 'javascript:alert(1)'
./elephc/runtime/kses attribute 'href="javascript:alert(1)"' a
./elephc/runtime/kses escape 'Ada & Bob'
curl -sS 'http://127.0.0.1:9000/kses?mode=attribute&element=a' \
  --data-binary 'title="Ada & Bob"'
./elephc/runtime/render html-shortcodes '<a title="[counter]">[counter]</a>'
```

The last command returns `<a title="1">2</a>`. `html-pipeline` also runs the
paragraph/content filters. These modes run `do_shortcode(..., false)` and
filter expanded quoted attributes with the original `wp_kses_one_attr` path.
Comments and CDATA remain excluded. Unquoted attributes and shortcodes in
attribute-name positions keep WordPress's original unfiltered-HTML behavior.
Rejected expansions can leave the original shortcode text in the attribute.
These modes expand content; they do not sanitize an entire HTML document.

The policy is defined in `wordpress_kses_setup` and shared with the original PHP
oracle. It allows `a`, `img`, `div`, `span` and `p`, with `title`, `class`, `id`,
`data-*` and `aria-label`. Anchors additionally allow `href`, `rel`, `target`
and valueless `download`. Images additionally allow `src`, `alt` and numeric
`width` from 1 to 4096. Allowed protocols are `http`, `https`, `mailto`, `ftp`
and `feed`. This is the experiment's policy, not the default WordPress post
allowlist. Protocol filtering removes disallowed scheme prefixes as core does;
it is not a complete URL validator.

The CLI takes `MODE [INPUT [ELEMENT]]`, defaulting to `attribute` and element
`a`; omitted input is read from stdin. POST `/kses` uses the `mode` and `element`
query parameters. Modes are `attribute`, `attributes`, `tag-attributes`,
`protocol`, `entities`, `decode` and `escape`. Filter diagnostic modes appear in
`tests/kses-fixtures.json`. Attribute parsers return JSON, text modes return
`text/plain`, and `decode` returns raw bytes as `application/octet-stream`.
The latter preserves core's byte-based numeric-entity primitive, including
values that are not valid UTF-8 output.

Inputs must be valid UTF-8. NUL is excluded from HTML attribute and HTML
shortcode inputs; protocol and entity primitives have separate binary-slice
adapters. Invalid inputs or unsupported HTML/CSS/XML modes return HTTP 400;
the CLI returns 2 with a stderr diagnostic. The native escaping bridge retains
the fixed core HTML 4.01 entity table and quote behavior without importing the
charset-recovery graph. Direct mutation of that entity table is outside the
profile. The source-defined `raw` shortcode exists for the attribute tests;
its output is escaped only where the selected core path applies escaping.

## Object cache profile

The original `WP_Object_Cache` and `wp_cache_*` API run as a cache in memory,
with one fresh instance for each request or CLI invocation. The adapter accepts
JSON operation sequences and returns their results plus the core hit/miss
counters. A stored `false` or `null` is distinguishable from a missing entry
through the `found` flag.

```bash
./elephc/runtime/cache '{"operations":[{"op":"set","key":"answer","value":false},{"op":"get","key":"answer"},{"op":"get","key":"missing"}]}'
```

This returns:

```json
{"results":[true,{"value":false,"found":true},{"value":false,"found":false}],"hits":1,"misses":1}
```

The CLI reads its first argument or stdin. POST `/cache` accepts the same JSON:

```bash
curl -sS http://127.0.0.1:9000/cache --data-binary @cache-operations.json
```

The root object contains `operations`, with at most 256 entries. Optional
`multisite` (boolean, default false) and `blog_id` (non-negative integer, default
1) supply the isolated site context. Multisite mode exercises core key prefixes
and global groups with that context. It does not load site routing or a database.

Operations are `set`, `add`, `replace`, `get`, `delete`, `incr`, `decr`,
`set_multiple`, `add_multiple`, `get_multiple`, `delete_multiple`, `flush`,
`flush_runtime`, `flush_group`, `global_groups`, `non_persistent_groups`, `switch_blog`, `suspend`, `supports`, `close` and `init`.
Single-key operations take `key`; writes also take `value`. Bulk writes take
`data`; bulk reads/deletes take `keys`. Optional `group`, `expire`, `offset` and
`force` follow the corresponding core API. Group registration takes `groups`,
site switching takes `blog_id`, suspension takes boolean/null `value`, and
feature checks take `feature`. Full examples are in `tests/cache-fixtures.json`.

Values are JSON-encodable scalars and arrays. JSON objects are decoded into
associative arrays. Keys must be integers or nonblank strings; keys and groups
exclude NUL bytes. Numeric strings, negative keys, null values and nested arrays
are covered by the reference checks. Invalid JSON, invalid operation fields or
unknown operations return HTTP 400 or CLI exit 2 with a stderr diagnostic.

The default core backend ignores `expire` and `force`; `close` and registration
of non-persistent groups are no-ops. A sequence's cache is discarded at the end
of its request. PHP object values/cloning, direct access to cache internals,
HTML statistics, deprecated reset and persistent cache drop-ins remain outside
this profile. The profile supplies the constructor's site context and selects
the original `wp_suspend_cache_addition` function without loading the CMS.

## List utility profile

`WP_List_Util` and the original `wp_list_filter`, `wp_filter_object_list`,
`wp_list_pluck` and `wp_list_sort` functions process JSON array records. The CLI
reads JSON from its first argument or stdin; POST `/lists` accepts the same body.

```bash
./elephc/runtime/lists '{"items":[{"id":2},{"id":1}],"op":"sort","orderby":"id"}'
```

The response is:

```json
{"input":[{"id":2},{"id":1}],"output":[{"id":1},{"id":2}],"steps":[]}
```

The root object requires `items`, an array or keyed object of at most 256
records. Each record becomes an associative array. `op` defaults to `filter`:

| Operation | Fields |
| --- | --- |
| `filter` | Optional `args` map and `operator` (`AND`, `OR`, `NOT`) |
| `filter-pluck` | Filter fields plus optional `field`, using `wp_filter_object_list` |
| `pluck` | Required `field`, optional `index_key` |
| `sort` | Optional `orderby` field or direction map, `order`, `preserve_keys` |
| `chain` | An `operations` list of at most 16 operations on one `WP_List_Util` instance |

Filters retain core's loose PHP comparisons and original keys. An unknown
operator returns an empty result when arguments are nonempty. Array fields
with null values remain present for matching; missing fields are excluded.
Plucking preserves keys by default, overwrites duplicate index values, and
appends values whose index field is absent/null. Falsy index selectors retain
core behavior. Sorting supports multiple fields, numeric strings, direction
normalization and optional key preservation. Empty ordering retains the input. A stable
merge-sort bridge uses the unchanged core comparator because the pinned SDK
rejects generic `uasort` storage.
Chained responses include each intermediate result in `steps` and the original
input in `input`.

Comparisons accept scalars or null. Record/field/index keys are integers or
strings; keys and comparison strings exclude NUL. Every selected record must
contain a requested pluck field, and non-null index values must be integer or
string keys. Nested arrays may be extracted but cannot be compared. After a
chain step extracts scalar values, another record operation is rejected.
Invalid input returns HTTP 400 or CLI exit 2 with a stderr diagnostic. PHP
object records, magic property access and WordPress query/database execution
remain outside this profile. Examples and boundaries are in
`tests/lists-fixtures.json` and `tests/lists-errors.json`.

## URL string profile

The original `esc_url`, `esc_url_raw`, `sanitize_url`, `wp_parse_url`,
`wp_make_link_relative`, `build_query` and their selected helpers process URL
strings and query arrays. The CLI reads JSON from its first argument or stdin;
POST `/urls` accepts the same object and returns `{"result": ...}` as JSON.

```bash
./elephc/runtime/urls '{"url":"example.org/?a=1&b=2"}'
# {"result":"http://example.org/?a=1&#038;b=2"}
curl -sS http://127.0.0.1:9000/urls --data-binary @url-operation.json
```

| Operation | Input and behavior |
| --- | --- |
| `display` (default) | `url`; core URL escaping for display |
| `raw`, `sanitize` | `url`; the original aliases using database/redirect context |
| `parse` | `url`, optional integer `component` from -1 to 7; returns all parts or a selected PHP_URL_* component |
| `relative` | `url`; original web-root-relative conversion |
| `query` | `data`; original unencoded `build_query` output |
| `encoded-query` | `data`, optional string `separator` (default `&`); original recursive URL encoding |
| `filtered` | `url`, optional `context` (`display` or `db`); diagnostic for the original clean_url filter |

Escaping uses the existing protocol policy (`http`, `https`, `mailto`, `ftp`,
`feed`) unless `protocols` supplies an explicit list. The list order/case is
passed through unchanged. As in core, a first literal `https` changes the scheme
prepended to a bare hostname. Display mode normalizes entities and escapes
ampersands/apostrophes; raw mode retains the corresponding original behavior.
Parsing preserves core's false-on-failure, null-for-absent and empty-component
results, including schemeless/relative URLs, credentials and IPv6.

Query data accepts JSON scalars and associative arrays, with at most 256 fields
per level and 16 nested levels below the root. Null values are omitted, false
becomes `0`, and empty nested arrays can leave empty segments as in WordPress.
`query` deliberately keeps values unencoded; `encoded-query` applies form URL
encoding recursively. The adapter supplies the separator explicitly; importing
PHP INI defaults and PHP object query data remain outside the profile.

All text must be valid UTF-8 and exclude NUL, including protocol names, query
keys, string values and separators. Invalid input returns HTTP 400 or CLI exit 2
with a stderr diagnostic. The profile performs string operations without HTTP
transport, DNS resolution, redirect execution or database access. It does not
import WordPress's remote-request validation/transport graph.

## Text and identifier sanitization

Eleven additional original formatting functions provide text sanitization,
HTML escaping, identifier filtering and hexadecimal color checks. The CLI
reads JSON from its first argument or stdin; POST `/sanitize` accepts the same
object and returns `{"result": ...}`.

```bash
./elephc/runtime/sanitize '{"text":"  Hello <b>WordPress</b> %20  "}'
# {"result":"Hello WordPress"}
curl -sS http://127.0.0.1:9000/sanitize \
  -H 'Content-Type: application/json' \
  --data-binary '{"op":"class","text":"!!!","fallback":"valid-class"}'
# {"result":"valid-class"}
```

Every operation requires a `text` string:

| Operation | Original core behavior |
| --- | --- |
| `field` (default) | `sanitize_text_field`: remove tags/percent sequences and collapse whitespace |
| `textarea` | `sanitize_textarea_field`: retain line breaks and tabs |
| `strip-tags` | `wp_strip_all_tags`: remove script/style contents and tags; optional boolean `remove_breaks` |
| `less-than` | `wp_pre_kses_less_than`: escape incomplete less-than sequences before KSES |
| `html` | `esc_html`: escape HTML with original entity normalization |
| `key` | `sanitize_key`: lowercase ASCII letters, digits, underscores and hyphens |
| `class` | `sanitize_html_class`: retain allowed class characters; optional string `fallback` |
| `hex` | `sanitize_hex_color`: return the original valid value, empty string or null |
| `hex-no-hash` | `sanitize_hex_color_no_hash`: remove leading hashes and check the color |

The profile preserves original core normalization, including recursive class
fallback, repeated percent removal and distinctions between empty and invalid
colors. It accepts UTF-8 strings without NUL. Non-string values and charset
recovery are outside the profile. Invalid input returns HTTP 400 or CLI exit 2
with a stderr diagnostic. Tag stripping does not provide the full HTML KSES,
CSS or template-context validation graph.

Optional `filtered: true` exposes the original filter arguments for `field`,
`textarea`, `html`, `key` and `class`: the diagnostic returns original text,
then sanitized text, separated by `|`; class also includes the fallback argument.
An unusable class with a fallback invokes the recursive call's filter context.
The adapter clears these diagnostic hooks between operations. Examples and
boundaries are in `tests/sanitize-fixtures.json` and `tests/sanitize-errors.json`.

## Error object profile

The original `WP_Error` class collects error codes/messages, stores associated
data and its history, and copies errors between objects. The original
`is_wp_error` helper recognizes these objects, including empty ones. The CLI
reads a JSON object from its first argument or stdin; POST `/errors` accepts
the same body.

```bash
./elephc/runtime/errors '{"operations":[{"op":"create","code":"invalid","message":"Invalid input","data":{"status":400}},{"op":"get-message"},{"op":"get-data"}]}'
curl -sS http://127.0.0.1:9000/errors --data-binary @error-operations.json
```

The request requires an `operations` list of at most 256 entries. Each request
starts with an empty object named `main`; optional boolean `hooks` records the
original `wp_error_added` and `is_wp_error_instance` actions. Responses contain
operation `results`, final object snapshots in `objects`, and recorded `events`.
Mutating operations return null. Earlier snapshots retain their original values.

| Operation | Fields and behavior |
| --- | --- |
| `create` | Replace/create `target`; optional `code`, `message` and `data` constructor arguments |
| `add` | Required `code` and `message`, optional `data`; append a core error |
| `add-data` | Required `data`, optional `code`; replace latest data and retain core history |
| `remove` | Required `code`; remove messages and all data/history for it |
| `get-codes`, `get-code` | Return ordered codes or the first code |
| `get-messages`, `get-message` | Optional `code`; return core message results |
| `get-data`, `get-all-data` | Optional `code`; return latest data or core chronological history |
| `has-errors`, `is-error` | Inspect content or recognize the actual error object |
| `merge`, `export` | Required `source`; copy from that object into `target` using the corresponding original method |
| `snapshot` | Return core query results, public message/data maps and per-code details |
| `check-value` | Required JSON `value`; verify that an ordinary JSON value is not a PHP error object |

`target` defaults to `main`; names are nonempty strings. Up to 16 objects can
share one request. Codes are integer/string PHP keys, messages are strings, and
names/codes/messages exclude NUL. JSON data is decoded associatively and allows
256 fields per level with at most 16 nested levels. The adapter also bounds
aggregate state to 1,024 entries (messages, latest-data slots and core history
results) and hook capture to 1,024 events,
rejecting excessive growth after an operation. Input errors return HTTP 400 or
CLI exit 2 with a stderr diagnostic.

The profile preserves original falsey-code defaults, constructor/add handling
of empty data, ordered message merging and copy independence. `add_data(null)`
replaces the latest data, but the original `isset` rules omit null from history.
`merge`/`export` ignore orphan data without an error code in the message map.
Self-copying follows core behavior within the growth limits. Hook events observe
the actual object after insertion, and diagnostic hooks/state reset per request.

PHP object data, subclasses, dynamic properties and direct property mutation
are outside the JSON profile. This does not load the REST API or CMS bootstrap.
Examples and invalid inputs are in `tests/errors-fixtures.json` and
`tests/errors-errors.json`.

## HTTP and REST response objects

The original `WP_HTTP_Response` and `WP_REST_Response` classes manage response
data, status, headers, links and matched-route metadata. Selected original
`rest_ensure_response` and `rest_convert_error_to_response` helpers connect them
to `WP_Error`. The CLI reads a JSON object from its first argument or stdin;
POST `/responses` accepts the same input.

```bash
./elephc/runtime/responses <<'JSON' | python3 -m json.tool
{
  "status": 201,
  "data": {"id": 42},
  "operations": [
    {"op": "header", "key": "X-Demo", "value": "Elephc"},
    {"op": "add-link", "rel": "self", "href": "/demo/42"},
    {"op": "snapshot"}
  ]
}
JSON
```

`kind` defaults to `rest`; `http` selects the base class. Optional `data`, `status`
and `headers` supply constructor arguments. An `operations` list is required,
with at most 256 entries. Results contain operation `results` and the final
`response` snapshot. Mutations return null. The HTTP endpoint returns an
inspection envelope with status 200 for valid operations; `response.status`
and `response.headers` describe the WordPress object inside that envelope.

| Operation | Input and original behavior |
| --- | --- |
| `get-data`, `json`, `get-status`, `get-headers`, `snapshot` | Inspect the object or explicitly call `jsonSerialize` |
| `set-data`, `set-status`, `set-headers` | Required `value`; replace that field |
| `header` | Required `key`, string `value`; optional boolean `replace` defaults true |
| `ensure` | Preserve a REST object's identity or upgrade a base HTTP object |
| `ensure-json` | Required JSON `value`; wrap it as an original REST response |
| `ensure-error` | Required `errors` operation list; preserve the actual WP_Error identity |
| `convert-error` | Required nonempty error state from `errors`; return the REST response and its conversion back to WP_Error/null |
| `get-links`, `get-curies`, `get-route`, `get-handler`, `is-error`, `as-error` | Inspect REST-specific state or convert it to WP_Error/null |
| `add-link` | Required `rel`, `href`; optional JSON `attributes` array |
| `add-links` | Required `value` relation map, with one record or a collection of records containing `href` |
| `remove-link` | Required `rel`, optional nullable string `href` |
| `link-header` | Required `rel`, `href`; optional string map `other` |
| `set-route`, `set-handler` | Required string/array `value`; store metadata |

The `errors` list uses the operations from the error-object profile and starts
with a fresh `main` object. Conversion requires at least one message. The last
numeric status in the first code's data history selects the REST response status;
other codes' statuses do not replace it. Previous data is emitted as
`additional_data`, which the original reverse conversion does not restore.
`as-error` on an array body with error status requires `code`, `message` and
`data`, plus an optional list of complete `additional_errors` records.

Original header case/order, comma appending, link attributes and sparse keys
after link removal remain. Empty or string-zero href removal clears the relation,
as in core. `curies` optionally provides up to 256 records through the original
CURIE filter; each operation sequence resets that filter. Snapshots retain
independent values. Handler metadata is stored as JSON data.

Status inputs are integers from -9999 to 9999, normalized through original
`absint`; this includes the core's nonstandard object status values. Numeric
status candidates in error conversion use the same range. Header names are
nonempty and header text excludes NUL/CR/LF. Other metadata excludes NUL. JSON
data uses the existing 256-field/16-level limits; headers allow 256 entries,
bulk insertion allows 256 links and accumulated link state allows 1,024.
Invalid inputs return HTTP 400 or CLI exit 2 with a stderr diagnostic.

The profile covers response objects. REST route dispatch, request parsing and
validation, authentication, handler execution, embedded-link rendering and the
complete CMS remain outside it. Examples and boundaries are in
`tests/responses-fixtures.json` and `tests/responses-errors.json`.

## REST request objects

The bounded `WP_REST_Request` profile models methods, canonical headers, route
metadata, parameter sources, lazy JSON bodies and `ArrayAccess`. The CLI reads
a JSON description from its first argument or stdin; POST `/requests` accepts
the same object. Its `method` selects the method of the modeled WordPress request.

```bash
./elephc/runtime/requests <<'JSON' | python3 -m json.tool
{
  "method": "POST",
  "headers": {"Content-Type": "application/json"},
  "query": {"id": 1},
  "body": "{\"id\":2}",
  "operations": [
    {"op": "get-param", "key": "id"},
    {"op": "offset-set", "key": "id", "value": 3},
    {"op": "get-query"},
    {"op": "get-json"}
  ]
}
JSON
```

The example reads JSON `id` first, then updates the existing JSON and query
sources. The final two results contain `id: 3`. Optional `method` and `route`
default to empty strings, `attributes` to an empty array and raw `body` to null.
`headers`, `query`, `url`, `post`, `files` and `defaults` supply explicit source
maps. File values are metadata; no file uploads or filesystem access are run.
An `operations` list of at most 256 entries is required.

| Operation | Input and original behavior |
| --- | --- |
| `get-method`, `set-method`, `is-method` | Setter/comparison take string `value`; core uppercases method names |
| `get-route`, `set-route`, `get-attributes`, `set-attributes` | Setter takes string/JSON-array `value`; metadata is stored |
| `get-headers`, `get-content-type`, `is-json` | Inspect canonical headers and original media-type detection |
| `canonical-header`, `get-header`, `header-values`, `remove-header` | Required header `key` |
| `set-header`, `add-header` | Required `key` and string/list `value` |
| `set-headers` | Required header-map `value`, optional boolean `override` defaults true |
| `get-body`, `set-body`, `get-json`, `json-check` | Body setter takes string `value`; JSON check exposes true or original WP_Error details |
| `get-query`, `get-url`, `get-post`, `get-files`, `get-defaults` | Read explicit parameter sources |
| `set-query`, `set-url`, `set-post`, `set-files`, `set-defaults` | Required JSON-array `value`; replace that source |
| `order`, `get-params` | Inspect core source priority and merged parameters |
| `get-param`, `has-param`, `set-param` | Required integer/string `key`; setter also requires JSON `value` |
| `offset-get`, `offset-exists`, `offset-set`, `offset-unset` | Exercise real ArrayAccess operators; setter requires `value` |
| `peek`, `snapshot` | Inspect raw sources/parse flags or run the normal merged-parameter getters |
| `validate`, `sanitize` | Run original required/callback validation or parameter sanitization explicitly |
| `callback-events` | Inspect this sequence's ordered parameter and route callback arguments |
| `schema-check`, `scalar-check` | Required JSON `value` and bounded `schema`, optional integer/string parameter `key`; run the original schema validator directly |

`get-param` skips null values while `has-param` recognizes a present null.
`get-params` can retain a higher-priority null that differs from individual
lookup. ArrayAccess `isset` uses the core's non-null existence rules. Setters
update existing active non-default sources; otherwise they write into the first
source in the current order. Unset removes the key from every source in the current order.
Numeric keys retain their original values and insertion order.

JSON parameters take priority when the content type is JSON, followed by POST
for POST/PUT/PATCH/DELETE, GET, URL and defaults. Optional `order` supplies one to
six source labels through the original filter. `permalinks` supplies the isolated
option context: false (default) removes `rest_route` from merged output, while
individual lookup can still read it. The PHP oracle supplies that same context
through the original option filter, with no database.

Core lazy-parsing behavior remains: changing headers does not reset a previous
JSON parse attempt, while `set-body` resets JSON and body parsing flags. `peek`
and adapter budget checks read state without forcing parsing; `snapshot` uses
normal getters and can trigger it. Invalid modeled JSON can be inspected through
`json-check` as original `rest_invalid_json` details; its getter returns null.
These are successful inspection calls with transport status 200. Invalid profile
inputs return HTTP 400 or CLI exit 2 with a stderr diagnostic.

Header text excludes NUL/CR/LF and names are nonempty. Raw body text excludes
NUL; parsed JSON bodies support arrays, objects, null and the core's empty-body
handling of string zero. Other scalar JSON bodies are outside the profile.
Each source-map or value input uses the existing 256-field/16-level JSON limits; request
state allows 256 fields per source, header names and values per name. Each
invocation resets the order filter and option context. The fixed English JSON
error label and pinned BAD_REQUEST constant avoid loading locale/transport
bootstrap in native code.

URL-encoded parsing for non-POST bodies, general JSON Schema validation/sanitization, URL
request discovery, authentication and route/handler dispatch remain outside the
profile. Supply already decoded POST/query maps explicitly. The class retains
explicit exceptions for automatic schema callbacks, URL discovery and the parse_str-dependent
branch. The media-type predicate retains its original regex without its static
memoization, which the pinned backend rejects. Examples and boundaries are in
`tests/requests-fixtures.json` and `tests/requests-errors.json`.

### Required parameters and callbacks

The original `has_valid_params()` and `sanitize_params()` methods run source-defined
callbacks. Optional `rules` replaces `attributes.args` with at most 64 parameter
rules. `required` is boolean; `validate` and `sanitize` select callbacks from the
following finite manifest. False or null disables a parameter callback.

| Rule field | Selectors |
| --- | --- |
| `validate` | `schema`, `integer`, `boolean`, `string`, `pass`, `fail`, `error`, `return-null`, `return-zero` |
| `sanitize` | `keep`, `trim`, `upper`, `boolean`, `null`, `error` |
| Root `route_validate` | `pass`, `fail`, `error`, `check-blocked`; null clears it |

```bash
./elephc/runtime/requests <<'JSON' | python3 -m json.tool
{
  "method": "POST",
  "route": "/demo",
  "query": {"id": "12", "name": " Ada ", "enabled": "FALSE"},
  "rules": {
    "id": {"required": true, "validate": "integer"},
    "name": {"validate": "string", "sanitize": "trim"},
    "enabled": {"validate": "boolean", "sanitize": "boolean"}
  },
  "route_validate": "check-blocked",
  "operations": [
    {"op": "validate"},
    {"op": "sanitize"},
    {"op": "get-params"},
    {"op": "callback-events"}
  ]
}
JSON
```

Both phases return true here; parameters become `id: "12"`, `name: "Ada"`
and `enabled: false`. Integer validation keeps the original value. The integer
and boolean predicates and boolean sanitizer are original WordPress REST helpers.
Trim/uppercase callbacks accept NUL-free strings and otherwise return `WP_Error`.
The other callbacks expose lifecycle behavior for this experiment; `check-blocked`
receives the actual request and rejects an effective `blocked: true` value.

Validation checks malformed JSON and missing required values before parameter
callbacks, then runs the route callback only after all parameter checks pass.
Only exact false or `WP_Error` fails a callback; null and zero remain valid.
Optional null values skip validation. Sanitization visits every active source,
including defaults, and keeps successful updates even when other values fail.
Sanitization without argument rules leaves JSON parsing lazy. Neither phase runs
automatically, so operation order is explicit.

Core failures return an `error` snapshot and an original REST `response` with its
own `status` and `data`. The inspection endpoint still returns HTTP 200. Error
details include the original aggregated parameter messages and callback data/history.
Raw `attributes.args` may also use the manifest's named functions, visible in
`get-attributes`; phase preflight permits `required`, `validate_callback`,
`sanitize_callback` and paired `profile_schema` fields with the appropriate manifest functions.
Callback wrappers, rules and diagnostics belong to this experiment.
The trace allows 1,024 events and resets for every sequence and HTTP request.
Tests are in `tests/request-validation-fixtures.json` and
`tests/request-validation-errors.json`.

### Scalar schema validation

`validate: "schema"` and a `schema` rule call the original WordPress schema
dispatcher and validators. Validation preserves the input value; sanitizers remain explicit.

```bash
./elephc/runtime/requests <<'JSON' | python3 -m json.tool
{
  "query": {"age": "17", "name": "Ada"},
  "rules": {
    "age": {
      "validate": "schema",
      "schema": {"type": "integer", "minimum": 18, "maximum": 99}
    },
    "name": {
      "validate": "schema",
      "schema": {"type": "string", "minLength": 1, "maxLength": 40}
    }
  },
  "operations": [{"op": "validate"}, {"op": "callback-events"}]
}
JSON
```

This produces the original `rest_invalid_param` aggregate for `age`, with a
`rest_out_of_bounds` detail. Changing age to `"20"` passes and keeps it a string.

| Schema type | Accepted keywords beyond `type` |
| --- | --- |
| `null`, `boolean` | None |
| `number`, `integer` | `minimum`, `maximum`, boolean `exclusiveMinimum`/`exclusiveMaximum`, positive `multipleOf` |
| `string` | Integer `minLength` and `maxLength` |

Numeric constraints must be finite JSON numbers within +/- 10^12; string lengths
range from 0 to 1,000,000. The original numeric comparisons, floating remainder
test and error precedence remain. Integer validation accepts original whole-valued
numeric forms. String length counts UTF-8 code points, including combining marks
separately. Errors use a fixed English locale and original number grouping.

`scalar-check` tests a value directly, including null values that request callbacks
normally skip. For example, supply an operation with `value: null` and
`schema: {"type": "null"}`. Direct helper errors have no status metadata, so their
original REST conversion defaults to status 500. Request validation aggregates
them under status 400. Both remain successful inspection calls with transport
status 200.

The rule factory stores this bounded schema as `profile_schema` beside the
source-defined callback. It does not enable automatic core schema sanitization.
Raw attributes require both that metadata and `wordpress_request_validate_schema`.
Type unions, enum, pattern, format, anyOf/oneOf and unknown keywords
fail explicitly. Full JSON Schema and locale bootstrap remain outside the profile.
The corpus compares the original specialized helpers with the original full schema
dispatcher, then checks patched PHP, native CLI and repeated HTTP behavior.

### Array and object schema validation

The same `schema` rule supports nested decoded arrays and objects through the
original core dispatcher, collection validators and key-shape predicate.

```bash
./elephc/runtime/requests <<'JSON' | python3 -m json.tool
{
  "query": {"people": [{"name": "Ada", "age": "20"}]},
  "rules": {
    "people": {
      "required": true,
      "validate": "schema",
      "schema": {
        "type": "array", "minItems": 1,
        "items": {
          "type": "object", "required": ["name", "age"],
          "properties": {
            "name": {"type": "string", "minLength": 1},
            "age": {"type": "integer", "minimum": 18}
          },
          "additionalProperties": false
        }
      }
    }
  },
  "operations": [{"op": "validate"}, {"op": "get-params"}]
}
JSON
```

The example passes and keeps `age` as the string `"20"`. Changing it to `"bad"`
returns the original error path `people[0][age]`. Direct `schema-check` operations
accept the same value/schema pair; `scalar-check` remains an alias.

| Type | Additional supported keywords |
| --- | --- |
| `array` | Single-schema `items`, integer `minItems`/`maxItems`, boolean `uniqueItems` |
| `object` | `properties`, required-name list, boolean/schema `additionalProperties`, integer `minProperties`/`maxProperties` |

Each schema allows 64 nodes including the root and eight nested levels below it.
Property maps and required-name lists allow 64 entries; collection counts range
from 0 to 256. Values retain the existing 256-field/16-level JSON limits.
Property schemas can use the original legacy boolean `required` flag. An explicit
object required list, including an empty list, takes precedence over those flags.
Required properties recognize present null; their child schema then validates it.

Array validation accepts decoded arrays whose keys are numeric, including sparse
and negative keys. Core normalizes its local copy before checking items, so error
paths use consecutive indices. String-keyed maps fail the original array type
check. Object validation preserves WordPress's acceptance of decoded lists/maps
and empty-string objects. Decoded empty JSON lists and objects follow PHP's
associative-array semantics. Validation visits values in their original order,
reports the first nested error, and leaves the input unchanged.

Scalar-to-list coercion (CSV/space-separated strings, numbers and booleans), PHP
objects, pattern properties, enum, formats and combining/multi-type
schemas remain outside the profile. Unsupported coercion branches fail explicitly.
The collection corpus covers nesting, required/additional properties, count limits,
error ordering/paths, unchanged values and schema budgets against original PHP.

`uniqueItems: true` uses the original recursive stabilization, serialization and
seen-key algorithm. Object property order is ignored recursively; list value
order is preserved. Values are not coerced: `1`, `1.0` and `"1"` remain distinct,
even with integer item validation. Empty JSON objects/lists both decode to PHP
arrays and therefore compare as duplicates. `uniqueItems: false` disables the check.
Only boolean flags are accepted, on array schemas within the existing budgets.

```bash
echo '{"operations":[{"op":"schema-check","value":["php","php"],"schema":{"type":"array","uniqueItems":true},"key":"tags"}]}' \
  | elephc/runtime/requests
```

This returns the original `rest_duplicate_items` error with
`tags has duplicate items.`. Core checks item validity and collection counts before
uniqueness. Request callbacks aggregate duplicate errors through the existing
validation lifecycle and leave caller values unchanged. Direct schema errors keep
their original absent status data; the inspection envelope remains HTTP 200.

## Verified behavior

```bash
python3 scripts/test-elephc.py
```

The integration check compares **22 fixtures** against the unmodified
WordPress parser running on the host PHP interpreter. It compares the patched
PHP implementation and the native CLI with that reference.
**31 hook scenarios** are independently compared with upstream PHP, patched
PHP and the hooks CLI. Another **89 rendering fixtures** compare original PHP,
patched PHP and the rendering CLI. Another **58 serialization fixtures** compare
original PHP, patched PHP and native output, reparsed trees and the next
serialization pass, with **22 invalid-input cases**. Another **87 KSES fixtures**
compare byte-exact output against original PHP, with **13 additional boundary cases** (12 through the CLI and all 13 through
HTTP). Another **56 object cache scenarios** compare operation sequences with
original PHP, with **38 invalid-input cases** and CLI stdin coverage.
Another **56 list scenarios** compare filtering, plucking, sorting and chained
state with original PHP, with **32 invalid-input cases**. Another **103 URL
cases** cover escaping, parsing, query building and filters, with **28 invalid
inputs**. Another **123 sanitization cases** cover text, HTML, identifiers,
colors and filters, with **17 invalid inputs**. Another **62 error-object cases**
cover messages, data/history, copying, recognition and hooks, with **38 invalid
inputs**. Another **74 response-object cases** cover inheritance, status, headers,
links, metadata, identity and error conversion, with **48 invalid inputs**.
Another **70 request-object cases** cover headers, precedence, lazy JSON,
ArrayAccess and context, with **45 invalid inputs**. Another **102 request-validation
cases** cover required fields, callbacks, source mutation and aggregated errors,
with **36 invalid inputs**. Another **163 scalar-schema cases** cover direct and
request callback validation, with **38 invalid inputs**. Another **74 collection-schema
cases** cover recursive arrays/objects, with **39 invalid inputs**. Another **53
unique-item cases** cover recursive duplicate semantics, type distinctions and
request lifecycle behavior, with **16 invalid inputs**. The server checks
all sixteen corpora twice through one worker for **3,476 HTTP checks**, including routing, body limits, response MIME types and request isolation.

| Request | Result |
| --- | --- |
| `GET /` | HTML demo, with a nested document parsed during the request |
| `GET /blocks` | Parsed demo as JSON |
| `GET /health` | Version/profile information, `database: false` |
| `POST /parse` | Parse a raw UTF-8 block document as JSON |
| `POST /requests` | Inspect REST request objects, parameters, JSON and explicit callback phases |
| `POST /responses` | Inspect native HTTP/REST response objects and convert core errors |
| `POST /errors` | Run a JSON sequence against original core error objects |
| `POST /sanitize` | Sanitize text, escape HTML and check identifiers/colors from JSON input |
| `POST /urls` | Escape/parse URLs and build query strings from JSON input |
| `POST /lists` | Filter, pluck, sort or chain operations on JSON records |
| `POST /cache` | Execute a JSON sequence against a fresh core object cache |
| `POST /kses?mode=attribute&element=a` | Filter one attribute using the selected KSES policy |
| `POST /serialize` | Serialize a JSON block tree as a WordPress document |
| `POST /serialize?mode=roundtrip` | Parse and serialize a raw block document |
| Invalid serialization input or mode | `400` |
| `GET /hooks?case=priority&value=Hello` | Run the selected hook scenario as JSON |
| `POST /filter` | Run a real priority filter pipeline on the raw request body |
| `GET /render` | HTML demo from the core content pipeline |
| `POST /render?mode=pipeline` | Format and expand request content as HTML |
| `POST /render?mode=attributes` | Parse shortcode attributes as JSON |
| Unknown render mode | `400` |
| Unknown hook scenario | `400` |
| `HEAD /` | `200`, empty body |
| `GET /missing` | `404` |
| `GET /parse`, `POST /blocks` | `405`, appropriate `Allow` header |
| Request body over 64 KiB | `413` from the native transport |

The block fixture corpus covers Unicode, nested groups, void blocks, freeform text,
JSON data, invalid JSON, missing and mismatched closers, and parser reuse.
These checks establish the behavior of this corpus, not general WordPress or
PHP compatibility. No performance comparison has been made.

The tests start a temporary native server on an available loopback port and
stop it automatically. Set `PHP_BIN=/absolute/path/to/php` to choose the
reference interpreter. The native host workflow was verified with PHP 8.5.12.
Hook coverage includes callback identity/removal, same-priority ordering,
zero and negative priorities, accepted argument counts, mixed values, live
insertion/removal, nested/recursive hooks, action context and counters,
`all`, argument-array forwarding and `WP_Filter_Sentinel` identity.
Rendering covers attribute syntaxes and C escapes, literal regex metacharacters
in tags, escaped and nested shortcodes, HTML boundaries, shortcode filters,
registration/removal, context cleanup, paragraph/newline behavior, protected
`pre`/script/style content and selected block-level HTML.

## Sources and patches

[`elephc/upstream.json`](elephc/upstream.json) pins the official
[WordPress archive](https://wordpress.org/wordpress-7.1.4.tar.gz) by SHA-256.
`scripts/prepare-elephc.py` downloads it into the ignored `upstream/` directory
and extracts the complete original tree for inspection and reference checks.

These core classes and the hook API enter the compiled graph:

```text
WP_Block_Parser
WP_Block_Parser_Block
WP_Block_Parser_Frame
WP_Hook
WP_Filter_Sentinel
wp-includes/plugin.php
wp-includes/shortcodes.php
24 selected functions from wp-includes/formatting.php
five selected functions from wp-includes/blocks.php
21 selected functions and the entity table from wp-includes/kses.php
WP_Object_Cache and wp-includes/cache.php
WP_List_Util
WP_Error
WP_HTTP_Response and WP_REST_Response
WP_REST_Request (bounded parameter/header/JSON profile)
absint, is_wp_error and wp_is_json_media_type from wp-includes/load.php
rest_ensure_response, rest_convert_error_to_response, rest_is_integer,
rest_is_boolean, rest_sanitize_boolean and five scalar schema validators
plus four collection helpers, two collection validators and the schema dispatcher
from wp-includes/rest-api.php
four list wrappers, wp_suspend_cache_addition, two query builders and wp_is_numeric_array
from wp-includes/functions.php
three selected URL parser functions from wp-includes/http.php
```

The three parser patches add explicit storage types, narrow parser frames,
replace a constructor passed directly to `array_push`, and adapt token capture
to Elephc's three-argument `preg_match`. The original regular expression and
parser state machine remain. Captures use numbered groups and byte offsets
are recovered from the matched suffix.

Two additional patches adapt `WP_Hook` and `plugin.php`. Priority-array
positions are explicit integers instead of PHP's hidden array pointers.
The upstream priority-resorting algorithm is retained, with differential
checks for additions and removals during dispatch. Typed object identity,
callback descriptor resolution and safe local copies for table updates
bridge the native storage/calling boundary. `WP_Filter_Sentinel` is copied
byte-for-byte without a patch.

The shortcode patch retains the original parser regex and adds explicit
types, closure adapters for regex callbacks, callback resolution and the original
HTML attribute branch for the selected KSES profile. Small adapters cover the pinned SDK's missing `preg_quote` and
`stripcslashes`, array-search `str_replace`, typed string replacement maps and string-value
intersection of shortcode tag lists. Empty-subject guards avoid a pinned
native regex runtime crash while retaining the reference output.
They are exercised through the upstream differential corpus.

`formatting.php`, `blocks.php`, `kses.php`, `http.php` and `functions.php` are patched as
complete original files, then reduced to selected functions and source segments using byte ranges
and hashes recorded in the manifest. Preparation verifies the complete patched source, each selected
fragment and each final generated module. This selection avoids importing the
unrelated formatting/UTF-8/bootstrap graph. Builds still require no PHP
interpreter; PHP was used only to identify function ranges during maintenance
and is needed for reference tests.

Original source, patch and generated source SHA-256 hashes are checked before
compilation. Patches apply with zero fuzz to an isolated, generated
`elephc/runtime/core/` tree. Original files remain untouched.

```bash
python3 scripts/prepare-elephc.py --check
python3 scripts/test-clean-room.py
```

The clean-room check exports maintained source files into a temporary directory,
downloads a fresh WordPress archive, applies the patches, compiles all thirteen
executables and repeats the reference/HTTP checks. It reuses the installed SDK
and native dependency cache, and removes its export when finished.
PCRE2 10.47 is pinned in the native manifest and lockfile.

## Complete WordPress probes

```bash
./scripts/check-upstream.sh
```

This diagnostic intentionally returns `1` on Elephc 0.27.8. It checks four
unmodified source paths without executing them:

- Standard bootstrap: conditional `ABSPATH` definition prevents resolving a
  subsequent include path.
- `SHORTINIT` with `ABSPATH` defined in advance: `compat-utf8.php` contains
  unsupported `goto` statements and labels.
- Original standalone parser: unsupported `preg_match` arguments and stack
  object/storage inference prevent compilation.
- Original hook API: a comma-separated static declaration blocks parsing;
  splitting it exposes type/storage and callback compatibility work.

These are observed first blockers, not an exhaustive compatibility audit.
No diagnostic starts an installation or connects to a database.

### Main SDK after goto support, 2026-11-17

[Elephc PR #2370](https://github.com/illegalstudio/elephc/pull/2370) added
compiled `goto` support. An isolated Linux SDK from main commit
`23314d3e1fdb1ec72b5a915a7753dc72473e7591` was compared with the pinned release:

| Diagnostic | Pinned release | Tested main SDK |
| --- | --- | --- |
| Standalone goto control | Unsupported goto/labels | Native output matches PHP: `[true,false,"AxB"]` |
| Original `compat-utf8.php` | Goto syntax errors | No goto errors; semantic/dependency errors remain |
| Original `SHORTINIT` | Goto syntax errors | Dynamic `require_once $file` in `sodium_compat/autoload.php:31` |
| Standard bootstrap | Conditional `ABSPATH` resolution | Same first blocker |

The UTF-8 probe also exposes missing `strspn`, independently reproduced by
`elephc/probes/string-span.php`. Its missing WordPress helper functions are
expected when loading that file alone; remaining type/reference diagnostics need
reassessment after its primitive dependencies are available.

The first Sodium include failure is inside a `PHP_VERSION_ID < 70000` branch.
`elephc/probes/versioned-include.php` reproduces rejection of that inactive branch
under `--php-version 8.4`. The active PHP 7+ loader also has dynamic includes, so
discarding the legacy branch alone does not establish bootstrap compatibility.

To repeat the comparison, unpack the complete candidate SDK, keeping its native
archives beside the compiler, then run:

```bash
python3 scripts/check-goto.py \
  --compiler /path/to/unpacked-sdk/elephc \
  --revision 23314d3e1fdb1ec72b5a915a7753dc72473e7591 \
  --sdk-sha256 b418802f05abf1106b1fa0b37e14b0b65ed3f4c800f0baa60d9865d9960a4871 \
  --output elephc/build/goto-main-23314d3
```

WordPress probes are compile-only; only the standalone control is compiled and
executed. Its success does not mean WordPress boots. The
[frozen report](docs/elephc/goto-main-23314d3.json)
records artifact provenance, checksums and diagnostics. Both SDKs report
`elephc 0.27.8`, so identify the development SDK by its source revision and checksum.
The project pin and application profile remain unchanged; their previously
verified 3,338 HTTP checks were not rerun against this development SDK.

`elephc/probes/regex-empty-subject.php` separately reproduces a native runtime
crash on an empty `preg_replace` subject. It is excluded from normal builds and
verified application tests. Compile a copy inside a native project with the
locked PCRE2 manifest and lockfile. The content profile has explicit empty-input
guards and passes the empty-input differential fixtures.

See the [porting log](docs/elephc/porting-log.md) and
[patch maintenance notes](patches/README.md). WordPress and this experiment
are distributed under [GPL-2.0-or-later](LICENSE).

The separate `elephc/probes/regex-nul-subject.php` diagnostic preserves a NUL
inside a 12-byte subject. Original PHP returns zero matches for the anchored
ASCII-letter pattern; the pinned native matcher returns one. The URL profile
checks NUL before regex validation and excludes this diagnostic from its verified
builds. Compile a copy in a native project with the locked PCRE2 dependencies.

The separate `elephc/probes/query-parse.php` diagnostic calls `parse_str` with an
initialized output array. Elephc 0.27.8 reports `Undefined function: parse_str`.
Consequently `wp_parse_str`, `add_query_arg`, `remove_query_arg` and the string
branch of `wp_parse_args` remain outside the verified profile. Run
`elephc --php-version 8.4 --check elephc/probes/query-parse.php` to reassess that
primitive after an SDK upgrade. This negative probe is excluded from normal builds.

The separate `elephc/probes/sanitize-null-result.php` diagnostic retains the old
sanitizer dispatcher. For an invalid color, PHP produces `null` in its dispatcher,
direct-core and explicit-mixed-encoder controls; native produces an empty string
only in the old dispatcher. The application returns each operation directly
through a `mixed` encoder parameter and preserves the original nullable color
signatures. This probe requires prepared core sources and locked PCRE2; compile
a copy with the existing native manifest/lockfile. It is excluded from verified
application builds.

`elephc/probes/static-method-instance.php` preserves PHP-valid instance syntax
for a static method. PHP prints `header`; the pinned checker reports Undefined
method. The request patch uses a late-bound `static::` call. The separate
`static-json-cache.php` probe retains the original media cache form, for checking
the pinned static-array initialization backend failure before retiring its cache
elision. Compile a copy with the locked native manifest/lockfile. Both diagnostics
are excluded from verified application builds.

The separate `elephc/probes/json-error-message.php` diagnostic shows the SDK's
intentional `near location` suffix alongside the original PHP error message.
The request bridge strips only that suffix in WordPress `json_error_message`
payloads, retaining the original decoder code and complete WP_Error structure.
