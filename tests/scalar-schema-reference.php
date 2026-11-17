<?php

require __DIR__ . '/requests-source.php';
$results = [];
foreach (json_decode(file_get_contents(__DIR__ . '/scalar-schema-fixtures.json'), true) as $fixture) {
    $actual = json_decode(wordpress_requests_text($fixture['input']), true);
    if (($argv[1] ?? '') !== 'profile') {
        $input = json_decode($fixture['input'], true);
        foreach ($input['operations'] as $index => $operation) {
            if ($operation['op'] !== 'scalar-check') { continue; }
            $value = rest_validate_value_from_schema($operation['value'], $operation['schema'], $operation['key'] ?? 'value');
            $expected = json_decode(json_encode(wordpress_request_validation_result($value)), true);
            if ($actual['results'][$index] !== $expected) { throw new RuntimeException('Scalar helper differs from original schema dispatcher: ' . $fixture['name']); }
        }
    }
    $results[$fixture['name']] = $actual;
}
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
