# WordPress on Elephc

This experiment starts from the **complete original WordPress 7.1.4 distribution**
and ports its real bootstrap to Elephc using full-file compatibility patches and
explicit fixtures, following the sibling Tempest and Laravel experiments.
The primary build does not extract function ranges or use a PHP-hosted extension.

**Current status:** the complete patched source graph compiles and links through
the ordinary public build command. Its isolated native CLI bootstrap and all five
runtime documents match unmodified WordPress under PHP, with no unexpected
runtime diagnostics. This includes all 116 registered core blocks and 109 pattern
hashes, static and dynamic rendering, nested Unicode content, navigation and the
video shortcode.

All 47 native control groups pass 230,504 comparisons with IR optimization
on. A separate export containing only maintained source and the verified original
archive reconstructs the complete tree, passes the controls with IR optimization
off, builds another native executable and repeats the bootstrap/document parity.
Its temporary export is removed after verification.

The verified build produced 17,756,316,802 assembly bytes and a
4,221,808,432-byte combined object containing
2,109,009,393 code bytes. The public linker preserves allocated
contents and keeps function folding disabled. These are correctness results;
this profile makes no performance claim.

The execution fixture covers single-site initialization, the bundled default
theme, in-memory SQLite and explicit application probes. Full HTTP request
handling, administration, authentication, arbitrary plugins and network activity
remain outside the verified native profile. Compiling the complete graph does
not establish that every WordPress path works. Read the patch manifest and the
boundaries below before extending the fixture.

The Multisite constant control compares the complete original defining routine in
nine configurations. Its two mutually dependent aliases use an explicit native
runtime inventory; network database startup remains unverified.

## Requirements

Run directly on a Linux x86_64 host, without Docker or DDEV:

- A complete Elephc SDK, including its runtime archives. The tested main revision
  is `f20729406216d21ce99841cf4636b771db9874dd`, with PHP compilation profile 8.4
  and the maintained [compiler compatibility patch](patches/compiler/README.md).
  The release banner alone reads `0.27.8`; use the revision and hashes in
  [toolchain.json](elephc/full-source/toolchain.json) to identify this SDK.
- Python 3.12 or newer, Bash, `patch`, a C compiler/linker and GNU binutils.
  The tested development SDK requires glibc 2.39 or newer.
- LLVM `ld.lld` on `PATH`, or its executable selected with `WORDPRESS_LLD`.
  The build can also discover `rust-lld` through an active `rustc` on `PATH`.
  The [section-placement compatibility linker](scripts/wordpress-link.py)
  preserves generated instructions and distinct function addresses, and records
  its allocated-content checksums in `elephc/build/full-source/bootstrap-link.json`.
- The libsodium development library and headers available to the linker.
- PHP 8.4 or newer with PDO SQLite and mbstring for the reference checks.
- Internet access for the pinned WordPress archive and locked native dependencies.
  The complete retained Requests graph also links locked Curl and its SSH, HTTP/2,
  TLS and compression dependencies through the SDK native package manager.
  The retained fileinfo bridge uses locked libmagic 5.46 with its embedded magic
  database.

The measured complete build reaches about 35 GiB of compiler resident memory
and produces 17.76 GB of assembly, plus the object and executable. The 512 MiB
heap setting applies to the compiled program.

Use `elephc` on `PATH` or set `ELEPHC_BIN` to the executable in another complete
SDK. No version manager or workstation-specific helper is required.
`.elephc-version` records the tested main revision.

## Obtain the tested development SDK

The SDK metadata records the original GitHub Actions artifact and its checksums.
That run no longer exposes downloadable artifacts, as checked on 2026-11-23.
Use the [pinned compiler and bridge source-build instructions](patches/compiler/README.md)
to construct a complete local SDK from the same base revision and maintained
compiler patch. Keep its compiler and runtime archives together, then set
`ELEPHC_BIN` to that compiler.

A source-built SDK has its own binary identities. The hashes in
`toolchain.json` identify the SDK used for the recorded results; they do not
describe every local build of that source. Run the PHP/native controls for your
SDK before relying on those results. Another complete SDK can also be selected
with `ELEPHC_BIN` and needs its own verification.

## Reproduce the source and PHP baseline

```bash
python3 scripts/prepare-wordpress.py
python3 scripts/prepare-wordpress.py --check
./scripts/build-wordpress.sh --php-reference
```

Preparation verifies all 3,782 original dependency files against the official
archive, copies the complete tree to `elephc/build/full-source/wordpress`, applies
the [full-file patch series](patches/full-source/README.md), and adds the explicit
[fixtures](elephc/full-source/fixtures). Original files in `upstream/` are never
edited. Original, patch and resulting file hashes are checked; generated source
inventories are written to `elephc/build/full-source/source-manifest.json`.

