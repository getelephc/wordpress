<?php

class WordPressNullableSingletonProbe {
    private static $instance = null;
    public static function get_instance(): mixed {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
}

$first = WordPressNullableSingletonProbe::get_instance();
$second = WordPressNullableSingletonProbe::get_instance();
echo json_encode(array(is_object($first), $first === $second)), "\n";
