<?php

function wordpress_scalar_schema(mixed $input): array {
    if (!is_array($input)) { throw new InvalidArgumentException('Scalar schemas must be objects.'); }
    $schema = (array) $input;
    $type = $schema['type'] ?? null;
    if (!is_string($type) || !in_array($type, ['null', 'boolean', 'integer', 'number', 'string'], true)) { throw new InvalidArgumentException('Scalar schemas require one supported type.'); }
    foreach ($schema as $key => $value) {
        if ($key === 'type') { continue; }
        if (($type === 'number' || $type === 'integer') && in_array($key, ['minimum', 'maximum', 'multipleOf'], true)) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || (float) $value < -1000000000000.0 || (float) $value > 1000000000000.0) { throw new InvalidArgumentException('Numeric schema bounds must be finite numbers within +/- 10^12.'); }
            if ($key === 'multipleOf' && (float) $value <= 0.0) { throw new InvalidArgumentException('multipleOf must be positive.'); }
        } elseif (($type === 'number' || $type === 'integer') && in_array($key, ['exclusiveMinimum', 'exclusiveMaximum'], true)) {
            if (!is_bool($value)) { throw new InvalidArgumentException('Exclusive schema flags must be boolean.'); }
        } elseif ($type === 'string' && in_array($key, ['minLength', 'maxLength'], true)) {
            if (!is_int($value) || (int) $value < 0 || (int) $value > 1000000) { throw new InvalidArgumentException('String schema lengths must be integers from 0 to 1000000.'); }
        } else { throw new InvalidArgumentException('Schema keyword is outside the scalar profile.'); }
    }
    return $schema;
}

function wordpress_scalar_validate(mixed $value, mixed $schema, mixed $param): mixed {
    $args = wordpress_scalar_schema($schema);
    switch ((string) $args['type']) {
        case 'null': return rest_validate_null_value_from_schema($value, $param);
        case 'boolean': return rest_validate_boolean_value_from_schema($value, $param);
        case 'number': return rest_validate_number_value_from_schema($value, $args, $param);
        case 'integer': return rest_validate_integer_value_from_schema($value, $args, $param);
        case 'string': return rest_validate_string_value_from_schema($value, $args, $param);
    }
    throw new LogicException('Unknown scalar schema type.');
}

function wordpress_request_validate_schema(mixed $value, WP_REST_Request $request, mixed $key): mixed {
    wordpress_request_callback_record('validate', 'schema', $value, $request, $key);
    $attributes = $request->get_attributes();
    $arg = (array) $attributes['args'][$key];
    return wordpress_scalar_validate($value, $arg['profile_schema'], $key);
}
