<?php
// This file loads before wp-settings, so core calls during bootstrap can route.
$GLOBALS['hybrid_fallbacks'] = [];
function hybrid_fallback(string $name, string $reason): array {
    $key=$name . ':' . $reason;
    $GLOBALS['hybrid_fallbacks'][$key]=($GLOBALS['hybrid_fallbacks'][$key]??0)+1;
    return ['handled'=>false];
}
function hybrid_plain(mixed $value, int $depth=0): bool {
    if ($depth>32 || is_object($value) || is_resource($value)) { return false; }
    if (is_array($value)) {
        if (count($value)>4096) { return false; }
        foreach ($value as $key=>$item) { if ((is_string($key) && str_contains($key,"\0")) || !hybrid_plain($item,$depth+1)) { return false; } }
    }
    if (is_float($value) && !is_finite($value)) { return false; }
    if (is_string($value) && (str_contains($value,"\0") || !mb_check_encoding($value,'UTF-8'))) { return false; }
    return true;
}
function hybrid_try_call(string $name,array $args): array {
    if (!function_exists('elephc_wp_call')) { return hybrid_fallback($name,'extension-unavailable'); }
    if($name==='wp_is_numeric_array' || ($name==='absint'&&is_int($args[0])&&$args[0]!==PHP_INT_MIN)) {
        $result=elephc_wp_call($name,$args,[]);return ['handled'=>true,'value'=>$result['value']];
    }
    if(in_array($name,['rest_ensure_response','rest_convert_error_to_response'],true))return hybrid_response_call($name,$args[0]);
    if(in_array($name,['wp_list_filter','wp_list_sort','wp_list_pluck','wp_filter_object_list'],true))return hybrid_list_call($name,$args);
    if($name==='parse_blocks') {
        $class=apply_filters('block_parser_class','WP_Block_Parser');
        if($class!=='WP_Block_Parser') { hybrid_fallback($name,'custom-parser'); $parser=new $class(); return ['handled'=>true,'value'=>$parser->parse($args[0])]; }
    }
    if($name==='rest_validate_value_from_schema') {
        $nodes=0;$schema=hybrid_schema_prepare($args[1],0,$nodes);
        if($schema===null)return hybrid_fallback($name,'schema-unsupported');
        $args[1]=$schema;
    }
    if(str_starts_with($name,'rest_validate_') && function_exists('has_action') && (has_action('wp_error_added')!==false||has_action('is_wp_error_instance')!==false))return hybrid_fallback($name,'error-action-callback');
    if(str_starts_with($name,'rest_validate_') && str_ends_with($name,'_value_from_schema') && !in_array($name,['rest_validate_null_value_from_schema','rest_validate_boolean_value_from_schema','rest_validate_value_from_schema'],true)) {
        $type=substr($name,strlen('rest_validate_'),-strlen('_value_from_schema'));
        $nodes=0;$schema=hybrid_schema_prepare(array_replace($args[1],['type'=>$type]),0,$nodes);
        if($schema===null)return hybrid_fallback($name,'schema-unsupported');
        $args[1]=$schema;
    }
    if (!hybrid_plain($args)) { return hybrid_fallback($name,'non-json-or-binary-input'); }
    $context=[];
    if (in_array($name,['esc_url','esc_url_raw','sanitize_url','wp_kses_one_attr'],true)) {
        $context['protocols']=wp_allowed_protocols();
        if (in_array($name,['esc_url','esc_url_raw','sanitize_url'],true) && (!isset($args[1]) || $args[1]===null)) { $args[1]=$context['protocols']; }
    }
    if (in_array($name,['shortcode_unautop','shortcode_exists','has_shortcode','get_shortcode_tags_in_content','get_shortcode_regex'],true)) { $context['shortcodes']=array_fill_keys(array_keys($GLOBALS['shortcode_tags']??[]),true); }
    $hasHook=static function(string $hook): bool { return function_exists('has_filter') && has_filter($hook)!==false; };
    if($hasHook('all') && in_array($name,['esc_html','esc_attr','sanitize_key','sanitize_html_class','sanitize_text_field','sanitize_textarea_field','_sanitize_text_fields','esc_url','esc_url_raw','sanitize_url','wp_pre_kses_less_than','wp_kses_one_attr','wp_kses_uri_attributes'],true))return hybrid_fallback($name,'global-hook-observer');
    if (in_array($name,['sanitize_text_field','sanitize_textarea_field','_sanitize_text_fields','wp_pre_kses_less_than','wp_kses_one_attr'],true) && $hasHook('esc_html')) {
        return hybrid_fallback($name,'nested-esc-html-filter');
    }
    if ($name==='wp_kses_one_attr') {
        if($hasHook('wp_kses_allowed_html'))return hybrid_fallback($name,'attribute-policy-filter');
        if ($hasHook('attribute_escape') || $hasHook('wp_kses_uri_attributes')) { return hybrid_fallback($name,'nested-attribute-filter'); }
        $context['allowed_html']=wp_kses_allowed_html('post');
        if (!hybrid_plain($context['allowed_html'])) { return hybrid_fallback($name,'attribute-policy-callback'); }
        foreach ([(array)($context['allowed_html'][strtolower($args[1])]??[])] as $attrs) {
            foreach ($attrs as $rule) {
                if (is_array($rule) && isset($rule['value_callback'])) { return hybrid_fallback($name,'attribute-value-callback'); }
            }
        }
    }
    if ($name==='wp_kses_check_attr_val' && strtolower($args[2])==='value_callback') { return hybrid_fallback($name,'attribute-value-callback'); }
    if ($name==='wp_kses_normalize_entities' && isset($args[1]) && $args[1]!=='html') { return hybrid_fallback($name,'xml-entities'); }
    $envelope=elephc_wp_call($name,$args,$context);
    $value=$envelope['kind']==='string'?base64_decode($envelope['value'],true):($envelope['kind']==='error'?hybrid_error_from_state($envelope['value']):$envelope['value']);
    foreach ($envelope['filters']??[] as $event) {
        $callArgs=$event['args'];
        // These public functions expose the original input to their terminal hook.
        if (in_array($name,['esc_html','esc_attr','sanitize_key','sanitize_text_field','sanitize_textarea_field'],true) && count($callArgs)>1) { $callArgs[1]=$args[0]; }
        $filtered=apply_filters($event['name'],...$callArgs);
        if($event['terminal']??true)$value=$filtered;
    }
    foreach($envelope['actions']??[] as $event) {
        if($event['name']==='wp_error_added')do_action($event['name'],...array_merge($event['args'],[$value]));
        elseif($event['name']==='is_wp_error_instance')do_action($event['name'],$value);
    }
    return ['handled'=>true,'value'=>$value];
}

