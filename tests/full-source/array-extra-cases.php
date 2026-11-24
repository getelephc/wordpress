<?php
function extra_seed(): mixed { return array('A'=>'file10',2=>'File2','z'=>'file1'); }
class ExtraCountable implements Countable { public function count(): int {return 7;} }
$results = array();
$records = array(array(), array('id'=>1,'name'=>'alpha','key'=>'first'),array('name'=>'beta'),array('id'=>null,'key'=>3),array('id'=>false,'key'=>'0'),array('id'=>array('nested'=>2),'key'=>null),array('id'=>2,'key'=>0),null,17,'skip');
foreach (array(null,'id','name','missing',0,true,1.0) as $column) foreach (array(null,'key','missing',0) as $index) {
    $value = extra_column($records,$column,$index);
    $results[] = array($value,array_keys($value));
}
$arrays = array(array(),array(1,null,false,0,''),array('a'=>null,3=>false,-1=>0,'z'=>'0'));
foreach ($arrays as $array) foreach (array(null,false,true,0,1,'','0') as $needle) foreach (array(false,true) as $strict) foreach (array(false,true) as $filter) $results[] = extra_keys($array,$needle,$strict,$filter);
foreach (array(array(),array('a','b'),array(1,true,null,1.5,'01','-2')) as $keys) foreach (array(array(),array(1,2),array(null,false,'x',3,4,5)) as $values) {
    try {$value=extra_combine($keys,$values);$results[]=array('ok',$value,array_keys($value));}
    catch (ValueError $error) {$results[]=array('error','ValueError');}
}
foreach (array(null,false,1,'text',new stdClass()) as $invalid) foreach (array(false,true) as $invalid_values) {
    try {
        $value=extra_combine($invalid_values?array('key'):$invalid,$invalid_values?$invalid:array('value'));
        $results[]=array('ok',$value);
    } catch (TypeError $error) {$results[]=array('error','TypeError');}
}
foreach (array(array(),array(1,2),new ExtraCountable()) as $value) $results[] = extra_count($value);
foreach (array('natural','case') as $operation) {
    $value=extra_seed();$ok=extra_sort($operation,$value);$results[]=array($ok,$value,array_keys($value));
}
$value=extra_seed();$before=$value;$ok=extra_shuffle($value);$sorted=array_values($value);sort($sorted);$original=array_values($before);sort($original);$results[]=array($ok,array_keys($value),$sorted===$original,$before);
foreach (array(null,false,true,0,1,-1,1.5,'0','01','foo','2000','0000') as $left) foreach (array(null,false,true,0,1,-1,1.5,'0','01','foo','2000','0000') as $right) $results[] = extra_compare($left,$right);
$flip_values = array(-9223372036854775807 - 1, -7, -1, 0, 1, 7, 9223372036854775807, '', '0', '1', '01', '+1', '-0', '-1', '9223372036854775807', '9223372036854775808', 'A', 'a', 'café', "key\0tail");
$flip_arrays = array(array());
foreach ($flip_values as $value) $flip_arrays[] = array($value);
foreach ($flip_values as $left) foreach ($flip_values as $right) {
    $flip_arrays[] = array('first' => $left, 12 => $right, -2 => $left);
}
foreach ($flip_arrays as $array) {
    $before = $array;
    $flipped = extra_flip($array);
    $results[] = array($flipped, array_keys($flipped), $array === $before);
}
foreach (array(null, false, true, 0, 7, 1.5, 'text', new stdClass()) as $invalid) {
    try { $results[] = array('ok', extra_flip($invalid)); }
    catch (TypeError $error) { $results[] = array('error', 'TypeError'); }
}
echo json_encode($results), "\n";
