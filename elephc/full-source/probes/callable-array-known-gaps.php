<?php

$array_join_trace = array();

class ArrayJoinOwner {
    public string $name;
    public function __construct(string $name) {$this->name = $name;}
    public function join(mixed $left, mixed $right = 'default', mixed ...$tail): mixed {
        return array($this->name, $left, $right, $tail);
    }
    public function identity(mixed $value): mixed {return $value;}
    public function fail(mixed $value): never {throw new RuntimeException((string) $value);}
    public function integer(int $value): mixed {return $value;}
    public function nested(mixed $other): mixed {
        return array_join_instance($other, 'join', array('left' => $this->name));
    }
    public static function combine(mixed $left, mixed $right = 'static'): mixed {
        return array($left, $right);
    }
    public function __destruct() {
        global $array_join_trace;
        $array_join_trace[] = $this->name;
    }
}

class ArrayJoinChild extends ArrayJoinOwner {}

function array_join_instance(mixed $receiver, string $method, array $arguments): mixed {
    return call_user_func_array(array($receiver, $method), $arguments);
}

function array_join_static(string $class, string $method, array $arguments): mixed {
    return call_user_func_array(array($class, $method), $arguments);
}

function array_join_cycle(int $index): array {
    $first = new ArrayJoinOwner('owner-' . $index);
    $second = new ArrayJoinChild('child-' . $index);
    $marker = (object) array('index' => $index);
    $values = array();
    $values[] = array_join_instance($first, 'join', array('left' => $index));
    $values[] = array_join_instance($second, 'JOIN', array('right' => null, 'left' => false, 'extra' => 'tail'));
    $values[] = array_join_instance($first, 'identity', array('value' => $marker)) === $marker;
    $values[] = array_join_instance($first, 'nested', array('other' => $second));
    try {
        array_join_instance($second, 'fail', array('value' => 'expected-' . $index));
    } catch (RuntimeException $exception) {
        $values[] = array(get_class($exception), $exception->getMessage());
    }
    try {
        array_join_instance($first, 'integer', array('value' => array()));
    } catch (TypeError $type_error) {
        $values[] = get_class($type_error);
    }
    $values[] = array_join_static('ArrayJoinOwner', 'combine', array('left' => $index));
    $values[] = array_join_static('ArrayJoinChild', 'COMBINE', array('right' => false, 'left' => null));
    return $values;
}

$array_join_results = array();
for ($array_join_index = 0; $array_join_index < 12; ++$array_join_index) {
    $array_join_results[] = array_join_cycle($array_join_index);
}
$array_join_results[] = $array_join_trace;
echo json_encode($array_join_results), "\n";
