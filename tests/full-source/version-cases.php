<?php
$versions = array('', '.', '0', '1', '1.0', '1.0.0', '1.0-dev', '1.0alpha1', '1.0a', '1.0beta', '1.0b2', '1.0RC1', '1.0rc2', '1.0pl1', '1.0p2', '1.0foo', '7.4', '8.1.6', '8.4.0', '8.0.0-elephc-sqlite-fixture', '1.0+build', '1.0_foo', '-1', '1..0', '01.0', '1foo2', '1-#N#', 'dev', 'alpha', 'RC', 'patch', '#N#', '.1', '...', '+');
$operators = array(null, '<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne');
$out = array();
foreach ($versions as $first) foreach ($versions as $second) foreach ($operators as $operator) {
    $out[] = version_compare($first, $second, $operator);
}
echo json_encode($out), "\n";
