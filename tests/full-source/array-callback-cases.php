<?php
class WordPressCallbackSortBox { public mixed $values; public function __construct(mixed $values) { $this->values = $values; } }
function aot_rank_compare(mixed $a, mixed $b): int { return (int) $a['rank'] <=> (int) $b['rank']; }
class WordPressCallbackComparator {
    protected static function private_static_compare(mixed $a, mixed $b): int { return aot_rank_compare($a, $b); }
    protected function private_instance_compare(mixed $a, mixed $b): int { return aot_rank_compare($a, $b); }
    public static function static_descriptor(): mixed { return self::private_static_compare(...); }
    public function instance_descriptor(): mixed { return $this->private_instance_compare(...); }
    public static function compare(mixed $a, mixed $b): int { return aot_rank_compare($a, $b); }
    public function by_rank(mixed $a, mixed $b): int { return aot_rank_compare($a, $b); }
}
$object = new WordPressCallbackComparator();
$sets = array(array(), array(array('rank' => 2, 'label' => 'first'), array('rank' => 1), array('rank' => 2, 'label' => 'second')), array('z' => array('rank' => 0), 5 => array('rank' => -1), -2 => array('rank' => 0)));
$callbacks = array(WordPressCallbackComparator::static_descriptor(), $object->instance_descriptor(), 'aot_rank_compare', aot_rank_compare(...), WordPressCallbackComparator::compare(...), array($object, 'by_rank'), static function(mixed $a, mixed $b): int { return aot_rank_compare($a, $b); });
$results = array();
foreach ($sets as $data) foreach (array('usort', 'uasort') as $operation) foreach ($callbacks as $callback) {
    $array = (array) $data; $alias =& $array;
    $done = callback_sort_call((string) $operation, $array, $callback);
    $results[] = array($done, $array, array_keys($array), $alias);
    $box = new WordPressCallbackSortBox($data);
    $done = callback_sort_call((string) $operation, $box->values, $callback);
    $results[] = array($done, $box->values, array_keys((array) $box->values));
}
foreach ($sets as $data) {
    $array = (array) $data;
    $done = callback_sort_call('uksort', $array, static function(mixed $a, mixed $b): int { return strcmp((string) $a, (string) $b); });
    $results[] = array($done, $array, array_keys($array));
}
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
