# WordPress on Elephc porting log

## Complete-source port, 2026-11-22

The primary experiment starts from the entire original WordPress 7.1.4 archive,
following the Tempest/Laravel workflow. All 3,782 original files are verified
byte for byte. The current build uses 1,103 checksummed full-file patches and fifteen
explicit fixtures. No selected function ranges or PHP-hosted extension form the
primary port.

The toolchain is main `f20729406216d21ce99841cf4636b771db9874dd`, PHP profile
8.4, with maintained compiler compatibility and scaling changes. Exact archive, compiler, patch and build
identities live in `elephc/full-source/toolchain.json`. Current validation passes
108 exact library tests: six literal-property, eight callable-index, three module-runtime,
eight sparse callable-state, two global-reference, two callable-array join cases
and fifteen method-adapter cases, plus seven visibility, four assembly, three physical
declaration and thirty-five getter/setter target cases, five shared-write target
cases and ten existing child/handoff target cases.
Another 28 native regressions pass, covering native/eval property ownership,
global returns, live aliases, captured aliases, destructor publication and magic
property cache, probe, visibility, ownership and generated argument collectors. The total is 136 focused
Rust checks. The property regression
requires actual calls to the shared literal helpers. Nineteen earlier
Rust regressions still need refresh. Thirty-five PHP/native CLI program modes pass on
Linux x86_64; 155 assembly emissions cover all five supported targets. In addition to callable
names, method lookup, tentative interfaces, ArrayAccess, once discovery, null
scope inventory and mixed/XML append ownership, the patch bounds constant loop
paths, indexes virtual families and inherited methods, and finds numeric CFG
cycles with one iterative component traversal. Opaque callback boundaries inspect
only tracked reference cells and main-scope globals, preserving mutation epochs
without rescanning every synthetic temporary.
Codegen also indexes normalized function, closure, extern and include-dispatch
names once per module emission, retaining the former scanner's first-match order.
Emitted class-method membership now shares the per-module index, retaining
exact class names, normalized method keys and static/instance distinctions. Two
additional regressions compare every answer with the previous scanner and check
large-graph reuse and emission isolation.
Callable-array invocation now joins after target selection, retaining separate
owned-instance and borrowed-static descriptor lifetimes. Two additional regressions
cover all five targets; native/PHP probes verify focused behavior and explicit
retirement. General argument coercion and implicit scope destruction order remain
separate diagnostic gaps.
Known literal property reads now choose the runtime class before consulting its
dynamic hash and magic accessors. Stored null values retain their presence;
get-only literal coalesce still calls the getter on a miss. Concrete result
conversion retires its temporary cell. Four new native regressions cover both
IR modes, with all earlier focused checks retained. The positive managed-value
probe uses mixed getter returns and verifies copies, object identity and clean
heap statistics. Concrete string/object getter lifetimes remain a separate
compiler boundary, preserved by `typed-magic-cache-owners.php`.
The nested Boolean packing diagnostic also remains explicit. Its finite expected
entries now have Boolean casts in the WordPress fixture; all 3,087 property/cursor
comparisons pass without altering WordPress algorithms or removing observations.
These compiler checks do not establish native CMS startup.
The final interface adaptation also forwards matching compiler-generated
argument collectors unchanged while boxing a contravariant fixed prefix. This
preserves the original SimplePie and WordPress constructor signatures, argument
values and counts. The standalone constructor/method/captured-callback probe
matches PHP and is heap-clean in both IR modes; all five targets emit assembly.
Reference mismatches, differing count protocols and true source variadics keep
their existing checks. The full CMS still requires native link and bootstrap
parity on this compiler.

The preceding compiler generated 18,579,013,878 bytes of assembly.
It assembled all 241 function-section groups and combined
them into a 4,485,898,632-byte object. Named and `.L` local references retain
their bindings; physical conditional declarations no longer duplicate bodies.
The early buffer release reduces observed compiler resident memory from about
39 GiB to 19 GiB before assembly. All eight native packages, including the
embedded libmagic database, are installed.

The final x86_64 executable link fails with relative relocation overflows.
The object contains 2,220,315,945 bytes of code and 48,086,936 bytes of allocated
data. That complete attempt exits after 3,052.85 instrumented seconds
with no PHP or EIR diagnostics. Native bootstrap execution remains unverified.
Source markers in the two largest functions attribute substantial expansion
to method dispatch, reference argument materialization and temporary cleanup.
Twenty bounded source/EIR/assembly/native comparisons pass. Five further
default-optimizer comparisons pass but reduce the dominant property-write bodies
by less than 0.4 percent. A complete optimized trial was stopped during repeated
numeric-domain analysis. Checked nominal views reduce the bounded array-property
write caller by 96.5 percent. The latest full-file patches use this approach at
339 HTML parser state accesses and 25 capability-map accesses. They preserve
object identity and read the live property at each original access. The parser
requires its declared state class, while capability maps require stdClass;
arbitrary plugin map implementations remain outside this fixture. No WordPress
functions have been removed to reduce size. The resulting complete object still
exceeds x86 relative relocation reach. The selected compiler now extracts a
concrete receiver after each successful runtime getter class guard. This aims to
avoid repeated generic method dispatch through unrelated classes. All 111 Rust
regressions and the native/PHP controls pass, including heap-clean getter reads
in both IR modes. The complete-graph navigation comparator shrinks from
189,980,096 to 8,094,663 assembly bytes. The selected compiler retains that correction; the object above still exceeds
relative relocation reach.

The selected compiler binds already selected native XML write-fetch directly to
its native method, retaining the boxed receiver protocol. Both call sites already
prove the native type or guard it at runtime. The ordinary user method path keeps
its normal magic dispatch. Five additional target regressions and native/PHP comparisons in
both IR modes pass before selection. The bounded caller shrinks from 987,123 to
830,439 assembly bytes, but the complete assembly drops only 0.22 percent. Both
compiler versions match all six result rows and retain three live XML-probe
blocks totaling 72 bytes; this ownership diagnostic remains explicit.

The newly selected compiler shares repeated literal property-write bodies by
lexical scope, exact spelling, opcode and original value representation. It keeps
borrowed caller owners and captures the diagnostic line. Its PHP reference probe
covers managed arrays and objects, ordinary and magic writes, private scopes,
exceptions and repeated-write pressure. Its 17-row native/PHP probe is heap-clean in both IR modes and emits on all five
targets. The pressure caller shrinks from 797,742 to 57,106 assembly bytes; the
complete-graph image still requires measurement. The compiler passes 121 Rust,
33 native/PHP modes and 145 target emissions before selection. Its complete native
WordPress rebuild is in progress; the object measurements above precede it.

The functional bootstrap build uses `--ir-opt=off`, `--gc-stats` and an explicit
512 MiB heap. The EIR optimizer and generic assembly-body sharing exposed
additional repeated scans on this complete graph. GC diagnostic mode disables
the latter size optimization and prints allocation statistics to stderr.
Native controls keep the default optimizer; source-only verification also runs
them with `ELEPHC_IR_OPT=off`. This profile makes no runtime performance claim.

The complete original and patched PHP bootstrap agree for `init`, `wp_loaded`,
116 blocks, 109 pattern contents, 16 post types and eight taxonomies. Another
955 groups cover 468 setting results, 23 template/widget constructor cases,
16 visitor traces, 147 hook traces, 147 lazy block-list traces, 82 AI groups
60 lexical callback cases, six HTML template-state cases and six live capability
map cases. The latter observe an in-place capability change through the original
public mapping API, then restore the registry state.
The isolated single-site English configuration uses Twenty Twenty-Five, no
plugins or external network, and PDO SQLite in memory with site options and empty
post and taxonomy tables. The latter let original theme-style SQL queries run
when rendering the nested Unicode document. All five runtime-input documents
match between original and patched PHP.
The probe runs original `do_shortcode` after original `do_blocks`; its fourth
runtime input renders a local MP4 video shortcode.
The fifth renders eight navigation blocks with repeated names, Unicode and
numeric-string labels, and a navigation inside an overlay. Its original and
patched PHP output agrees, including the original wrapper attribute behavior
for the label `"0"`. The complete-file navigation patch updates a local mixed
counter map and publishes the entire late-static property after the original
increment or initialization. Name lookup stays before the snapshot, publication
stays before suffix formatting, and property declarations remain unchanged.
This removes the backend reference-binding failure encountered after `WP_Block`;
native execution of the navigation document still requires bootstrap parity.
The remaining closure-scope adaptations keep private `self` targets explicit in
the URL prefixer and attachment controller. The HTML processor's internal
bookmark callback calls a private forwarding method whose `parent` target stays
lexical. This adds one private method to reflection metadata. PHP comparisons
check inherited URL escaping, exact bookmark keys and void returns with an
overridden public release method, and real registered attachment validation
callbacks for valid and invalid values. No upload is dispatched or file written.
The attachment filename-filter closure remains structurally adapted; these
finite cases do not execute the upload lifecycle.
The plugin-dependency name callback keeps its original body in a private named
static method, passed as a lexical method descriptor. Each call reads the live
protected cache with the original presence check, `Name` value and slug fallback.
An explicit class name inside the closure had resolved the receiver but still
failed native non-public property access. The added private method changes
reflection metadata. Four PHP-only AJAX checks seed request-local caches,
validate a real nonce and intercept `wp_die`, preserving rejection responses for
Unicode names and missing, null, false, empty and numeric values. They do not
install or activate plugins or request remote dependency data.
The font-face schema creates its protected `sanitize_src` descriptor in the
original method scope after the schema-cache guard and captures it in the
sanitizer closure. This avoids creating a first-class callable on a mixed closure
receiver. Fourteen PHP comparisons retain scalar/list branches, sparse keys,
Unicode file keys, input independence, schema callback identity and a subclass
override that observes live object state. The bounded inputs perform no DNS,
HTTP or font upload operations.
Each bootstrap state also includes six results from original `number_format_i18n`,
checking grouping, rounding and negative zero in the selected English locale.
The version probe temporarily changes the global version and checks two cached
`wp_get_wp_version` results around a read of that global, then restores it.
Original and patched PHP return the original file version twice while retaining
the temporary global value between them. Version and core-block metadata caches
populated by complete `require` operations use nullable value cells.
The bootstrap also calls original `image_edit_apply_changes` with eleven JSON
history records and an explicit recording editor. Original normalization,
object mutation, adjacent rotation/flip coalescing, zero-operation skipping,
filter context, original base size lookup and crop scaling execute under PHP.
The resulting five operation calls and both record views match between original
and patched PHP. Pixel operations and file I/O are fixture boundaries. A fresh
`stdClass` local preserves JSON record identity before writes and deletions;
custom history object classes remain outside this finite contract.

