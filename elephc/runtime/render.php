<?php

require __DIR__ . '/render-bootstrap.php';
require __DIR__ . '/render-demo.php';

echo wordpress_render_text($argv[1] ?? 'pipeline', $argv[2] ?? wordpress_content_demo_input());
