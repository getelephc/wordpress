<?php
function hybrid_extra_cases(): array {
    foreach(['sanitize_html_class','sanitize_key','esc_html','attribute_escape','wp_error_added','is_wp_error_instance','wp_kses_allowed_html','wp_kses_uri_attributes'] as $name)remove_all_filters($name);
    $out=[];
    add_shortcode('ep_test',static fn()=> 'host callback');
    add_shortcode('ep_other',static fn()=> 'other');
    foreach(['[ep_test a="b" x=1]Hi [ep_other/][/ep_test]','[[ep_test]]','[unknown]','plain'] as $index=>$text) {
        $out['shortcodes-'.$index]=[has_shortcode($text,'ep_test'),get_shortcode_tags_in_content($text),shortcode_parse_atts($text),get_shortcode_regex(),get_shortcode_regex(['ep_other'])];
    }
    add_filter('shortcode_atts_ep_test',static fn($value,$pairs,$atts,$name)=>array_merge($value,['trace'=>[$pairs,$atts,$name]]),10,4);
    $out['shortcode-atts']=shortcode_atts(['a'=>0,'b'=>1],['a'=>2,'c'=>3],'ep_test');
    remove_all_filters('shortcode_atts_ep_test');remove_shortcode('ep_test');remove_shortcode('ep_other');
    foreach(['a,b c',1,true,['a',false,null,[],['x'=>1],3]] as $index=>$input)$out['parse-list-'.$index]=[wp_parse_list($input),rest_is_array($input),rest_sanitize_array($input)];
    $data=(object)['value'=>7,'nested'=>(object)['a'=>1]];
    $error=new WP_Error('opaque','message',$data);
    $out['error-object-identity']=$error->get_error_data()===$data;
    $error->add_data($data);$all=$error->get_all_error_data();
    $out['error-object-history']=[$all[0]===$data,$all[1]===$data];
    $response=new WP_HTTP_Response($data,201,['X-Test'=>'yes']);
    $ensured=rest_ensure_response($response);
    $out['response-object-identity']=[$response->get_data()===$data,$ensured instanceof WP_REST_Response,$ensured->get_data()===$data,rest_ensure_response($ensured)===$ensured,rest_ensure_response($error)===$error];
    $request=new WP_REST_Request('GET','/ep-test');$request->set_query_params(['a'=>$data]);
    $out['request-object-identity']=[$request->get_param('a')===$data,$request->get_query_params()['a']===$data];
    wp_cache_init();
    wp_cache_set('ep-object',$data,'ep');$found=null;$cached=$GLOBALS['wp_object_cache']->get('ep-object','ep',false,$found);
    $out['cache-direct-class']=[$found,$cached!==$data,$cached->nested===$data->nested];
    $GLOBALS['wp_object_cache']->set('ep-direct',['v'=>2],'ep');
    $out['cache-direct-api']=wp_cache_get('ep-direct','ep');
    $value=['v'=>1];wp_cache_set('ep-array',$value,'ep');$value['v']=2;$cached=wp_cache_get('ep-array','ep');$cached['v']=3;
    $out['cache-array-independence']=wp_cache_get('ep-array','ep');
    $events=[];
    add_filter('esc_html',static function($safe,$original)use(&$events){$events[]=[$safe,$original];return '['.$safe.']';},10,2);
    $out['nested-hook']=[sanitize_text_field('a < b'),$events];remove_all_filters('esc_html');
    $rows=[(object)['id'=>1,'flag'=>null],(object)['id'=>2,'flag'=>false],(object)['id'=>3,'flag'=>true]];
    $out['object-list-identity']=array_map(static fn($row)=>$row->id,wp_list_filter($rows,['flag'=>null]));
    $list=new WP_List_Util($rows);$selected=$list->filter(['flag'=>true]);$out['list-class-identity']=reset($selected)===$rows[2];
    $error=new WP_Error('x','first',1);$error->get_error_codes();
    $error->errors['x'][]='external';$error->error_data['x']=2;
    $out['native-handle-external-error']=[$error->get_error_messages(),$error->get_error_data(),$error->get_all_error_data()];
    $response=new WP_REST_Response('data',200);$response->get_links();$response->set_status(403);$response->header('X-State','yes');
    $out['native-handle-inheritance']=[$response->is_error(),$response->get_status(),$response->get_headers()];
    $response->set_status(201);$out['native-handle-status-resync']=$response->is_error();
    $request=new WP_REST_Request('GET','/handles');$request->set_query_params(['before'=>1]);$request->get_param('before');
    $mutate=Closure::bind(static function($request){$request->params['GET']['outside']=2;},null,WP_REST_Request::class);$mutate($request);
    $out['native-handle-external-request']=[$request->get_param('outside'),$request->get_params()];
    $recycled=[];for($i=0;$i<16;++$i){$temporary=new WP_REST_Request('GET','/'.$i);$temporary->set_query_params(['i'=>$i]);$recycled[]=$temporary->get_param('i');unset($temporary);}
    $out['native-handle-lifetime']=$recycled;
    $out['numeric-array-opaque-values']=[wp_is_numeric_array([$data]),wp_is_numeric_array(['name'=>$data]),wp_is_numeric_array([]),wp_is_numeric_array((object)[])];
    $out['typed-entry-error-recovery']=true;
    if(defined('HYBRID_NATIVE')&&HYBRID_NATIVE) {
        try { elephc_wp_call('__unknown_profile_route',[]); } catch(Error $error) {}
        $out['typed-entry-error-recovery']=elephc_wp_call('absint',[3])['value']===3 && elephc_wp_call('wp_is_numeric_array',[[]])['value']===true;
    }
    return $out;
}
