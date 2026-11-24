<?php

// Original PHP returns [null,0,12]. The pinned native SDK returns [0,0,12].
define('WP_AOT_NATIVE_RUNTIME', true);
class BoundaryFieldProbe {
    private $tokens;
    function __construct(?int $value=null) {$this->tokens=$value;}
    function value(): ?int {return $this->tokens;}
}
echo json_encode(array((new BoundaryFieldProbe())->value(),(new BoundaryFieldProbe(0))->value(),(new BoundaryFieldProbe(12))->value())), "\n";
