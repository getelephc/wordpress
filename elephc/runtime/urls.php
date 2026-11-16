<?php

require __DIR__ . '/urls-bootstrap.php';

try {
    echo wordpress_urls_text($argv[1] ?? (string) stream_get_contents(STDIN));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'URL error: ' . $exception->getMessage() . "\n");
    exit(2);
}
