<?php

// Explicit nullable property storage preserves [null,0,12] in PHP and native execution.
define('WP_AOT_NATIVE_RUNTIME', true);
class BoundaryFieldProbe {
    private ?int $tokens = null;
    function __construct(?int $value=null) {$this->tokens=$value;}
    function value(): ?int {return $this->tokens;}
}
echo json_encode(array((new BoundaryFieldProbe())->value(),(new BoundaryFieldProbe(0))->value(),(new BoundaryFieldProbe(12))->value())), "\n";
