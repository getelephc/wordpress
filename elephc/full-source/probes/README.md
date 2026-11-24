# Compiler boundary probes

These probes distinguish remaining compiler boundaries from base-SDK failures
repaired by the maintained compatibility patch. The current toolchain is main
`f20729406216d21ce99841cf4636b771db9874dd`. They are separate from the passing
native controls and do not prove that WordPress starts natively.

```bash
"${ELEPHC_BIN:-elephc}" --php-version 8.4 --check elephc/full-source/probes/reference-elements.php
"${ELEPHC_BIN:-elephc}" --php-version 8.4 --check elephc/full-source/probes/reference-assignment.php
```

PHP prints `after` for each file. The SDK rejects reference elements in an array
literal and reference assignment into an array field.

```bash
php elephc/full-source/probes/callable-include.php
"${ELEPHC_BIN:-elephc}" --php-version 8.4 elephc/full-source/probes/callable-include.php
./elephc/full-source/probes/callable-include
```

PHP prints `WORDPRESS`. On the unpatched SDK, the native executable compiles but fails to invoke the
included user function through its literal string name. The diagnostic describes
an unlinked runtime-only builtin, although the target is a declared user function.
Both `require` and `require_once` reproduce the failure. The broader diagnosis
also found that `function_exists` and `is_callable` return true; adding an unused
first-class root does not repair string invocation. An entry-file control works.

First-class callbacks, captured closures, method arrays and invokable objects
preserve reference updates in the separate positive control
`tests/full-source/callable-cases.php`. The maintained [compiler patch](../../../patches/compiler/README.md) adds public
include-loaded descriptors and passes focused native string-callback tests. The
complete CMS bootstrap and document checks are separate, with their current
results recorded in the project README and verification snapshot. The array reference probes
remain unsupported by that patch.

## Generic runtime variadic forwarding

```bash
php elephc/full-source/probes/variadic-forward.php
"${ELEPHC_BIN:-elephc}" --php-version 8.4 elephc/full-source/probes/variadic-forward.php
./elephc/full-source/probes/variadic-forward
```

The probe compares direct calls, forwarding from a generic array and forwarding
from typed array variadics. With the maintained compiler compatibility patch, the generic
array forwarded after a fixed prefix becomes a null variadic tail. The PHP
result retains the original values. Direct explicit calls and the other two
forwarding forms work. WordPress pack and merge call sites therefore package
source argument vectors explicitly. This boundary remains open in Elephc.

## Nullable property storage

`nullable-property.php` prints `[null,0,12]` in PHP. The pinned compiler with the
maintained compatibility changes produces `[0,0,12]` natively. An untyped private
property assigned from a nullable constructor parameter loses the null value.
`nullable-property-explicit.php` adds an initialized `?int` declaration and
matches the PHP result. A separate boxed property and checked getter control
also preserves the three values.

`nullable-property-dynamic.php` adds an unused dynamic property setter behind a
native rejection guard. The current compiler then fails in EIR lowering with
`runtime_call from PHP type Mixed to PHP type TaggedScalar`. The full-source
fixture keeps PHP-only dynamic property operations in `aot-php-platform.php`
and provides explicit throwing native counterparts in `aot-platform.lfc`.
Removing the PHP setter from the native graph restores the 145-comparison
bundled AI control. These probes concern storage and backend behavior; a
successful `--check` alone does not detect the failures.

## Dense loop paths

`dense-loop-paths.php` contains 36 sequential conditions in one loop body.
Its PHP and native output is `0:172:136:344:272:72:36:144:72`, covering zero
iterations, both branches, ordinary iteration, break and continue. The original
optimizer enumerates branch combinations without a path budget. The maintained
patch joins shared constant facts above 64 active paths. Seventeen focused Rust
loop tests and this native CLI control pass; assembly emission also passes on
all five supported targets.

## Virtual slot families

`vtable-families.php` dispatches overridden and inherited methods across 544
classes in 32 unrelated hierarchies. Its 256 result records match PHP. The
maintained reachability pass indexes live descendants and groups occupants of
the same virtual slot, preserving behavioral roots without repeated scans of
unrelated classes. Forty-six declaration reachability tests pass, and this
probe emits assembly on all five supported targets.

