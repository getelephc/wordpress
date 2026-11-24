<?php

// Backtraces require generated argument snapshots on every source frame.
$ignored_trace = debug_backtrace();
unset($ignored_trace);

interface GeneratedCollectorCache {
    public function __construct(string $location, string $name, $type);
    public function inspect(string $label, float $number);
}

class GeneratedCollectorCacheValue implements GeneratedCollectorCache {
    public array $constructor_args = array();
    public function __construct(mixed $location, mixed $name, mixed $type) {
        $this->constructor_args = func_get_args();
    }
    public function inspect(mixed $label, mixed $number): mixed {
        return array($label, $number, func_num_args(), func_get_args());
    }
}

function generated_collector_read(GeneratedCollectorCache $cache, string $label, float $number): mixed {
    return $cache->inspect($label, $number);
}

function generated_collector_extra(GeneratedCollectorCache $cache): mixed {
    return $cache->inspect('extra', 2.5, 'tail', array('nested' => 7));
}

$cache = new GeneratedCollectorCacheValue('memory', 'città', 'spc', 'ctor-tail');
$results = array($cache->constructor_args, generated_collector_read($cache, 'plain', 1.5),
    generated_collector_extra($cache));
$read = $cache->inspect(...);
$results[] = $read('captured', 3.5, null, 9);
unset($read, $cache);
$cache = new GeneratedCollectorCacheValue(null, 7, array('type' => 'spi'));
$results[] = $cache->constructor_args;
unset($cache);
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
unset($results);
