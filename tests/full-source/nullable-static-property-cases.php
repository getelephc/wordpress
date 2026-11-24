<?php

class WordPressNullableStaticPropertyControl {
    private static mixed $value = null;
    public int $visits = 0;

    public static function read(): mixed { return self::$value; }
    public static function write(mixed $value): void { self::$value = $value; }
    public static function instance(): mixed {
        if (self::$value === null) self::$value = new self();
        return self::$value;
    }
    public static function concrete_instance(): WordPressNullableStaticPropertyControl {
        return nullable_static_property_view(self::instance());
    }
    public static function nullable_instance(bool $ready): ?WordPressNullableStaticPropertyControl {
        if (!$ready) return null;
        return nullable_static_property_view(self::instance());
    }
}

function nullable_static_property_view(mixed $value): WordPressNullableStaticPropertyControl {
    if ($value instanceof WordPressNullableStaticPropertyControl) return $value;
    throw new TypeError('The value does not satisfy the original declared contract.');
}

function nullable_static_property_cases(): array {
    $rows = array();
    $record = new WordPressNullableStaticPropertyControl();
    foreach (array(null, false, 0, 7, 1.5, '', 'WordPress', array(), array('nested' => array('x' => 7)), $record) as $value) {
        WordPressNullableStaticPropertyControl::write($value);
        $read = WordPressNullableStaticPropertyControl::read();
        $rows[] = array($read === $value, is_null($read), is_bool($read), is_int($read),
            is_float($read), is_string($read), is_array($read), is_object($read), json_encode($read));
        if (is_array($read)) {
            $snapshot = $read;
            $read['copy-only'] = true;
            $rows[] = array($snapshot, WordPressNullableStaticPropertyControl::read(), $read);
        }
    }
    WordPressNullableStaticPropertyControl::write(null);
    $first = WordPressNullableStaticPropertyControl::instance();
    $first->visits = 7;
    $second = WordPressNullableStaticPropertyControl::instance();
    $rows[] = array($first === $second, $second->visits);
    WordPressNullableStaticPropertyControl::write(null);
    $third = WordPressNullableStaticPropertyControl::instance();
    $rows[] = array($third === $first, $third->visits, $first->visits);
    $rows[] = array(WordPressNullableStaticPropertyControl::nullable_instance(false) === null,
        WordPressNullableStaticPropertyControl::concrete_instance() === $third,
        WordPressNullableStaticPropertyControl::nullable_instance(true) === $third);
    WordPressNullableStaticPropertyControl::write(null);
    $rows[] = array(WordPressNullableStaticPropertyControl::read());
    return $rows;
}
