<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/hooks-demo.php';

echo json_encode(wordpress_hooks_demo($argv[1] ?? 'priority', $argv[2] ?? 'WordPress'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
