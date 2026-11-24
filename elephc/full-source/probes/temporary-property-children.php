<?php
class ChildProbeDeclared { public mixed $items = array(); }
#[AllowDynamicProperties]
class ChildProbeMagic {
    public int $calls = 0;
    public function __get(string $name): mixed { $this->calls++; return array('getter' => true); }
}
#[AllowDynamicProperties]
class ChildProbeBag0 {}
#[AllowDynamicProperties]
class ChildProbeBag1 {}
#[AllowDynamicProperties]
class ChildProbeBag2 {}
#[AllowDynamicProperties]
class ChildProbeBag3 {}
#[AllowDynamicProperties]
class ChildProbeBag4 {}
#[AllowDynamicProperties]
class ChildProbeBag5 {}
#[AllowDynamicProperties]
class ChildProbeBag6 {}
#[AllowDynamicProperties]
class ChildProbeBag7 {}
#[AllowDynamicProperties]
class ChildProbeBag8 {}
#[AllowDynamicProperties]
class ChildProbeBag9 {}
#[AllowDynamicProperties]
class ChildProbeBag10 {}
#[AllowDynamicProperties]
class ChildProbeBag11 {}
#[AllowDynamicProperties]
class ChildProbeBag12 {}
#[AllowDynamicProperties]
class ChildProbeBag13 {}
#[AllowDynamicProperties]
class ChildProbeBag14 {}
#[AllowDynamicProperties]
class ChildProbeBag15 {}
#[AllowDynamicProperties]
class ChildProbeBag16 {}
#[AllowDynamicProperties]
class ChildProbeBag17 {}
#[AllowDynamicProperties]
class ChildProbeBag18 {}
#[AllowDynamicProperties]
class ChildProbeBag19 {}
#[AllowDynamicProperties]
class ChildProbeBag20 {}
#[AllowDynamicProperties]
class ChildProbeBag21 {}
#[AllowDynamicProperties]
class ChildProbeBag22 {}
#[AllowDynamicProperties]
class ChildProbeBag23 {}
#[AllowDynamicProperties]
class ChildProbePadded { public int $padding = 41; }
class ChildProbePaddedBase { private string $padding = 'base'; }
#[AllowDynamicProperties]
class ChildProbePaddedChild extends ChildProbePaddedBase { public int $number = 42; public bool $flag = true; }
#[AllowDynamicProperties]
class ChildProbeDebug { public int $padding = 43; public function __debugInfo(): array { return array('visible' => true); } }
function child_probe_keep(mixed $object): mixed { return $object; }
function child_probe_seed(mixed $object, array $items): void { $object->items = $items; }
function child_probe_mutate(mixed $object): void {
    child_probe_keep($object)->items['first'] = 'one';
    child_probe_keep($object)->items[] = 'tail';
}
function child_probe_pressure(mixed $object): void {
    child_probe_keep($object)->items['first'] = 'one';
    child_probe_keep($object)->items['second'] = 'two';
    child_probe_keep($object)->items['third'] = 'three';
    child_probe_keep($object)->items['fourth'] = 'four';
    child_probe_keep($object)->items['fifth'] = 'five';
}
$capability = new SimpleXMLElement('<root/>');
unset($capability);
$objects = array(new ChildProbeBag0(), new ChildProbeBag1(), new ChildProbeBag2(), new ChildProbeBag3(), new ChildProbeBag4(), new ChildProbeBag5(), new ChildProbeBag6(), new ChildProbeBag7(), new ChildProbeBag8(), new ChildProbeBag9(), new ChildProbeBag10(), new ChildProbeBag11(), new ChildProbeBag12(), new ChildProbeBag13(), new ChildProbeBag14(), new ChildProbeBag15(), new ChildProbeBag16(), new ChildProbeBag17(), new ChildProbeBag18(), new ChildProbeBag19(), new ChildProbeBag20(), new ChildProbeBag21(), new ChildProbeBag22(), new ChildProbeBag23(), new ChildProbeDeclared(), new ChildProbeMagic(), new stdClass(), new ChildProbePadded(), new ChildProbePaddedChild(), new ChildProbeDebug());
$rows = array();
foreach ($objects as $object) {
    $before = array('seed' => array('value' => 'before'));
    child_probe_seed($object, $before);
    child_probe_mutate($object);
    $rows[] = array(get_class($object), $before, $object->items);
}
unset($object);
$pressure = new ChildProbeBag0();
child_probe_seed($pressure, array());
child_probe_pressure($pressure);
$rows[] = array('pressure', $pressure->items);
$magic = new ChildProbeMagic();
child_probe_seed($magic, array());
child_probe_mutate($magic);
$rows[] = array('cached-magic', $magic->calls, $magic->items);
echo json_encode($rows), "\n";