The PHP comparison executes the original `wp-load.php` and `wp-settings.php`
from a temporary copy of the verified distribution, then the complete patched
counterpart. Both receive the same explicit `wp-config.php` fixture. Both use an isolated **SQLite database in memory** containing
site options and empty post and taxonomy tables. Original theme-style queries
execute against those tables. It never connects to a database server. The fixture selects the
original Twenty Twenty-Five theme, English locale and no installed plugins.
There is no persisted database or captured WordPress HTML response. The probe
also renders static and dynamic blocks through original `do_blocks`, followed
by original `do_shortcode`. The native executable accepts another block document
as its first argument.
The bootstrap also returns six localized number results from original
`number_format_i18n`, including half-up rounding, grouping and negative zero.
Two original `get_weekstartend` calls check leap-day and year-boundary ranges.
Repeated original `wp_normalize_path` calls check Unix, Windows, network-share
and Unicode paths through its persistent cache.
The probe extracts the original theme's file headers through `get_file_data`,
including a closure filter for additional header names, and returns the original
post-format slug map.
It also verifies that `wp_get_wp_version` keeps the version read from the original
file when the global version is temporarily changed and restored.
The original theme-directory search runs with a forced filesystem scan and then
through its cache. The probe compares sorted theme names without exposing local paths.
It also passes an `ArrayIterator` to original `wp_insert_user` with a negative ID,
checking iterator consumption and the original `invalid_user_id` result before
password hashing or user-table access. Account creation remains outside this probe.
A whitespace-only password-reset request checks the original `empty_username`
validation before user lookup or mail. The `lostpassword_errors` filter must
return its documented `WP_Error` object; the checked projection preserves its identity.
An empty registration checks both original credential-validation errors before
account creation. Asset registries and header-video validity objects also retain
their documented `WP_Scripts`, `WP_Styles` and `WP_Error` contracts through checked views.
The script and style factories expose their documented concrete returns after
their original initialization guards. The probe checks repeated-call identity
and original missing-handle query results for both registries.
Widget editor dependency checks use the existing script/style identity views at
all four original query receivers. The probe executes four real queue scenarios,
checking null returns, original diagnostic messages and ordering, and unchanged
queue contents. It restores both queues and removes its observer. The fixture's
`WP_DEBUG=false` checks diagnostic actions without emitting PHP warnings.
The original comments-template guard also returns before querying when no post is active.
Its two property references retain the original `WP_Query` object through a checked
view. The template-created `comments_by_type` field has an explicit native slot;
property existence and reflection before initialization remain outside the fixture.
The property, global-alias and iterator controls have 3,192 native/PHP comparisons;
comment querying and template rendering are not verified by this guard.
Widget conversion retains its original live sidebar-cache reference through a
named global. The property controls add 672 comparisons for fallback loading,
front/admin behavior, nested first-match updates and independent snapshots.
The root probe calls the actual original conversion twice with an isolated
in-memory sidebar cache, then restores the prior value. Admin option persistence
and the complete widget rendering lifecycle remain outside that probe.
The bookmark probe calls original `get_bookmark` with an existing global record,
checking both lookup branches and object identity before any links-table query.
Its native getters use named-global storage; PHP reference getters retain the
original `$GLOBALS` reads. The compiler patch preserves live aliases to these
named globals. General `$GLOBALS` symbol-table behavior remains outside this proof.
Database method calls use 1,105 checked `wpdb` receiver views in 96 complete files.
They preserve the actual object, subclass dispatch, SQL text and argument evaluation
order. The 341 SQL comparisons include bounded receiver rebinding and 93 checks
loading the complete original database, hook and error files. Those checks compare
prepared scalar/array arguments, quotes, percent escaping, Unicode, cached values,
row and result output modes, column reads and fixture option lookups in both IR
modes. They do not establish full WordPress SQL coverage. The fixture preserves
the parent's mixed return declarations and applies original query filters before
SQLite execution. `get_var` initializes its optional local result array before
the original branch, and `prepare` projects its exact placeholder count to an
integer before the original strict comparison.

