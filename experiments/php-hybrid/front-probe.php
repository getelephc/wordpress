<?php
$root=dirname(__DIR__,2);
$site=$root.'/elephc/build/php-hybrid/wordpress';
$target=(string)($argv[1]??7);$id=(int)$target;
$uri=str_starts_with($target,'/')?$target:($id?'/?p='.$id:'/');
$query=(string)(parse_url($uri,PHP_URL_QUERY)??'');parse_str($query,$_GET);
$_SERVER=array_merge($_SERVER,['HTTP_HOST'=>'127.0.0.1:9000','SERVER_NAME'=>'127.0.0.1','SERVER_PORT'=>'9000','REQUEST_URI'=>$uri,'REQUEST_METHOD'=>'GET','QUERY_STRING'=>$query,'PHP_SELF'=>'/index.php','SCRIPT_NAME'=>'/index.php','SCRIPT_FILENAME'=>$site.'/index.php','DOCUMENT_ROOT'=>$site,'SERVER_PROTOCOL'=>'HTTP/1.1']);
require __DIR__.'/router.php';
