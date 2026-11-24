<?php
$sets = array(array(), array('a', 'b', 'c'), array(3 => 'a', 9 => 'b'), array(-4 => 'a', -2 => 'b'), array('x' => null, 8 => false, 'y' => array(1, 2)), array('x' => 'first', 'y' => 'last'));
$results = array();
foreach ($sets as $data) foreach (array(0, 1, 2) as $history) foreach (array('push', 'unshift', 'shift', 'pop') as $operation) {
    $array = (array) $data;
    if ($history) { $array[19] = 'latent'; unset($array[19]); }
    if ($history === 2) foreach (array_keys($array) as $key) unset($array[$key]);
    $saved = $array; $alias =& $array;
    for ($repeat = 0; $repeat < 4; ++$repeat) {
        $value = mutation_call((string) $operation, $array, $repeat % 2 ? array() : array('prefix', array('nested' => $repeat)));
        $copy = $array; $copy[] = 'after';
        $results[] = array($value, $array, array_keys($array), $copy, array_keys($copy), $alias, $saved);
    }
}
foreach (array(0,12,40,-2,'01','label',null,true,false) as $nullable_key) {
    foreach (array('error',null,false,0,array('code'=>'value')) as $nullable_value) {
        foreach (array(false,true) as $nullable_write) {
            $nullable_result = nullable_array_write($nullable_write,$nullable_key,$nullable_value);
            $results[] = array($nullable_result,is_array($nullable_result)?array_keys($nullable_result):null);
        }
    }
}
foreach (array(0,12,40,-2,'01','label',null,true,false) as $map_key) {
    foreach (array('category','post_tag','link_category') as $map_taxonomy) {
        foreach (array(false,true) as $map_legacy) {
            $results[] = upgrade_shape($map_legacy,$map_key,$map_taxonomy,31);
        }
    }
}
foreach(array('[]','[{"keep":1,"translations":[1],"compatibility":{"x":2}}]','[[1,2],null,7,"text"]','{"first":{"keep":2,"translations":null},"08":[3],"last":{"compatibility":false,"keep":4}}') as $json){
 foreach(array('response','no_update') as $property)foreach(array(0,1,2) as $throw_at){
  $holder=new stdClass();$holder->$property=(array)json_decode($json);$saved=$holder->$property;$trace=mutation_mixed(array());$seen=0;
  $property_walk_callback=static function(mixed &$item,mixed $key) use(&$trace,&$seen,$throw_at): mixed {
   ++$seen;$item=(object)$item;$trace[]=array($key,$seen);if($seen===$throw_at)throw new RuntimeException('stopped');
   $record=mutation_record($item);unset($record->translations,$record->compatibility);$item=$record;return $item;
  };
  try{$ok=walk_holder($holder,$property,$property_walk_callback);$outcome=array('ok',$ok);}catch(RuntimeException $e){$outcome=array('error',$e->getMessage());}
  $results[]=array($property,$throw_at,$outcome,$holder->$property,$saved,$trace,$seen);
 }
}
foreach ($sets as $unset_seed) foreach(array(0,1,3,8,-2,'x','y','missing','01','0') as $unset_key) {
    $unset_source=(array)$unset_seed;
    $unset_snapshot=$unset_source;
    $unset_after=mutation_unset_key($unset_source,$unset_key);
    $unset_copy=$unset_after;$unset_copy['local-only']='kept';
    $results[]=array($unset_key,$unset_after,array_keys($unset_after),$unset_copy,$unset_source,$unset_snapshot);
}
require __DIR__ . '/connector-callback-cases.php';
foreach (connector_callback_cases() as $row) $results[] = $row;
require __DIR__ . '/nav-menu-splice-cases.php';
foreach (nav_menu_splice_cases() as $row) $results[] = $row;
$append_object=(object)array('label'=>'kept');
foreach(array(array(),array(null),array(false,0,'é'),array(array('nested'=>array('x'=>1))),array($append_object,$append_object)) as $append_items) {
    $results[]=nullable_append_call((array)$append_items);
}
foreach (array('[]','[{}]','[{"slug":"one","screenshot_url":"one.png","author":{"display_name":"A"},"keep":[1]}]',
    '[{"slug":null,"screenshot_url":false,"author":null,"name":"é"},{"slug":false,"screenshot_url":null,"author":false}]',
    '[{"slug":"one","screenshot_url":"s","author":{"x":1}},{"slug":"two","keep":{"x":[2]}},{"keep":false}]') as $theme_json) {
    $theme_rows = (array)json_decode($theme_json,false,512,JSON_THROW_ON_ERROR);
    $theme_snapshot = $theme_rows;
    $theme_before = json_encode($theme_rows,JSON_THROW_ON_ERROR);
    $theme_trace = array();
    foreach ($theme_rows as &$theme_item) {
        $theme_alias = $theme_item;
        $theme_returned = theme_record_remove($theme_item);
        $theme_trace[] = array($theme_returned===$theme_item,$theme_alias===$theme_item,array_keys(get_object_vars($theme_item)),
            $theme_item,$theme_alias,$theme_returned);
    }
    $theme_after = json_encode($theme_rows,JSON_THROW_ON_ERROR);
    if (count($theme_rows)) {
        $theme_old_last = $theme_item;
        $theme_item = json_decode('{"replacement":true}',false,512,JSON_THROW_ON_ERROR);
        $theme_last_state = array($theme_old_last,$theme_item,$theme_rows,$theme_snapshot);
    } else $theme_last_state = null;
    unset($theme_item);
    $results[] = array($theme_json,$theme_before,$theme_after,$theme_trace,$theme_rows,$theme_snapshot,$theme_last_state);
}
$_wp_post_type_features = mutation_mixed(array());
foreach (wp_aot_post_support_cases('post_support_remove') as $row) $results[] = $row;
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
