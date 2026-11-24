<?php
final class StyleCallbackProbe {
 protected static function calculate(mixed $value,mixed $definition,mixed $options=array()):mixed{return array($value,$definition,$options);}
 public static function inspect():array{
  $array=array(self::class,'calculate');$native=self::calculate(...);
  return array(is_callable($array),is_callable($native),call_user_func($native,'value',array('field'=>1),array()));
 }
}
echo json_encode(StyleCallbackProbe::inspect()),"\n";
