# WordPress on Elephc

A native AOT experiment with WordPress blocks, serialization, hooks and content.
WordPress **7.1.4**, Elephc **0.27.8**, PHP profile **8.4**, Linux x86_64.

The experiment uses nine original core files, with eight compatibility
patches, to build block parsing, serialization, hooks and rendering CLIs plus
an HTTP server. The formatting graph selects seven verified functions from the original
`formatting.php`; another five come from `blocks.php`. Documents, JSON block
trees and content inputs are processed at runtime.
The HTTP adapter, callback manifest and demo callbacks belong to this experiment.

This is a core subsystem port. The complete CMS bootstrap, database, plugin
discovery, themes, block rendering and `wp-admin` are outside the current profile.

## Requirements

Build and run directly on the host. The verified target is Linux x86_64.
You need:

- The complete Elephc 0.27.8 SDK, including its runtime archives, on `PATH`.
  [elvm](https://github.com/illegalstudio/elvm) reads the repository's
  `.elephc-version`; run `elvm install 0.27.8` if needed.
- Bash, Python 3.12 or newer, `patch`, a C compiler/linker (`cc`) and GNU
  binutils (`as`, `ar`, `strip`). On Arch Linux these tools are provided by
  `base-devel`, `python` and `patch`; on Debian/Ubuntu use `build-essential`,
  `binutils`, `python3` and `patch` with a sufficiently recent Python.
- Internet access for the first WordPress download and PCRE2 installation.
- PHP 8.4 or newer only for the differential reference tests. PHP is not
  required to compile or run the native executables.

## Build with Elephc

From the repository root:

```bash
elephc --version
./scripts/build-elephc.sh
```

The script verifies the downloaded sources and patches, installs the locked
PCRE2 dependency, and produces `elephc/runtime/parse`, `elephc/runtime/hooks`,
`elephc/runtime/render`, `elephc/runtime/serialize` and `elephc/runtime/server`.
It uses `elephc` from `PATH`; set
`ELEPHC_BIN=/absolute/path/to/elephc` to select another complete SDK.
On workstations with the author's build queue installed, the script uses it
automatically. The queue is optional on other machines.

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
`/blocks`, `/hooks`, `/render` and `/health`. `/serialize` accepts POST only.
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
themes, KSES or installed plugins.

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

Shortcode processing uses `ignore_html=true`: shortcode-looking text inside
HTML attributes, comments and CDATA is not expanded. Attribute expansion with `ignore_html=false`
requires KSES and fails explicitly in this profile when encountered. Invalid
tag registration and invalid callbacks raise an exception instead of using
WordPress's translation/notice subsystem.

The renderer formats content and expands its registered shortcodes. It is not
an HTML sanitizer. Same-tag nesting retains WordPress's original regex behavior;
callbacks that process enclosed content explicitly invoke the core again.

## Verified behavior

```bash
python3 scripts/test-elephc.py
```

The integration check compares **22 fixtures** against the unmodified
WordPress parser running on the host PHP interpreter. It compares the patched
PHP implementation and the native CLI with that reference.
**31 hook scenarios** are independently compared with upstream PHP, patched
PHP and the hooks CLI. Another **70 rendering fixtures** compare original PHP,
patched PHP and the rendering CLI. Another **58 serialization fixtures** compare
original PHP, patched PHP and native output, reparsed trees and the next
serialization pass, with **22 invalid-input cases**. The server checks all four
corpora twice through one worker for **566 HTTP checks**, including routing,
body limits, response MIME types and request isolation.

| Request | Result |
| --- | --- |
| `GET /` | HTML demo, with a nested document parsed during the request |
| `GET /blocks` | Parsed demo as JSON |
| `GET /health` | Version/profile information, `database: false` |
| `POST /parse` | Parse a raw UTF-8 block document as JSON |
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
seven selected functions from wp-includes/formatting.php
five selected functions from wp-includes/blocks.php
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
types, closure adapters for regex callbacks, callback resolution and an explicit KSES
boundary. Small adapters cover the pinned SDK's missing `preg_quote` and
`stripcslashes`, array-search `str_replace`, typed string replacement maps and string-value
intersection of shortcode tag lists. Empty-subject guards avoid a pinned
native regex runtime crash while retaining the reference output.
They are exercised through the upstream differential corpus.

`formatting.php` and `blocks.php` are first patched as complete original files,
then reduced to their selected functions using byte ranges and per-function
hashes recorded in the manifest. Preparation verifies the complete patched source, each selected
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
downloads a fresh WordPress archive, applies the patches, compiles all five
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

`elephc/probes/regex-empty-subject.php` separately reproduces a native runtime
crash on an empty `preg_replace` subject. It is excluded from normal builds and
verified application tests. Compile a copy inside a native project with the
locked PCRE2 manifest and lockfile. The content profile has explicit empty-input
guards and passes the empty-input differential fixtures.

See the [porting log](docs/elephc/porting-log.md) and
[patch maintenance notes](patches/README.md). WordPress and this experiment
are distributed under [GPL-2.0-or-later](LICENSE).
