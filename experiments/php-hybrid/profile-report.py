#!/usr/bin/env python3
"""Summarize exact profiling for the current PHP hybrid implementation."""
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];OUT=ROOT/'elephc/build/php-hybrid';PROF=OUT/'profiling'
load=lambda path:json.loads(path.read_text())
capture=load(PROF/'rest.json');results=load(OUT/'results.json')
node=lambda name:next(row for row in capture['nodes'] if row['name']==name)
output={
    'exact_replay_calls':node('ep_dispatch')['call_count'],
    'entity_table_initializations':node('_elephc_kses_entity_names')['call_count'],
    'entity_table_allocations':node('_elephc_kses_entity_names')['alloc_exclusive'],
    'rest_request_handle_accesses':node('ep_retained_request')['call_count'],
    'rest_request_state_imports':node('ep_import_request')['call_count'],
    'typed_calls_per_page':{name:sum((value.get('fast_calls') or {}).values()) for name,value in results['native_call_proof']['hybrid'].items()},
    'scope':'Exact monitoring replays current native calls with instrumentation and generic diagnostic transport. Trace capture disables typed transport for replayability. HTTP and microbenchmarks disable profiling and use the actual transports. Retained-at-function-return counters do not establish a leak.'
}
(PROF/'summary.json').write_text(json.dumps(output,indent=2)+'\n')
print('Saved profiling/summary.json')
