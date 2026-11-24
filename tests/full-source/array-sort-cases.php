<?php
class WordPressSortBox { public mixed $values; public function __construct(mixed $values) { $this->values = $values; } }
$rows = array();
$sets = array(array(), array('b', 'A', 'a10', 'a2', 'a01', 'a1'), array(3, 0, -2, 3, 1), array(3, '3', 3.0, 0, '0'), array('x' => 42, 4 => 'name', 'y' => 42, -2 => 'last'));
foreach ($sets as $data) {
    foreach (array(0, 1, 2, 6, 10, 14) as $flags) {
        foreach (array('sort', 'rsort', 'asort', 'arsort', 'ksort', 'krsort') as $operation) {
            $array = (array) $data; $alias =& $array;
            $done = sort_call((string) $operation, $array, (int) $flags);
            $rows[] = array($done, $array, array_keys($array), $alias);
            $box = new WordPressSortBox($data);
            $done = sort_call((string) $operation, $box->values, (int) $flags);
            $rows[] = array($done, $box->values, array_keys((array) $box->values));
        }
    }
}
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
