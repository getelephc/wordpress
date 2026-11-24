<?php
$values=array(null,false,true,0,1,-1,PHP_INT_MIN,PHP_INT_MAX,1.0,1.5,-1.5,1e300,-1e300,1e309,'','0','-0','+0012','-00001',' 12 ',"\t-9\r\n",'1.5','-1.5','1e3','2E-1','0xFF','foo',"1\0",'9223372036854775807','-9223372036854775808','9223372036854775808','-9223372036854775809','9223372036854775807.0','-9223372036854775808.0','+0009223372036854775807',array(),new stdClass());
$results=array();
foreach ($values as $value) {
    $row = array();
    try {$row[] = array('ok', integer_call($value));}
    catch (TypeError $error) {$row[] = array('error', 'TypeError');}
    try {$row[] = array('ok', weak_integer_call($value));}
    catch (TypeError $error) {$row[] = array('error', 'TypeError');}
    try {$row[] = array('ok', nullable_integer_call($value));}
    catch (TypeError $error) {$row[] = array('error', 'TypeError');}
    try {$row[] = array('ok', nullable_float_call($value));}
    catch (TypeError $error) {$row[] = array('error', 'TypeError');}
    $results[] = $row;
}
require __DIR__ . '/number-format-cases.php';
foreach (number_format_cases() as $row) $results[] = $row;
require __DIR__ . '/calendar-cases.php';
foreach (calendar_cases() as $row) $results[] = $row;
echo json_encode($results), "\n";
