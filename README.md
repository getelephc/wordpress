# WordPress on Elephc

A first AOT experiment with the real WordPress Gutenberg block parser.
WordPress **7.1.4**, Elephc **0.27.8**, PHP profile **8.4**, Linux x86_64.

The experiment compiles three WordPress core classes into a native CLI and an
HTTP server. Both parse block documents at runtime, including nested blocks,
JSON attributes, custom namespaces, freeform HTML and malformed delimiters.
The HTTP adapter and demo page are specific to this experiment.

This is a core subsystem port. The complete CMS, database, plugins, themes,
block rendering and `wp-admin` are outside the current profile.

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
PCRE2 dependency, and produces `elephc/runtime/parse` and
`elephc/runtime/server`. It uses `elephc` from `PATH`; set
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

The demo, parsed blocks and health endpoints are `/`, `/blocks` and `/health`.
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

## Verified behavior

```bash
python3 scripts/test-elephc.py
```

The integration check compares **22 fixtures** against the unmodified
WordPress parser running on the host PHP interpreter. It compares the patched
PHP implementation and the native CLI with that reference, then runs
**62 HTTP checks**, including the same fixtures twice through one native worker.

| Request | Result |
| --- | --- |
| `GET /` | HTML demo, with a nested document parsed during the request |
| `GET /blocks` | Parsed demo as JSON |
| `GET /health` | Version/profile information, `database: false` |
| `POST /parse` | Parse a raw UTF-8 block document as JSON |
| `HEAD /` | `200`, empty body |
| `GET /missing` | `404` |
| `GET /parse`, `POST /blocks` | `405`, appropriate `Allow` header |
| Request body over 64 KiB | `413` from the native transport |

The fixture corpus covers Unicode, nested groups, void blocks, freeform text,
JSON data, invalid JSON, missing and mismatched closers, and parser reuse.
These checks establish the behavior of this corpus, not general WordPress or
PHP compatibility. No performance comparison has been made.

The tests start a temporary native server on an available loopback port and
stop it automatically. Set `PHP_BIN=/absolute/path/to/php` to choose the
reference interpreter. The native host workflow was verified with PHP 8.5.12.

## Sources and patches

[`elephc/upstream.json`](elephc/upstream.json) pins the official
[WordPress archive](https://wordpress.org/wordpress-7.1.4.tar.gz) by SHA-256.
`scripts/prepare-elephc.py` downloads it into the ignored `upstream/` directory
and extracts the complete original tree for inspection and reference checks.

Only these classes enter the compiled graph:

```text
WP_Block_Parser
WP_Block_Parser_Block
WP_Block_Parser_Frame
```

Three file-level patches add explicit storage types, narrow parser frames,
replace a constructor passed directly to `array_push`, and adapt token capture
to Elephc's three-argument `preg_match`. The original regular expression and
parser state machine remain. Captures use numbered groups and byte offsets
are recovered from the matched suffix.

Original source, patch and generated source SHA-256 hashes are checked before
compilation. Patches apply with zero fuzz to an isolated, generated
`elephc/runtime/core/` tree. Original files remain untouched.

```bash
python3 scripts/prepare-elephc.py --check
python3 scripts/test-clean-room.py
```

The clean-room check exports maintained source files into a temporary directory,
downloads a fresh WordPress archive, applies the patches, compiles both
executables and repeats the reference/HTTP checks. It reuses the installed SDK
and native dependency cache, and removes its export when finished.
PCRE2 10.47 is pinned in the native manifest and lockfile.

## Complete WordPress probes

```bash
./scripts/check-upstream.sh
```

This diagnostic intentionally returns `1` on Elephc 0.27.8. It checks three
unmodified source paths without executing them:

- Standard bootstrap: conditional `ABSPATH` definition prevents resolving a
  subsequent include path.
- `SHORTINIT` with `ABSPATH` defined in advance: `compat-utf8.php` contains
  unsupported `goto` statements and labels.
- Original standalone parser: unsupported `preg_match` arguments and stack
  object/storage inference prevent compilation.

These are observed first blockers, not an exhaustive compatibility audit.
No diagnostic starts an installation or connects to a database.

See the [porting log](docs/elephc/porting-log.md) and
[patch maintenance notes](patches/README.md). WordPress and this experiment
are distributed under [GPL-2.0-or-later](LICENSE).