The full EIR diagnostic identified unrelated `BcMath\Number::add` candidates
when original error or asset-registry receivers had become mixed. A bounded
reproduction fails at a string-to-nullable-integer conversion, while a checked
identity view of the documented error object compiles and matches PHP. The
complete-file patches now check documented `WP_Error`, `WP_Scripts` and
`WP_Styles` receivers at error filters, asset-registration entry points,
header-video validation and the captured core block style registry. They retain
the original objects and algorithms. Objects outside these documented contracts
remain outside the finite fixture.
The original `wp_scripts()` and `wp_styles()` factories expose concrete registry
returns after their existing initialization guards. A bounded generic `query`
receiver reproduces the string-to-tagged-scalar backend failure; the concrete
contract compiles and matches PHP. Root probes check actual factory types,
repeated-call identity and missing-handle queries. These are PHP checks until
the complete native bootstrap passes.
Widget editor dependency checks use the existing script/style identity views at
all four original query receivers. The probe executes four real queue scenarios,
checking null returns, original diagnostic messages and ordering, and unchanged
queue contents. It restores both queues and removes its observer. The fixture's
`WP_DEBUG=false` checks diagnostic actions without emitting PHP warnings.

The bootstrap also calls original password retrieval with whitespace and original
registration with empty credentials. Both return their original validation errors
before user lookup, password hashing, mail or account creation. The complete
original and patched PHP results match; native bootstrap verification is pending.

A later complete compile passes the earlier error-dispatch frontier and stops at
mixed property reference binding in `comments_template`. Both original property
aliases now use checked identity views of the global `WP_Query`. The existing
`comments` field keeps its original null default in explicit mixed storage, so its
alias can cross the original mixed by-reference `separate_comments` boundary.
The originally dynamic
`comments_by_type` field gains a nullable mixed slot because dynamic-hash reference
binding is not supported by this SDK. Pre-initialization reflection and property
existence are explicit boundaries. The expanded property control compares that
slot with original PHP dynamic-field storage across 24 value/replacement pairs,
retaining two live aliases, object identity and independent snapshots. Its 312
new comparisons pass together with the earlier 607. A further 276 comparisons
cover named-global references, whole-value rebinding and independent snapshots,
bringing the property control to 1,195 comparisons.
Another 140 comparisons exercise direct queue-property prepends, live aliases,
key order and snapshots, for 1,335 comparisons before the sidebar-cache additions.
The complete PHP bootstrap also verifies the original no-post comments-template
guard. This does not establish comment SQL or template rendering under native execution.

Mapped Diff keeps its original `orig` and `final` property references through
checked identity views of the original `Text_Diff_Op` objects. The complete Diff
class, native engine and classic renderer match six original PHP inputs with
three context configurations and repeated rendering. The 132 observations check
reconstructed lines, concrete operations, counts, LCS and exact rendered output.
The renderer retains its original false/array block lifecycle through mixed
identity initialization and resets. Its guarded `x0` and `y0` locals receive mixed
null initializers before the loop: otherwise the SDK emits literal null at reads
that precede their assignments textually but follow them on a later iteration.
Every original coordinate assignment and arithmetic operation remains. These
controls bring the property group to 2,537 comparisons. Complete native WordPress
verification remains pending.

List-table compatibility deletion expands the original five-name guard into
literal property deletions, avoiding an unrelated referenced `items` candidate.
Additional compatibility fields from custom subclasses fail explicitly. The root
fixture inherits the original magic methods and skips only screen construction.
Original/patched PHP match fifteen field set/read/remove/rewrite cycles and retain
a live unrelated `items` alias with an independent snapshot. This is PHP proof
until the full native bootstrap passes; admin screen setup is outside this probe.

User custom-field deletion keeps its original presence guard and legacy alias
behavior. At deletion, a checked `stdClass` identity view avoids unrelated
reference-property candidates. Other record classes are outside this finite
mutation proof. The root constructs a zero-ID user and compares 21 removals,
including absent, null, false, nested and numeric-name fields, preserved aliases
and snapshots, unchanged user ID and three legacy `id` diagnostic actions.
Original/patched PHP agree; native root execution remains pending.

The complete compiler then reaches `WP_User_Query::__unset`, whose original
private compatibility guard admits only `results` and `total_users`, but considers
the unrelated referenced `query_vars` field. Two literal deletions retain that
guard and the original diagnostic path. Twelve actual original/patched PHP
lifecycles verify defaults, set/get/isset/unset and rewriting, invalid-field
returns, and an unchanged live `query_vars` alias with an independent snapshot.
The empty constructor performs no query. SQL and reflective changes to the private
compatibility inventory remain outside the fixture; native root proof is pending.

Query result fields `queried_object`, `queried_object_id`, `post` and `comment`
keep their original null defaults in explicit mixed storage. This lets the SDK's
owned-reference removal handle every original `init()` reset without replacing
`unset()` with assignment. Original/patched PHP agree on eight removed fields,
five detached aliases, snapshots, fresh writes and reset counters/flags. Type
reflection and direct reads after removal before reinitialization are outside
this proof: typed mixed raises an error where an untyped PHP property warns and
returns null. Native execution of this lifecycle remains pending.

Customizer menu item conversion uses a checked `stdClass` view around its original
array-to-object cast. The original three deletions, `WP_Post` construction and
three filters stay intact. Original/patched PHP compare three custom-link records,
removed fields, status/order/IDs, title fallback, string/array classes, independent
input values and nine filter events in attribute, description and post order.
The fixture isolates manager construction and value acquisition, supplies author
and type label, and excludes preview/save and URL discovery for other object types.
Native execution remains pending. Sixty-seven fixed Customizer loader includes use
`__DIR__` with the same bundled targets because isolated compiler discovery does
not resolve the surrounding `WPINC` expression at those sites.

The actual menu-list preview filter matches original/patched PHP in nine cases:
replacement, insertion, empty lists, prior placeholder IDs, deletion and missing
IDs, name sorting and the other-blog early guard. Survivor object identity, input
snapshots and value-read counts are checked independently. The original insertion
at index `-1` is preserved, including its position before the final item.

Both list splices use `wp_aot_array_splice_values`. The native builtin rejects a
concrete object replacement for this mixed receiver; boxing the item compiles but
still leaves associative arrays unchanged. The explicit fixture rebuilds retained
and removed value arrays with original string-key preservation and integer-key
reindexing, then updates the caller's array. Its 420 native/PHP cases add 5,880
comparisons, including negative/null lengths, empty/multiple replacements, aliases,
snapshots and object identity. Offsets are integers, lengths integers/null and
replacements arrays; external element reference cells and scalar replacement
coercion are outside this proof. Full manager construction, preview/save and
persistence remain isolated. Complete native root execution is still pending. The pure auto-add option transformer also matches
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
The fixture skips manager construction and disables user authorization checks;
these results do not verify native root execution or user permissions yet.

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

The full compile then reaches remote theme JSON cleanup. A fresh checked
`stdClass` identity view narrows only the original `slug`, `screenshot_url` and
`author` deletions, avoiding unrelated declared reference-property candidates.
Five native/PHP scenarios add 35 comparisons for missing/null/false fields,
identity, shared snapshots and the lingering original foreach reference.
Earlier `WP_Theme` receivers and preceding remote-record writes remain intact.
The network request, authorization and complete endpoint are not executed.

Theme JSON resolver file and block caches use fresh local mixed arrays followed
by whole late-static property writeback. This keeps original class selection
without binding an eval-overridable late-static reference slot. Twelve native/PHP
scenarios add 181 comparisons for shared, inherited and separately declared caches,
single-value reads, live whole-property aliases and independent snapshots.
The control enables and executes a bounded native eval program. Static values
collected into array literals need explicit mixed views; otherwise the selected
compiler returns integer array lengths in the diagnostic probe.
Forty-six actual original/patched resolver PHP calls cover both bundled theme JSON
files, cached null/false/scalar/array values, false-like paths and registered-block
inventories, restoring the original cache afterward. Complete native resolver
execution, external element reference cells and arbitrary eval programs remain
unverified.

Fixed core loader paths use the same verified `wp-includes` targets through
paths relative to each original source file. A complete audit additionally
rewrites 585 literal includes in 57 files, including root bootstrap and upgrader
loaders. Every target exists in the immutable archive. The fixture uses the
standard archive layout; custom `ABSPATH`/`WPINC` remapping is outside this loader
profile. Dynamic loaders retain their separate documented boundaries.

