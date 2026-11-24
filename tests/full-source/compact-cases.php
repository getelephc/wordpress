<?php

$compact_names = array(
    array(array()), array('first'), array('nothing'), array('zero', 'flag', 'empty'),
    array('second', 'first', 'second'), array(array('list', array('nothing', 'record'))),
    array('alias', array('first', array('alias'))), array('names'),
);
foreach (array('first', 'second', 'nothing', 'zero', 'flag', 'empty', 'list', 'record', 'alias') as $first_name) {
    foreach (array('first', 'second', 'nothing', 'zero', 'flag', 'empty', 'list', 'record', 'alias') as $second_name) {
        $compact_names[] = array($first_name, $second_name);
    }
}
$rows = array();
foreach ($compact_names as $names) $rows[] = aot_compact_scope((array) $names);
echo json_encode($rows, JSON_THROW_ON_ERROR) . "\n";
