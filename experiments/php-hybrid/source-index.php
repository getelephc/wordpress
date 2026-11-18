<?php
$source = file_get_contents($argv[1]);
$tokens = token_get_all($source);
$offset = 0;
$items = [];
foreach ($tokens as $token) {
    $text = is_array($token) ? $token[1] : $token;
    $items[] = ['id'=>is_array($token)?$token[0]:null,'text'=>$text,'start'=>$offset,'end'=>$offset+strlen($text)];
    $offset += strlen($text);
}
$result = [];
for ($i=0; $i<count($items); ++$i) {
    if ($items[$i]['id'] !== T_FUNCTION) { continue; }
    $j=$i+1;
    while ($items[$j]['id']===T_WHITESPACE || $items[$j]['text']==='&') { ++$j; }
    if ($items[$j]['id']!==T_STRING) { continue; }
    $name=$items[$j]['text'];
    while ($items[$j]['text']!=='(') { ++$j; }
    $paramsStart=$items[$j]['end'];
    $level=1;
    while ($level>0) { ++$j; if($items[$j]['text']==='(')++$level; if($items[$j]['text']===')')--$level; }
    $paramsEnd=$items[$j]['start'];
    $signatureEnd=$items[$j]['end'];
    while ($items[$j]['text']!=='{') { ++$j; }
    $bodyStart=$items[$j]['end'];
    $returnType=substr($source,$signatureEnd,$items[$j]['start']-$signatureEnd);
    $level=1;
    while ($level>0) { ++$j; if($items[$j]['text']==='{')++$level; if($items[$j]['text']==='}')--$level; }
    $start=$items[$i]['start'];
    $lineStart=strrpos(substr($source,0,$start),"\n");
    $result[$name]=['start'=>$start,'line_start'=>$lineStart===false?0:$lineStart+1,'end'=>$items[$j]['end'],'body_start'=>$bodyStart,'body_end'=>$items[$j]['start'],'params'=>substr($source,$paramsStart,$paramsEnd-$paramsStart),'return_type'=>trim($returnType)];
    $i=$j;
}
echo json_encode($result,JSON_THROW_ON_ERROR);
