<?php

if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/serialize-bootstrap.php';
} else {
    define('ABSPATH', __DIR__ . '/../upstream/wordpress/');
    define('WPINC', 'wp-includes');
    require ABSPATH . WPINC . '/class-wp-block-parser.php';
    require ABSPATH . WPINC . '/functions.php';
    require ABSPATH . WPINC . '/blocks.php';
    require __DIR__ . '/../elephc/runtime/serialize-support.php';
}

$results = [];
$fixtures = json_decode(file_get_contents(__DIR__ . '/serialize-fixtures.json'), true);
foreach ($fixtures as $fixture) {
    $text = wordpress_serialize_text($fixture['mode'], $fixture['input']);
    $parser = new WP_Block_Parser();
    $parsed = $parser->parse($text);
    $results[$fixture['name']] = ['text' => $text, 'parsed' => $parsed, 'reserialized' => serialize_blocks($parsed)];
}
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
