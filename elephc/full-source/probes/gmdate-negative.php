<?php

date_default_timezone_set('UTC');
$rows = array();
foreach (array(-2, -1, 0) as $timestamp) {
    foreach (array('U', 'Y-m-d H:i:s', '\\', 's\\') as $pattern) {
        $rows[] = array($timestamp, $pattern, gmdate($pattern, $timestamp));
    }
}
echo json_encode($rows), "\n";
