<?php

$results = array();
$arrays = array(array(), array(1, 2), array('x'=>1, 'y'=>null), array(3=>'a', 0=>'b', -2=>'c'), array('1'=>'a', 'z'=>false));
foreach ($arrays as $left) foreach ($arrays as $right) $results[] = aot_test_union($left, $right);
$strings = array('', 'abca', 'aaaa', 'abcabc', "\0a\xffb", 'éètest');
foreach ($strings as $left) foreach ($strings as $right) $results[] = base64_encode(aot_test_xor($left, $right));
$maps = array(array(), array('a'=>'ab','ab'=>'X'), array('ab'=>'Y','a'=>'Z'), array(1=>2,'abc'=>false,'b'=>null), array('é'=>'e',"\0"=>'nul'));
foreach ($strings as $text) foreach ($maps as $map) $results[] = base64_encode(aot_test_translate($text, $map));
foreach ($strings as $text) foreach ($strings as $from) foreach ($strings as $to) $results[] = base64_encode(aot_test_translate($text, $from, $to));
foreach ($arrays as $left) foreach ($arrays as $right) $results[] = array_keys((array) aot_test_union($left, $right));
$left = array(100=>'removed'); unset($left[100]);
$merged = aot_test_union($left, array(2=>'right')); $merged[] = 'tail'; $results[] = $merged;
$left = array('nested'=>array('original'=>1));
$merged = aot_test_union($left,array()); $merged['nested']['result']=2; $results[] = $left; $results[] = $merged;
$empty_left = array(100=>1); unset($empty_left[100]);
$empty_right = array(200=>1); unset($empty_right[200]);
foreach (array(array($empty_left,$empty_right), array($empty_left,array()), array(array(),$empty_right), array(array(1),$empty_right)) as $pair) {
    $merged = aot_test_union($pair[0],$pair[1]); $merged[] = 'tail'; $results[] = $merged;
}
echo json_encode($results), "\n";
