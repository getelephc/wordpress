<?php

function null_default_box(mixed $value): mixed {return $value;}

class NullAnnotationDefaults {
    protected static $annotations = array('readonly' => null, 'destructive' => null, 'idempotent' => null);

    public static function read(): mixed {return null_default_box(static::$annotations);}
    public static function nested(): mixed {return array('annotations' => null_default_box(static::$annotations));}
}

echo json_encode(array(NullAnnotationDefaults::read(), NullAnnotationDefaults::nested())), "\n";
