<?php
$rows=array();
foreach(array('md5','MD5','sha256','SHA256')as$algorithm){
    try{$rows[]=array($algorithm,hash_hmac($algorithm,"a\0b",'key'));}
    catch(ValueError $error){$rows[]=array($algorithm,get_class($error));}
}
echo json_encode($rows,JSON_THROW_ON_ERROR),"\n";
