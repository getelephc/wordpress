<?php
$forward_results = array();
foreach (array('["one"]','["two",false]','["many",null,1,true,{"x":2},[3,4]]',
    '["long",1,2,3,4,5,6,7,8,9,10,11]','{"feature":"named","extra":null,"second":2}',
    '{"0":"mixed","1":7,"named":false}') as $forward_json) {
    $forward_input = (array)json_decode($forward_json,true,512,JSON_THROW_ON_ERROR);
    $forward_results[] = array($forward_input,theme_forward_call($forward_input),$forward_input);
}
return $forward_results;
