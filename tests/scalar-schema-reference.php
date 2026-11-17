<?php

require __DIR__ . '/requests-source.php';
$results = [];
foreach (json_decode(file_get_contents(__DIR__ . '/scalar-schema-fixtures.json'), true) as $fixture) {
    $actual = json_decode(wordpress_requests_text($fixture['input']), true);
    if (($argv[1] ?? '') !== 'profile') {
        $input = json_decode($fixture['input'], true);
        foreach ($input['operations'] as $index => $operation) {
            if ($operation['op'] !== 'scalar-check') { continue; }
            $schema = $operation['schema'];
            $param = $operation['key'] ?? 'value';
            switch ($schema['type']) {
                case 'null': $value = rest_validate_null_value_from_schema($operation['value'], $param); break;
                case 'boolean': $value = rest_validate_boolean_value_from_schema($operation['value'], $param); break;
                case 'number': $value = rest_validate_number_value_from_schema($operation['value'], $schema, $param); break;
                case 'integer': $value = rest_validate_integer_value_from_schema($operation['value'], $schema, $param); break;
                case 'string': $value = rest_validate_string_value_from_schema($operation['value'], $schema, $param); break;
                default: throw new RuntimeException('Unexpected scalar control type.');
            }
            $expected = json_decode(json_encode(wordpress_request_validation_result($value)), true);
            if ($actual['results'][$index] !== $expected) { throw new RuntimeException('Scalar helper differs from original schema dispatcher: ' . $fixture['name']); }
        }
    }
    $results[$fixture['name']] = $actual;
}
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