The cron control loads the complete original scheduling, hook and error files
and their prepared counterparts. Its 45 comparisons cover large timestamp keys,
recurring and single events, duplicates, validation errors, reads and serialized
request-local option storage. A transient removed string key selects associative
storage for a fresh native cron map. Mixed event-field views retain strings,
Boolean schedule flags and argument arrays; natural comparison accepts integer
keys through PHP string conversion. HTTP dispatch, locks, callback execution,
unscheduling and the complete cron lifecycle remain outside this control.
Two reads in the original taxonomy upgrade keep mixed array storage explicit
where the same local later holds a flat map. Its writes and upgrade algorithm remain.
The dependency queue retains its original empty-array default in mixed storage,
allowing original prepend calls to update the property by reference. The probe
also runs the original `sizes=auto` CSS enqueue routine and inspects its first
queued handle, registration, inline CSS and hook removal.
Term-cache updates retain their original clone and `object_id` removal. The
SimplePie parser's header field keeps its original array default in explicit
mixed reference storage. Deletion uses identity views of the documented `WP_Term`
and SQL `stdClass` records; other record classes are outside this fixture.
The probe checks actual `WP_Term` and SQL `stdClass` cache records, retained source
fields and independent cached values after source mutation. It also checks method
metadata on the original database fixture, through the existing `wpdb` identity view.
Twenty dynamic method-metadata calls in included admin, mail, XML and feed paths
use the explicit platform boundary. PHP retains the original checks; native
invocation reports unavailable metadata. These paths are outside the verified
execution profile. The gallery's ordered pair of theme JSON candidates uses
literal metadata checks and the original core static call.
Navigation's three unstable-location helpers remain under their original
Gutenberg plugin guard. Their unresolved sibling calls use the existing explicit
plugin boundary. The plugin is not loaded by this fixture, so these paths are
outside native execution coverage. The boundary inventory distinguishes direct
API calls, constructor calls and named unsupported adapters; counts cover prepared
original files and do not establish execution.
Property metadata supports string names on exact core Ability, Ability Category,
Block Type, Post and Term objects, plus `stdClass`. Its checked inventory preserves
declared static and non-public names, removed declared fields and dynamic null
keys. Other classes, subclasses, class strings and non-string names remain explicit
boundaries. The probe also constructs original Ability and Category objects and
checks their getters and prepared metadata.
The complete original REST context filter retains its recursive algorithm. JSON
object mutation uses fresh `stdClass` identity views; array deletion uses the
checked value-array adapter. Other object classes and external element reference
cells remain outside this fixture. The root checks 28 calls over validated JSON
data and schemas, including nesting, array item filtering and clearing, numeric
keys, pattern/additional properties, aliases and explicit key order. Fixture JSON
uses `JSON_THROW_ON_ERROR`. Combining schemas and REST routing remain outside
these executed cases.
The probe also calls original `image_edit_apply_changes` on JSON history records.
An explicit editor fixture records rotate, flip and crop calls while the original
routine normalizes and combines operations, applies the original filter and reads
size through the original base editor. No image is loaded, modified or written.
The admitted history records are `stdClass` objects, projected without copying
before property updates and deletions.

The comparison also checks nested Customizer setting operations and deprecated
template-theme transformations against unmodified WordPress, plus live hook
mutation, lazy block-list cursor behavior and 82 bundled AI enum, DTO and PSR-7
groups. Additional runtime-input cases cover empty content, nested Unicode
blocks, a dynamic site title with custom attributes and a local MP4 video
shortcode. A fifth document renders eight navigation blocks, checking repeated
names, Unicode escaping, numeric-string labels and overlay counter behavior.
The navigation counter uses a local array and complete late-static writeback
after its original increment, avoiding an unsupported static element reference.
`--native` requires
those document renderings as well as the default bootstrap to match. Its report is
`elephc/build/full-source/bootstrap-verification.json`.

Another 60 PHP comparisons check lexical callbacks in the complete URL prefixer,
HTML processor, attachment controller, font-face controller and plugin dependencies. Fixed private `self` targets use their
explicit class names inside closures. The HTML bookmark callback forwards through
a private helper that retains the exact parent implementation, key and void
return. The added private method changes reflection metadata. The font schema
captures its protected method descriptor after the cache guard, retaining the
original object, subclass override, scalar/list behavior and cached callback.
These PHP checks
cover bounded callback behavior; upload dispatch remains outside their scope.
The inactive-dependency name callback runs as a private named method with a
lexical descriptor, retaining its live class cache. Four PHP-only checks seed
request-local caches, validate a nonce and intercept AJAX termination. They
verify rejection responses without installing or activating plugins. This
additional private method also changes reflection metadata.

## Native controls and bootstrap build

```bash
./scripts/build-wordpress.sh --controls
./scripts/build-wordpress.sh --check
./scripts/build-wordpress.sh
```

`--controls` compiles and runs focused native tests against ordinary PHP, using
shared case files in `tests/full-source/`. They cover libsodium bridge primitives,
binary string spans, natural string comparison, character masks, PHP version
ordering, complete core UTF-8 source files and
in-memory PDO SQLite and reference updates through first-class callables,
closures, method arrays and invokable objects, PHP-compatible regular-expression
quoting, array stack boundary values, query encoding, tokenization, boolean filters
and the complete Requests header dictionary. Additional cases cover source-adapted integer/string packing, binary
string searches, substring comparison, C escaping and optional constant guards.
Bundled AI and source-local `compact()` controls also run in this suite.

PSR stream size getters project the two non-null cache returns through the
existing checked integer contract. Original untyped storage, nullable return,
cache guards and invalidation remain. Six complete-class native/PHP scenarios
add 66 comparisons for zero, binary, Unicode and large memory streams: repeated
and stale cached sizes, external writes, method-write invalidation, resource
identity on detach, reattach, closed/detached null results and exact content
hashes. The URI port getter similarly returns null explicitly and projects its
non-null integer value without changing original port storage or normalization.
Thirty valid scheme/port cases and six invalid-port cases add 234 comparisons for
standard ports, zero, numeric strings, the maximum port, immutable clones,
scheme changes, string round trips and unchanged state after rejection.
The AI control passes 445 comparisons. These checks do not establish transport
or userspace-wrapper support.