The complete native build next passes user-query deletion and reaches SimplePie
IRI byte conversion. The original constant `range()` with string endpoints is
unsupported by the selected compiler. A complete-source audit finds the same
128-byte mask in exactly SimplePie and Requests; both now use its exact literal
bytes while retaining the original loops and lazy cache. Complete original and
patched classes pass native/PHP checks over 266 byte sequences, repeated calls
and eight non-string Requests guards. The complete binary control passes 4,742
comparisons. Its unrelated `substr_compare()` checks retain ordering and exception
semantics using the [documented result sign](https://www.php.net/manual/en/function.substr-compare.php);
PHP's raw integer magnitude varied between reference runs. Parsing, normalization,
IPv6 processing, transports and general string ranges are not verified here.

The next complete build reaches nullable lists in SimplePie item enclosures.
Fifteen list variables keep 32 original null assignments through mixed identity
views. Direct native append exposed two history-snapshot mismatches in the new
five-case corpus. A checked null-or-array append now uses the existing copy-safe
array push and replaces 48 list appends, preserving untouched null, whole-list
aliases, inherited copies and earlier observations. All 30 new comparisons pass.
Fresh checked Registry views narrow 65 calls to the original typed setter's
contract. Complete original/patched Registry files with controlled overriding
subclasses pass another 217 native/PHP comparisons for identity, dispatch and
receiver selection before argument-side rebinding. The real enclosure aggregator,
reflection factory, external element reference cells and full feed pipeline remain
outside these execution checks. No compiler speedup is inferred from the views.

The original `wp_convert_widget_settings` front-page alias now uses a named
global for `_wp_sidebars_widgets`. It preserves the live reference, fallback
lookup, first-match nested write and separate admin persistence behavior. Another
672 comparisons cover those reference operations and snapshots, bringing the
property control to 2,007 before the 132 mapped-Diff and rendering observations. The root probe also invokes the actual conversion
twice against an isolated in-memory cache and restores its prior value. This
probe does not execute admin persistence or the full widget rendering lifecycle.

The original dependency queue keeps its empty-array default in explicit mixed
storage, matching the prepend adapter's reference contract. Property declaration
type reflection is outside this compatibility proof. The root probe calls the
original `sizes=auto` CSS enqueue routine and inspects its prepended handle,
registered dependency, inline CSS and removal of the legacy head hook. Actual
native bootstrap verification remains required.

The SimplePie parser's original header array now has explicit mixed reference
storage. A bounded untyped-header fixture reproduces the unrelated reference-slot
rejection during generic property deletion; mixed storage compiles and matches
PHP for deletion, surviving aliases and fresh owner assignments. Declaration type
reflection remains outside this proof. The original `update_term_cache` clone and
`object_id` removal retain identity views of documented `WP_Term` and SQL `stdClass`
records. Other record classes are outside this finite fixture. Root probes update actual `WP_Term` and SQL
`stdClass` records, checking clone identity, source-field retention, cached-field
removal and independence after changing the source names.
The two database method checks in `wp_version_check` use the existing checked
`wpdb` identity view. Root probes inspect both method names on the actual database
fixture. Version-check networking remains outside the executed probe.
The core ability's database probe uses the same receiver contract. An inventory
of the prior complete EIR found 29 method-existence calls; the native builtin
requires constant member names and concrete targets. Twenty dynamic calls in
eleven admin, mail, XML and feed files now use the existing platform API boundary.
PHP executes real `method_exists`; native invocation fails explicitly. Those
paths are not verified native coverage. The gallery's two original theme JSON
candidates are unrolled in their original order, retaining literal metadata
guards, the final local class name and the original core static call.

The oEmbed width and fluid typography clamps now call the complete original
`compat.php` polyfill under a distinct source name, declared unconditionally.
This avoids numeric builtin interception and conditional function registration.
Its original comparison order and ValueError messages remain. The scalar/null
domain passes 10,648 groups, including mixed boolean precedence, numeric and
nonnumeric strings, NaN and infinities. The combined UTF-8/compatibility control
passes 26,350 comparisons. An initial matrix found native/PHP differences involving
empty arrays; arrays, objects and resources now fail explicitly. The complete
oEmbed response and frontend typography workflows are not runtime proof.

Navigation's three unstable-location helpers remain under their original
Gutenberg plugin guard. Their unresolved sibling calls use the existing explicit
plugin boundary. The plugin is not loaded by this fixture, so these paths are
outside native execution coverage. The boundary inventory distinguishes direct
API calls, constructor calls and named unsupported adapters; counts cover prepared
original files and do not establish execution.

The media upload form preserves its original null success result and nullable
error accumulator. Its initializer crosses the existing mixed identity boundary,
allowing the original offset writes to perform PHP's null-to-array conversion.
The array-mutation control passes 8,993 comparisons, including untouched null,
integer and string keys, null and boolean keys, and nullable/scalar/array values.
Two original nested taxonomy-map reads in `upgrade_230` cross the existing mixed
identity boundary because the same local later holds a flat link map. The writes,
resets and original algorithm remain. Another 216 comparisons check both branches,
key forms, nested writes and snapshots. This does not execute a WordPress upgrade.
This control does not execute uploads or the complete media form lifecycle.

Two plugin-update property walks retain the original `array_walk` and callback
at source-local receivers. A `finally` block writes back partial changes after
callback exceptions. Another 168 comparisons cover array and record values,
snapshots, key traces and partial exceptions. The fixed payload sanitizer keeps
its original object cast and checks the `stdClass` identity before deletion.
External property reference cells, observer callbacks and magic payload classes
remain outside this JSON profile. Plugin-update transport is not executed proof.
The callback stays at the original source sites because a generic helper in the
always-loaded array fixture requires a signature even in unrelated profiles.
The paired plugin and theme update array deletions now use a local value-array
adapter for integer and string keys. Another 360 comparisons cover present and
missing keys, original key order and source/snapshot independence. External
property or element reference cells remain outside the admitted JSON records.

The connector provider registry retains its original `is_active` closure and
nested array assignment. An explicit mixed value projection avoids an unsupported
callable-to-string branch during lowering. Callback identity, captured-object
updates, local rebinding, exception fallback and independent snapshots have 108
additional comparisons. Provider transport remains outside these controls.
The introspection control now passes 1,443 comparisons, including 1,382 property
metadata comparisons against five complete original PHP class definitions.
Native field-storage schemas are checked independently with original PHP
Reflection. The metadata inventory preserves 78 declared names, including static,
protected and private declarations, and every removed instance field. Dynamic
public null keys retain their presence. The domain is string names on exact
`WP_Ability`, `WP_Ability_Category`, `WP_Block_Type`, `WP_Post`, `WP_Term` and
`stdClass` objects. Other classes, subclasses, class strings and non-string names
fail explicitly. Original file and fixture checksums are recorded in
`elephc/full-source/property-provenance.json` and checked during preparation.
The root also constructs original Ability and Category objects and reads their
prepared metadata. Permission checks, execution and REST exposure remain outside
that constructor probe. Reports retain harness and auxiliary source hashes.

The complete original REST context filter retains its recursive algorithm. JSON
object mutation uses fresh `stdClass` identity views; array deletion uses the
checked value-array adapter. Other object classes and external element reference
cells remain outside this fixture. The root checks 28 calls over validated JSON
data and schemas, including nesting, array item filtering and clearing, numeric
keys, pattern/additional properties, aliases and explicit key order. Fixture JSON
uses `JSON_THROW_ON_ERROR`. Combining schemas and REST routing remain outside
these executed cases.

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

The original bookmark lookup now uses named-global aliases inside the original
branches. Native getters read named globals; PHP reference getters preserve the
original `$GLOBALS` expressions. The root probe checks both branches and three
object identities with one existing record before any links-table query.
The compiler reads the actual global reference cell and preserves its wrapper
when later stores publish a new value. General `$GLOBALS` symbol-table reads,
writes and indexed unset remain separate SDK boundaries.

A token-aware rewrite places 1,105 fresh checked `wpdb` identity views around
literal method receivers in 96 complete files. The original SQL, arguments,
subclass dispatch and global rebinding remain. All 96 files pass PHP syntax;
the 248 SQL comparisons retain real SQLite tests and add bounded stand-in
base/subclass/rebinding scenarios. They do not prove complete native WordPress SQL.

The complete native control suite is being refreshed for this source. Focused
checks include real libsodium, byte
spans/searches/escapes, version comparison, original complete UTF-8 files,
SQLite, callable references, regex quoting, array cursors, query operations,
Requests dictionary behavior and the complete bundled AI graph. Source-collected
compact inventories preserve nulls, ordering, nested names, object identity and
value independence in 356 comparisons. Binary integer/string pack/unpack adapters
pass 1,042 comparisons across 523 groups. Merge/map/filter adapters pass 77 groups
with 154 comparisons including recursive key-order traces. Value-array uniqueness,
search, sets and slicing pass 603 groups and 1,206 comparisons. Stable generic
value/key sorting passes 360 groups and 1,260 comparisons, including reference
updates and explicit key-order traces. Array/scalar string replacement passes
560 groups and 1,680 comparisons, including sequential replacements, NUL bytes,
subject keys and accumulated replacement counts.
Ordered shutdown callbacks add 10 comparisons; recursive merging adds 418.
Offset-aware PCRE2 captures and scalar/Stringable operand conversion pass 18,416
comparisons with the expanded split-options control, metadata and complete Requests stringability queries add 61,
and finite port lookup adds 37. The three complete Gutenberg parser files match
original PHP for 24 document parses and 30 comparisons, including nesting,
invalid JSON, UTF-8/NUL input and parser reuse. Checked nominal block receivers
keep original nested appends on their owning records. Native invariants match
PHP with assertions enabled in 28 comparisons with explicit descriptions.
The UTF-8 entity decoder adds 50,828 comparisons over 2,125 named references,
four doctypes, quote flags, numeric validity boundaries and malformed input.
Its generated tables retain original WordPress source checksums and the checked
PHP entity-table identity. Charset conversion and warning state remain boundaries.

The HTML control also verifies UTF-8 escaping with both `double_encode` modes.
Preserved numeric references follow PHP's escaping rules independently of the
decoder's scalar-validity checks. Invalid UTF-8, other charsets and
`ENT_DISALLOWED` remain explicit encoding boundaries. The complete token-map
class passes 617 comparisons for valid precomputed tables, construction from
arrays, lookups, offsets, match lengths and array conversion. Constructor cases
cover empty maps, short and long tokens, equal-length ordering, shared prefixes,
Unicode mappings, snapshot independence and an inherited call whose child
comparator must remain unused. The comparator closure names `WP_Token_Map`
explicitly instead of `self`, preserving the original private lexical target
while avoiding a native closure-scope diagnostic. The finite case list projects
its known array arguments explicitly. Its short-token patch explicitly casts the
original boolean comparison before `strtoupper`; the comparison stays inside
the call. Source export and invalid-state diagnostics are not
part of the executed token-map control.

The complete `compat.php` control verifies the original PCRE probe, cached results
and deprecated arguments together with the UTF-8 fallback files. The error handler
captures a local probe result and publishes it back to the static cache. Mutually
exclusive codec declarations share one declaration per name, keeping their
original mbstring/fallback choice inside the body. The conditional public codec
polyfills are outside the executed control. Theme cache sorting likewise uses a
local value before publishing the sorted result back to its original static cache.

The complete Query block file has an isolated pagination control. Persistent
mixed property cells preserve nested query stacks, dirty flags, callback identity
and self-removal. The registry and hook boundaries are explicit fixtures. The
control does not execute script/style enqueueing, HTML rewriting or content-query
rendering. Reflected identities of adapted function-static variables remain
outside the profile.

The admin update nonce, temporary-directory and spacing caches retain their
original empty-string sentinel through persistent mixed property cells. Local
references keep their original assignments and lazy initialization. The property
control passes 1,335 comparisons across string, array and nullable cache lifecycles,
including repeated writes, presence, reads and independent snapshots. Query
pagination still passes its 55 comparisons.
Iterator collection checks duplicate keys, numeric-string/null/boolean keys,
aggregate invocation counts, method order, final positions and catchable failures.
Value-only collection appends without invoking `key`; keyed collection rejects
array and object keys explicitly before a native offset write.
Both remaining original `iterator_to_array` calls use the existing collector.
The bootstrap calls original `wp_insert_user` with an `ArrayIterator` containing
a negative ID. Original and patched PHP consume it and return `invalid_user_id`
before password hashing or any user-table query. User creation remains unverified.
Admin nonce HTML execution and reflected static-variable identities are outside
these focused controls.

The stream control loads the complete AVIF parser, POMO stream classes and
PclZip library. It passes 2,321 comparisons with original PHP in its focused run.
AVIF read lengths and seek offsets use the existing checked integer adapter;
its original uncoerced length comparison remains. POMO file reads and seeks use
the same boundary, retaining the original uncoerced cached position. The control
checks ordinary binary reads and EOF, AVIF box validation and format recognition,
and all four POMO reader variants with endian-aware integer decoding. Full AVIF
image-feature extraction and legacy mbstring overload behavior remain unverified.
PclZip adds 84 uncompressed copy cases in each of three isolated configurations,
comparing returned values or exception
classes, copied bytes, stream positions and EOF state. This preserves partial
copy effects before the original error for a boolean size. A bounded one-name
inventory preserves executed `PCLZIP_READ_BLOCK_SIZE` definedness and values.
The original conditional declaration supplies its unchanged default of 2,048;
PHP counterparts call real `define`, `defined` and `constant`. Comparison cases
also verify definedness before and after loading, with explicit fixture block
sizes 7 and 128. Direct external native definitions,
duplicate-definition diagnostics, compressed ZIP operations and raw runtime
enumeration remain boundaries.
Across complete source files, checked integer conversions now cover native I/O
lengths, offsets, modes and flags; original loop and comparison values remain.
The fixture covers valid local seekable streams and scalar write data. Transport
behavior and invalid-resource diagnostic precedence remain boundaries.

Dynamic regex pattern and subject operands cross the explicit scalar/Stringable
string fixture. Original PCRE calls, output references, flags and offsets stay
in their complete files. The focused native control includes the original Vimeo
and YouTube patterns and valid scalar, null and Stringable inputs. Invalid
operands are checked by exception class; exact diagnostic wording and validation
timing relative to other argument expressions remain outside the finite profile.

The replacement control passes 13,322 comparisons, including weak scalar
subjects, pipe-delimited cookie patterns and the counter after invalid subjects.
The remaining 470 scalar and array regex replacement calls in 145 complete files
use the existing PCRE2 replacement adapter. This includes weak scalar cookie-path
subjects that the native builtin rejects. The original PHP counterpart still
calls PHP's real replacement API. The bootstrap returns all four cookie paths so
native startup must also preserve their actual values.

Number formatting crosses a checked numeric boundary while retaining the original
WordPress locale dispatcher and filter arguments. The adapter preserves signed
64-bit integer digits and the integer path for large integral doubles, then uses
PHP half-up rounding and full byte-string separators. Null, numeric strings,
non-finite values and invalid types have reference cases. The native precision
range is explicitly -22 through 22; larger precision and exact deprecation
diagnostics remain boundaries.

The combined weak-integer control passes 6,753 comparisons, including the expanded
number-formatting corpus, explicit UTC calendar components and formatting vectors.
Checked component projections retain the original `mktime`/`gmmktime` calls,
normalization and substring values. The bootstrap also compares two original
`get_weekstartend` results around leap-day and year boundaries. Null/current-date
component defaults, extreme ranges, other timezones and argument-error timing
relative to later side effects remain outside this finite profile.

UTC formatting corrects the SDK's timestamp -1 sentinel, unsigned negative `U`
field and dropped terminal escape. The adapter derives unchanged UTC fields from
the adjacent second for timestamp -1, preserves seconds and signed Unix text, and
expands composite formats and escapes. A terminal escape retains PHP's NUL output.
Input formats containing NUL and unsupported SDK format fields remain boundaries.
The complete SimplePie date parser keeps mixed day locals across pattern
construction and numeric captures.
Three dynamic regex limits, flags or offsets in two complete files cross the
existing checked integer boundary. This includes the original word limit plus
one in `wp_trim_words`, where integer addition can also produce a float. Literal
flag constants stay unchanged. The bootstrap returns four original word-trimming
results. The 68 original split calls in 38 complete files use the existing
PCRE2 capture boundary. This replaces a native builtin with incorrect negative
limits, empty-match progress and NUL handling. Split delimiter and byte-offset
captures retain original ordering, and captured delimiters do not consume the
subject-piece limit.

Initially empty function-static arrays use persistent property cells in 67
declarations across 34 complete files. Eight appends in seven files use the
existing array adapter to keep earlier returned snapshots independent. The raw
reference append mutates earlier snapshots on this SDK, including with an
explicitly typed array property; a local array copy preserves PHP behavior.
Cache reads, writes, null/presence distinctions, clearing and value snapshots
have differential cases. The bootstrap also returns repeated results from
original `wp_normalize_path` on eight fixed path inputs. Adapted static reflection
and shutdown ordering remain outside this fixture.
Another 44 initially-null data caches in 24 complete files use the existing
nullable value cell. A token audit identified direct string/array assignments;
the original null checks and population logic remain. These include path
truncation, texturization data, regexes, URL caches and lookup tables. Object
singleton caches were excluded from this rewrite. A second audit covers another
61 scalar/data caches whose values arrive through variables, calls or includes,
including the current development mode. Seventeen typed object singleton
declarations retain their original storage and return contracts. The property
control also checks object identity, reset and retention of an earlier instance.
An unset-capable local alias needs a by-value boundary when a returned array
must keep a snapshot; the control checks that case explicitly.
Nineteen false sentinels and eight further empty-string sentinels in twenty
complete files use cells with their respective original defaults. The UUID
fallback cache keeps false and function-name strings. Concrete literal stores
and their later snapshots are covered in the 1,335-comparison property control.

The image subsize routine uses a callable capability guard for the optional
public instance `make_subsize` method in the fixture's two core editors. A PHP-only policy
check loads the complete original GD and Imagick class files and verifies public instance
visibility, no magic-call fallback, original checksums and the equivalent guards.
It constructs no image and invokes no image operation. Custom editors with
non-public methods or magic dispatch remain outside this adaptation; native
image processing is unverified.

The three `array_combine` calls use the existing scalar-key adapter. A checked
array return alone still lowered as `Mixed` for this builtin in the isolated
control, so the source uses the verified adapter. The expanded additional-array
control passes 1,702 comparisons, preserving earlier cases and adding scalar-value
array flipping, duplicate/order and numeric-string key cases, input independence
and non-array argument errors. The 33 flip calls in 23 complete files use a
guarded integer/string-value fixture. Invalid element values explicitly fail in
native code; PHP's warning-and-skip paths remain outside that fixture.
The bootstrap also reads the original theme headers through a
real additional-header closure filter and returns all ten original post-format
slugs; original and patched PHP agree on these fields.
It also executes original `search_theme_directories` with a forced scan and a
cached read. Both PHP source trees return the same three sorted theme names;
the probe omits local root paths. Complete native verification must match both
lists as part of the bootstrap state.

Additional native controls cover generic union, byte translation and string XOR
(394 comparisons), replacement limits/arrays/callback flags (13,322), additional
array operations (1,702), associative differences (392), stream reads and seekable
EOF state (2,321), fresh-value cursor/property reset/iterator and cache operations
(1,335), weak integer, numeric formatting and calendar operations (6,753), whole
Walker dispatch (80), glob flags (14) and
filesystem timestamps on task-owned files (36). Private nullable AI token-limit
storage preserves null, zero and integer limits. The standalone Walker control
uses PHP profile 8.5; the primary bootstrap remains on 8.4 and complete compat.php.
PHP-only service, constructor, method and dynamic property bodies live in the
PHP counterpart; the LFC counterpart rejects unavailable native operations.

The complete Multisite alias routine adds 113 native comparisons across nine
isolated configurations, including strict value types and second-call warning
traces. `VHOST` and `SUBDOMAIN_INSTALL` otherwise recursively expand each other's
initializer in this SDK. They use a request-local native inventory, while PHP
counterparts call the engine's actual constant APIs. The original WordPress
routine still performs conversion, strict comparison and deprecation handling.
Core detection and reads use the same two-name inventory. Raw global enumeration
of those runtime entries, duplicate redefinition and complete network/database
startup remain boundaries. Other upload and cookie APIs in the isolated control
have explicit throwing declarations and are not counted as executed behavior.

Originally untyped named parameters and compatible returns are explicitly mixed.
Bodies and defaults remain; original bare/fall-through returns explicitly return
null where required. Parents of existing void overrides retain their absent return
annotation. The fixture database matches these contracts. Explicit base-method
calls share private bodies, preserving legacy widget constructor binding.
Exception factories retain concrete types. HTML insertion projects its original
non-null token contract without replacing the parser algorithm.
Originally untyped reference properties now use explicit mixed storage, retaining
implicit null defaults. Four static array stacks use persistent mixed property
cells. Dynamic read receivers cross a PHP identity boundary, preserving values
and object identity while selecting the native runtime path.

The source pack and merge calls package argument lists explicitly. A standalone
reproduction confirms that the SDK can lose a generic runtime spread tail after
fixed variadic arguments. This remains a compiler boundary. The array adapters
operate on value arrays; external sharing of element reference cells and exact
native warning-buffer behavior are outside their verified scope. Binary adapters
cover integer, string/byte, hex and positioning fields, without floating point or
pointer formats. Original byte-wise UTF-8 algorithms run independently of mbstring.
Optional absent constants keep their defined guards and executed-read errors.

The widgets merge receives the original `array_values()` list directly, avoiding
redundant spread repackaging that confuses native Array and Hash layouts. The
public probe retains that compiler defect. `WP_User` keeps its lazy static cache
with an uninitialized declared array. The SimplePie registry and Requests Curl
reference getters retain their reference markers and use matching mixed property
and return storage. HTTP transports and runtime reflection construction still
cross the existing explicit platform boundary.

PCRE2 10.47 recipe 8 and libxml2 2.15.3 recipe 11 are locked. The native Sodium
fixture links the actual system library. AI streams use the original standard
memory path at every size; SDK userspace wrapper metadata is unsupported.
Optional external packages, Redis/Memcache adapters, native DOMXPath, RSS reference
trees, uploads, unsupported URL filters and warning-buffer inspection remain
explicit boundaries. The native lock also pins Curl 8.21.0 recipe 4, libssh2
1.11.1 recipe 2, nghttp2 1.70.0 recipe 2, OpenSSL 3.5.8 recipe 1 and zlib 1.3.2
recipe 1. The complete Requests input-validator control preserves implicit and
inherited `Stringable` semantics on thirteen scalar, array and object values.
Its numeric-key and array-access methods are compiled but are not executed proof.
The fatal-error page handler is disabled to retain native
diagnostics. Query decoding keeps its one-MiB, 1,000-field and 64-level bounds.

The current compiler shares repeated literal-name mixed-property dispatch by
lexical scope, exact name and fetch mode. Its borrowed synthetic EIR helpers reuse
the original property implementation and owned result cleanup. Single sites,
quiet reads, hook bodies and other representations retain their direct paths.
The complete native WordPress build is being resumed on this validated compiler.
**No complete native WordPress
bootstrap, frontend, admin or REST dispatch is claimed.** The twenty compiler
changes address observed correctness and scaling blockers, but the current
source still needs actual native compile/link, PHP bootstrap parity and a
source-only native reconstruction. The current source and compiler identities, verified controls and pending groups
are explicit in `docs/elephc/full-source-status.json`. Earlier diagnostic counts belong to
previous source hashes and are not current compiler results.
Public scripts use tools on PATH or explicit compiler arguments; workstation
scheduling stays outside the repository.

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
native build prerequisites. All scripts run directly with the documented tools
on `PATH`, without workstation-specific scheduling helpers. Source-only
reproduction copies `.elephc-version` along with the maintained source files
so an optional version manager can select the pinned compiler in the export.

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
passed the same checks, then removed its temporary tree.

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
server is 24,521,089 bytes. The cache now shares the selected functions bootstrap.
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
reproduced separately with locked PCRE2 10.47.

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

## Original bootstrap after goto support, 2026-11-17

Elephc [PR #2370](https://github.com/illegalstudio/elephc/pull/2370) merged as
`7d49e3b22e14e564cff2830301deb91230f2d028` on 2026-11-16. The Linux x86_64
development SDK for main `23314d3e1fdb1ec72b5a915a7753dc72473e7591` was downloaded
from [Actions run 44110722929](https://github.com/illegalstudio/elephc/actions/runs/44110722929),
artifact `14344105647`. The tarball checksum was verified against its bundled
checksum file; the SDK manifest revision and compiler/native archive hashes
were verified before testing. The archive SHA-256 is
`b418802f05abf1106b1fa0b37e14b0b65ed3f4c800f0baa60d9865d9960a4871`.
The [frozen report](goto-main-23314d3.json) retains the compiler checksum,
artifact metadata and both SDKs' diagnostics. The development snapshot and
pinned release both print `elephc 0.27.8`; the banner alone cannot identify them.

The standalone goto control exercises a jump out of a loop and a forward jump
within a loop, following the shapes in the original UTF-8 compatibility file.
The pinned checker rejects its goto/labels. Main checks and compiles it, and its
native output exactly matches PHP: `[true,false,"AxB"]`. Original compat-utf8.php
no longer produces goto syntax errors on main. It still reports missing strspn
and type/reference constraints. Its missing _deprecated_function and
wp_has_noncharacters are dependencies omitted by the isolated probe, not
independent compiler defects. Reassess type/reference diagnostics after the
primitive dependency is available.

Original SHORTINIT now reaches sodium_compat/autoload.php:31 and stops on
`require_once $file`. That first failure is inside the PHP_VERSION_ID < 70000
legacy branch. A minimal version-guarded dynamic-include probe is also rejected
under the PHP 8.4 compiler profile by both SDKs, while PHP 8.5.12 prints guarded
without running that branch. The active PHP 7+ Sodium loader also has dynamic
includes, so version pruning alone does not demonstrate bootstrap support.
The independent strspn probe fails on both SDKs while PHP prints 3. Standard
wp-blog-header bootstrap retains the conditional ABSPATH resolution failure at
wp-load.php:50. These are observed blockers, not a complete compatibility audit.

scripts/check-goto.py repeats six compile-only probes per SDK and compiles/runs
only the standalone goto control directly with the selected SDK. No WordPress
startup or database access was executed. Preparation checks still verify 21
core files and 20 patches. No upstream file, application adapter, patch or SDK
pin changed. The earlier full/native source-only result remains 3,338 HTTP
checks on the pinned SDK; that suite was not rerun on the main snapshot.
The downloaded SDK scratch directory was removed after verification.

## Complete-source PSR size cache, 2026-11-22

The next complete attempt passes the stream getter and stops after 905.13 seconds
at Nyholm `Uri::getPort` with the same mixed-to-nullable-integer backend error.
Its getter now returns null explicitly and projects the remaining original value
through the existing checked integer contract. The private untyped property and
original `filterPort` casts, range checks and scheme normalization stay intact.
Thirty valid scheme/port cases and six invalid-port cases add 234 native/PHP
comparisons for zero, standard and custom ports, numeric strings, 65535, clone
identity, scheme changes, string round trips and unchanged state after rejection.
The complete AI control now passes 445 comparisons. Other nullable integer getters
in this AI tree already have typed property storage and are unchanged.

Compiler19 passes its focused gates and reaches Nyholm Stream::getSize in the
complete source graph after 938.22 seconds. Its nullable integer return rejects
an inferred mixed cache value. The two non-null return branches now use the
existing checked integer projection, preserving the original cache lifecycle.

PSR stream size getters project the two non-null cache returns through the
existing checked integer contract. Original untyped storage, nullable return,
cache guards and invalidation remain. Six complete-class native/PHP scenarios
add 66 comparisons for zero, binary, Unicode and large memory streams: repeated
and stale cached sizes, external writes, method-write invalidation, resource
identity on detach, reattach, closed/detached null results and exact content
hashes. The AI control passes 211 comparisons. These memory-stream checks do not
establish transport or userspace-wrapper support.

Complete native WordPress compilation and root execution remain pending.

## Complete-source widget factory identity, 2026-11-22

The next complete compile passes both repaired PSR getters and stops after
888.49 seconds at `WP_Widget_Factory::register`, where `spl_object_id` rejects
the mixed representation inside the original `instanceof WP_Widget` branch.
Both factory instance-ID calls now use an object-typed wrapper that invokes the
original builtin. Original instance guards, prefixed keys and registry algorithms
stay intact. The full factory file executes with explicit test widget classes.
Twenty scenarios add 140 native/PHP comparisons for constructor hook arguments,
instance and class replacement, key uniqueness and lookup order, unknown/repeated
removal, live registry aliases, independent array snapshots and empty results.
The property/cursor group passes 2,677 comparisons. Widget constructors, options
and callback registration remain outside this control. Complete native root
verification is still pending.

## Complete-source toolbar binding, 2026-11-22

The next full compile passes widget registration and stops after 893.74 seconds
at `WP_Admin_Bar::_bind`. Generic deletion of a node's `group` field considers
an unrelated referenced `Text_Diff_Op::$final` property. The original final
node factory casts every node to an object and stores it privately. A fresh
checked `stdClass` view now owns only that existing deletion; the rest of the
original tree algorithm remains unchanged.

Eight complete-class scenarios pass 64 native/PHP comparisons for root replacement,
automatic groups, nested group containers, top-level order, orphans, removal of
`group`, shared node handles, cloned public snapshots and one-shot binding.
Both container insertion and replacement branches execute. The property/cursor
group now passes 2,741 comparisons. Test-only environment and rendering functions
throw, and a finite array-default fixture supplies node setup. Full authentication,
toolbar rendering and native root execution remain outside this proof. Initial
frontend failures in test stub return/argument types were corrected before the
successful native run; no additional WordPress algorithm was replaced.

## Complete-source ability annotation defaults, 2026-11-22

The next full compile passes toolbar binding and stops after 925.69 seconds in
`WP_Ability::prepare_properties`. Its original all-null annotation map reaches a
nested hash store as `AssocArray<Str, Void>`. Boxed late-static reads fix that
consumer representation, while an isolated complete-class control then exposes
the untyped static initializer's `Void` value diagnostic. A reduced native probe
confirms that `mixed` and `array` declarations preserve the exact original nulls.
The patch uses `mixed`, keeping all original values, and boxes both consumers.
Property type reflection and untyped subclass redeclarations remain compatibility
boundaries of that declaration.

The complete ability class, error class and filter sentinel pass thirty metadata
scenarios, adding 270 native/PHP comparisons. Cases cover missing annotations,
explicit null/false/true values, extra metadata, public versus REST precedence,
getters and independent nested copies. Property/cursor totals 3,011 comparisons.
English translation is an identity fixture; filters, schema validation and ability
callback execution throw at explicit control boundaries. Initial missing control
dependencies and a compiler panic around nested calls to `never` stubs are retained
in diagnostics. Those stubs now have generic return declarations and still throw.
Full native bootstrap remains unverified.

## Complete-source REST attachment record, 2026-11-22

The ability map fix advances complete lowering to
`WP_REST_Posts_Controller::can_access_password_content`, where the assembly
buffer reaches its 16 GiB capacity. Its 32 GiB growth request exceeds the private
48 GiB virtual-address limit. Retrying the identical source/compiler at 56 GiB
passes that boundary with about 38 GiB resident memory; public build commands and
the 512 MiB application heap are unchanged. The retry then reports a generic
property deletion error after 910.05 seconds in
`WP_REST_Attachments_Controller::edit_media_item`.

A REST-source audit finds one direct object-property deletion: removing `ID` from
the prepared attachment object. A fresh checked `stdClass` view now owns only that
operation. Eight record cases add 40 native/PHP comparisons for absent/null/false/
zero/string IDs, unchanged fields and order, object aliases and prior snapshots.
Property/cursor totals 3,051 comparisons. Non-`stdClass` prepared results remain
an explicit boundary. Image editing, files, attachment insertion and REST dispatch
are outside this executed proof. Full native WordPress compilation remains pending.

## Complete-source REST menu auto-add, 2026-11-22

The prepared attachment fix passes its previous frontier and complete lowering
stops after 916.78 seconds at `WP_REST_Menus_Controller::handle_auto_add`.
The reported `array_pop Pointer(None)` is inside lowering of its original
three-argument nested `array_splice`. This is the same operation already covered
by the menu fixture's nested-reference and omitted-replacement controls.
That one call now uses `wp_aot_array_splice_values`, preserving the original option
read, strict lookup, append/remove decision, update and action. Existing 420 native
splice groups cover 5,880 comparisons within array-mutation's 8,993 observations.
No new algorithm or controller persistence fixture was introduced. The prepared
REST tree has no remaining raw stack/splice calls; this is a structural audit,
not proof of REST dispatch, authentication or saved-option updates. Full native
WordPress compilation is being resumed under the private 56 GiB address cap.

## Complete-source HTTP status dispatch, 2026-11-21

The complete build reached the original dynamic HTTP exception constructor in
Requests Response::throw_for_status. Mixed identity views retain both original
arguments and the exact response object. The first native control exposed boolean
storage coercion of the original untyped false-default status field; explicit
mixed property storage preserves the original default and status values. A
six-case isolated probe then identified leading namespace separators as the
remaining dynamic-new failure, including with inherited constructors. Normalize
only the local constructor name, retaining Http::get_class output and selection.

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

Complete native WordPress compilation and root execution remain unverified.

## REST unique-item validation, 2026-11-17

The selected REST graph now includes original rest_stabilize_value and
rest_validate_array_contains_unique_items, for nineteen helpers. The graph retains
21 core files, 20 patches and thirteen executables. Explicit types, a local array
copy and a cast at the unique-check call provide native storage contracts. Original
recursive key sorting, PHP serialization and seen-key lookup remain. PHP object
stabilization throws explicitly; decoded JSON arrays/maps follow the existing
profile. The duplicate message uses the existing fixed English locale.

The application schema guard accepts boolean uniqueItems on array schemas at any
supported schema level. True detects duplicate values after item/count checks;
false skips the uniqueness check. Property order is ignored recursively, list
value order is retained, and values are not coerced. Integer 1, float 1.0 and
string "1" remain distinct, including under an integer items schema. Empty JSON
objects and lists both decode as PHP arrays and compare as duplicates. Inputs
remain unchanged. Direct duplicate errors retain original absent status metadata;
request validation aggregates them under rest_invalid_param with status 400.

The new corpus adds 53 original-PHP/profile/native scenarios and 16 invalid inputs.
It covers nested/property-order duplicates, sparse keys, mixed scalar types,
Unicode/NUL, signed zero, large integers, exact/exceeded value budgets, error
precedence, source priority, callback traces and unchanged snapshots. Three old
boundary inputs now use invalid non-boolean flags because boolean flags are
supported. All earlier valid cases remain. Full native and fresh source-only
reproduction each pass 3,476 HTTP checks through one repeated worker. The latter
rebuilds all thirteen executables and removes its export. No new compiler defect
was identified; the primitive ksort/serialize control matches PHP. Original
upstream files and the SDK pin remain unchanged. The original bootstrap blockers
documented above remain separate from this verified subsystem.

## Follow-up direction

A broader CMS experiment can now address the missing strspn primitive and
version-aware include resolution/Sodium loader strategy, then repeat the original
UTF-8 and SHORTINIT diagnostics. The ordinary bootstrap also needs conditional
ABSPATH resolution. Database-backed bootstrap, plugin discovery, themes, whole-document KSES,
CSS and arbitrary block rendering require separate verified profiles. Current
subsystem coverage does not establish their compatibility.


## Portable build tools, 2026-11-18

Native builds, source-only verification, compiler comparisons and the PHP hybrid
scripts now invoke the documented toolchain directly from PATH. Personal agent
helpers, scheduling wrappers and private directory discovery are absent from the
repository scripts. A version manager is optional; the hybrid build manifest
resolves the selected compiler executable from PATH.

The hybrid source preparer creates its output directory and copies the maintained
elephc.toml and elephc.lock. The build installs the locked PCRE2 dependency before
compilation. All required dependency inputs are maintained in the repository.
The generated native PHP source remains byte-identical to the measured build.

Verification used a restricted PATH containing the complete SDK, PHP toolchain
and standard system tools, with no version manager or personal scheduling helper.
A source-only export rebuilt the native profiles and passed all 3476 HTTP checks
and the differential CLI corpora. A separate temporary hybrid project compiled
the shared library, linked and called the PHP extension, and passed an exact
Elephc profiling assertion. Both temporary trees were removed.


The selected compiler passes 136 focused Rust checks, 35 native/PHP modes and
155 target emissions. Its 32-row child-property fixture matches PHP with clean
heaps in both IR modes and reduces the bounded caller assembly by 23.1 percent.
Only assembly-comment spacing changed after the build; the compiler metadata
retains the original and published source hashes and mechanical formatting proof.

The complete source graph generates 17,718,439,299 assembly bytes and a
4,215,159,224-byte combined object, including 2,105,038,654 code bytes. The default
GNU link exceeds relative relocation reach. The public section-placement linker
now links that exact object in three adaptive attempts, with identical allocated
content SHA-256 `23bbcc0240087d52bdadf1c86dad0a8ba1a1a937bd9b3cbf2dc9a083b0cbabd1`.
It keeps every function address distinct, preserves COMMON size/alignment and
visibility, and retains TLS, unwind metadata, RELRO and a non-executable stack.
A separate native control verifies function identity and memory protections.
The compiler runner also passes a real Elephc compile/link smoke check.

That linked object predates the bootstrap corrections and reaches a null object
cache caused by an unsupported GLOBALS write. Cache initialization now uses the
named global cell, and the complete root settings file accesses its eleven global
objects/maps through named cells in the fixed top-level profile.

A second bounded defect affects function-executed constant presence: a literal
`defined` call and `get_defined_constants` do not observe an earlier conditional
definition on the next invocation. A variable argument to `defined` is rejected
by AOT checking. An explicit 65-name bootstrap/configuration inventory now records
successful original define calls and seeds the four original entry definitions.
The original native constants and values remain in use. The original source,
helper checksums and boundaries are recorded in
`elephc/full-source/constant-presence-provenance.json`. General dynamic constant
introspection remains outside this fixture.

The current source preserves 3,782 original files, 1,103 complete-file patches
and 15 fixtures. The original and patched PHP root, 955 additional groups and
five runtime documents still agree. Eighteen native constant-policy comparisons
pass in both IR modes, and 113 Multisite comparisons pass with IR off. A normal
public build generated all 17,719,906,851 assembly bytes in 1,084.12 seconds,
then stopped at native-package lookup because its temporary GCC wrapper changed
the PATH-based cache fingerprint. The public runner now uses `COMPILER_PATH`
linker interception while preserving `PATH`. Two real PCRE2 compile/link controls
and the complete prior object pass that path. An exact SDK assembly resumption
is checking the current startup before another ordinary build. Native root and
document parity remain unverified.

The current complete 17,719,906,851-byte assembly has now assembled into239
function-section groups and linked through the public LLD placement path in three
attempts. A private recovery adapter initially selected raw ld instead of the
SDK's gcc driver; all groups were preserved and combined with the exact SDK flags.
That diagnostic recovery is separate from an ordinary successful build. The
19 prior Rust regressions have also been refreshed successfully on the unchanged
selected compiler sources.

Native startup now passes the cache initialization frontier, then fails callable
validation for `_wp_add_global_attributes` inside KSES before its source
declaration. A two-file probe reproduces this forward-declaration activation gap
in both IR modes; moving the declaration before its use matches PHP with clean
31-allocation heaps. The complete KSES patch now moves its original function and
documentation before initialization, retaining its entire body. The public
negative compiler reproduction is `elephc/full-source/probes/callable-forward-include.php`.
Current source identity is `a8cd46722c7d5db534a10e68f6079691b7b4fda5159025018cafacd8c7f655d5`;
an ordinary rebuild and complete native PHP parity are still required.

A debugger-only activation of that original KSES callback reaches block-support
registration and exposes an initially null class-static singleton returning null.
The isolated `nullable-static-singleton.php` probe reproduces this in both IR
modes. Explicit `mixed` property storage preserves the object; narrowing only
the return type does not. The current complete-file patches annotate25 such
properties across18 complete original files. The property controls pass3,189
PHP comparisons in bothIR, including scalar types, array snapshots, object
identity and reset. Original/patched PHP startup,955groups and five documents
still match. Source5343fe7c5eab796271045b574e3be29090ef925ff7ac7bede9040914dcf2d495
is now undergoing an ordinary public build followed by native parity. Debugger
state changes have not been counted as successful native execution.

The first ordinary build of those annotations stopped during checking after
159.63seconds: three existing concrete/nullable singleton return contracts require
checked projections of the mixed static cells. The original contracts and early
null returns remain in place. Three identity-view helpers in the existing
`aot-core-contracts.php` fixture now preserve them. The extended property control
passes3,192 comparisons in bothIR, including concrete and nullable factory
identity, and complete original/patched PHP startup and five documents agree.
The mechanical audit in `nullable-static-property-audit.json` verifies the25
storage annotations and three checked return projections against the preceding
complete source files. Current source36f533baa77cd7f5f33d82f08dbb8c7a7e203e4f8a00260fa1762870d35bd235
is undergoing another ordinary build; complete native parity is still pending.

The ordinary public source36f533ba build now exits0 after2,278.82seconds. It
generates17,721,100,721 assembly bytes, combines239 groups into4,215,873,576
object bytes and links2,105,357,842 code bytes without folding. Both allocated
content hashes are b5be8bd9867942e79393d8a329e69084ef2d1b9fb146a2cb8c27a8349a321773.
Native startup reaches create_initial_post_types/get_option and the original
wpdb::prepare before a fixture override returning string corrupts its inherited
mixed-return call. A two-mode bounded reproduction isolates the ABI gap; matching
fixture return representations preserve actual string values. Complete runtime
parity is still pending. The SDK strips its ordinary executable and removes its
combined user object after linking; WORDPRESS_KEEP_DEBUG=1 now retains an exact
symbolized copy before stripping, with a passing native linker control.

The SQL frontier now has a complete-file regression control. It loads the
original database, hook and error files for PHP and their entire prepared
counterparts for native execution, with the same isolated SQLite fixture.
It compares93 results covering scalar/array prepare arguments, quotes, percent
escaping, Unicode, cached scalar values, row/result modes, column reads and
fixture options. BothIR modes pass; the SQL group totals341 comparisons.

An uninitialized optional local column array in original get_var loses nonempty
string results natively. Initializing it before the original branch restores
parity. Explicit mixed result storage, checked row views, return boxing and
PHPDoc changes did not fix the complete-file case and were removed. The exact
placeholder count uses an integer projection because the SDK's floating division
otherwise triggers the original strict diagnostic for equal counts.

Native uniqid now follows the pinned PHP8.4 clock/format algorithm with native
CSPRNG entropy, passing2830 comparisons in bothIR. Its NUL-free prefix, explicit
argument-type, thread-local and CSPRNG-failure boundaries are documented in the
native API manifest. Full source0e2e6f21 is undergoing another ordinary build;
complete native root/document parity is still pending.

The ordinary source0e2e6f21 build exits0 after2176.85seconds, with239 complete
assembly groups,17722429793 assembly bytes,4216197480 object bytes and2105513491
code bytes. Allocated contents remain identical under the public linker,
SHA936579224b762b4e2b13bc1db34cfc5469aa1c67e92367d636e4e5eeca23e8a0,
with function folding disabled. Native execution passes the SQL frontier and
stops at original remove_post_type_support: the nested unset has erased storage
without a native removal path.

That whole-file patch now uses the existing value-array deletion adapter for an
existing parent map and preserves absent parents. The mutation control passes
10273 comparisons in bothIR, including128 feature-map scenarios checking null,
false, numeric-string and sparse keys, order, aliases and snapshots. The actual
bootstrap probe also calls the original removal routine for these scenarios and
restores the original global map. Complete original/patched PHP and five documents
still match. Current source3fc3c636 is undergoing a new ordinary native build.
Root/document native parity remains pending.

Private GDB runs that skip the removal routine encounter an allocation failure.
Those altered executions are diagnostic only and do not prove a native heap bug
or successful startup. The current source must run without skipped functions.

Source3fc3c636 also compiles/links0 in2172.52seconds:239groups,17754937220assembly
bytes,4221414040object bytes and2108858265code bytes. Allocated SHA is
12d425f28929714515bd8809491905ea5d3ac9a99ed1d4312ce2e1d8e95d6bae before and
after placement, with no folding. Its unmodified native run passes feature
deletion but exhausts512MiB while scheduling a recovery-mode cron event.

Standard GDB unwinding at the allocator names an unrelated Rust constructor.
Walking saved frame pointers resolves mixed-array get-for-write growth, original
wp_schedule_event and WP_Recovery_Mode::initialize. The cron key is a sparse Unix
timestamp. A fresh associative map, constructed with a transient removed string
seed, preserves the original empty result and avoids dense numeric allocation.
The full scheduling algorithm remains original.

A new control loads complete cron/plugin/error files and compares scheduling,
queries, duplicate/error paths and serialized request-local option storage with
original PHP. Natural comparison now accepts integer keys by PHP string
conversion. Event record literals retain schedule/argument/interval values
through explicit mixed identity views; otherwise native storage converted strings
and false to0 and argument arrays to their lengths. Cron45 and string1448
comparisons pass bothIR, and complete original/patched PHP/five documents agree.
Actual HTTP dispatch, job callbacks, locks and the rest of the cron lifecycle
remain outside this control. All47 control groups retain the earlier46.
Current source29b724a1 is rebuilding; full native root/document parity is pending.


Source43d0e195 compiles and links through the ordinary public command in2214.81
seconds. All239 assembly groups and three portable LLD attempts pass. Assembly
contains17755110259bytes, the object4221457480bytes and code2108877201bytes.
Allocated contents are identical after placement, with function folding disabled.
The unmodified native program passes cron and actual widget registration during
init, then reaches the application probes. Both private widget registries use
named global cells; the22 original deprecated callbacks remain unchanged. The
PHP baseline verifies19 default widgets and22 deprecated callback names.

The next observed failure is the original wp_strip_all_tags @script/style regex.
The fixture now accepts all27 unpaired ASCII punctuation delimiters, leading
ASCII whitespace and the first unescaped closing delimiter. It ignores modifier
spaces, carriage returns and newlines as PHP does. Paired and non-ASCII delimiters
remain explicit boundaries. Replacement15276 and match/split19874 comparisons
pass with IR on and off, including the exact original WordPress pattern.
Complete original/patched PHP bootstrap and five documents still agree. Current
sourcee940badb is rebuilding; full native root/document parity remains pending.


Prepared source `e940badb` compiles and links through the ordinary command in
2,645.94 seconds. All 239 assembly groups and three portable LLD placement
attempts pass. The assembly has 17,755,293,552 bytes; the combined object has
4,221,496,976 bytes and 2,108,897,210 code bytes. Allocated SHA-256 remains
`d17673abfe70a14bc58140fea80baf8e0ed860652ba454a9f8caf78a888d5018`
before and after placement, with function folding disabled.

The unmodified executable passes the original `wp_strip_all_tags()` pattern and
reaches script registry initialization. Its next failure is the explicit
`hash_hmac_algos()` fixture boundary. Whole native bootstrap and document parity
remain pending. Staged HMAC, query-property, named-capture and builtin-callback
diagnostics are not counted as completed native WordPress execution.


Prepared source `a80a4bc8` verifies the HMAC capability catalog through the actual
native HMAC implementation. All 44 canonical lowercase names, their ordering
and digest vectors match PHP. Uppercase direct algorithm aliases remain a
separate compiler boundary. Whole original hook files retain callback keys and
use a first-class descriptor when invoking the builtin `strip_tags` callback.
Complete PHPMailer and SMTP controls preserve named captures, language fallback
dictionaries, error dictionaries and value snapshots without runtime diagnostics.

Both IR modes pass crypto 2,356, callable 89 and property-cursor 3,252 comparisons,
including 54 whole-hook and 60 whole-mailer comparisons. Regex controls now also
cover PHP's ignored `S` and `X` modifiers: replacement 17,220 and match/split
21,332 comparisons pass in each mode. Original and patched PHP root bootstraps
and five documents agree. The ordinary full native rebuild is in progress;
complete native root/document parity remains pending.


Prepared source `a80a4bc8` compiles and links through the ordinary public command
in 2,548.45 seconds. All 239 assembly groups and three portable LLD attempts
pass. The assembly contains 17,755,718,310 bytes; the object contains
4,221,600,080 bytes and 2,108,942,991 code bytes. Allocated contents have the
same SHA-256 before and after placement:
`e63a912b5172f2fe721f5cd5b701ee65f62405571aa236c7f4abab9d770c2f51`.
Function folding remains disabled.

The unmodified native executable now passes the HMAC, hook and PHPMailer
frontiers. It reaches original theme pattern loading and fails nested
`filePath` deletion in `WP_Block_Patterns_Registry::get_content`. The root probe
also needs an explicit binding for the existing theme-feature global. Both
corrections are staged for whole-class/native qualification. Complete native
root/document parity remains pending.


Prepared source `d0fff16d` replaces only the original nested pattern metadata
`unset` with the existing checked value-array deletion adapter. Complete-file
checksums and the nullable-static audit include this additional rewrite. The
root probe now explicitly binds the existing theme-feature global.

The public whole-registry control passes 60 registry comparisons and 24 adapter
comparisons in both IR modes, without runtime diagnostics. It executes the
original `more-posts.php` template and verifies first-read path snapshots, cached
content, independent outside-init state, supplied empty content and unregister.
Its escaping and block-hook environment is explicit and bounded; the full
bootstrap retains the real helpers and all 109 original theme includes.

Both IR modes pass mutation 10,357, crypto 2,356, callable 89, property-cursor
3,252, regex replacement 17,220 and regex match/split 21,332 comparisons through
the public harnesses. Complete original/patched PHP bootstrap, five documents
and 955 additional PHP groups agree. The ordinary native rebuild has started;
whole native root/document parity remains pending.


The ordinary build of source `d0fff16d` completes in 2,624.02 seconds. All 239
assembly groups and three LLD placement attempts pass, with 17,754,243,474
assembly bytes, 4,221,253,296 object bytes and 2,108,761,605 code bytes. The
unmodified native root now completes and its default rendered markup matches
original PHP. Its complete state still differs in nine areas. These include
27 static block registrations with pointer-valued paths, runtime cookie and
time constants, 30 pattern URL hashes, REST object filtering, two query array
snapshots, two observed term names, six capability results and block-dependent
theme caches. Five native document comparisons remain behind the root gate.

Source `d45c1a35` adds a closed 17-String bootstrap data inventory, integer time
operands and directory-based bundled block paths, preserving original define
calls and all registrars. REST object traversal uses public value views; the
probe's value observations keep original cache/reset operations. The complete
Style Engine class directly dispatches its two immutable protected callbacks.
A whole-file capability control reproduces six null returns with missing final
filter calls. All 66 exits from its single switch now jump to the original
filter position, preserving all cases and the original return contract.

Original and patched PHP bootstraps still agree on all 116 blocks, 109 patterns,
five documents and 955 additional groups. Focused both-IR controls pass 80 Style
Engine comparisons and 52 capability/filter comparisons without runtime
warnings. Public constant and property controls add runtime String, time-unit,
public object value and snapshot checks. The new ordinary native build remains
in progress, so none of these focused results establishes complete native root
or document parity yet.


The first native attempt for `d45c1a35` stops after 26.03 seconds on five
parenthesized `require` path prefixes, before code generation. PHP accepts this
syntax; the pinned native parser does not. The whole block index now flattens
all 11 directory expressions, retaining the same bundled files and data paths.
Current source `c6077b21` is repeating the reference and native preflight before
a fresh ordinary build. The compiler is unchanged.


Source `c6077b21` compiles and links in 2,580.91 seconds: all 239 assembly groups
and three LLD attempts pass. The assembly has 17,759,836,087 bytes, the combined
object 4,222,630,256 bytes and its code 2,109,437,995 bytes. Allocated contents
retain SHA-256 `8d50f2067bc4ae917119bcc9a4088ef79b4cbd5a37f38217394409b9a605856d`
with function folding disabled. Native binary SHA-256 is
`2adbfe0de68ef3f604d826cc354c27ffaa0eca5635e88175965c0a991bc6dffd`.

The actual unmodified default root now matches every original-PHP state field,
including all nine previously divergent areas, 116 registered blocks and 109
pattern hashes. Independent execution takes 10.89 seconds and emits only the
enabled GC statistics. Empty, dynamic-title and video document states also match
fresh original-PHP states. Video still emits lexer warnings for literal `$1`.
Nested-group rendering fails cleanup of erased hook iteration arrays, and the
navigation document crashes while transferring `showSubmenuIcon` context. The
core confirms an invalid Mixed reference at that assignment, without evidence
of resource exhaustion. Full document and source-only native gates remain open.

Focused complete hook files now pass 128 comparisons in both IR modes with
explicit value-array deletion and dense current-filter stack writeback. A small
probe proves class-constant `isset` incorrectly accepts absent selector keys;
`array_key_exists` passes the equivalent non-null-map predicate. Another probe
proves numeric dollars in constant double-quoted strings are interpolated
incorrectly; escaping them preserves PHP bytes and native output. These changes
are staged, with whole-block context qualification still in progress.


Source `ffac7772` applies the seven qualified complete-file corrections. Both IR
modes pass the public constant 142, string/style 1,528, callback/capability 215,
property 3,333 and regex replacement 17,230 comparisons. The whole block/list
constructor control passes 65 comparisons after capturing both context and
cached attribute values; context projection alone did not fix nested magic
attribute reads. Complete original/patched PHP, all five documents and 955
additional groups agree. The new ordinary native build is in progress with the
same frozen compiler. Its full native document parity remains pending.


The resumed ordinary build of source `ffac7772` completes in 2,731.86 seconds,
with 17,758,901,473 assembly bytes, a 4,222,411,800-byte combined object and
2,109,322,145 code bytes. All 239 assembly groups and three LLD attempts pass.
Allocated contents retain SHA-256
`753d61c0177950f1afc1f85e1d07aa10c3905222dcd7472f0ddaa88ed01186eb`
after placement; function folding remains disabled.

The strict native harness passes default and empty state comparisons, then
nested Unicode rendering still fails erased-array deletion inside hook cleanup.
The generic value-array adapter still contains a native `unset`, whose removal
path is absent for this complete-graph specialization. The earlier focused
whole-hook success did not establish this runtime path. A whole-class candidate
rebuilds only the three private integer-level maps, whose writes always use
explicit keys. It is awaiting native qualification and has not been applied to
the maintained patch series. Complete document and source-only parity remain
pending. The compiler is unchanged.


Independent executions of the same `ffac7772` binary confirm complete original-PHP
state equality for default, empty, dynamic-title and video-shortcode, with only
GC statistics as runtime diagnostics. Native SHA-256 is
`0e56892171509def4b1535a9b703516800fc13de3ae4ed91879b33499cdff8b8`.
Both nested Unicode and navigation now fail on the same erased-array hook
cleanup. Navigation reaches rendered markup instead of the earlier invalid
context-reference crash. A candidate for the private maps matches original PHP
across 175 groups and 939 comparisons, including the 147 existing mutation/cursor
scenarios, but has not yet passed native qualification. The first native test
attempt stopped at the SDK parser's unsupported `require` inside `foreach`; the
test now stores the complete included result before iterating it. No WordPress
function ranges were extracted.


The complete hook implementation now passes 175 groups and 939 comparisons in
both native IR modes, without runtime diagnostics, against original PHP.
The tests retain the 147 existing priority-mutation/cursor scenarios and isolate
their local variables in a fixture function. The first combined test exposed a
string/object local-name collision, so the scope change belongs to the test;
the WordPress files remain complete.

The qualified private iteration-map projection is applied in source `461f71b4`.
All other prepared files are unchanged. Original, patch and resulting complete
class checksums are recorded. Component metadata also binds the cursor-case
file hash. Seven public groups are queued in both IR modes before a fresh
ordinary full build. Full native document and source-only gates remain pending.


Source `461f71b4` compiles and links in 2462.13 seconds with
17,759,031,809 assembly bytes and 4,222,443,200 object bytes.
The nested document still throws the same error. Live native tracing corrects
the earlier attribution to hook cleanup: the actual caller is
`WP_Theme_JSON::sanitize`, then its constructor, the Theme JSON resolver, global
settings and layout support before reaching the hook dispatcher. Line 1357
removes `viewport` from a per-block schema; the PHP-style trace omits those
intermediate native frames.

An XML-enabled complete-class control reproduces this exact failure. The local
`VALID_SETTINGS` snapshot and 13 non-null block-dictionary presence predicates
pass 66 groups and 330 comparisons in both IR modes without runtime diagnostics.
Inputs include both original theme JSON files and allowed/denied style variations.
The finite environment collaborators throw for unused migration, registry, CSS
and capability paths. The exact previous hook class passes all 939 comparisons
with XML enabled too, so the five private-map projections introduced in the
previous attempt were removed.

The qualified changes are applied in source `701b8bd5`; original/patch/result
hashes and component dependencies are recorded. All 18 nullable-static audit
files and 25 slots are unchanged. Original/patched PHP bootstrap, five documents
and 955 other groups agree. Seven public native groups precede the next full
ordinary build; native document and source-only parity remain pending.


The schema-corrected source `701b8bd5` completes ordinary public compilation and
linking in 2201.97 seconds, emitting 17,756,317,206 assembly bytes and a
4,221,805,816-byte combined object. Allocated contents remain identical after
section placement, with function folding disabled. The strict native harness
accepts default and empty states. Nested Unicode rendering advances past the
previous Theme JSON removal refusal and now fails the explicit original `wpdb`
receiver contract. The printed hook frame does not establish the actual native
caller; read-only tracing is the next diagnostic. Complete document parity and
source-only native reproduction remain pending.


Read-only native heap snapshots identify the query cache receiver failure: the
global symbol keeps the same Mixed-cell address, but its object tag and payload
are overwritten after recursive traversal. A bounded global-by-value capture
reproducer fails in both IR modes; its local-capture variant matches PHP in both.
The entire current `WP_Query` class reproduces the same failure. A whole-class
candidate with a local capture snapshot passes 60 groups and 360 comparisons in
each IR mode, including global replacement before capture and during removal.

Prepared source `469ade65` applies that class correction, updates the full-file
patch checksums and adds a public complete-class query-cache component under the
SQL control group. It uses the existing real fixture receiver guard. The remaining
query, taxonomy and metadata environment is explicit and throws on unused paths.
The original dependency tree is unchanged. A fresh ordinary build and both
public native control modes are queued. SDK journal task `b1f6b937` will compare
the reducer with the related borrowed-global fix on `strict-mode`; no SDK source
was edited or rebuilt here. Complete native document and source-only parity
remain pending.


Source `469ade65` completes ordinary compilation and linking in 2157.95 seconds,
producing 17,756,320,866 assembly bytes and a 4,221,806,656-byte combined object.
The strict native harness now matches default, empty, nested Unicode, dynamic
title and video states with clean runtime diagnostics. The database-capture
correction advances the previous failing path.

Navigation also executes without errors. All returned fields agree except its
rendered markup: every navigation label becomes `0`, including the labels that
should preserve text and receive uniqueness suffixes. The exact mismatch is
retained separately. The original naming helpers are queued for read-only native
input/return tracing. Complete document and source-only parity remain pending.


Read-only native return tracing confirms that the original navigation name and
uniqueness helpers return the correct strings. The complete navigation file also
passes 156 bounded comparisons in both IR modes with a recording wrapper
collaborator. Real wrapper tracing instead shows integer zero already at the
input to `esc_attr`.

The complete `class-wp-block-supports.php` reproduces this mismatch for `id` and
`aria-label` in both IR modes. Four explicit internal string callback contracts
preserve the arguments that the original caller already normalizes to strings.
This whole-file candidate passes 409 comparisons per IR mode against original
PHP, including precedence, empty and zero values, Unicode, escaping and input
independence. Finite block-type, safe CSS and escaping collaborators bound the
component control. Prepared source `c19cbe6d` applies only that qualified file
change; public controls and another ordinary native build are in progress.


Source `c19cbe6d` compiles and links through the ordinary public command in
2172.99 seconds, producing 17,756,316,802 assembly bytes and a 4,221,808,432-byte
combined object. Allocated contents are unchanged after placement and function
folding is disabled. The strict native bootstrap and all five runtime documents
now match original PHP with clean runtime diagnostics. Navigation labels retain
their text, Unicode escaping and original uniqueness behavior.

Both IR modes pass the seven public groups with 24,744 comparisons each, including
the complete wrapper file. The final 47-group suite and independent source-only
ordinary native build are queued; their verification remains pending.


Source `c19cbe6d850639389b67fd665a288f491afcf6dfbb929d538db2423bb75d00c5` completes the ordinary public native
build in 2172.99 seconds. The build emits
17,756,316,802 assembly bytes, a 4,221,808,432-byte object
and 2,109,009,393 code bytes. Allocated contents retain SHA-256
`12c9fd825d5e2d24a2a6c40154db9d1625e36c644c075f72864da1ea7298cbe9` after placement, with function folding
disabled. Native binary SHA-256 is `cf8296ba1dc808e3070c2cab814587ce0790641894f201d0a3e3f48e21440dbf`.

The actual native root and all five runtime documents now match the complete
original-PHP states with no unexpected runtime diagnostics. Nested Unicode group
rendering, navigation context transfer and video shortcode rendering pass the
previous failing frontiers. This includes 116 core blocks, 109 original pattern
hashes and the full application-probe state.

All 47 primary native control groups pass 230,504 comparisons with IR
optimization on. A separate source-only export passes all groups with IR
optimization off, performs another ordinary complete native build and repeats
root/five-document parity. Its export is removed. The retained subsystem suite
also passes independently. The published verification snapshot binds these
results to the exact source, compiler, test inputs and linker provenance.

These results establish the isolated single-site CLI fixture, not full HTTP,
administration, authentication, arbitrary plugins or network coverage. No SDK
source was changed for the final WordPress compatibility corrections.
