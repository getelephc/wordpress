<?php

require __DIR__ . '/kses-source.php';
$results = [];
foreach (json_decode(file_get_contents(__DIR__ . '/kses-fixtures.json'), true) as $fixture) {
    $results[$fixture['name']] = base64_encode(wordpress_kses_text($fixture['mode'], $fixture['input'], $fixture['element'] ?? 'a'));
}
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
