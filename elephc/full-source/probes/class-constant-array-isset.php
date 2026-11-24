<?php
final class ConstantSelectorProbe {
 const VALUES = array('link'=>array(':hover',':focus'),'button'=>array(':active'));
 public static function observe(mixed $key):array {
  return array($key,isset(self::VALUES[$key]),array_key_exists($key,self::VALUES),self::VALUES[$key]??array());
 }
}
$rows=array();foreach(array('link','heading','h1','button','caption','textInput','select','')as$key)$rows[]=ConstantSelectorProbe::observe($key);
echo json_encode($rows,JSON_THROW_ON_ERROR),"\n";
