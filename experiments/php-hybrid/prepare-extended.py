#!/usr/bin/env python3
"""Build an explicit maximal supported graph, with mechanically lowered methods."""
import importlib.util,json,re,hashlib,subprocess,shutil
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/'elephc/build/php-hybrid'
CORE=ROOT/'elephc/runtime/core/wp-includes'
OUT.mkdir(parents=True,exist_ok=True)
for name in ['elephc.toml','elephc.lock']:
    shutil.copyfile(ROOT/'elephc/runtime'/name,OUT/name)
spec=importlib.util.spec_from_file_location('source_tools',Path(__file__).with_name('source-tools.py'))
tools=importlib.util.module_from_spec(spec);spec.loader.exec_module(tools)
parts=[]; inventory=[]
def add(path,source=None):
    data=Path(path).read_bytes()
    code=data.decode() if source is None else source
    code='\n'.join(l for l in code.splitlines() if not re.match(r'\s*(require|include)(_once)?\s',l))
    parts.append(code.removeprefix('<?php').strip())
    item={'source':str(Path(path).relative_to(ROOT)),'source_sha256':hashlib.sha256(data).hexdigest(),'selected_sha256':hashlib.sha256(code.encode()).hexdigest()}
    names=set(re.findall(r'\bfunction\s+(\w+)\s*\(',code))
    prefix={'class-wp-block-parser.php':'ep_parser_','class-wp-list-util.php':'ep_list_','class-wp-object-cache.php':'ep_cache_','class-wp-error.php':'ep_error_','class-wp-http-response.php':'ep_http_','class-wp-rest-response.php':'ep_response_','class-wp-rest-request.php':'ep_request_'}.get(Path(path).name,'')
    chosen={name for name in tools.functions(path) if name in names or (prefix and prefix+name in names)}
    def ranges(origin):
        content=origin.read_bytes()
        return [{'name':name,'start':entry['start'],'end':entry['end'],'sha256':hashlib.sha256(content[entry['start']:entry['end']]).hexdigest()} for name,entry in tools.functions(origin).items() if name in chosen or (not prefix and name in names)]
    item['source_function_ranges']=ranges(Path(path))
    if Path(path).is_relative_to(CORE):
        original=ROOT/'upstream/wordpress/wp-includes'/Path(path).relative_to(CORE)
        item['upstream_source']=str(original.relative_to(ROOT))
        item['upstream_sha256']=hashlib.sha256(original.read_bytes()).hexdigest()
        item['upstream_function_ranges']=ranges(original)
    inventory.append(item)
def selected(path,names):
    raw=path.read_bytes();index=tools.functions(path)
    return '\n\n'.join(raw[index[n]['start']:index[n]['end']].decode() for n in names)