The complete widget factory routes its two guarded instance-ID calls through an
object-typed wrapper of the original `spl_object_id` builtin. Twenty registry
scenarios add 140 native/PHP comparisons for constructor hook arguments, instance
and class registration, repeated registration, distinct instance keys, lookup
order, repeated removal, live registry aliases and independent array snapshots.
Test widget classes isolate
the factory registry; widget constructors, options and callback registration
remain outside this control.

Toolbar nodes are always created as `stdClass` by the original final node factory.
The binding pass uses a fresh checked view only when deleting their original
`group` property. Eight complete-class tree scenarios add 64 native/PHP comparisons
for root replacement, automatic groups, nested containers, order, orphans,
live node handles, cloned public snapshots and the one-shot bound state.
Its toolbar environment and
rendering boundaries throw; a finite array-default fixture supports node setup.

The complete ability class retains its original three null annotation defaults
in explicit `mixed` static storage, with boxed late-static reads at both consumers.
Thirty metadata scenarios add 270 native/PHP comparisons for omitted defaults,
null/false/true annotations, extra metadata, public/REST flag precedence, getters
and independent nested snapshots. English translation is an identity fixture; ability filters, schema
validation and callback execution remain outside this control. Property type
reflection and untyped subclass redeclarations remain compatibility boundaries.

The original `WP_Block` lazy attributes getter and dynamic cache remain in place.
Its refresh lookup uses the existing checked array view. A fixture with the same
lazy cache adds 36 comparisons for key presence, null/false values, one-time
initialization, independent copies, cache reuse and invalid inputs. Its finite
Boolean expectations use explicit casts; the original nested Boolean packing
failure remains a compiler diagnostic.

The attachment controller uses a fresh checked `stdClass` view only to remove the
original ID from a prepared attachment record. Eight native/PHP record cases add
40 comparisons for missing/null/false/zero/string IDs, preserved field order and
values, object aliases and prior snapshots. The property/cursor group passes
3,087 comparisons. Non-`stdClass` prepared results fail at the view; complete image
editing, filesystem writes, attachment insertion and REST dispatch are unverified.

REST menu auto-add removal uses the same value-array splice adapter already
verified for nested menu options and omitted replacement. Its option read,
update and action remain original. Saved-option updates and REST dispatch are
outside the executed splice controls.

HTML controls compare UTF-8 entity decoding and escaping, including both
`double_encode` modes. The token-map control loads the complete original class
and compares valid precomputed short and long tables, lookups, offsets, match
lengths and array conversion. Its table builder, source exporter and invalid-state
diagnostics are outside the executed control.
Results are recorded in
`elephc/build/full-source/controls.json` only after every comparison passes.
An interrupted or failing run retains completed groups in `controls-progress.json`.
Use `python3 scripts/test-wordpress-controls.py --only NAME` for a focused group;
its separate report does not replace the complete report.
The Sodium bridge calls the real system library. The original WordPress Sodium
polyfill supplies the crypto reference when host PHP lacks that extension.
The UTF-8 group also loads complete `compat.php` and checks the PCRE support
probe, its cached results and deprecated argument traces. Conditional public
`utf8_encode` and `utf8_decode` polyfills remain outside its executed checks.
The complete original `clamp` polyfill uses a distinct source name at its two
oEmbed and typography callers, avoiding interception by the numeric builtin.
It preserves scalar/null comparison order, selected value types and NaN-bound
errors across 10,648 case groups. The combined UTF-8/compatibility control passes
26,350 comparisons. Array, object and resource operands fail explicitly; native
generic array comparisons remain outside this proof.
The Query pagination control loads complete `blocks/query.php` and checks nested
query stacks, dirty flags and callback identity and removal. Its registry and
hook boundaries are explicit fixtures; script/style enqueueing, HTML rewriting
and content-query rendering are outside that isolated control.
Persistent string cache cells retain the original empty-string sentinel and
mixed values for the admin update nonce, temporary-directory and spacing caches.
Repeated reads, writes and independent value snapshots are checked natively.
Reflected static-variable identity and admin nonce HTML remain outside this proof.

