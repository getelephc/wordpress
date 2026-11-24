<?php
function pattern_snapshot(mixed $record):mixed{
 if($record===null)return null;
 $value=(array)$record;
 if(array_key_exists('filePath',$value))$value['filePath']=basename((string)$value['filePath']);
 if(isset($value['content']))$value['content']=array(strlen((string)$value['content']),hash('sha256',(string)$value['content']));
 return $value;
}
function pattern_list_snapshot(mixed $records):array{$result=array();foreach((array)$records as$record)$result[]=pattern_snapshot($record);return$result;}
$rows=array();
foreach(array(array(array(),'filePath'),array(array('filePath'=>'path','name'=>'kept'),'filePath'),array(array('x'=>null,8=>false,'filePath'=>array('nested'=>1)),'x'),array(array(3=>'a',9=>'b'),3))as$deletion){
 $source=(array)$deletion[0];$before=$source;$removed=wp_aot_array_without_key($source,$deletion[1]);$appended=$removed;$appended[]='next';
 $rows[]=array($deletion[1],$removed,array_keys($removed),$appended,$source,$before);
}

foreach(array('init','admin_init')as$action){
 $pattern_test_action=$action;$pattern_test_echoes=0;$pattern_test_trace=array();
 $registry=new WP_Block_Patterns_Registry();
 $file=WP_CONTENT_DIR.'/themes/twentytwentyfive/patterns/more-posts.php';
 $rows[]=array($action,$registry->register('fixture/inline',array('title'=>'Inline é','content'=>'<p>inline</p>')));
 $rows[]=array($action,$registry->register('fixture/file',array('title'=>'Loaded','filePath'=>$file)));
 $rows[]=array($action,$registry->register('fixture/provided',array('title'=>'Provided','content'=>'','filePath'=>$file)));
 $rows[]=array(pattern_snapshot($registry->get_registered('fixture/inline')),$pattern_test_echoes);
 $first=$registry->get_registered('fixture/file');
 $rows[]=array(pattern_snapshot($first),$pattern_test_echoes);
 $rows[]=array(pattern_snapshot($registry->get_registered('fixture/file')),$pattern_test_echoes,pattern_snapshot($first));
 $rows[]=array(pattern_snapshot($registry->get_registered('fixture/provided')),$pattern_test_echoes);
 $rows[]=array(pattern_list_snapshot($registry->get_all_registered(true)),$pattern_test_echoes);
 $rows[]=array(pattern_list_snapshot($registry->get_all_registered(true)),$pattern_test_echoes);
 $rows[]=array(pattern_list_snapshot($registry->get_all_registered()),$pattern_test_echoes);
 $rows[]=array(pattern_list_snapshot($registry->get_all_registered()),$pattern_test_echoes);
 $rows[]=array($registry->unregister('fixture/file'),$registry->is_registered('fixture/file'),$registry->get_registered('fixture/file'));
 $rows[]=array(pattern_list_snapshot($registry->get_all_registered()),pattern_list_snapshot($registry->get_all_registered(true)),$pattern_test_echoes,$pattern_test_trace);
}
echo json_encode($rows,JSON_THROW_ON_ERROR),"\n";
