<?php

require __DIR__ . '/kses-source.php';
if (($argv[1] ?? '') !== 'profile') {
    require __DIR__ . '/../upstream/wordpress/wp-includes/shortcodes.php';
} else {
    require_once __DIR__ . '/../elephc/runtime/shortcodes-support.php';
    require __DIR__ . '/../elephc/runtime/core/wp-includes/shortcodes.php';
}
require __DIR__ . '/../elephc/runtime/render-demo.php';

$results = [];
foreach (json_decode(file_get_contents(__DIR__ . '/render-fixtures.json'), true) as $fixture) {
    $results[$fixture['name']] = wordpress_render_text($fixture['mode'], $fixture['input']);
}
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
