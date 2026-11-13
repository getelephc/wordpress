<?php

$source = ($argv[1] ?? '') === 'profile'
    ? __DIR__ . '/../elephc/runtime/core/wp-includes'
    : __DIR__ . '/../upstream/wordpress/wp-includes';
require $source . '/class-wp-block-parser.php';

$parser = new WP_Block_Parser();
$output = [];
foreach (json_decode(file_get_contents(__DIR__ . '/fixtures.json'), true) as $fixture) {
    $output[$fixture['name']] = $parser->parse($fixture['document']);
}
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
