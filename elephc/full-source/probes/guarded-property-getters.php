<?php

class GuardedGetterBase {
    public static int $destroyed = 0;
    public array $trace = array();
    public string $value = 'base' {
        get { $this->trace[] = 'base'; return $this->value; }
    }
    public mixed $failure {
        get { throw new LogicException('getter failure'); }
    }
    public function __destruct() { ++GuardedGetterBase::$destroyed; }
}
class GuardedGetterChild extends GuardedGetterBase {
    public string $value = 'child' {
        get { $this->trace[] = 'child'; return $this->value; }
    }
}
class GuardedGetterMagic {
    public function __get(string $name): mixed { return 'magic:' . $name; }
}
class GuardedGetterNoise {
    public function __call(string $name, array $args): mixed { throw new LogicException('foreign call'); }
}
class GuardedGetterNoise1 extends GuardedGetterNoise {}
class GuardedGetterNoise2 extends GuardedGetterNoise {}
class GuardedGetterNoise3 extends GuardedGetterNoise {}
class GuardedGetterNoise4 extends GuardedGetterNoise {}
class GuardedGetterNoise5 extends GuardedGetterNoise {}
class GuardedGetterNoise6 extends GuardedGetterNoise {}
class GuardedGetterNoise7 extends GuardedGetterNoise {}
class GuardedGetterNoise8 extends GuardedGetterNoise {}

function guarded_getter_literal(mixed $object): mixed { return $object->value; }
function guarded_getter_dynamic(mixed $object, string $name): mixed { return $object->{$name}; }
function guarded_getter_temporary(): mixed { return new GuardedGetterChild(); }

// Keep the native XML dispatch capability present during ordinary boxed reads.
$xml_capability = new SimpleXMLElement('<root/>');
$rows = array();
foreach (array(new GuardedGetterNoise(), new GuardedGetterNoise1(), new GuardedGetterNoise2(),
    new GuardedGetterNoise3(), new GuardedGetterNoise4(), new GuardedGetterNoise5(),
    new GuardedGetterNoise6(), new GuardedGetterNoise7(), new GuardedGetterNoise8()) as $noise) {
    try {
        $noise->__elephc_property_get('value');
        throw new LogicException('missing magic exception');
    } catch (LogicException $noise_error) {
        $rows[] = array('noise', get_class($noise), $noise_error->getMessage());
    }
    unset($noise_error);
}
unset($noise);
foreach (array(new GuardedGetterBase(), new GuardedGetterChild(),
    (object) array('value' => 'record'), new GuardedGetterMagic()) as $object) {
    $rows[] = array(get_class($object), guarded_getter_literal($object),
        guarded_getter_dynamic($object, 'value'),
        $object instanceof GuardedGetterBase ? $object->trace : array());
}
unset($object);
$rows[] = array('temporary', guarded_getter_dynamic(guarded_getter_temporary(), 'value'));
try {
    guarded_getter_dynamic(new GuardedGetterBase(), 'failure');
    throw new LogicException('missing getter exception');
} catch (LogicException $error) {
    $rows[] = array('exception', $error->getMessage());
}
unset($error);
unset($xml_capability);
$rows[] = array('destroyed', GuardedGetterBase::$destroyed);
echo json_encode($rows), "\n";
