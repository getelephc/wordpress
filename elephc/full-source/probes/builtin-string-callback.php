<?php
function strip_dispatch(mixed $callback,string $text): mixed {return call_user_func_array($callback,array($text));}

$rows=array();
foreach(array('strip_tags','STRIP_TAGS','\\strip_tags')as$name)foreach(array('', '<b>one</b> &amp; <a href="x">two</a>', "A\0<b>B</b>", '<script>alert(1)</script>text')as$text){
    try{$rows[]=array($name,$text,strip_dispatch($name,$text));}
    catch(Throwable $error){$rows[]=array($name,$text,get_class($error));}
}
echo json_encode($rows,JSON_THROW_ON_ERROR),"\n";