add(CORE/'class-wp-block-parser-block.php');add(CORE/'class-wp-block-parser-frame.php')
c, funcs=tools.flatten(CORE/'class-wp-block-parser.php','WP_Block_Parser','ep_parser_',{'parse':'array','proceed':'bool','next_token':'array','freeform':'mixed','add_freeform':'void','add_inner_block':'void','add_block_from_stack':'void'})
add(CORE/'class-wp-block-parser.php',c+'\n\n'+'\n\n'.join(funcs))
add(CORE/'formatting.php')
shortcode_names=['shortcode_exists','has_shortcode','get_shortcode_tags_in_content','get_shortcode_regex','get_shortcode_atts_regex','shortcode_parse_atts','shortcode_atts','strip_shortcode_tag','unescape_invalid_shortcodes']
shortcode_source=selected(CORE/'shortcodes.php',shortcode_names).replace("$tagregexp = implode( '|', array_map( _elephc_shortcode_quote(...), (array) $tagnames ) );", "$quoted_tags=[]; foreach((array)$tagnames as $tag) { $quoted_tags[]=_elephc_shortcode_quote((string)$tag); } $tagregexp=implode('|',$quoted_tags);")
add(CORE/'shortcodes.php',shortcode_source)
add(ROOT/'elephc/runtime/shortcodes-support.php',selected(ROOT/'elephc/runtime/shortcodes-support.php',['_elephc_shortcode_quote','_elephc_replace_many','_elephc_shortcode_unescape','_elephc_translate_pairs','_elephc_shortcode_intersect']))
add(ROOT/'elephc/runtime/kses-support.php',selected(ROOT/'elephc/runtime/kses-support.php',['_elephc_kses_parts','_elephc_kses_replace','_elephc_kses_remove_nulls','wordpress_kses_utf8','wordpress_kses_attribute_input','_elephc_kses_escape']))
# Include the source-defined entity table and pure attribute/entity/protocol graph.
kses=(CORE/'kses.php').read_text()
# Allowed policy remains owned by the real PHP WordPress runtime.
ksidx=tools.functions(CORE/'kses.php')
raw=kses.encode();start,end=ksidx['wp_kses_allowed_html']['start'],ksidx['wp_kses_allowed_html']['end']
kses=(raw[:start]+b'function wp_kses_allowed_html(mixed $context = ""): array { global $ep_context; return (array) ($ep_context["allowed_html"] ?? []); }'+raw[end:]).decode()
kses=kses.replace("if ( ! call_user_func( $checkvalue, $value ) ) {\n\t\t\t\t$ok = false;\n\t\t\t}", "throw new LogicException('Attribute callbacks execute in the PHP host.');")
add(CORE/'kses.php',kses)
add(CORE/'http.php')
block=(CORE/'blocks.php').read_text().replace('$serialized_block_name = strip_core_block_namespace( $block_name );','$serialized_block_name = (string) strip_core_block_namespace( $block_name );')
block=block.replace("return sprintf( '<!-- wp:%s %s/-->', $serialized_block_name, $serialized_attributes );","return '<!-- wp:' . $serialized_block_name . ' ' . $serialized_attributes . '/-->';")
block=block.replace("return sprintf(\n\t\t'<!-- wp:%s %s-->%s<!-- /wp:%s -->',\n\t\t$serialized_block_name,\n\t\t$serialized_attributes,\n\t\t$block_content,\n\t\t$serialized_block_name\n\t);","return '<!-- wp:' . $serialized_block_name . ' ' . $serialized_attributes . '-->' . $block_content . '<!-- /wp:' . $serialized_block_name . ' -->';")
add(CORE/'blocks.php',block)
list_class,list_methods=tools.flatten(CORE/'class-wp-list-util.php','WP_List_Util','ep_list_')
add(CORE/'class-wp-list-util.php',list_class+'\n\n'+'\n\n'.join(list_methods))
list_helpers=selected(ROOT/'elephc/runtime/lists-support.php',['wordpress_lists_merge','wordpress_lists_sorted'])
list_helpers=list_helpers.replace('callable $compare','WP_List_Util $sorter').replace('$compare((array)', 'ep_list_sort_callback($sorter,(array)').replace('$compare','$sorter')
add(ROOT/'elephc/runtime/lists-support.php',list_helpers)
fn_names=['build_query','_http_build_query','wp_is_numeric_array','wp_suspend_cache_addition','wp_filter_object_list','wp_list_filter','wp_list_pluck','wp_list_sort']
parse_names=['wp_parse_list']
functions_source=selected(CORE/'functions.php',fn_names)+'\n\n'+selected(ROOT/'upstream/wordpress/wp-includes/functions.php',parse_names)
functions_source=functions_source.replace('function wp_parse_list( $input_list )','function wp_parse_list( mixed $input_list )').replace('function wp_parse_id_list( $input_list )','function wp_parse_id_list( mixed $input_list )').replace('return $input_list;', 'return (array)$input_list;').replace("preg_split( '/[\\s,]+/', $input_list", "preg_split( '/[\\s,]+/', (string)$input_list").replace("array_filter( $input_list, 'is_scalar' )",'array_filter( (array)$input_list, static function(mixed $value): bool { return is_scalar($value); } )').replace('array_unique( array_map(', 'array_unique( (array)array_map(').replace("array_map( 'absint', $input_list )",'array_map( static function(mixed $value): int { return absint($value); }, (array)$input_list )')
for method in tools.functions(CORE/'class-wp-list-util.php'):
    if method=='__construct':continue
    pattern=r'\$util->'+re.escape(method)+r'\(\s*'
    functions_source=re.sub(pattern,lambda m:'ep_list_'+method+'($util'+('' if functions_source[m.end():].startswith(')') else ', '),functions_source)
