# WordPress core parser experiment

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
They do not invoke WordPress routing, themes, block rendering, hooks, plugins,
the REST API or `WP_Query`.

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
The verified native host executables are 1,757,528 bytes for the CLI and
3,894,208 bytes for the HTTP server.

## Follow-up direction

A broader CMS experiment should first address the UTF-8 compatibility loader's
control flow and repeat the `SHORTINIT` diagnostic to discover the next real
blocker. A database-backed bootstrap, hooks, filters, plugin discovery, themes
and arbitrary block rendering require separate verified profiles. The parser
experiment does not establish their compatibility.
