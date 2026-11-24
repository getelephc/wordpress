<?php
function av_trace(mixed $value): mixed {
    if (!is_array($value)) return $value;
    $values = array();
    foreach ((array) $value as $item) $values[] = av_trace($item);
    return array(array_keys((array) $value), $values);
}
$rows = array();
$sets = array(array(), array('a', 'b', 'a', '', '0'), array(3, 0, false, null, '3', 3.0), array('x' => 42, 4 => 'name', 'y' => 42, -2 => false), array('', false, null, 0, '0', '01', 1, 1.0, '1', 2.5, '2.5', '42px', '1e3'));
foreach ($sets as $array) {
    foreach (array(1, 2) as $flag) $rows[] = av_unique((array) $array, (int) $flag);
    foreach (array(null, false, 0, '0', 3, '3', 42, 'absent') as $needle) {
        $rows[] = array(av_search($needle, (array) $array), av_search($needle, (array) $array, true));
    }
    foreach (array(-99, -4, -1, 0, 1, 3, 99) as $offset) {
        foreach (array(null, -99, -2, 0, 2, 99) as $length) {
            foreach (array(false, true) as $preserve) $rows[] = av_slice((array) $array, (int) $offset, $length === null ? null : (int) $length, (bool) $preserve);
        }
    }
    foreach ($sets as $second) {
        foreach ($sets as $third) {
            $inputs = array($array, $second, $third);
            $rows[] = array(av_diff($inputs), av_intersect($inputs), av_diff_key($inputs), av_intersect_key($inputs));
        }
    }
}
foreach (array(array('x' => 1), array('x' => '1'), array()) as $needle) {
    $rows[] = array(av_search($needle, array(array('x' => 1), array('x' => '1'))), av_search($needle, array(array('x' => 1), array('x' => '1')), true));
}
foreach (array(array('a', 'b', 'a', '0', '00', '0.0'), array(1, 1.0, 2, 2.0, 0), array(false, true, false), array(null, null), array()) as $homogeneous) $rows[] = av_unique((array) $homogeneous, 0);
echo json_encode(av_trace($rows), JSON_THROW_ON_ERROR), "\n";
