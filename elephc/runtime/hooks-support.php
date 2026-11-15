<?php

// Give the native object-id builtin an explicit object contract after validation.
function _elephc_hook_object_id(object $value): int {
    return spl_object_id($value);
}

final class WordPressHookCallbacks {
    public static array $functions = [];

    public static function register(string $name, callable $callback): void {
        self::$functions[$name] = $callback;
    }

    public static function resolve(mixed $callback): mixed {
        if (is_string($callback) && isset(self::$functions[$callback])) {
            return self::$functions[$callback];
        }
        return $callback;
    }
}
