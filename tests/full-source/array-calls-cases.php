<?php
function aot_map_one(mixed $value): array { return array(gettype($value), $value); }
function aot_map_many(mixed $a, mixed $b, mixed $c = 'default'): array { return array($a, $b, $c); }
function aot_filter_value(mixed $value): bool { return $value !== null && $value !== false; }
function aot_filter_key(mixed $key): bool { return $key === 'text' || $key === 4; }
function aot_filter_both(mixed $value, mixed $key): bool { return $key === 'text' || $value === 42; }
function aot_merge_variadic_probe(mixed ...$values): array { return array(count($values), $values); }
function aot_vararg_walk(mixed ...$values): array {
    $out = array();
    foreach ($values as $value) $out[] = $value;
    return $out;
}
$rows = array();
function aot_merge_value_arguments(mixed $groups): array {
    return aot_test_merge(array_values($groups));
}
foreach (array(
    array(),
    array('active' => array('text-2'), 'inactive' => array('rss-3')),
    array(9 => array('keep' => 1, 8 => 'first'), 3 => array('keep' => 2, 20 => 'second')),
    array('nested' => array(array('value' => array(null, false))), -5 => array(true, 2)),
) as $groups) {
    $rows[] = aot_merge_value_arguments($groups);
}
$rows[] = aot_vararg_walk(...array(array('a' => 1), array(3, 4)));

$rows[] = aot_merge_variadic_probe(1, 'two');
$rows[] = aot_merge_variadic_probe(...array(1, 'two'));
$rows[] = aot_merge_variadic_probe(...array(array('a' => 1), array(3, 4)));

$arrays = array(array(), array(null, false, 0, '0', '', 42, 'text'), array('text' => 'same', 4 => null, -2 => false, 'again' => 42), array(4 => 'sparse', 1 => 'last', 'text' => array('nested' => true)));
$rows[] = aot_test_merge(array());
foreach ($arrays as $array) {
    $rows[] = aot_test_map(null, (array) $array);
    $rows[] = aot_test_map(aot_map_one(...), (array) $array);
    $rows[] = aot_test_filter((array) $array);
    $rows[] = aot_test_filter((array) $array, aot_filter_value(...));
    $rows[] = aot_test_filter((array) $array, aot_filter_key(...), 2);
    $rows[] = aot_test_filter((array) $array, aot_filter_both(...), 1);
    foreach ($arrays as $other) {
        $rows[] = aot_test_merge(array($array, $other, array('text' => 'replace', 9 => true)));
        $rows[] = aot_test_map(null, (array) $array, (array) $other);
        $rows[] = aot_test_map(aot_map_many(...), (array) $array, (array) $other, array('third'));
    }
}
function aot_array_key_trace(mixed $value): mixed {
    if (!is_array($value)) return null;
    $keys = array_keys((array) $value);
    $children = array();
    foreach ((array) $value as $item) $children[] = aot_array_key_trace($item);
    return array($keys, $children);
}
$captured = array();
foreach ($rows as $row) $captured[] = array($row, aot_array_key_trace($row));
echo json_encode($captured, JSON_THROW_ON_ERROR) . "\n";
