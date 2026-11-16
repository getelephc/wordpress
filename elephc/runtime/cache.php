<?php

require __DIR__ . '/cache-bootstrap.php';

try {
    echo wordpress_cache_text($argv[1] ?? (string) stream_get_contents(STDIN));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'Cache error: ' . $exception->getMessage() . "\n");
    exit(2);
}
