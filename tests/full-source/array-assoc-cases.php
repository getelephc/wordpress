<?php
$records=array(array(),array(0,1,2),array(2,1,0),array(1=>'x',0=>'y'),array('a'=>'x','b'=>'y'),array('b'=>'y','a'=>'x'),array('a'=>0,'b'=>null),array('a'=>'0','b'=>false),array('a'=>false,'b'=>''),array('a'=>true,'b'=>1),array('a'=>1.0,'b'=>'1'),array(-3=>'x','03'=>'z'),array(3=>'x','03'=>'z'),array('a'=>'é','b'=>"x\0y"));
$results=array();
foreach ($records as $left) foreach ($records as $right) {
    $results[]=array(assoc_difference_call(array($left,$right)));
    $results[]=array(assoc_difference_call(array($left,$right,array('a'=>false,0=>'0'))));
}
echo json_encode($results),"\n";
