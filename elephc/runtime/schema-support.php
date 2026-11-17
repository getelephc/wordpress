<?php

function wordpress_schema_guard(mixed $input, int $depth = 0): int {
    if ($depth > 8) { throw new InvalidArgumentException('Schema nesting exceeds eight levels below root.'); }
    if (!is_array($input)) { throw new InvalidArgumentException('Schemas must be objects.'); }
    $schema = (array) $input;
    $type = $schema['type'] ?? null;
    if (!is_string($type) || !in_array($type, ['null', 'boolean', 'integer', 'number', 'string', 'array', 'object'], true)) { throw new InvalidArgumentException('Schemas require one supported type.'); }
    $nodes = 1;
    foreach ($schema as $key => $value) {
        if ($key === 'type') { continue; }
        if (($type === 'number' || $type === 'integer') && in_array($key, ['minimum', 'maximum', 'multipleOf'], true)) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || (float) $value < -1000000000000.0 || (float) $value > 1000000000000.0) { throw new InvalidArgumentException('Numeric schema bounds must be finite numbers within +/- 10^12.'); }
            if ($key === 'multipleOf' && (float) $value <= 0.0) { throw new InvalidArgumentException('multipleOf must be positive.'); }
        } elseif (($type === 'number' || $type === 'integer') && in_array($key, ['exclusiveMinimum', 'exclusiveMaximum'], true)) {
            if (!is_bool($value)) { throw new InvalidArgumentException('Exclusive schema flags must be boolean.'); }
        } elseif ($type === 'string' && in_array($key, ['minLength', 'maxLength'], true)) {
            if (!is_int($value) || (int) $value < 0 || (int) $value > 1000000) { throw new InvalidArgumentException('String schema lengths must be integers from 0 to 1000000.'); }
        } elseif (($type === 'array' && in_array($key, ['minItems', 'maxItems'], true)) || ($type === 'object' && in_array($key, ['minProperties', 'maxProperties'], true))) {
            if (!is_int($value) || (int) $value < 0 || (int) $value > 256) { throw new InvalidArgumentException('Collection schema counts must be integers from 0 to 256.'); }
        } elseif ($type === 'array' && $key === 'items') {
            $nodes += wordpress_schema_guard($value, $depth + 1);
        } elseif ($type === 'object' && $key === 'properties') {
            if (!is_array($value) || count((array) $value) > 64) { throw new InvalidArgumentException('Schema properties allow at most 64 names.'); }
            foreach ((array) $value as $name => $child) {
                wordpress_error_code($name);
                $nodes += wordpress_schema_guard($child, $depth + 1);
                if ($nodes > 64) { throw new InvalidArgumentException('Schemas allow at most 64 nodes.'); }
            }
        } elseif ($type === 'object' && $key === 'additionalProperties') {
            if (!is_bool($value)) { $nodes += wordpress_schema_guard($value, $depth + 1); }
        } elseif ($key === 'required') {
            if (is_bool($value)) { continue; }
            if ($type !== 'object' || !is_array($value) || !array_is_list((array) $value) || count((array) $value) > 64) { throw new InvalidArgumentException('Object required names must be a list of at most 64 names.'); }
            foreach ((array) $value as $name) { wordpress_error_code($name); }
        } else { throw new InvalidArgumentException('Schema keyword is outside this profile.'); }
        if ($nodes > 64) { throw new InvalidArgumentException('Schemas allow at most 64 nodes.'); }
    }
    return $nodes;
}

function wordpress_schema(mixed $input): array {
    wordpress_schema_guard($input);
    return (array) $input;
}

function wordpress_schema_validate(mixed $value, mixed $schema, mixed $param): mixed {
    return rest_validate_value_from_schema($value, wordpress_schema($schema), $param);
}

function wordpress_request_validate_schema(mixed $value, WP_REST_Request $request, mixed $key): mixed {
    wordpress_request_callback_record('validate', 'schema', $value, $request, $key);
    $attributes = $request->get_attributes();
    $arg = (array) $attributes['args'][$key];
    return wordpress_schema_validate($value, $arg['profile_schema'], $key);
}
