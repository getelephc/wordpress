<?php

require __DIR__ . '/lists-source.php';
$results = [];
foreach (json_decode(file_get_contents(__DIR__ . '/lists-fixtures.json'), true) as $fixture) {
    $results[$fixture['name']] = json_decode(wordpress_lists_text($fixture['input']), true);
}
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
