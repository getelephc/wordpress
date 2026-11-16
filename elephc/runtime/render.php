<?php

require __DIR__ . '/render-bootstrap.php';
require __DIR__ . '/render-demo.php';

try {
    echo wordpress_render_text($argv[1] ?? 'pipeline', $argv[2] ?? wordpress_content_demo_input());
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'Rendering error: ' . $exception->getMessage() . "\n");
    exit(2);
}
