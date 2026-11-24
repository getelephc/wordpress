<?php
$results = array();
foreach (array(true, false, 0, 1, '', 'value', array(), array(0), null, new stdClass()) as $condition) {
    try { $results[] = array('returned', assert($condition, 'WordPress invariant')); }
    catch (Throwable $error) { $results[] = array('error', get_class($error), $error->getMessage()); }
}
$description = new RuntimeException('original exception');
try { assert(false, $description); }
catch (Throwable $error) { $results[] = array(get_class($error), $error->getMessage(), $error === $description); }
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