The optional public instance `make_subsize` guard uses native callable metadata for the
fixture's core GD/Imagick editors. A PHP policy check reads both original classes
and verifies public instance visibility and the absence of magic-call fallback. Custom
editors with non-public methods or magic dispatch, and image processing itself,
remain outside this execution proof.
Initially empty static arrays also use persistent property cells. Their append
operations pass through the existing array adapter to preserve returned value
snapshots. Reflection of adapted static variables and shutdown ordering remain
outside the fixture.
Nullable data caches retain their original null guard and lazy population through
the existing mixed value cell. The property/cursor control checks both null and
empty sentinels, scalar and array writes, presence and independent snapshots.
Mapped Diff retains live operation-property references through checked identity
views. Its complete class, native engine and classic renderer match six original
PHP inputs with three context configurations and repeated rendering. The 132
observations include reconstructed lines, operation records, LCS and exact output.
The renderer keeps its false/array block lifecycle in mixed storage and initializes
guarded loop-carried coordinates before their earlier textual reads. These checks
run inside the property control.
The array-mutation control passes 8,993 comparisons, including nullable writes,
nested versus flat map lifecycles, property walks and independent snapshots.
The update payload walks retain the original callback and local `array_walk`,
with `finally` writing back partial changes. Their admitted values are JSON arrays
and records. External property reference cells, observer callbacks and magic
payload objects remain outside this adapter's proof.
Plugin and theme update record arrays use local value-array deletion for integer
and string keys. The control checks key order, missing keys and independent
source snapshots. External element reference cells are outside this JSON fixture.
The connector provider registry retains its original `is_active` closure and
nested array assignment. An explicit mixed value projection avoids an unsupported
callable-to-string branch during lowering. Callback identity, captured-object
updates, local rebinding, exception fallback and independent snapshots have 108
additional comparisons. Provider transport remains outside these controls.
The root fixture also checks the original list-table compatibility methods across
five fields, including removal, rewriting and an unrelated live `items` alias.
The patch expands only the five original deletion names; extra compatibility
fields in custom subclasses fail explicitly. The fixture skips admin screen
construction. The native bootstrap compares these root probe results with original PHP.
User-field removal uses a checked `stdClass` view only at the original guarded
deletion. The PHP root checks 21 record operations and legacy `id` actions,
including null retention, false removal, numeric-name keys, aliases and snapshots.
Other user-data record classes are outside this mutation proof.
`WP_User_Query` retains its original private compatibility guard and uses literal
deletion only for its two admitted fields, `results` and `total_users`. Twelve
actual original/patched PHP lifecycle cases check defaults, set/get/isset/unset
and rewriting, plus invalid-field returns and an unchanged live `query_vars`
alias and independent snapshot. The empty constructor executes no query.
SQL execution and reflective changes to the private compatibility inventory
remain outside this fixture. The native bootstrap verifies these supplied-input operations.
Query reset keeps the original `init()` and `unset()` operations. Four nullable
result fields use explicit mixed storage, preserving the tested removal, aliases,
snapshots and fresh assignments. Type reflection and direct reads after removal
before reinitialization remain boundaries of this fixture. The native bootstrap verifies these root checks against original PHP.
Customizer menu conversion retains the original record deletions, real `WP_Post`
construction and attribute, description and post filters. The PHP fixture checks
three supplied custom-link records and nine ordered filter events. Only value
acquisition and manager construction are isolated; preview/save, other menu-object
URL discovery and author/type-label fallback remain outside this probe. A checked
`stdClass` view preserves the converted record identity. Sixty-seven fixed Customizer
loader includes use equivalent paths relative to their original file, allowing
isolated compiler discovery. The native bootstrap verifies these supplied-input operations.
The actual menu preview filter also matches original PHP across nine replacement,
insertion, deletion, placeholder, sorting and other-blog cases, retaining survivor
object identities and independent input arrays. Its two original list splices use
an explicit value-array fixture: 420 native cases add 5,880 comparisons for integer
offsets, integer/null lengths and empty/single/multiple array replacements. String
keys survive, integer keys are reindexed, aliases update and object handles stay
shared. External element reference cells and scalar replacement coercion remain
outside this fixture. Manager construction, preview setup/save and persistence
are isolated. The native bootstrap verifies these supplied-input cases. The pure auto-add option transformer also matches
original/patched PHP in 128 cases, including strict membership, duplicate removal,
null initialization, sparse/negative keys and the setting's own appended ID.
Its nested splice uses the same fixture; native controls cover nested reference
arguments and omitted replacements. No saved-option reads, writes or manager
save lifecycle are exercised.
Customizer settings, panels and sections keep their original theme guards and
`current_theme_supports` algorithm. Their array arguments use
`call_user_func_array`, preserving positional, named and mixed-key variadic tails
without an arity limit. Six native forwarding cases contribute 18 comparisons
to the 35-comparison callable control. Sixty actual original/patched PHP checks
verify feature branches, false filter results and exact filter argument traces.
The native bootstrap verifies these supplied-input feature checks. Manager
construction and user authorization remain outside this fixture.

Customizer template constructors retain their dynamic class names and original
arguments; all three values use explicit mixed storage. Twelve actual
manager registration/render cases match original PHP, preserving empty registries,
duplicates, parent constructors, manager identity and captured output. Inner
component templates use recording fixture subclasses; the original print wrappers
and default control templates still run. The final manager is allocated through
Reflection without its constructor, isolating setup and persistence. Separate
native/PHP probes verify dynamic arguments beside scalar constructors and
constructor-free final-class allocation with defaults and owned references.
These probes do not establish complete native manager or UI execution yet.

Remote theme JSON records use a fresh checked `stdClass` identity view only for
the original `slug`, `screenshot_url` and `author` deletions. Five native/PHP
scenarios verify missing/null/false fields, object identity, shared snapshots and
the lingering reference from the original by-reference loop, adding 35 comparisons.
The network request, authorization and complete theme endpoint remain unverified.

