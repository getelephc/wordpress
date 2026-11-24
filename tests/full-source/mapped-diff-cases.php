<?php
function mapped_diff_cases(): array {
    $rows = array();
    foreach (array(
     array(array(),array()),
     array(array('Alpha','Same','Old'),array('alpha','Same','New','Extra')),
     array(array('one'),array()),
     array(array(),array('Two')),
     array(array('A','A','B'),array('a','B','B')),
     array(array('head','old-1','keep-a','keep-b','old-2','tail'),array('head','new-1','keep-a','keep-b','new-2','tail')),
    ) as $case) {
     $from=$case[0];$to=$case[1];$mf=array();$mt=array();
     foreach($from as $line)$mf[]=strtolower($line);
     foreach($to as $line)$mt[]=strtolower($line);
     $diff=new Text_MappedDiff($from,$to,$mf,$mt);
     $ops=array();foreach($diff->getDiff() as $op)$ops[]=array(get_class($op),$op->orig,$op->final);
     foreach (array(array(),array('leading_context_lines'=>1,'trailing_context_lines'=>1),array('leading_context_lines'=>2,'trailing_context_lines'=>0)) as $params) {
         $renderer = new Text_Diff_Renderer($params);
         $rendered = $renderer->render($diff);
         $rows[] = array($renderer->getParams(),$rendered,$renderer->render($diff),$diff->getOriginal(),$diff->getFinal());
     }
     $rows[]=array($diff->getOriginal(),$diff->getFinal(),$ops,$diff->countAddedLines(),$diff->countDeletedLines(),$diff->isEmpty(),$diff->lcs());
    }
    return $rows;
}