function hybrid_cache_encode(mixed $value): mixed {
    if (is_null($value) || is_bool($value) || is_int($value) || (is_float($value) && is_finite($value)) || (is_string($value) && !str_contains($value,"\0") && mb_check_encoding($value,'UTF-8'))) { return $value; }
    if (is_object($value)) {
        if (method_exists($value,'__clone') || method_exists($value,'__destruct')) { throw new LogicException('The experimental native cache excludes custom clone/destructor callbacks.'); }
        $value=clone $value;
    }
    $id=++$GLOBALS['hybrid_cache_next'];
    $GLOBALS['hybrid_cache_values'][$id]=$value;
    return ['__ep_value'=>$id];
}
function hybrid_cache_decode(mixed $value): mixed {
    if (is_array($value) && count($value)===1 && isset($value['__ep_value']) && is_int($value['__ep_value'])) {
        $result=$GLOBALS['hybrid_cache_values'][$value['__ep_value']];
        return is_object($result)?clone $result:$result;
    }
    return $value;
}
function hybrid_cache_call(string $name,array $args): mixed {
    global $wp_object_cache;
    if ($name==='wp_cache_init') {
        $GLOBALS['hybrid_cache_next']=0;
        $GLOBALS['hybrid_cache_values']=[];
        $wp_object_cache=new WP_Object_Cache();
    }
    if (in_array($name,['wp_cache_add','wp_cache_set','wp_cache_replace'],true)) { $args[1]=hybrid_cache_encode($args[1]); }
    if (in_array($name,['wp_cache_add_multiple','wp_cache_set_multiple'],true)) {
        foreach ($args[0] as $key=>$value) { $args[0][$key]=hybrid_cache_encode($value); }
    }
    $envelope=elephc_wp_call($name,$args,['multisite'=>is_multisite(),'blog_id'=>(int)($GLOBALS['blog_id']??1)]);
    if (isset($envelope['cache']['hits'])) { $wp_object_cache->cache_hits=$envelope['cache']['hits']; $wp_object_cache->cache_misses=$envelope['cache']['misses']; }
    $value=$envelope['kind']==='string'?base64_decode($envelope['value'],true):$envelope['value'];
    if ($name==='wp_cache_get') { $value['data']=hybrid_cache_decode($value['data']); }
    elseif ($name==='wp_cache_get_multiple') { foreach ($value as $key=>$item) { $value[$key]=hybrid_cache_decode($item); } }
    return $value;
}

