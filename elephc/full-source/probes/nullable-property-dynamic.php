<?php

// An unused PHP-only dynamic setter triggers a Mixed-to-TaggedScalar EIR lowering error.
define('WP_AOT_NATIVE_RUNTIME', true);
class BoundaryFieldProbe {
    private $tokens;
    function __construct(?int $value=null) {$this->tokens=$value;}
    function value(): ?int {return $this->tokens;}
}
function unused_php_property_setter(mixed $object,string $name,mixed $value): mixed {
    if (defined('WP_AOT_NATIVE_RUNTIME')) throw new LogicException('PHP-only property');
    $object->{$name}=$value;
    return $value;
}
echo json_encode(array((new BoundaryFieldProbe())->value(),(new BoundaryFieldProbe(0))->value(),(new BoundaryFieldProbe(12))->value())), "\n";
