<?php
class HybridUnsupportedPayload extends RuntimeException {}
function hybrid_object_pack(mixed $value,int $depth=0): mixed {
    if($depth>32)throw new HybridUnsupportedPayload('object-data-depth');
    if(is_object($value)||is_resource($value)||(is_float($value)&&!is_finite($value))||(is_string($value)&&!mb_check_encoding($value,'UTF-8'))) {
        $id=++$GLOBALS['hybrid_object_next'];$GLOBALS['hybrid_object_values'][$id]=$value;return ['__ep_php_value'=>$id];
    }
    if(is_array($value)) {
        $out=[];foreach($value as $key=>$item) {
            if(ReflectionReference::fromArrayElement($value,$key)!==null)throw new HybridUnsupportedPayload('reference-data');
            if(is_string($key)&&str_contains($key,"\0"))throw new HybridUnsupportedPayload('binary-data-key');
            $out[$key]=hybrid_object_pack($item,$depth+1);
        }return $out;
    }return $value;
}
function hybrid_object_unpack(mixed $value): mixed {
    if(is_array($value)) {
        if(count($value)===1 && isset($value['__ep_php_value']) && is_int($value['__ep_php_value']) && array_key_exists($value['__ep_php_value'],$GLOBALS['hybrid_object_values']??[]))return $GLOBALS['hybrid_object_values'][$value['__ep_php_value']];
        foreach($value as $key=>$item)$value[$key]=hybrid_object_unpack($item);
    }return $value;
}
function hybrid_object_state(object $object): array {
    static $readers=[];$class=get_class($object);
    $read=$readers[$class]??=Closure::bind(static function(object $object): array { return get_object_vars($object); },null,$class);
    return $read($object);
}
function hybrid_object_restore(object $object,array $state): void {
    if(!$state)return;
    static $writers=[];$class=get_class($object);
    $write=$writers[$class]??=Closure::bind(static function(object $object,array $state): void { foreach($state as $key=>$value)$object->$key=$value; },null,$class);
    $write($object,hybrid_object_unpack($state));
}
function hybrid_object_try(string $class,string $method,object $object,array $args): array {
    $route=$class.'::'.$method;
    $actual=get_class($object);
    if(!in_array($actual,['WP_Error','WP_HTTP_Response','WP_REST_Response','WP_REST_Request','WordPressRequestAdapter'],true))return hybrid_fallback($route,'custom-subclass');
    if(!function_exists('elephc_wp_call'))return hybrid_fallback($route,'extension-unavailable');
    $state=hybrid_object_state($object);
    if($class==='WP_Error') {
        if($method==='add' && (!is_string($args[1]??null) || has_action('wp_error_added')!==false || has_filter('all')!==false))return hybrid_fallback($route,'error-callback-or-message');
        foreach($state['errors']??[] as $messages)foreach($messages as $message)if(!is_string($message))return hybrid_fallback($route,'non-string-error-message');
    }
    if(in_array($class,['WP_HTTP_Response','WP_REST_Response'],true)) {
        if($method==='header' && (!is_string($args[0])||!is_string($args[1])))return hybrid_fallback($route,'header-coercion');
        if($method==='set_status'&&!is_scalar($args[0]))return hybrid_fallback($route,'status-coercion');
    }
    $context=[];
    if($class==='WP_REST_Request') {
        $parameterMethods=['get_param','has_param','set_param','get_params','get_body_params','offsetExists','offsetGet','offsetSet','offsetUnset'];
        if(in_array($method,$parameterMethods,true) && (has_filter('rest_request_parameter_order')!==false||has_filter('all')!==false))return hybrid_fallback($route,'parameter-order-hook');
        if(isset($state['body'])&&!mb_check_encoding($state['body'],'UTF-8'))return hybrid_fallback($route,'binary-body');
        $type=strtolower(implode(',',$state['headers']['content_type']??[]));
        if(in_array($method,$parameterMethods,true) && !empty($state['body']) && empty($state['parsed_body']) && ($type===''||str_starts_with($type,'application/x-www-form-urlencoded')))return hybrid_fallback($route,'host-form-decoding');
        if($method==='get_params')$context['permalinks']=function_exists('get_option') && (bool)get_option('permalink_structure');
    }
    $other=null;
    if($class==='WP_Error' && in_array($method,['merge_from','export_to'],true)) {
        if(!isset($args[0])||!$args[0] instanceof WP_Error||get_class($args[0])!=='WP_Error'||has_action('wp_error_added')!==false)return hybrid_fallback($route,'error-transfer-callback');
        $other=$args[0];$context['self_transfer']=$other===$object;$args[0]=hybrid_object_state($other);
    }
    $records=$GLOBALS['hybrid_native_objects'][$object]??[];
    $entry=$records[$class]??null;
    $id=$entry['id']??++$GLOBALS['hybrid_next_object_handle'];
    $context['handle']=$id;
    $sync=$entry===null || $state!==$entry['snapshot'];
    try { $payload=hybrid_object_pack([$sync?$state:[],$args]); } catch(HybridUnsupportedPayload $e) { return hybrid_fallback($route,$e->getMessage()); }
    $envelope=elephc_wp_call($route,$payload,$context);
    hybrid_object_restore($object,$envelope['object_state']);
    if($other!==null)hybrid_object_restore($other,$envelope['second_state']);
    $records[$class]=['id'=>$id,'snapshot'=>hybrid_object_state($object)];
    $GLOBALS['hybrid_native_objects'][$object]=$records;
    if($envelope['kind']==='error')$value=hybrid_error_from_state(hybrid_object_unpack($envelope['value']));
    else $value=$envelope['kind']==='string'?base64_decode($envelope['value'],true):hybrid_object_unpack($envelope['value']);
    foreach($envelope['filters']??[] as $event) {
        $callArgs=hybrid_object_unpack($event['args']);
        if($event['name']==='rest_request_parameter_order')$callArgs[1]=$object;
        $filtered=apply_filters($event['name'],...$callArgs);
        if($event['name']==='rest_response_link_curies')$value=array_merge($value,(array)$filtered);
        elseif($event['terminal']??false)$value=$filtered;
    }
    foreach($envelope['actions']??[] as $event) {
        $target=$object instanceof WP_Error?$object:$value;
        if($event['name']==='wp_error_added')do_action($event['name'],...array_merge(hybrid_object_unpack($event['args']),[$target]));
        elseif($event['name']==='is_wp_error_instance')do_action($event['name'],$target);
    }
    return ['handled'=>true,'value'=>$value];
}
$GLOBALS['hybrid_object_next']=0;
$GLOBALS['hybrid_object_values']=[];
$GLOBALS['hybrid_native_objects']=new WeakMap();
$GLOBALS['hybrid_next_object_handle']=0;
function hybrid_response_call(string $name,mixed $input): array {
    $kind='value';$payload=$input;
    if(is_object($input)) {
        $kind=match(get_class($input)) { 'WP_Error'=>'error','WP_HTTP_Response'=>'http','WP_REST_Response'=>'response',default=>'value' };
        if($input instanceof WP_HTTP_Response && $kind==='value')return hybrid_fallback($name,'custom-response-subclass');
        if($input instanceof WP_Error && $kind!=='error')return hybrid_fallback($name,'custom-error-subclass');
        if($kind!=='value')$payload=hybrid_object_state($input);
    }
    if($name==='rest_convert_error_to_response' && ($kind!=='error'||!$input->has_errors()))return hybrid_fallback($name,'invalid-error-input');
    if($kind==='error')foreach($input->errors as $messages)foreach($messages as $message)if(!is_string($message))return hybrid_fallback($name,'non-string-error-message');
    try { $payload=hybrid_object_pack($payload); } catch(HybridUnsupportedPayload $error) { return hybrid_fallback($name,$error->getMessage()); }
    $envelope=elephc_wp_call($name,[$payload],['input_kind'=>$kind]);
    if($name==='rest_ensure_response'&&in_array($kind,['error','response'],true))$value=$input;
    else {
        $value=(new ReflectionClass(WP_REST_Response::class))->newInstanceWithoutConstructor();
        hybrid_object_restore($value,$envelope['value']);
    }
    foreach($envelope['actions']??[] as $event)if($event['name']==='is_wp_error_instance')do_action($event['name'],$input);
    return ['handled'=>true,'value'=>$value];
}
