<?php
function lookup_array(array $values,int $x): mixed {return (isset($values[$x])&&''!==$values[$x])?$values[$x]:null;}
function row_view(mixed $value): stdClass {if($value instanceof stdClass)return $value;throw new TypeError('Expected SQL record');}
function mixed_value(mixed $value): mixed {return $value;}
function array_view(array $value): array {return $value;}
class LookupRow {
 public array $rows=array();
 public function projected(mixed $x=0): mixed {
  $values=array_values(get_object_vars(row_view($this->rows[0])));
  return (isset($values[$x])&&''!==$values[$x])?$values[$x]:null;
 }
 public function local(mixed $x=0): mixed {
  $row=$this->rows[0];$values=array_values(get_object_vars($row));
  return (isset($values[$x])&&''!==$values[$x])?$values[$x]:null;
 }
 public function erased(mixed $x=0): mixed {
  $values=array_view(array_values(get_object_vars($this->rows[0])));
  return (isset($values[$x])&&''!==$values[$x])?$values[$x]:null;
 }
 public function boxed(mixed $x=0): mixed {
  $values=array_values(get_object_vars($this->rows[0]));
  return mixed_value((isset($values[$x])&&''!==$values[$x])?$values[$x]:null);
 }
 public function helper(mixed $x=0): mixed {
  $values=array_values(get_object_vars($this->rows[0]));
  return lookup_array($values,(int)$x);
 }
 public function lookup(mixed $x=0): mixed {
  if(!empty($this->rows[0]))$values=array_values(get_object_vars($this->rows[0]));
  return (isset($values[$x])&&''!==$values[$x])?$values[$x]:null;
 }
}
$lookup=new LookupRow();
foreach(array('WordPress','0','',null,'é汉')as$value){$row=new stdClass();$row->value=$value;$lookup->rows=array($row);echo json_encode(array(lookup_array(array($value),0),$lookup->lookup(),$lookup->lookup(0),$lookup->projected(),$lookup->local(),$lookup->erased(),$lookup->boxed(),$lookup->helper())),"\n";}
