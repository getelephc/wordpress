<?php
function nav_menu_splice_cases(): array {
    $results = array();
    foreach (array('[]','[{"term_id":1},{"term_id":2}]','[null,false,{"term_id":3}]','{"keep":{"term_id":4},"8":{"term_id":5},"08":null}') as $json) {
        foreach (array(-4,-1,0,1,4) as $offset) foreach (array(null,-5,-1,0,1,2,9) as $length) foreach (array(0,1,2) as $replacement_count) {
            $values = (array) json_decode($json);
            $snapshot = $values;
            $alias =& $values;
            $replacement = new stdClass();
            $replacement->term_id = 71;
            $replacement->name = 'replacement';
            $removed = mutation_splice_call($values,$offset,$length,$replacement,$replacement_count);
            $published = json_encode($values,JSON_THROW_ON_ERROR);
            $matches = 0;
            foreach ($values as $entry) if ($entry === $replacement) ++$matches;
            $replacement->name = 'changed';
            $nested_source = array('auto_add'=>$snapshot,'keep'=>17);
            $nested_snapshot = $nested_source;
            $nested_result = mutation_nested_splice($nested_source,$offset,$length,$replacement,$replacement_count);
            $results[] = array($json,$offset,$length,$replacement_count,$removed,$values,array_keys($values),$alias,$snapshot,$published,$matches,$nested_result,$nested_source,$nested_snapshot);
            unset($alias);
        }
    }
    return $results;
}
