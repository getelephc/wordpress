# Compiler boundary probes

These small probes isolate limitations observed on main SDK
`22654308233a43f96ff4ae2482e08afe61fe956e`. They are separate from the passing
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

PHP prints `WORDPRESS`. The native executable compiles but fails to invoke the
included user function through its literal string name. The diagnostic describes
an unlinked runtime-only builtin, although the target is a declared user function.
Both `require` and `require_once` reproduce the failure. The broader diagnosis
also found that `function_exists` and `is_callable` return true; adding an unused
first-class root does not repair string invocation. An entry-file control works.

First-class callbacks, captured closures, method arrays and invokable objects
preserve reference updates in the separate positive control
`tests/full-source/callable-cases.php`. Those results do not repair string callback
dispatch in WordPress. The complete CMS still needs its own native verification.
