#!/usr/bin/env python3
"""Separate compiled entry points, injected routes and observed native execution."""
import hashlib,json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];OUT=ROOT/'elephc/build/php-hybrid'
load=lambda name:json.loads((OUT/name).read_text())
compiled=load('extended-inventory.json');routing=load('extended-routing.json');corpus=load('corpus-hybrid.json');report=load('results.json')
assert report['schema']==2 and report['parser_native']
page_calls={name:stats.get('calls',{}) for name,stats in report['native_call_proof']['hybrid'].items()}
seen={name for calls in page_calls.values() for name in calls}|set(corpus['native']['calls'])
def module(name):
    if '::' in name:return name.split('::')[0]
    if name.startswith('wp_cache_') or name=='wp_suspend_cache_addition':return 'cache'
    if name.startswith('rest_'):return 'REST helpers and schemas'
    if 'shortcode' in name:return 'shortcodes'
    if name in ['parse_blocks','serialize_blocks','serialize_block','serialize_block_attributes','get_comment_delimited_block_content','strip_core_block_namespace']:return 'blocks'
    if name.startswith('wp_kses') or name=='valid_unicode':return 'KSES helpers'
    if name in ['wp_list_filter','wp_list_pluck','wp_list_sort','wp_filter_object_list','wp_is_numeric_array','wp_parse_list']:return 'lists'
    if name in ['build_query','_http_build_query','wp_parse_url','_get_component_from_parsed_url_array','_wp_translate_php_url_constant_to_key']:return 'URL helpers'
    return 'formatting and load helpers'
modules={}
for name in compiled['routes']:
    bucket=modules.setdefault(module(name),{'compiled':[],'observed':[]})
    bucket['compiled'].append(name)
    if name in seen:bucket['observed'].append(name)
files=['wp_extended.php','libwp_extended.so','wp_elephc_extended.so']
output={'schema':1,'compiled_native_entries':len(compiled['routes']),'compiled_native_functions_including_adapters':len(compiled['native_functions']),'compiled_native_classes':compiled['native_classes'],'injected_core_interception_points':sum(len(row['functions']) for row in routing),'observed_entry_points':len(seen),'observed_per_module':modules,'page_calls':page_calls,'page_entry_call_totals':{name:sum(calls.values()) for name,calls in page_calls.items()},'corpus_checks':sum(len(cases) for cases in corpus['results'].values()),'corpus_native_calls':corpus['native']['calls'],'corpus_fallbacks':corpus['fallbacks'],'page_fallbacks':{name:report['raw_pages']['hybrid'][name][-1]['fallbacks'] for name in page_calls},'sha256':{name:hashlib.sha256((OUT/name).read_bytes()).hexdigest() for name in files},'meaning':'Counters count entries from PHP into the compiled graph. Internal native calls are not counted separately. Compiled or observed entry counts are not CPU-time percentages or whole-WordPress coverage. Class cache/list interception points reuse native global entries.'}
(OUT/'coverage.json').write_text(json.dumps(output,indent=2)+'\n')
print(json.dumps({key:output[key] for key in ['compiled_native_entries','compiled_native_functions_including_adapters','injected_core_interception_points','observed_entry_points','corpus_checks','page_entry_call_totals']},indent=2))
