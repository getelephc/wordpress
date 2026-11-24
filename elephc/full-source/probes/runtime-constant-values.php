<?php
function value(mixed $v): mixed { return $v; }
function mark(string $name, bool $success): bool { return $success; }
function setup(): mixed {
    define('DIRECT_STRING', value('/wp-content'));
    mark('NESTED_STRING', define('NESTED_STRING', value('/wp-content')));
    define('TYPED_STRING', (string) value('/wp-content'));
    mark('NESTED_TYPED_STRING', define('NESTED_TYPED_STRING', (string) value('/wp-content')));
    define('DIRECT_INTEGER', value(86400));
    mark('NESTED_INTEGER', define('NESTED_INTEGER', value(86400)));
    define('TYPED_INTEGER', (int) value(86400));
    mark('NESTED_TYPED_INTEGER', define('NESTED_TYPED_INTEGER', (int) value(86400)));
    return null;
}
setup();
$rows = array(
    DIRECT_STRING, NESTED_STRING, TYPED_STRING, NESTED_TYPED_STRING,
    DIRECT_INTEGER, NESTED_INTEGER, TYPED_INTEGER, NESTED_TYPED_INTEGER,
    (string) NESTED_STRING, (string) NESTED_TYPED_STRING,
    (int) NESTED_INTEGER, (int) NESTED_TYPED_INTEGER
);
$rows[] = array(constant('DIRECT_STRING'),constant('NESTED_STRING'),constant('DIRECT_INTEGER'),constant('NESTED_INTEGER'));
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
