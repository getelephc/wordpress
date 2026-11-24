<?php
require_once __DIR__ . '/aot-binary.php';
function wp_aot_object_id(object $value): int {
    return spl_object_id($value);
}

function wp_aot_pclzip_block_size_defined(): bool {return defined('PCLZIP_READ_BLOCK_SIZE');}
function wp_aot_pclzip_block_size_define(mixed $value): bool {return define('PCLZIP_READ_BLOCK_SIZE', $value);}
function wp_aot_pclzip_block_size(): mixed {return constant('PCLZIP_READ_BLOCK_SIZE');}

// PHP-only engine operations, mirrored by explicit native rejection in the LFC fixture.
function wp_aot_subdomain_defined(string $name): bool {
    if ($name !== 'VHOST' && $name !== 'SUBDOMAIN_INSTALL') throw new LogicException('Unknown Multisite alias.');
    return defined($name);
}
function wp_aot_subdomain_constant(string $name): mixed {
    if ($name !== 'VHOST' && $name !== 'SUBDOMAIN_INSTALL') throw new LogicException('Unknown Multisite alias.');
    return constant($name);
}
function wp_aot_subdomain_define(string $name, mixed $value): bool {
    if (wp_aot_subdomain_defined($name)) throw new LogicException('Duplicate Multisite alias definitions are outside this fixture.');
    return define($name, $value);
}

function wp_aot_static_get(string $class, string $property): mixed { return $class::${$property}; }
function wp_aot_static_set(string $class, string $property, mixed $value): mixed {
    $class::${$property} = $value;
    return $value;
}

function wp_aot_native_get_property(mixed $object, string $name): mixed {
    return $object->{$name};
}

function wp_aot_native_set_property(mixed $object, string $name, mixed $value): mixed {
    $object->{$name} = $value;
    return $value;
}

function wp_aot_native_api(string $name, array $arguments): mixed {
    return call_user_func_array($name, $arguments);
}

function wp_aot_native_new(string $name, array $arguments): mixed {
    return new $name(...$arguments);
}

function wp_aot_native_method(mixed $object, string $method, array $arguments): mixed {
    return call_user_func_array(array($object, $method), $arguments);
}

function wp_aot_property_exists(mixed $object,mixed $name): bool {
    return property_exists($object,$name);
}

function wp_aot_bootstrap_defined(string $name): bool {
    if (!wp_aot_bootstrap_known_constant($name)) throw new LogicException('Unknown bootstrap constant.');
    return defined($name);
}
function wp_aot_bootstrap_mark(string $name, bool $success): bool {
    if (!wp_aot_bootstrap_known_constant($name)) throw new LogicException('Unknown bootstrap constant.');
    return $success;
}

function wp_aot_bootstrap_capture_string(string $name, string $value): string {
    if (!wp_aot_bootstrap_string_known($name)) throw new LogicException('Unknown bootstrap string constant.');
    return $value;
}
function wp_aot_bootstrap_string(string $name): string {
    if (!wp_aot_bootstrap_string_known($name)) throw new LogicException('Unknown bootstrap string constant.');
    return (string) constant($name);
}

