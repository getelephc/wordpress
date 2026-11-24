<?php

$results=array();
foreach(array('{}','{"background":{"backgroundImage":{"url":"https://example.test/a.png"}}}','{"background":{"backgroundImage":"linear-gradient(red,blue)"}}','{"spacing":{"margin":{"top":"1px","bottom":"2px"},"padding":{"left":"3px","right":"4px"}}}','{"border":{"color":{"top":"red","bottom":"blue"},"radius":{"topLeft":"1px","bottomRight":"2px"}}}','{"color":{"text":"var:preset|color|blue","background":"#fff"}}','{"typography":{"fontSize":"var:preset|font-size|small"}}','{"dimensions":{"aspectRatio":"4/3"}}')as$json){
 foreach(array(false,true)as$convert){$input=json_decode($json,true,512,JSON_THROW_ON_ERROR);$before=$input;$result=WP_Style_Engine::parse_block_styles($input,array('convert_vars_to_classnames'=>$convert));$results[]=array($json,$convert,$result,$input,$before);}
}
echo json_encode($results,JSON_THROW_ON_ERROR),"\n";
