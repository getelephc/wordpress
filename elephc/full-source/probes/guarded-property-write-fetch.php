<?php

class GuardedWriteCell { public mixed $items = array(); }
class GuardedWriteChild extends GuardedWriteCell {}
class GuardedWriteNoise {
    public function __call(string $name, array $arguments): mixed { return 'magic:' . $name; }
}

function guarded_property_write(mixed $object): void {
    $object->items['name'] = 'first';
    $object->items[] = 'tail';
    $object->items['nested'] = array('old' => 0);
    $object->items['nested']['new'] = 'next';
}
function guarded_xml_write(mixed $object): void {
    $object->items[] = 'second';
    $object->items[0] = 'changed';
}
function ordinary_write_fetch_name(mixed $object): mixed {
    return $object->__elephc_property_write_fetch('items');
}

$rows = array();
$capability = new SimpleXMLElement('<root><items>seed</items></root>');
foreach (array(new GuardedWriteCell(), new GuardedWriteChild(), (object) array('items' => array())) as $object) {
    $before = $object->items;
    guarded_property_write($object);
    $snapshot = $object->items;
    guarded_xml_write($object);
    $rows[] = array(get_class($object), $before, $snapshot, $object->items);
}
unset($object);
guarded_xml_write($capability);
$rows[] = array('xml', count($capability->items), (string) $capability->items[0], (string) $capability->items[1]);
unset($capability);
$missing = new SimpleXMLElement('<root/>');
guarded_xml_write($missing);
$rows[] = array('missing-xml', count($missing->items), (string) $missing->items[0], (string) $missing->items[1]);
unset($missing);
$rows[] = array('ordinary-magic', ordinary_write_fetch_name(new GuardedWriteNoise()));
echo json_encode($rows), "\n";