add(CORE/'functions.php',functions_source)
# Lower the existing cache methods directly; PHP keeps opaque values in a
# request-local registry while the native core owns cache keys/groups/state.
cache_class, cache_methods=tools.flatten(CORE/'class-wp-object-cache.php','WP_Object_Cache','ep_cache_')
add(CORE/'class-wp-object-cache.php',cache_class+'\n\n'+'\n\n'.join(cache_methods))
cache_api=(CORE/'cache.php').read_text()
for method in tools.functions(CORE/'class-wp-object-cache.php'):
    if method=='__construct':continue
    pattern=r'\$wp_object_cache->'+re.escape(method)+r'\(\s*'
    cache_api=re.sub(pattern,lambda m:'ep_cache_'+method+'($wp_object_cache'+('' if cache_api[m.end():].startswith(')') else ', '),cache_api)
add(CORE/'cache.php',cache_api)
cache_names=list(tools.functions(CORE/'cache.php'))
cache_names.remove('wp_cache_reset') # Original deprecated reset is an explicit boundary.
error_class,error_methods=tools.flatten(CORE/'class-wp-error.php','WP_Error','ep_error_')
add(CORE/'class-wp-error.php',error_class+'\n\n'+'\n\n'.join(error_methods))
add(CORE/'load.php',selected(CORE/'load.php',['is_wp_error','absint','wp_is_json_media_type']))
add(ROOT/'elephc/runtime/rest-locale.php')
rest_names=list(tools.functions(CORE/'rest-api.php'))
rest_source=selected(CORE/'rest-api.php',rest_names)
rest_source=rest_source.replace('$response->get_data()', 'ep_http_get_data($response)').replace('$response->get_status()', 'ep_http_get_status($response)').replace('$response->get_headers()', 'ep_http_get_headers($response)')
rest_source=re.sub(r'\$error->get_all_error_data\(\s*',lambda m:'ep_error_get_all_error_data($error'+('' if rest_source[m.end():].startswith(')') else ', '),rest_source)
rest_source=rest_source.replace("if ( is_scalar( $maybe_array ) ) { throw new InvalidArgumentException('Scalar list coercion is outside the collection schema profile.'); }",'if ( is_scalar( $maybe_array ) ) { $maybe_array=wp_parse_list($maybe_array); }')
add(CORE/'rest-api.php',rest_source)
add(ROOT/'elephc/runtime/requests-support.php',selected(ROOT/'elephc/runtime/requests-support.php',['wordpress_request_map','wordpress_request_groups','wordpress_request_json_error_message']))
object_spec=importlib.util.spec_from_file_location('object_protocol',Path(__file__).with_name('object-protocol.py'))
objects=importlib.util.module_from_spec(object_spec);object_spec.loader.exec_module(objects)
object_code,object_branches,object_routes,object_properties=objects.generate(ROOT,CORE,tools,add)
parts.append(object_code)
add(Path(__file__).with_name('extended-runtime.php'))
add(Path(__file__).with_name('fast-exports.php'))
exports='''#[Export]
function ep_dispatch(string $input): string {
    global $ep_context, $ep_filters, $ep_actions, $allowedentitynames, $shortcode_tags;
    $spec=(array) json_decode($input,true);
    $name=(string) $spec['name'];
    $args=(array) $spec['args'];
    $ep_context=(array) ($spec['context'] ?? []);
    $ep_context['route']=$name;
    $ep_filters=[];
    $ep_actions=[];
    global $ep_object_state,$ep_second_state;
    $ep_object_state=[]; $ep_second_state=[];
    $shortcode_tags=(array) ($ep_context['shortcodes'] ?? []);
    $value=ep_call_value($name,$args);
    global $wp_object_cache;
    $cache_meta=[];
    if(str_starts_with($name,'wp_cache_') && $wp_object_cache instanceof WP_Object_Cache) { $cache_meta=['hits'=>$wp_object_cache->cache_hits,'misses'=>$wp_object_cache->cache_misses]; }
    $kind=is_string($value)?'string':'json';
    if($value instanceof WP_Error) { $kind='error'; $value=ep_error_payload($value); }
    elseif($value instanceof WP_REST_Response) { $kind='response'; $value=ep_export_response($value); }
    $result=['kind'=>$kind,'value'=>$kind==='string'?base64_encode((string)$value):$value];
    if($ep_filters)$result['filters']=$ep_filters;
    if($ep_actions)$result['actions']=$ep_actions;
    if($cache_meta)$result['cache']=$cache_meta;
    if(str_contains($name,'::'))$result['object_state']=$ep_object_state;
    if($ep_second_state)$result['second_state']=$ep_second_state;
    return (string) json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION);
}
function ep_call_value(string $name,array $a): mixed {
'''
exports+='\n'.join(object_branches)
exports+='''
    global $ep_context;
    if($name==='rest_ensure_response') {
        $kind=(string)($ep_context['input_kind']??'value');
        if($kind==='error')return rest_ensure_response(ep_import_error((array)$a[0]));
        if($kind==='response')return rest_ensure_response(ep_import_response((array)$a[0]));
        if($kind==='http')return rest_ensure_response(ep_import_http((array)$a[0]));
        return rest_ensure_response($a[0]);
    }
    if($name==='rest_convert_error_to_response')return rest_convert_error_to_response(ep_import_error((array)$a[0]));
'''
exports+='''
    switch($name) {
        case 'parse_blocks': $p=new WP_Block_Parser(); return ep_parser_parse($p,(string) $a[0]);
        case 'wp_cache_get': $found=null; $value=wp_cache_get($a[0],(string)($a[1]??''),(bool)($a[2]??false),$found); return ['data'=>$value,'found'=>$found];
'''
# Explicit signatures are generated from the verified declarations, with fixed
# argument slots rather than reflective invocation.
route_names=['wpautop','wp_html_split','get_html_split_regex','wp_replace_in_html_tags','shortcode_unautop','sanitize_key','sanitize_html_class','_deep_replace','esc_url','esc_url_raw','sanitize_url','esc_html','esc_attr','wp_make_link_relative','wp_pre_kses_less_than','wp_strip_all_tags','sanitize_text_field','sanitize_textarea_field','_sanitize_text_fields','wp_spaces_regexp','sanitize_hex_color','sanitize_hex_color_no_hash','wp_parse_url','_get_component_from_parsed_url_array','_wp_translate_php_url_constant_to_key','serialize_blocks','serialize_block','serialize_block_attributes','get_comment_delimited_block_content','strip_core_block_namespace',*fn_names,*rest_names,'absint','wp_is_json_media_type','wp_kses_bad_protocol','wp_kses_no_null','wp_kses_bad_protocol_once','wp_kses_bad_protocol_once2','wp_kses_normalize_entities','wp_kses_decode_entities','valid_unicode','wp_kses_hair_parse','wp_kses_attr_parse','wp_kses_one_attr','wp_kses_uri_attributes','wp_kses_check_attr_val',*cache_names]
indices={}
route_names.extend(shortcode_names+parse_names)
for p in [ROOT/'upstream/wordpress/wp-includes/functions.php',CORE/'shortcodes.php',CORE/'formatting.php',CORE/'http.php',CORE/'blocks.php',CORE/'functions.php',CORE/'rest-api.php',CORE/'kses.php',CORE/'cache.php',CORE/'load.php']:
    for name,entry in tools.functions(p).items():indices[name]=entry
