<?php
class wpdb {
 public function _real_escape(mixed $data): mixed {return (string)$data;}
 public function prepare(mixed $query,mixed ...$args): mixed {
  $escaped=array();foreach($args as $value)$escaped[]=$this->_real_escape($value);
  echo json_encode(array('arguments'=>$args,'escaped'=>$escaped)), "\n";
  return vsprintf($query,$escaped);
 }
}
class Fixture extends wpdb {
 public function _real_escape(mixed $data): string { return str_replace("'", "''", (string)$data); }
}
function database_view(mixed $value): wpdb {if($value instanceof wpdb)return $value;throw new TypeError('Expected database');}
$db=new Fixture();echo database_view($db)->prepare("SELECT '%s'",'wp_attachment_pages_enabled'), "\n";
