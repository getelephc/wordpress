<?php
function more_trace(mixed $value): mixed {
    if (!is_array($value)) return $value;
    $children = array(); foreach ((array) $value as $item) $children[] = more_trace($item);
    return array(array_keys((array) $value), $children);
}
$sets = array(array(), array(1, null, false, 'x'), array('A' => 1, 'a' => null, 'Z' => array('nested' => 3)), array(7 => 'seven', 'x' => 8, -3 => 'minus'), array('x' => array(3, 2 => array('a' => 1)), 'y' => false));
$results = array();
foreach ($sets as $data) {
    foreach (array(false, true) as $preserve) $results[] = more_reverse($data, $preserve);
    foreach (array(0, 1, 7) as $case) $results[] = more_case($data, $case);
    foreach ($sets as $other) foreach ($sets as $third) {
        $arrays = array($data, $other, $third);
        $results[] = more_replace($arrays); $results[] = more_recursive($arrays);
    }
}
foreach (array(array(), array('a', 'b', 'a'), array(1, '1', true, false, null, 2.2, '02', '-1'), array('A', 'a', "x\0y", 'á')) as $keys)
    foreach (array(null, false, 'value', array('nested' => 1)) as $value) $results[] = more_fill($keys, $value);
$traces = array(); foreach ($results as $result) $traces[] = more_trace($result);
echo json_encode(array($results, $traces), JSON_THROW_ON_ERROR), "\n";