for name in route_names:
    if name in ['wp_cache_get','rest_ensure_response','rest_convert_error_to_response']:continue
    entry=indices[name]
    # All declarations have simple literal defaults; split only top-level commas.
    params=re.split(r',\s*(?![^()]*\))',entry['params'].strip()) if entry['params'].strip() else []
    args=[]
    for i,p in enumerate(params):
        match=re.match(r'\s*(\??\w+)?\s*&?\s*\$(\w+)(?:\s*=\s*(.*))?',p,re.S)
        if not match:raise ValueError((name,p))
        typ,param,default=match.groups()
        arg='$a['+str(i)+']'
        if default is not None:arg='(array_key_exists('+str(i)+', $a) ? '+arg+' : '+default+')'
        if typ in ['string','int','bool','array','float']:arg='('+typ+') '+arg
        elif typ and typ.startswith('?'):arg='('+arg+' === null ? null : ('+typ[1:]+') '+arg+')'
        args.append(arg)
    call=name+'('+', '.join(args)+')'
    statement=call+'; return null;' if entry['return_type']==': void' else 'return '+call+';'
    exports+="        case '"+name+"': "+statement+'\n'
exports+="    }\n    throw new InvalidArgumentException('Unsupported native route: ' . $name);\n}\n"
exports+='''
#[Export]
function ep_autop(string $content, bool $br = true): string { return wpautop($content,$br); }
#[Export]
function ep_identity(string $content): string { return $content; }
#[Export]
function ep_serialize_json(string $input): string { return serialize_blocks((array)json_decode($input,true)); }
#[Export]
function ep_parse_json(string $content): string { $p=new WP_Block_Parser(); return (string)json_encode(ep_parser_parse($p,$content),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION); }
#[Export]
function ep_request_reset(): void { global $ep_context,$ep_filters,$wp_object_cache,$allowedentitynames; $ep_context=[]; $ep_filters=[]; $wp_object_cache=null; $allowedentitynames=_elephc_kses_entity_names(); ep_objects_reset(); wp_suspend_cache_addition(false); }
'''
parts.append(exports)
source='<?php\n\n'+'\n\n'.join(parts)+'\n'
source=re.sub(r'\bsprintf\s*\(', 'ep_sprintf(', source)
specialized={}
def fixed_callback(match):
    pattern,target,subject=match.groups()
    helper='ep_callback_'+target
    specialized[helper]='function '+helper+'(string $pattern,string $subject): string { $output=[]; foreach(_elephc_kses_parts($subject) as $part) { $text=(string)$part; $output[]=$text === "" ? "" : (string)preg_replace_callback($pattern,static function(array $m): string { return '+target+'($m); },$text); } return implode("\\0",$output); }'
    return helper+'('+pattern+','+subject+')'
source=re.sub(r"_elephc_kses_callback\(\s*('(?:\\.|[^'])*')\s*, static function \(array \$m\): string \{ return (\w+)\( \$m \); \}, (\$\w+) \)",fixed_callback,source)
source+='\n\n'+'\n\n'.join(specialized.values())+'\n'
(OUT/'wp_extended.php').write_text(source)
(OUT/'extended-inventory.json').write_text(json.dumps({'sources':inventory,'generated_source_sha256':hashlib.sha256(source.encode()).hexdigest(),'routes':['parse_blocks',*route_names,*object_routes],'object_properties':object_properties,'native_functions':re.findall(r'^function (\w+)',source,re.M),'native_classes':re.findall(r'^(?:final )?class (\w+)',source,re.M)},indent=2)+'\n')
print('Prepared',len(route_names)+1+len(object_routes),'native routes')
