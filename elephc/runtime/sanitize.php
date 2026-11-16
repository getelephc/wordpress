<?php

require __DIR__ . '/kses-bootstrap.php';
require __DIR__ . '/sanitize-support.php';

try {
    echo wordpress_sanitize_text($argv[1] ?? (string) stream_get_contents(STDIN));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'Sanitization error: ' . $exception->getMessage() . "\n");
    exit(2);
}