## Sparse inherited methods

`sparse-inherited-methods.php` uses 96 unrelated parent and child pairs with
96 distinct inherited method names. PHP produces 96 result records. This probe
checks that class-indexed reachability retains the selected inherited bodies
without scanning every global method name for every unrelated class. Its 96 native
records match PHP, and assembly emission passes on all five supported targets.

## Numeric storage cycle discovery

`numeric-cycle-graph.php` combines 256 branch points with a loop-carried counter
that promotes from integer to float. PHP prints
`int:0|float:-2298|float:3054|float:378`. The backend previously searched the full
control-flow graph separately from each block. An iterative component analysis
preserves the same cycle predicate. Three focused graph regressions pass;
the native probe matches PHP and emits assembly on all five supported targets.

## Escaped callable locals

`escaped-callable-locals.php` keeps 256 private locals visible while a harmless
destructor runs. PHP prints `259:open`: the local inventory remains complete and
the unchanged closure retains its explicit binding scope. The maintained compiler
patch finds tracked reference cells and main-scope globals directly,
instead of rescanning every synthetic temporary at each opaque call boundary.
Its native output matches PHP, and assembly emission passes on all five targets.

## Terminal selected methods

`selected-method-terminal.php` gives source and magic method targets alongside a
ReflectionClass candidate. PHP prints `ok|magic`. The full WordPress graph has
exposed a compiler panic when runtime `newInstanceArgs()` arrays terminate a
selected reflection arm and the outer dispatcher still tries to store its
result. The minimal probe reproduces the same compiler panic (exit 101) with the
maintained twelve-change compiler, while PHP prints the expected result.

The complete SimplePie registry keeps the dynamic reflection constructor and
that method behind the existing PHP-only platform boundary. Its PHP counterpart
uses the actual reflection object and preserves the constructor argument array.
General runtime reflection construction remains outside native verification.

## HTML encoding arguments

`htmlspecialchars-four-args.php` prints `&amp; &lt;` under PHP. The current SDK
rejects its fourth argument during checking. The complete WordPress graph also
exposed this unsupported arity during final EIR validation. Its native UTF-8
adapter retains `double_encode=false` through the existing doctype entity table;
the focused encoding and decoding control passes 87,196 comparisons. Invalid
UTF-8, other charsets and `ENT_DISALLOWED` remain explicit encoder boundaries.

## Spreading values from mixed storage

`spread-array-values.php` prints `[[1],[2]]|[[1],[2]]` under PHP. Its redundant
spread literal fails native EIR validation with an `Array`/`Hash` operand layout
mismatch. Passing the original `array_values()` result directly to an array
parameter works. The complete widgets file uses that equivalent direct argument
list for its merge adapter. Positive controls also cover sparse numeric keys,
string-key overwrites, nested values and empty inputs.

## Negative UTC timestamps and format escapes

`gmdate-negative.php` compares timestamps -2, -1 and 0 with calendar fields,
the signed Unix `U` field and terminal format escapes. The SDK treats explicit -1
as an omitted timestamp, emits unsigned text for negative `U`, and drops the NUL
byte PHP emits for a trailing backslash. This diagnostic uses the raw builtin.
The separate `wp_aot_gmdate` fixture corrects these cases in its finite UTC domain;
the combined weak-integer control passes 6,753 comparisons against original PHP.

## Static array value snapshots

`static-array-snapshot.php` returns an empty array from a persistent property cell,
then appends through a local reference to that property. PHP preserves the first
returned snapshot. The SDK changes it to contain `tail`, matching the new cache
value. Declaring the property as `array` reproduces the same defect.
The full-source cache patches append through `wp_aot_array_push`, which modifies
a typed local copy and publishes it back. The expanded property/cursor control
passes 3,087 comparisons, including independent nested snapshots, clearing and
nullable cache lifecycle operations.

`object-static-callable.php` compares a public static method through an object
and through its class name. PHP prints `[true,true]`; the pinned compiler builds
it but native output is `[false,true]`. This remains a diagnostic. The complete
source editor capability control is explicitly for public instance methods and
does not count this static-object query as supported.

## Static maps containing only null

