# WordPress compiler compatibility

`wordpress-main.patch` applies to Elephc main revision
`f20729406216d21ce99841cf4636b771db9874dd`. It contains the compatibility and scaling changes identified
while compiling the complete WordPress bootstrap:

- Include-loaded functions expose their public PHP names to runtime callable
  descriptors, preserving activation order and reference arguments.
- The mixed-storage scan looks up normalized method names in each class instead
  of scanning every method for every call.
- Built-in Iterator and ArrayAccess tentative return contracts honor
  `ReturnTypeWillChange`. Source-defined interface contracts remain strict.
- User ArrayAccess dimension append calls `offsetSet(null, value)`, including
  interface receivers, while preserving the internal container routes.
- Nested conditional once loads share declaration metadata. A file's own guard
  remains distinct from its parent's guard, preserving ordinary redeclaration errors.

- Initialized null locals remain visible in `get_defined_vars()`; unset and
  uninitialized bindings stay absent.
- XML dimension dispatch keeps ordinary boxed array receivers alive after append,
  while concrete nullable object receivers retain their required cleanup.
- Constant loop simulation joins common facts above 64 active paths, preserving
  conservative flow without exponential enumeration of dense branch sequences.
- Virtual slot reachability indexes live descendants and groups shared lineage
  slots, preserving inherited bodies without repeated all-class hierarchy scans.
- Inherited and implicit magic method lookup uses class-indexed implementation
  metadata and examines available methods instead of all program-wide names.
- Numeric loop storage discovers cyclic blocks in one iterative component pass,
  preserving self loops and disconnected cycles without per-block graph searches.
- Opaque call boundaries inspect tracked reference cells and main-scope globals,
  preserving mutation epochs without rescanning every synthetic temporary.
- Function contexts and frame setup share immutable module PCNTL and backtrace
  facts, preserving complete body-table detection without per-function module scans.

The patch includes focused runtime and diagnostic regression tests. It does not
implement references created inside PHP array literals or internal array cursors
on object properties. Complete WordPress source adaptations remain in
`patches/full-source/`.

Use Rust 1.98.1, the tested upstream CI toolchain for this source build.
Build from a separate, clean source checkout at the exact revision:

```bash
# Run from this WordPress repository. The destination must not already exist.
gh repo clone illegalstudio/elephc elephc/build/full-source/compiler -- --no-checkout
git -C elephc/build/full-source/compiler checkout --detach \
  f20729406216d21ce99841cf4636b771db9874dd
git -C elephc/build/full-source/compiler apply --check \
  "$PWD/patches/compiler/wordpress-main.patch"
git -C elephc/build/full-source/compiler apply \
  "$PWD/patches/compiler/wordpress-main.patch"
(
  cd elephc/build/full-source/compiler
  CARGO_PROFILE_RELEASE_OPT_LEVEL=1 \
  CARGO_PROFILE_RELEASE_DEBUG=line-tables-only \
    cargo build --release --locked -j 1 -p elephc --bin elephc
)
```

