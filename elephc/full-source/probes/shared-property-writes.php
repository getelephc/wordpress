<?php

class SharedWriteRecord { public mixed $value = null; public int $number = 0; }
class SharedWriteChild extends SharedWriteRecord {}
#[AllowDynamicProperties]
class SharedWriteBag {}
class SharedWriteMagic {
    public mixed $saved = null;
    public mixed $trace = array();
    public function __set(string $name, mixed $value): void {
        $this->trace[] = array($name, $value);
        if ($value === 'throw') { throw new RuntimeException('setter refused'); }
        $this->saved = $value;
    }
    public function __get(string $name): mixed { return $this->saved; }
}
class SharedWriteVault {
    private mixed $value = null;
    public function inside(mixed $other, mixed $first, mixed $second): void {
        $other->value = $first;
        $other->value = $second;
    }
    public function snapshot(): mixed { return $this->value; }
}
function shared_write_named(mixed $object, mixed $first, mixed $second): void {
    $object->value = $first;
    $object->value = $second;
}
function shared_write_dynamic(mixed $object, mixed $first, mixed $second): void {
    $object->{'value'} = $first;
    $object->{'value'} = $second;
}
function shared_write_string(mixed $object, string $value): void {
    $object->value = $value;
    $object->value = $value;
}
function shared_write_integer(mixed $object, int $value): void {
    $object->number = $value;
    $object->number = $value;
}
function shared_write_array(mixed $object, array $value): void {
    $object->value = $value;
    $object->value = $value;
}
function shared_write_object(mixed $object, SharedWriteRecord $value): void {
    $object->value = $value;
    $object->value = $value;
}
function shared_write_pressure(mixed $object, mixed $value): void {
    $object->value = $value; $object->value = $value;
    $object->value = $value; $object->value = $value;
    $object->value = $value; $object->value = $value;
    $object->value = $value; $object->value = $value;
}
class SharedWriteNoise0 extends SharedWriteRecord {}
class SharedWriteNoise1 extends SharedWriteRecord {}
class SharedWriteNoise2 extends SharedWriteRecord {}
class SharedWriteNoise3 extends SharedWriteRecord {}
class SharedWriteNoise4 extends SharedWriteRecord {}
class SharedWriteNoise5 extends SharedWriteRecord {}
class SharedWriteNoise6 extends SharedWriteRecord {}
class SharedWriteNoise7 extends SharedWriteRecord {}
class SharedWriteNoise8 extends SharedWriteRecord {}
class SharedWriteNoise9 extends SharedWriteRecord {}
class SharedWriteNoise10 extends SharedWriteRecord {}
class SharedWriteNoise11 extends SharedWriteRecord {}
class SharedWriteNoise12 extends SharedWriteRecord {}
class SharedWriteNoise13 extends SharedWriteRecord {}
class SharedWriteNoise14 extends SharedWriteRecord {}
class SharedWriteNoise15 extends SharedWriteRecord {}
$noises = array(new SharedWriteNoise0(), new SharedWriteNoise1(), new SharedWriteNoise2(), new SharedWriteNoise3(), new SharedWriteNoise4(), new SharedWriteNoise5(), new SharedWriteNoise6(), new SharedWriteNoise7(), new SharedWriteNoise8(), new SharedWriteNoise9(), new SharedWriteNoise10(), new SharedWriteNoise11(), new SharedWriteNoise12(), new SharedWriteNoise13(), new SharedWriteNoise14(), new SharedWriteNoise15());
$rows = array();
$first = array('nested' => array('name' => 'before'));
$second = array('nested' => array('name' => 'after'));
foreach (array(new SharedWriteRecord(), new SharedWriteChild(), new SharedWriteBag(), new stdClass(), new SharedWriteMagic()) as $object) {
    shared_write_named($object, $first, $second);
    $snapshot = $object->value;
    shared_write_dynamic($object, null, false);
    $rows[] = array(get_class($object), $snapshot, $object->value, $first, $second);
    shared_write_string($object, str_repeat('native', 32));
    $rows[] = array('string', get_class($object), strlen($object->value));
}
unset($object);
$record = new SharedWriteRecord();
shared_write_integer($record, 27);
shared_write_pressure($record, $second);
$rows[] = array('typed', $record->number, $record->value);
shared_write_array($record, $first);
$array_snapshot = $record->value;
shared_write_array($record, $second);
$rows[] = array('typed-array', $array_snapshot, $record->value);
$payload = new SharedWriteRecord();
$payload->number = 36;
shared_write_object($record, $payload);
$rows[] = array('typed-object', $record->value === $payload, $record->value->number);

$vault = new SharedWriteVault();
$vault->inside($vault, $first, $second);
$rows[] = array('private-inside', $vault->snapshot());
try { shared_write_named($vault, $first, $second); }
catch (Throwable $error) { $rows[] = array('private-outside', get_class($error), $error->getMessage()); }
unset($error);
$magic = new SharedWriteMagic();
try { shared_write_named($magic, 'accepted', 'throw'); }
catch (Throwable $error) { $rows[] = array('throwing-magic', $magic->saved, $magic->trace, $error->getMessage()); }
unset($error);
$rows[] = array('retained-classes', count($noises));
echo json_encode($rows), "\n";