function hybrid_list_call(string $name,array $args): array {
    $original=$args[0];
    if (!is_array($original)) { return hybrid_fallback($name,'list-input'); }
    $fields=[]; $pluck=false;
    if ($name==='wp_list_pluck') { $fields=[$args[1]]; if(isset($args[2]))$fields[]=$args[2]; $pluck=true; }
    elseif ($name==='wp_list_sort') {
        $order=$args[1]??[];
        $fields=is_array($order)?array_keys($order):[(string)$order];
    } else {
        if(!hybrid_plain($args[1]??[]))return hybrid_fallback($name,'list-comparison');
        $fields=array_keys($args[1]??[]);
        if($name==='wp_filter_object_list' && !empty($args[3])) { $fields[]=$args[3]; $pluck=true; }
    }
    $marker='__ep_native_row';
    while(in_array($marker,$fields,true))$marker.='_';
    $records=[]; $identities=[];
    foreach($original as $key=>$row) {
        if(!is_array($row) && !is_object($row))return hybrid_fallback($name,'list-row');
        $id=count($identities); $identities[$id]=$row; $record=[$marker=>$id];
        foreach(array_unique($fields,SORT_REGULAR) as $field) {
            if(!is_int($field) && !is_string($field))return hybrid_fallback($name,'list-field');
            $exists=is_array($row)?array_key_exists($field,$row):(isset($row->$field)||property_exists($row,(string)$field));
            if(!$exists) { if($pluck && $field===$args[1])return hybrid_fallback($name,'missing-pluck-field'); continue; }
            $value=is_array($row)?$row[$field]:$row->$field;
            if(is_object($row) && !$pluck && $name!=='wp_list_sort' && $value===null)continue;
            if(!hybrid_plain($value))return hybrid_fallback($name,'non-scalar-list-field');
            $record[$field]=$value;
        }
        $records[$key]=$record;
    }
    $args[0]=$records;
    $envelope=elephc_wp_call($name,$args,[]);
    $result=$envelope['value'];
    if(!$pluck) { foreach($result as $key=>$row) { $result[$key]=$identities[$row[$marker]]; } }
    return ['handled'=>true,'value'=>$result];
}
function hybrid_schema_prepare(mixed $input,int $depth=0,int &$nodes=0): ?array {
    if(!is_array($input)||$depth>8||++$nodes>64)return null;
    $type=$input['type']??null;
    if(!is_string($type)||!in_array($type,['null','boolean','integer','number','string','array','object'],true))return null;
    $ignored=['title','description','default','context','readonly','readOnly','arg_options','validate_callback','sanitize_callback'];
    $allowed=['type','minimum','maximum','multipleOf','exclusiveMinimum','exclusiveMaximum','minLength','maxLength','minItems','maxItems','items','uniqueItems','properties','required','additionalProperties','minProperties','maxProperties'];
    $schema=[];
    foreach($input as $key=>$value) {
        if(in_array($key,$ignored,true))continue;
        if(!in_array($key,$allowed,true))return null;
        if($key==='items'||($key==='additionalProperties'&&!is_bool($value))) {
            $value=hybrid_schema_prepare($value,$depth+1,$nodes); if($value===null)return null;
        } elseif($key==='properties') {
            if(!is_array($value)||count($value)>64)return null;
            $children=[];foreach($value as $name=>$child) { $child=hybrid_schema_prepare($child,$depth+1,$nodes); if($child===null)return null; $children[$name]=$child; }$value=$children;
        }
        $schema[$key]=$value;
    }
    return $schema;
}
function hybrid_error_from_state(array $state): WP_Error {
    $error=new WP_Error();
    $fill=Closure::bind(static function(WP_Error $object,array $state): void {
        $object->errors=$state['errors'];$object->error_data=$state['data'];$object->additional_data=$state['history'];
    },null,WP_Error::class);
    $fill($error,$state);
    return $error;
}
require_once __DIR__.'/host-objects.php';
