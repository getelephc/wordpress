<?php
$results = array();
foreach (array('', 'a', 'aaa', 'aba', 'alpha', "a\0b", 'á😀a', 42, null, array('x' => 'aba', 5 => 'aaa', -1 => 42)) as $subject) {
    foreach (array('', 'a', 'aa', "\0", array('a', 'b'), array('a', 'aa'), array('b', 'a'), array(), array('a', 'a')) as $search) {
        foreach (array('', 'b', 'aa', "\0", array('x', 'y'), array('x'), array(), array('aa', 'b')) as $replace) {
            if (!is_array($search) && is_array($replace)) continue;
            $count = null;
            $result = replace_call($search, $replace, $subject, $count);
            $results[] = array($result, $count, is_array($result) ? array_keys($result) : null);
        }
    }
}
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
