<?php

require __DIR__ . '/requests-source.php';
$results = [];
foreach (json_decode(file_get_contents(__DIR__ . '/unique-schema-fixtures.json'), true) as $fixture) {
    $results[$fixture['name']] = json_decode(wordpress_requests_text($fixture['input']), true);
}
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
