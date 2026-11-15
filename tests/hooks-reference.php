<?php

require __DIR__ . '/../elephc/runtime/hooks-support.php';
if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/core/wp-includes/plugin.php';
} else {
    require __DIR__ . '/../upstream/wordpress/wp-includes/plugin.php';
}
require __DIR__ . '/../elephc/runtime/hooks-demo.php';

$results = [];
foreach (json_decode(file_get_contents(__DIR__ . '/hooks-fixtures.json'), true) as $scenario) {
    $results[$scenario] = wordpress_hooks_demo($scenario, 'WordPress 🐘');
}
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