The recorded Actions SDK is no longer downloadable. Build the matching bridge
outputs from this checkout using the crate inventory in the pinned
[upstream build action](https://github.com/illegalstudio/elephc/blob/f20729406216d21ce99841cf4636b771db9874dd/.github/actions/build-release-artifacts/action.yml):

```bash
(
  cd elephc/build/full-source/compiler
  cargo build --release --locked -j 1 \
    -p elephc-tls -p elephc-pdo -p elephc-crypto -p elephc-openssl \
    -p elephc-bcmath -p elephc-gmp -p elephc-random -p elephc-mbstring \
    -p elephc-iconv -p elephc-phar -p elephc-tz -p elephc-image \
    -p elephc-probe -p elephc-instr -p elephc-web -p elephc-pcntl \
    -p elephc-xml -p elephc-tidy -p elephc-fileinfo -p elephc-tokenizer \
    -p elephc-magician -p elephc-reference -p elephc-curl -p elephc-zend
  cargo build --release --locked -j 1 -p elephc-magician --features curl \
    --target-dir target/elephc-magician-curl-release-build
  cp target/elephc-magician-curl-release-build/release/libelephc_magician.a \
    target/release/libelephc_magician_curl.a
  printf '\n' > target/release/elephc-pdo-features.txt
)
mkdir -p elephc/build/full-source/sdk-local
cp elephc/build/full-source/compiler/target/release/elephc \
  elephc/build/full-source/compiler/target/release/libelephc_*.a \
  elephc/build/full-source/compiler/target/release/elephc-pdo-features.txt \
  elephc/build/full-source/sdk-local/
export ELEPHC_BIN="$PWD/elephc/build/full-source/sdk-local/elephc"
"$ELEPHC_BIN" --print-capabilities
```

The bridge recipe follows the upstream release build. It has not been used to
replace the recorded CI runtime archives in this experiment. Local Rust and native
build dependencies must satisfy the pinned upstream source. Reconstructed SDK
bytes have their own provenance and require the WordPress controls again.
Keep runtime archives beside the compiler as regular files. Runtime archives
represented by symbolic links are rejected by Elephc's SDK validation.

Source build identities include the base revision, this patch's checksum, the
Rust version and build flags. A locally built compiler's binary checksum can
differ across build paths and toolchains. Runtime archives must retain the
verified base SDK identities.

Callable-name CFG states retain only finite facts. Missing entries remain unknown,
and joins, handler roots, reference invalidation and the 32-name budget preserve
open runtime dispatch. This avoids dense locals-by-blocks allocation. The
`elephc/full-source/probes/sparse-callable-states.php` control compares branches,
loops, catch handlers and reference mutation with ordinary PHP.

Module emission indexes normalized function, closure and extern names plus canonical
include dispatch groups once. Direct lookup keeps the original first-match order,
public group spelling and first surviving variant. The per-emission index is shared
with literal-only wrapper snapshots and never reused by another program. Six unit
regressions compare the former scanner with small and large graphs, including all
five targets and independent emissions.

Repeated literal property reads on boxed Mixed receivers share a borrowed synthetic
EIR helper per lexical scope, exact name and fetch mode. The helper runs the original
property dispatcher and returns an owned Mixed value. This bounds repeated assembly
ladders while retaining visibility, accessor, hash, error and cleanup behavior.
Single sites, quiet reads, hook bodies and other representations keep their direct
paths. Focused controls cover all five emission targets and native/eval ownership.

Function-local aliases of named global variables borrow the process symbol slot
instead of promoting an uninitialized local. Ordinary writes to those globals
retain existing Mixed reference wrappers and publish before retiring old values.
Focused native checks cover values, snapshots, captured aliases and destructors;
EIR and assembly controls cover all five targets. The general `$GLOBALS` table
and indexed unset remain separate compiler capabilities.

Emitted class-method membership is indexed once per module alongside callable
names. Exact class spelling, normalized PHP method keys and static versus instance
kind retain the original scanner answers. Literal-only snapshots share the index;
separate emissions stay isolated. The candidate passes two focused membership
regressions, retained ownership controls and all five assembly targets.

Callable-array target selection now joins before argument normalization and
invocation. Instance descriptors transfer their owner through one common path,
while persistent static descriptors use one borrowed path. Candidate order,
selector storage, eval fallback and per-call invocation metadata remain intact.
Two regressions check both joins on all five targets; native probes check named
arguments, inherited targets, identity, nested calls, exceptions and explicit
receiver retirement. Separate diagnostic probes retain the existing weak argument
coercion and implicit scope destruction-order gaps.

Known literal property reads now resolve the runtime class before consulting
dynamic hash storage and magic accessors. An existing entry, including null,
bypasses the getter. The shared owned-result path preserves lazy caches and
subclass layouts. Native PHP/heap checks run in both IR modes and assembly emits
for every supported target.

The positive managed-value probe uses mixed getter returns. Concrete string and
object getter ownership remains a separate EIR boundary, preserved in
`elephc/full-source/probes/typed-magic-cache-owners.php`. Nested Boolean values
can lose their tags when copied through heterogeneous manifests; the WordPress
fixture gives its finite expected booleans explicit casts. The original failing
shape remains in `elephc/full-source/probes/nested-boolean-manifest.php`.

Existing compiler-generated argument collectors now pass unchanged through
contravariant method adapters when the tail type, references and optional-count
protocol match. Fixed arguments retain their boxing and temporary-owner cleanup.
Constructor and ordinary method callbacks preserve argument counts and values;
true PHP variadics keep their existing checks. Native/PHP and heap checks run in
both IR modes, with assembly emitted for all five supported targets.

Final symbol visibility now consumes the owned assembly buffer and appends
its directives in place. This avoids a second complete-body allocation while
retaining export allowlists, linker internals and deterministic directive order.
Seven focused visibility controls include allocation reuse and exact output for
all five supported targets.

Large Linux programs now assemble serially at complete function-section boundaries.
Cross-section defined local symbols of every spelling become hidden linker symbols, and all resulting objects
are combined with a relocatable link. Large assembly and IR buffers are released
before invoking the assembler. Repeated copies of the same physical conditional
function declaration lower once while runtime include guards remain unchanged.

Guarded runtime property getters extract an owned concrete receiver after the
class check while retaining the common boxed owner. Named and computed reads
keep subclass dispatch, ordinary fallback and exception cleanup.

Already selected native XML property write-fetch operations bind directly to their
native method, preserving the boxed receiver ABI and ordinary user magic calls.

Repeated literal property writes share their ordinary backend implementation by
lexical scope, exact spelling, opcode and value representation. Helpers borrow
caller owners and retain each call site diagnostic line.

Dynamic property child probes share one raw-object read and classification arm
after exact-class membership checks. Declared fields and magic getters retain
their separate paths and parent ownership.

Unconditional functions in an included file are currently activated at their
source declaration, so a string callback invoked earlier in that same file
reports unavailable. The PHP/native diagnostic is
`elephc/full-source/probes/callable-forward-include.php`. The complete KSES patch
places its original `_wp_add_global_attributes` function before file initialization
while retaining its entire body. This preserves PHP callback availability for
that source file without changing generic callback validation.

An initially null untyped class-static property can also discard later singleton
object assignments. `elephc/full-source/probes/nullable-static-singleton.php`
retains this diagnostic. Complete-file patches give the matching 25 original
static properties explicit `mixed` storage, preserving their defaults and values.
The property controls compare scalar types, array snapshots, singleton identity
and reset against PHP in both IR modes.

Covariant string overrides called through an inherited `mixed` return can lose
their result representation. The synthetic diagnostic remains in
`elephc/full-source/probes/covariant-string-return.php`. The SQLite fixture uses
the parent's `mixed` return declarations for its three overrides, preserving the
actual returned strings. This does not replace WordPress query preparation.

The original `wpdb::get_var` also needs its optional local column array initialized
before conditional population. Without that initialization the native getter
returns empty strings for nonempty SQL values. The complete-file SQL control
compares prepared queries, cached scalar results, rows, columns and result sets
with unmodified WordPress under PHP in both IR modes. The exact placeholder count
uses an explicit integer projection because native division otherwise produces a
float before the original strict count comparison.


Runtime-computed string constants and constants defined from mixed values can be
read as numeric addresses. The diagnostic is
`elephc/full-source/probes/runtime-constant-values.php`. An explicit integer view
at the definition preserves the tested integer values; a string cast at the
read does not recover a string that was already interpreted as an integer.

`is_callable` also reports false for legal protected static callbacks created
inside their own class, including a captured first-class descriptor. Calling
that descriptor still succeeds. The diagnostic is
`elephc/full-source/probes/protected-static-callable.php`. These observed SDK
boundaries remain separate from verified native WordPress behavior.


The complete capability-file control also reproduces null results and a missing
final `map_meta_cap` filter when conditional exits leave its large switch.
Replacing only the 66 exits targeting that switch with jumps to the original
filter position passes 52 original-PHP comparisons in both IR modes. The public
control loads the complete file and an explicit finite dependency environment;
this is not a minimized standalone compiler test. See
`tests/full-source/capabilities-cases.php` and its support file. The WordPress
patch retains every case and the original final filter and return contract.


A dynamic `isset` lookup into a class constant array returns true for missing
keys on the selected compiler. The standalone
`elephc/full-source/probes/class-constant-array-isset.php` reproduces this in both
IR modes. The immutable Theme JSON selector map contains only non-null arrays,
so `array_key_exists` preserves its original predicate without reading absent
values.

Double-quoted literal `$1` and other numeric dollar sequences remain literal
under PHP but are incorrectly interpolated by the selected native lexer.
`elephc/full-source/probes/numeric-dollar-literals.php` reproduces wrong values
and warnings in both IR modes. Escaping the dollar preserves the original PHP
string bytes and avoids the native interpolation path.