Theme JSON file and registered-block caches retain late static binding through
local value-array updates and whole-property writeback. Twelve native/PHP
scenarios add 181 comparisons for inherited/shared and separately declared caches,
reads, live property aliases and independent snapshots, with native eval enabled.
Array-literal collection uses explicit mixed views of static property values.
The actual resolver also passes 46 original/patched PHP cases covering both JSON
files, cached null/false/scalar/array values, false-like paths and block inventories.
The fixture restores the original cache state. Complete native resolver execution,
external element reference cells and arbitrary eval programs remain unverified.

The complete SimplePie and Requests IRI classes independently pass native/PHP
byte conversion checks. Their original conversion loops and lazy caches remain;
only the two constant high-byte alphabets are precomputed as the exact 128 bytes
from `0x80` through `0xFF`. The binary control has 4,742 comparisons, including
266 IRI byte sequences, repeated calls and eight non-string Requests guards.
IRI parsing, normalization, IPv6 processing, network transport and general string
`range()` behavior remain outside these conversion checks. Comparator tests
observe the [documented sign of `substr_compare()`](https://www.php.net/manual/en/function.substr-compare.php),
whose raw return magnitude is unspecified.

SimplePie item enclosure lists retain their original `null` defaults through
explicit mixed storage at 32 assignments. Forty-eight list appends use a
null-or-array adapter that preserves earlier value snapshots and live whole-list
aliases. Five native/PHP scenarios add 30 comparisons; untouched lists stay null.
Sixty-five registry calls use fresh checked `SimplePie\Registry` identity views,
following the original typed setter. Native/PHP controls add 217 comparisons for
base identity, subclasses and receiver selection before argument-side rebinding.
They use controlled factory overrides. Actual enclosure aggregation, reflection
factories, external element references and complete feed parsing remain unverified.

Requests response status handling retains its original dynamic exception selection.
The status property uses mixed storage with its original false default; dynamic
constructor arguments use mixed identity views and only the local constructor
class name loses its optional leading namespace separator. A control loads 38
complete source files and passes 360 status/success/redirect-policy combinations,
1,837 total port and exception comparisons against original PHP. It verifies
strict redirect policy, numeric/string status distinctions, exception class,
message, type, code, reason and response-data identity. Constructor-free allocation
isolates these methods; header/cookie constructor fixtures throw if called.
Normal response construction, transport, routing, arbitrary status types, type
reflection and property reads after unset before reinitialization remain outside
these checks.

Fixed core loader paths use the same verified `wp-includes` targets through
paths relative to each original source file. A complete audit additionally
rewrites 585 literal includes in 57 files, including root bootstrap and upgrader
loaders. Every target exists in the immutable archive. The fixture uses the
standard archive layout; custom `ABSPATH`/`WPINC` remapping is outside this loader
profile. Dynamic loaders retain their separate documented boundaries.

The introspection control passes 1,443 comparisons. Its field-storage schemas are
checked against five complete original PHP class definitions, including every
instance field after removal. Original source hashes and the native metadata
fixture are recorded in `elephc/full-source/property-provenance.json`.
Stream controls load the complete AVIF, POMO and PclZip files. They compare reads, seeks,
EOF, AVIF headers and format recognition, plus all four POMO reader variants and
their 32-bit integer decoding. PclZip's uncompressed copy utility is checked at
block boundaries, with binary content, weak scalar sizes and partial copies
before errors. A one-name runtime inventory preserves the executed block-size
definition, with the original declaration supplying its default value. PHP
controls use the engine's actual constant APIs. Explicit fixture predefinitions
also have comparison cases. Full AVIF image-feature extraction, compressed ZIP
operations, direct external native constant definitions, raw constant enumeration
and the removed mbstring overload mode remain outside the
executed control. Dynamic native I/O options use checked integer conversions;
resource error precedence and non-seekable transports are outside this fixture.

The root bootstrap has a separate executed-presence inventory for 65 original
configuration and bootstrap constant names. Every original `define` call and
value remains in place; the fixture records successful definitions and preserves
guards on later invocations. Four unconditional entry definitions are seeded
after platform initialization. The name list and source/helper checksums are in
`elephc/full-source/constant-presence-provenance.json`. The constant-policy control
compares 142 values with PHP, including false presence, repeated initialization,
17 runtime String data views, first-definition retention and six integer time
units. Original String definition expressions are captured once and still pass
to `define`; the PHP data view reads the real constant. Pre-platform runtime
String overrides, non-String values for these 17 names and general dynamic
constant introspection remain outside this fixture.

Nine original socket calls in seven complete files use checked scalar operands
while retaining native `fsockopen`. The adapter preserves nullable timeout defaults
and initializes error output references after scalar validation. A temporary TCP
peer on loopback compares six connections with PHP, covering integer, numeric
string and integral float ports, null and numeric-string timeouts, successful error
outputs and payload exchange. The stream control includes these 24 comparisons
and passes 2,345 total. Nullable float conversion brings the weak numeric control
to 6,790 comparisons. Connection failures, timeout expiry, TLS and complete
XML-RPC, mail and FTP workflows remain outside this success-only proof. The peer
and generated test files are cleaned up after each run.
PHPMailer keeps its original static language declaration and publishes the
original dictionary through a mixed identity view. The root loads the complete
original class, checks 29 lazily populated English entries, language-code fallback
results and independent getter snapshots, then restores English. Translation-file
parsing and mail delivery remain outside that executed probe.
The complete-file mailer control checks eight fallback dictionaries and two SMTP
error-record snapshots without sending mail. The language regex uses the PCRE2
capture adapter and an explicitly mixed output local. SMTP error storage also
uses mixed values, preserving string and integer codes. These controls require
clean runtime diagnostics; reflected property declarations remain outside their
scope.
Regex controls also compare scalar and Stringable operand conversion with the
original Vimeo and YouTube shortcode patterns. Complete-file patches project
dynamic regex text operands to strings while retaining the original PCRE calls,
capture references, flags and offsets. Invalid-operand diagnostics and argument
validation timing remain outside this finite profile.
Replacement calls use the checked PCRE2 adapter with all 27 unpaired ASCII
punctuation delimiters, leading ASCII whitespace, escaped delimiters and PHP's
ignored `S`, `X`, space, carriage-return and newline modifiers. Paired delimiters,
non-ASCII delimiters, duplicate named captures and
PHP warning-buffer behavior remain outside this adapter.
Regex splitting uses the same PCRE2 capture boundary, preserving limits,
delimiter captures, byte offsets, empty-match progress and NUL subjects.
Number formatting preserves integer precision, weak numeric conversions,
PHP half-up rounding and full byte-string separators. The native adapter supports
decimal precision from -22 through 22 and explicitly rejects higher precision.
The fixed English locale and deprecation-diagnostic boundaries still apply.
Calendar controls compare explicit UTC `mktime` and `gmmktime` components,
including weak scalar conversion, normalization, leap years and Unix boundaries.
Checked projections preserve the original component variables and calendar calls.
UTC formatting preserves explicit timestamp -1, signed Unix fields, composite
formats and escaped literals, including a terminal escape's NUL output. Native
format strings containing NUL, implicit current-date calendar components,
extreme ranges and other timezones are outside this finite fixture.

`--check` attempts the complete native WordPress bootstrap. The default command
also compiles and links it, producing `elephc/build/full-source/bootstrap` after a
successful build. It uses `--ir-opt=off`, `--gc-stats` and a 512 MiB heap.
The ordinary public build compiles and links the complete graph, with identical
allocated contents and function folding disabled. The verified executable matches
the original-PHP bootstrap and all five runtime documents.
The current full graph emits about 17.8 GB of assembly and a 4.2 GB combined
object; temporary assembly parts need additional disk space. The checked
compiler reached approximately 34 GiB of resident memory. Source-only native
verification performs another complete build inside its temporary export.

Native controls use their default optimization setting; the source-only run also
checks them with IR optimization off. Allocation statistics go to stderr. Require
native parity for your local SDK explicitly:

```bash
python3 scripts/test-wordpress-bootstrap.py \
  --native elephc/build/full-source/bootstrap
```

The local URL stored in the fixture is `http://127.0.0.1:9000`. This fixture does
not start a server.

Reproduce the verified controls from an export containing only maintained source
files and the pinned original archive:

```bash
python3 scripts/test-wordpress-clean-room.py --compiler "${ELEPHC_BIN:-elephc}"
# Also require a complete native build and PHP parity inside the export:
ELEPHC_IR_OPT=off python3 scripts/test-wordpress-clean-room.py --native --compiler "${ELEPHC_BIN:-elephc}"
```

The export reconstructs the entire dependency tree, runs the PHP bootstrap
comparisons and compiles the native controls, then removes itself. The portable combined report remains at
`elephc/build/full-source/clean-room-verification.json`. Native mode also retains
linker provenance, unchanged allocated-content hashes and the disabled-folding
check. It does not
claim that the complete native WordPress bootstrap works unless `--native`
succeeds. That mode compiles the exported source and requires original-PHP parity.
`ELEPHC_IR_OPT=off` also checks the exported controls in the bootstrap's EIR mode;
the control report records the selected mode.

## Patch policy and boundaries

Every patch targets a complete original file and records its reason and hashes.
Mechanical rewrites preserve mixed HTML as literal `echo` statements and split
static declarations. Finite-profile changes declare closed source loaders,
unsupported transports or reference-alias restrictions in the manifest. Read
those reasons before treating an API as supported.

Originally untyped named function and method parameters are explicitly `mixed`.
Their defaults and bodies remain in place. Compatible untyped returns use
`mixed` with explicit null fall-through; ancestors with `void` overrides retain
their absent return annotation. Reflected type identity is outside the fixture. Explicit base-method calls share private
implementations so legacy widget constructors retain their original binding.
The original WordPress byte-wise UTF-8 fallbacks provide validation and scrubbing.
Optional constants absent from this configuration retain their `defined()` guards
and raise the original undefined-constant error on an executed read.
Merge, map and filter adapters preserve verified values, keys, ordering and
callback arguments. Externally shared array-element reference cells and exact
native warning-buffer diagnostics are outside those adapters. Binary packing
uses source-collected argument lists; the SDK's generic runtime spread to a
fixed-plus-variadic signature remains a separate compiler boundary. Binary
formats cover integer, byte/string, hex and positioning fields, without floating
point or pointer formats.
The three source `array_combine` calls use the existing native adapter for scalar
key values. It preserves duplicate-key replacement, element order, numeric key
conversion and equal-length errors. Object, resource and array key values remain
outside that adapter; non-array operands retain `TypeError` behavior.
The 33 `array_flip` calls use a fixture for integer and string values, preserving
numeric-string keys, duplicate replacement and order. Non-array inputs retain
`TypeError`; invalid element types raise an explicit native boundary error.
PHP's warning-and-skip behavior for those invalid elements remains outside this fixture.

UTF-8 escaping preserves existing named references according to the selected
doctype and numeric references according to PHP's escaping rules. Those numeric
rules differ from decoding validity. Invalid UTF-8, other charsets and
`ENT_DISALLOWED` remain explicit encoder boundaries. The token-map patch makes
the original boolean-to-string conversion explicit without changing the
original comparison or its case behavior.

The first fixture covers single-site initialization, in-memory site options,
the original default theme and no external network activity. The SQL fixture is
not a complete WordPress database schema. Arbitrary plugins, PHP catalogs,
custom PHP templates, runtime-loaded PHP files, installation and upgrades need
additional source manifests or adapters. RSS XML tree references and feed-link
alias maps are explicitly unavailable in this fixture. Full administrative,
frontend and REST request handling has not been verified natively.

The complete bundled AI graph uses checked projections for original declared
contracts and a finite reflection manifest for its fourteen enum classes.
Checked inputs must already satisfy those contracts; weak scalar coercion and
invalid-input error wording are outside the verified boundary.
String-backed PSR-7 streams use the original standard memory-stream branch at
every size. Custom userspace resource metadata and the large-stream wrapper URI
are outside the profile.
Discovery sees compiled class/interface metadata; external autoloading and checks
that depend on declaration activation order are outside this profile. Optional
Buzz, Symfony, Puli, unscoped PSR HTTP and PSR-16 packages absent from the archive
remain unavailable.
Redis and Memcache cache adapters require extensions outside this native profile.
The SDK lacks DOMXPath; its native constructor and query boundary fail explicitly.
This does not claim native RSS HTML sanitization or XPath support.
HTTP upload transport, URL filtering and warning-buffer inspection raise explicit
unsupported-operation errors. Query decoding accepts UTF-8/NUL-free strings with
well-formed bracket names, at most 1,000 fields, 64 bracket levels and one MiB of
input. Malformed bracket recovery and custom INI separators are outside this profile. The configuration disables
the WordPress fatal-error page handler, leaving native fatal diagnostics active.
Query building accepts scalar and nested array values with at most 64 nested
levels, the two PHP encoding modes and an explicit separator (the default is `&`).

The [compiler probes](elephc/full-source/probes/README.md) isolate unsupported
array references and the base SDK's included-function callback failure. The
maintained compiler patch repairs public included callback descriptors. The
verified bootstrap and focused native controls exercise those descriptors.
The explicit array-cursor adapters preserve the tested request behavior; sharing
internal PHP cursors through external property mutation remains a boundary.
Manual `WP_Hook` Iterator views require stable callback storage; filter/action
priority mutation uses separate, tested per-level cursors.

The [native API boundaries](docs/elephc/native-api-boundaries.md) list PHP runtime
calls that the current SDK cannot execute. They retain the actual PHP call in
reference checks and raise an explicit error in native execution. Original
capability guards remain in place. These APIs do not count as working native code.

The [published verification snapshot](docs/elephc/full-source-status.json) records
source provenance, PHP parity and native controls for its verified source.
The verified preparation uses 1,103 complete-file patches and fifteen fixtures.
Source counts are separate from native execution coverage.

The [porting log](docs/elephc/porting-log.md) records verified results and current
blockers. No percentage of WordPress is attributed to native execution merely
because its source is copied, patched or parsed.

## Other experiments

The [native subsystem profiles](docs/elephc/subsystem-profiles.md) remain separate
working examples for blocks, hooks, content, KSES, caching, list utilities, URLs,
sanitization and bounded REST objects. Their selected-function manifests and
released SDK requirements belong to those profiles.

The [PHP hybrid experiment](experiments/php-hybrid/README.md) runs WordPress under
PHP with selected algorithms routed through an Elephc extension. Its current
measurements describe that implementation, independently of this complete-source
native port.

WordPress is an original dependency under its own license. This repository's
code is covered by [LICENSE](LICENSE).