`null-static-map-defaults.php` returns the three null annotation values directly
and inside another map under PHP. The selected compiler rejects the untyped static initializer
with a `Void` value-type diagnostic. Declaring that property as `mixed` or `array`
builds and matches PHP in a reduced control. The complete `WP_Ability` patch uses
`mixed` storage and boxed late-static reads, retaining the original three nulls.
Thirty complete-class metadata scenarios pass 270 native/PHP comparisons.
Property type reflection and untyped subclass redeclarations remain compatibility
boundaries of the explicit static declaration.

## Lazy magic properties and finite Boolean manifests

`cached-magic-properties.php` checks cached null, false and arrays, inherited
layout offsets, literal and boxed reads, and the `isset`/`empty`/`??` call order.
Its concrete float, Boolean and array results and mixed string/object results
match PHP, including independent array copies and object identity. Both IR modes
have clean heap statistics and emit assembly on all five targets with the
maintained compiler patch.

`typed-magic-cache-owners.php` retains the concrete string/object getter variant.
Its values match PHP on the selected compiler, while heap diagnostics retain
eight allocations. This is a known ownership boundary and is separate from the
positive mixed managed-value profile.

`nested-boolean-manifest.php` preserves Boolean entries in the original rows but
repackages their copies as integer zero/one natively. The WordPress test oracle
casts its finite expected entries to Boolean explicitly. The lazy getter, key
presence checks, cache storage and application algorithms remain original.

## Generated argument collectors

`generated-collector-interface.php` checks contravariant constructors and methods
with compiler-generated argument snapshots. A backtrace that includes arguments
enables snapshots for interface declarations and implementation frames. The
adapter forwards the existing collector while boxing the fixed parameters,
preserving argument counts, extra values, captured calls and broad constructor
inputs. Results match PHP, both IR modes are heap-clean and all five targets
emit assembly. This verifies the compiler boundary reached by the complete feed
cache classes; it does not execute feed storage or remote retrieval.

```bash
php elephc/full-source/probes/generated-collector-interface.php
"${ELEPHC_BIN:-elephc}" --php-version 8.4 --heap-debug --gc-stats \
  elephc/full-source/probes/generated-collector-interface.php
./elephc/full-source/probes/generated-collector-interface
```

## Guarded property reads and repeated writes

`guarded-property-getters.php` checks concrete getter selection behind runtime
class guards, receiver lifetime and independent values. Its 16 result rows match
PHP and both IR modes are heap-clean. `guarded-property-write-fetch.php` checks
ordinary array writes and native XML selection. Values match PHP; its three live
XML probe allocations, totaling 72 bytes, remain an explicit ownership diagnostic.

`shared-property-writes.php` checks repeated literal writes with exact value
types, private scope, magic setters, exceptions and managed array/object owners.
Its 17 rows match PHP with clean heaps in both IR modes. The selected compiler
shares repeated backend bodies without removing the original write operations.

## Temporary dynamic-property children

`temporary-property-children.php` retains 24 dynamic classes alongside declared,
magic and stdClass records. It includes declared padding, inherited private slots and a debug projection, and
checks array snapshots, repeated mutations through
temporary receivers and existing dynamic entries taking precedence over getters.
The selected compiler matches all 32 PHP result rows with clean heaps in both IR
modes. Its shared dynamic-hash child probes use bounded metadata for the real
class layout, preserving declared padding, inherited slots and ordinary dynamic
entries even when debug projections hide them from dumps. All five targets emit
assembly. The bounded caller is about 23.1 percent smaller; complete WordPress
linking and execution remain under verification.

`covariant-string-return.php` isolates a known negative result: a child string
override called through an inherited mixed-return method loses its value in both
IR modes. Matching the fixture override's return declaration to the parent fixes
the actual SQL path while preserving its returned string. This probe is a
synthetic dispatch diagnostic, not an extracted WordPress method.

`conditional-array-local.php` retains the synthetic getter matrix for another
negative result. A local array first assigned inside a branch produces empty
strings for nonempty column values, while the comparison shapes preserve PHP
values. The complete WordPress patch initializes the local array before the
original branch. The separate complete-file SQL control verifies the correction
with real query preparation and result getters in both IR modes.
