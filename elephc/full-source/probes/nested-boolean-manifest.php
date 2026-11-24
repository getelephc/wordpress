<?php
$cases = array(
    array('{}', array(false, false, false, false)),
    array('{"nullable":null}', array(true, false, false, false)),
);
$rows = array();
foreach ($cases as $case) {
    $copy = array($case[1][0], $case[1][1], $case[1][2], $case[1][3]);
    $rows[] = array($case[1], $copy);
}
echo json_encode($rows), "\n";
