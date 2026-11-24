<?php
class QueryCacheProbe83 extends WP_Query {
 public function cache_key(array $args,mixed $sql):mixed{return parent::generate_cache_key($args,$sql);}
}
if(defined('WP_AOT_NATIVE_RUNTIME')){$query_cache_xml83=new SimpleXMLElement('<query-cache/>');}
$query_cache_rows83=array();
$query_cache_inputs83=array(array(),array('post_type'=>'post'),array('post_type'=>'any'),array('post_type'=>array('page','post','page'),'post_status'=>array('publish','draft'),'post__in'=>array(-3,'2',2,1),'post_parent__in'=>array(4,2,4),'post_name__in'=>array('z','a','z')),array('orderby'=>null,'post_type'=>'fixture','nested'=>array('plain','{seed}text',array('x{seed}',null,false,7))));
foreach(array('none','placeholder','remove')as $query_cache_replace83)foreach(array(false,true)as $query_cache_derived83)foreach($query_cache_inputs83 as $query_cache_input83)foreach(array(array(),array('cache_results'=>false,'fields'=>'ids','lazy_load_term_meta'=>true,'update_post_meta_cache'=>false,'update_post_term_cache'=>false,'update_menu_item_cache'=>true,'suppress_filters'=>true))as $query_cache_extras83){
 $wpdb=$query_cache_derived83?new QueryCacheDatabase83('seed'):new wpdb('seed');
 $query_cache_trace83=array();$wpdb->replace_stage=$query_cache_replace83;$wpdb->replacement=$query_cache_derived83?new wpdb('replacement'):new QueryCacheDatabase83('replacement');
 $query_cache_probe83=new QueryCacheProbe83();
 $query_cache_arguments83=array_merge((array)$query_cache_input83,(array)$query_cache_extras83);
 $query_cache_snapshot83=$query_cache_arguments83;
 $query_cache_key83=$query_cache_probe83->cache_key($query_cache_arguments83,'SELECT {seed}value');
 $query_cache_rows83[]=array($query_cache_key83,$query_cache_probe83->cache_key($query_cache_arguments83,'SELECT {seed}value'),$query_cache_arguments83===$query_cache_snapshot83,$wpdb->name,get_class($wpdb),$query_cache_trace83);
}
echo json_encode($query_cache_rows83,JSON_THROW_ON_ERROR),"\n";
