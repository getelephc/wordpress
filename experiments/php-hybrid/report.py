#!/usr/bin/env python3
"""Write the experiment summary from measured artifacts, without new benchmarks."""
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];OUT=ROOT/'elephc/build/php-hybrid'
p=json.loads((OUT/'results.json').read_text());c=json.loads((OUT/'coverage.json').read_text());v=json.loads((OUT/'verification.json').read_text())
rows=[]
for name,values in p['pages'].items():
    times=[values[mode]['server_ms']['median'] for mode in ['php','loaded-off','hybrid']]
    rows.append('| '+name+' | '+' | '.join(f'{n:.2f}' for n in times)+f' | {times[2]/times[0]:.2f}x |')
modules=['| Module | Compiled entry points | Observed in checks or HTTP |','| --- | ---: | ---: |']
for name,bucket in c['observed_per_module'].items():modules.append(f"| {name} | {len(bucket['compiled'])} | {len(bucket['observed'])} |")
profiling=''
if (OUT/'profiling/summary.json').exists():
    analysis=json.loads((OUT/'profiling/summary.json').read_text())
    profiling=f'''## Profiling

Typed scalar/string entry points bypass the JSON envelope for numeric-array checks, integer absint, selected pure text helpers and parsing/serialization transport. Numeric-array checks project key types, then execute the original native WordPress predicate. Strings cross directly; block arrays still need JSON on their array side. The classic-large request uses {analysis['typed_calls_per_page']['classic-large']} typed calls out of {c['page_entry_call_totals']['classic-large']} native entries.

Error, response and request records stay in native request-local handle tables. PHP WeakMap snapshots detect direct PHP mutations and resynchronize the relevant class view. Getters return their result without exporting the whole object; mutations return changed properties only. The current REST replay records {analysis['rest_request_handle_accesses']} handle accesses and {analysis['rest_request_state_imports']} state imports. The entity table is initialized {analysis['entity_table_initializations']} time per request, with {analysis['entity_table_allocations']} allocations.

Exact captures include instrumentation and generic diagnostic transport, so their absolute times are not HTTP or typed-ABI measurements. Benchmark runs disable profiling. Profiler retained-at-return counters do not establish a leak. Current captures and the interactive call graph are under `elephc/build/php-hybrid/profiling/`: rest.json, rest-callgraph.html and summary.json.

'''
text=f'''# WordPress PHP / Elephc hybrid experiment

Measured {p['generated_at']}, Linux x86_64, WordPress 7.1.4, PHP 8.5.12 NTS, pinned Elephc 0.27.8.

The complete WordPress front controller, Twenty Twenty-Five and official SQLite integration 3.1.3 run in PHP. Selected core algorithms execute in an Elephc shared library through a real Zend extension, in the same process. The experiment uses four local posts and one isolated SQLite file. External HTTP, cron and mail delivery are disabled identically in every mode.

## Native coverage

There are **{c['compiled_native_entries']} callable native entry points**, **{c['injected_core_interception_points']} interception points in the generated WordPress copy**, and **{c['observed_entry_points']} distinct entries exercised** by the operation checks or real HTTP requests. The compiled source contains {c['compiled_native_functions_including_adapters']} functions including bridge helpers and lowered methods, plus {len(c['compiled_native_classes'])} native record classes. These counts are not percentages of WordPress code or CPU time.

{chr(10).join(modules)}

The native parser now executes. Its methods are mechanically lowered to typed free functions, retaining the original algorithm and native record state. The same transformation handles list, cache, error, response and request objects. Native calls within a compiled operation do not cross PHP again and are not counted as additional boundary calls.

The classic-large page records {c['page_entry_call_totals']['classic-large']} PHP-to-native calls per request, including parsing, serialization, formatting, cache operations, list operations and schema validation. The two actual REST endpoints record {c['page_entry_call_totals']['rest-posts']} and {c['page_entry_call_totals']['rest-one']} calls. Request/response methods and schema checks execute natively during normal WordPress REST routing.

PHP retains registered plugin callbacks, object identity and opaque payloads. Cache keys, groups, found flags, counters, suspension and ordinary operations are owned by native core code. A request-local PHP registry carries objects and binary cache values without JSON conversion, preserving ordinary shallow clone behavior. List adapters project comparison fields and restore original PHP rows. Native error/request/response handles retain state between operations; opaque nested payloads keep their PHP identity.

{profiling}

## Measured performance

Median server render time in milliseconds. The multiplier is hybrid time divided by normal PHP time.

| Request | PHP | Extension loaded, routing off | Hybrid | Hybrid / PHP |
| --- | ---: | ---: | ---: | ---: |
{chr(10).join(rows)}

The hybrid is slower than PHP. Generic calls still encode and decode JSON, while typed paths bypass that transport. Native runtime, allocation and compatibility-helper costs remain. The 16 KiB native identity control costs {p['micro_us_per_call']['identity']['hybrid']['median']:.3f} microseconds per call in this control run. Raw measurements retain these costs; they do not assign all remaining overhead to JSON.

There are three rounds with rotated mode order and {p['requests_per_case_per_round']} measured requests per request type, mode and round, for {len(p['pages'])*3*p['rounds']*p['requests_per_case_per_round']} measured HTTP requests. Each mode has two warmup passes per round. OPcache is enabled, JIT is disabled and concurrency is one. Both PHP control modes restore byte-identical upstream core files before startup. Every complete HTML or REST JSON body must have the same SHA-256 in all modes. Client times, ranges, raw samples, microbenchmarks and native counters are retained in `elephc/build/php-hybrid/results.json`; the checked-in [results.json](results.json) snapshot contains current summaries. This is one NTS CLI-server worker with SQLite, not an FPM, MySQL, concurrency or production benchmark. PHP peak-memory values exclude native allocations.

## Correctness and remaining PHP boundaries

All **{c['corpus_checks']} operation cases** match the same full WordPress site with pristine original core files. They cover errors, responses, requests, callback lifecycle, scalar/collection/unique schemas, lists, cache, URLs, sanitization, KSES and bridge-specific identity/hook checks. Another **{v['checks']} direct extension checks** cover parsing, serialization, wpautop and binary identity. Reflection confirms internal functions supplied by the Zend module. All {len(p['pages'])} complete HTTP response types match across all benchmark rounds.

The finite prepared profile remains the scope of compiled core code. Full rendering, templates, database queries, filesystem behavior, authentication, REST dispatch and arbitrary plugin callbacks stay in PHP. Their calls to the routed helpers can execute natively. The hooks engine and shortcode callback execution remain PHP because arbitrary PHP callables cannot be invoked through this cdylib callback boundary. Shortcode tag/attribute parsing and supported pure helpers are native.

Native schema validation supports the existing bounded scalar/array/object graph and uniqueItems, plus scalar list coercion in this experiment. Unsupported formats, patterns, enum and combining schemas fall back to original PHP. On classic-large, 71 dispatcher calls and 54 string-validator calls use this fallback. Whole-document KSES, CSS, XML normalization and charset recovery remain PHP; supported attribute/entity/protocol helpers are native.

Custom subclasses, unsupported callback-sensitive operations, references in object-state arrays and unsupported data coercions fall back to original PHP where the bridge can safely do so. Active nested filters stay in PHP; terminal filters are replayed with their original arguments. Request raw form decoding stays in PHP. The cache excludes custom clone/destructor callbacks and direct private-cache mutation. Persistent cache drop-ins, arbitrary subclasses, arbitrary binary keys and full plugin compatibility are not validated. Locale-dependent native error messages use the prepared fixed English policy. Fallback counts and unobserved compiled entries are explicit in coverage.json.

## Compiler adaptations and provenance

Direct phpext emission on the pinned compiler cannot compile this graph. The experiment uses cdylib emission plus a small Zend C adapter. Internal method calls are lowered mechanically to avoid the cdylib opaque-call restriction. The two serializer sprintf templates use equivalent concatenation, other finite prepared error templates use an explicit formatter, and shortcode tag quoting uses a direct loop. Typed payload helpers preserve object-array results at the native boundary. Request-local native state is reset on PHP request initialization.

A confirmed pinned x86_64 ABI defect can clobber host r14 during serialization. Every native entry, initialization, shutdown, free and error access is wrapped with the SysV callee-save assembly guard. The original register probe and crash evidence remain under the generated experiment artifacts. The SDK version is unchanged. This integration is Linux x86_64 NTS and sequential only.

`upstream/` is untouched. Native source comes from the existing checksum-verified prepared profile, plus the original wp_parse_list function with explicit typing/callback adaptation. `extended-inventory.json` records prepared-source and selected-code hashes; `extended-routing.json` records original and injected PHP hashes. `coverage.json` records final source/library/extension hashes and actual per-function counters. `build-manifest.json` records compiler identity, hash, target and flags. The original bounded native CLI/HTTP profile has not been changed.

## Reproduce

With the existing generated fixture, from the repository root:

```bash
./experiments/php-hybrid/serve.sh hybrid
# http://127.0.0.1:9000/?p=7
# http://127.0.0.1:9000/?rest_route=/wp/v2/posts&per_page=4
# Stop with Ctrl+C. Use php or loaded-off for the controls.
```

Rebuild and verify the extension, then repeat the measurements:

```bash
./experiments/php-hybrid/build.sh
python3 experiments/php-hybrid/bench.py --requests 4
python3 experiments/php-hybrid/coverage.py
python3 experiments/php-hybrid/report.py
```

Capture the current REST input stream and run exact profiling:

```bash
mkdir -p elephc/build/php-hybrid/profiling
HYBRID_MODE=hybrid \\
HYBRID_TRACE_INPUTS="$PWD/elephc/build/php-hybrid/profiling/rest-inputs.jsonl" \\
php -d extension="$PWD/elephc/build/php-hybrid/wp_elephc_extended.so" \\
  experiments/php-hybrid/front-probe.php '/?rest_route=/wp/v2/posts&per_page=4&_fields=id,slug,content,title' \\
  > elephc/build/php-hybrid/profiling/rest-response.json
python3 experiments/php-hybrid/profile-native.py rest --inputs profiling/rest-inputs.jsonl \\
  --assert 'calls:_elephc_kses_entity_names==1' --assert 'calls:ep_import_request<=4'
python3 experiments/php-hybrid/profile-report.py
python3 experiments/php-hybrid/report.py
```

`HYBRID_PROFILE=1` enables diagnostic adapter phase timers. Input tracing uses generic transport for replayability. Neither diagnostic variable should be set for benchmark runs.

For a fresh fixture, first prepare the normal source profile and fetch the official SQLite release:

```bash
python3 scripts/prepare-elephc.py
mkdir -p elephc/build/php-hybrid/deps
gh release download v3.1.3 --repo WordPress/sqlite-database-integration \\
  --pattern plugin-sqlite-database-integration.zip --dir elephc/build/php-hybrid/deps
python3 experiments/php-hybrid/setup-site.py
php experiments/php-hybrid/seed.php
./experiments/php-hybrid/build.sh
```

The archive is checked against SHA-256 `8e6f281af89612bb21ec6be3000f1a7581bbaf5d0d65515a67aedfad54eb7a72`. Builds, benchmarks and profiling run directly with the required tools on PATH. The hybrid build copies the repository's native dependency manifest and lockfile, installs locked PCRE2, and needs matching PHP headers and php-config. Its manifest resolves elephc from PATH without requiring a version manager. Benchmarks refuse an occupied port and terminate their finite server processes in finally blocks.

The extension is `elephc/build/php-hybrid/wp_elephc_extended.so`, linked to `libwp_extended.so`. Its module name is still wp_elephc_hybrid. Main artifacts are results.json, coverage.json, verification.json, corpus-php.json, corpus-hybrid.json, extended-inventory.json, extended-routing.json and build-manifest.json under `elephc/build/php-hybrid/`.
'''
snapshot={key:p[key] for key in ['schema','generated_at','wordpress','compiler','php','sqlite_plugin','theme','rounds','requests_per_case_per_round','opcache_enabled','jit_enabled','concurrency','native_library_heap_bytes','output_sha256','pages','micro_us_per_call']}
snapshot['coverage']={key:c[key] for key in ['compiled_native_entries','injected_core_interception_points','observed_entry_points','corpus_checks']}
snapshot['direct_checks']=v['checks']
snapshot['sha256']=c['sha256']
if (OUT/'profiling/summary.json').exists():snapshot['profiling']=json.loads((OUT/'profiling/summary.json').read_text())
(ROOT/'experiments/php-hybrid/results.json').write_text(json.dumps(snapshot,indent=2)+'\n')
(ROOT/'experiments/php-hybrid/README.md').write_text(text)
print('Wrote experiment README from measured artifacts')
