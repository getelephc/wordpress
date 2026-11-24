# Native API boundaries

The complete-source port preserves the original capability checks and function
bodies. Calls to PHP APIs missing from the pinned SDK pass through
`wp_aot_native_api`. The [explicit manifest](../../elephc/full-source/native-boundaries.json)
lists those APIs and their source call counts.

The PHP reference invokes the actual PHP function. Native execution raises
`LogicException` naming the missing API. No successful native behavior is claimed
for these calls. This includes HTTP header/cookie state, password hashing, some
file, compression and XML operations and runtime introspection.
The original `function_exists` guards still inspect the original API
name, so the adapter does not advertise a missing capability.

The adapter also routes `uniqid` to a bounded native implementation based on the
[PHP 8.4 algorithm](https://github.com/php/php-src/blob/9af71942c6610852dd6350cf19e278628c8b5b60/ext/standard/uniqid.c).
It uses the native microsecond clock and CSPRNG, with PHP's hexadecimal timestamp
and optional decimal entropy format. The manifest records its pinned source
identity separately from unsupported APIs. Its control checks 588 deterministic
format cases and generated identifier invariants against PHP in both IR modes.
Prefixes must be NUL-free strings and the entropy flag must be Boolean. Weak
argument coercion, thread-local parity and the PHP fallback after CSPRNG failure
are outside this fixture. WordPress uses this path for SQL placeholder escaping.

`hash_hmac_algos` now derives its catalog from the actual native `hash_algos`
list, excluding entries rejected by native HMAC. Its 44 canonical lowercase
names and their order match PHP. The crypto control compares their digests,
binary outputs, long keys, NUL data and rejection paths. WordPress keeps its
original strict algorithm-membership validation. Direct uppercase HMAC algorithm
names remain an SDK boundary, reproduced by
[`hmac-algorithm-case.php`](../../elephc/full-source/probes/hmac-algorithm-case.php).

Hook invocation maps the original `strip_tags` string to a first-class native
descriptor while retaining its registration, lookup and removal keys. Other
callbacks pass through unchanged. The complete hook-file control checks string,
closure and method callbacks. Direct builtin string invocation remains an SDK
boundary, reproduced by
[`builtin-string-callback.php`](../../elephc/full-source/probes/builtin-string-callback.php).

The native marker is defined only by `aot-platform.lfc`. The PHP reference omits
that file and loads `aot-php-platform.php` for actual dynamic object and static
property operations. Their native counterparts contain only explicit exceptions,
so PHP-only dynamic writes do not affect native property storage inference. By-reference outputs use explicit reference parameters and aliases to existing
argument-vector elements. Reference elements inside array literals are not used. Original API calls can be restored after
an SDK implementation passes the corresponding original-PHP controls.

These boundaries let the compiler traverse optional and currently unused paths
while retaining an explicit error if execution reaches them. They do not establish
complete native bootstrap, frontend, admin, authentication or REST operation.
The bootstrap comparison and source-only native reproduction must still succeed.


The complete pattern registry retains original lazy PHP file loading through the
closed pinned-theme include manifest. Removing cached `filePath` uses the
existing checked value-array deletion adapter. First-read snapshots retain the
original path field, later reads use the cache, supplied empty content keeps its
path, and the outside-init map caches independently. The component control loads
the whole original registry and executes the original `more-posts.php` template.
Its escaping and block-hook environment is explicitly bounded; complete hooked
block execution is checked separately by the full bootstrap. It compares 60
registry results and 24 key-deletion results, including retained numeric append
indices. All 109 original theme files remain in the compiled loader manifest;
this isolated control executes one of them.


The bootstrap constant inventory also provides typed data reads for 17 runtime
String constants, including cookie names and paths and content/plugin URLs.
Each original definition still evaluates its value once and calls `define`.
The native fixture retains the first captured String value; the PHP fixture
reads the real constant. Six time-unit definition operands use integer views.
This is a closed bootstrap inventory, not dynamic constant introspection.
Pre-platform runtime String overrides and non-String definitions are outside
this fixture. The constant-policy control covers all 17 values, empty and UTF-8
data, repeated capture and the six time units.

Static core block registration reads the complete `blocks/index.php` directory
for its bundled paths. All original registrars and the `BLOCKS_PATH` definition
remain present. Native runtime String constants can otherwise appear as integer
addresses, reproduced by `runtime-constant-values.php`.

The complete Style Engine class directly invokes the two immutable protected
self callbacks listed in its original metadata, then retains the original
fallback for other callbacks. This preserves the final class's callback scope
without claiming generic native protected-callable support. The whole-class
parse control compares 80 values with original PHP in both IR modes. Its unused
CSS serialization, stores and processors raise explicit exceptions. Those
collaborators and CSS sanitization are outside this isolated control. The SDK
predicate boundary is reproduced by `protected-static-callable.php`.

The complete capability mapper keeps every case and its final filter. Its 66
switch exits jump explicitly to the original filter position. The function has
no nested switch or loop whose target could change. The whole-file control
compares 52 values and filter traces, including a live capability-map change,
with original PHP in both IR modes. User/role loading and unrelated dependency
APIs raise explicit exceptions in this isolated environment. This preserves the
source control flow where the pinned SDK's conditional break exits otherwise
return null before the final filter.

REST context traversal reads public object values through `get_object_vars`
while retaining removals and recursive writes on the original object. The
fixture accepts bounded JSON trees without cyclic or callback-driven mutation.
The root probe also observes cached term names through public value views and
copies array snapshots before later detached-reference mutations. These
observation adapters do not replace cache operations or the original query
reset algorithm. Full native root verification remains necessary for all of
these changes.


Hook traversal cleanup and callback-bucket deletion use the checked value-array
adapter. The active filter names remain a dense stack built by the original
plugin API; each original pop position writes back the retained list. Complete
hook files pass 128 original-PHP comparisons in both IR modes, including nested
filters, nested `all` hooks, active-name traces and independent bucket snapshots.
Arbitrary external mutation of that active-name stack and reference binding to
the public callback storage are outside this isolated control.

Block context transfer reads the documented context array and the original
cached attribute array into local value views before the existing mapping loop.
The original magic getter still performs lazy attribute preparation and caching.
The complete block and lazy-list classes pass 65 context comparisons with PHP,
covering false, null, UTF-8, defaults, repeated refresh and independent inputs.
Its type/default/registry collaborators are finite fixtures. Unused HTML and
rendering collaborators throw; their behavior is covered only by separate
actual bootstrap/document execution, not by this constructor control.

The pinned Theme JSON selector map contains non-null arrays. Its nine presence
checks use `array_key_exists` to avoid native class-constant `isset` accepting
missing keys. Escaped numeric dollars in five constant string tokens preserve
original PHP regex replacement bytes. Standalone diagnostics for both compiler
boundaries remain separate from positive WordPress execution results.
