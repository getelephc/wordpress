<?php

require __DIR__ . '/requests-bootstrap.php';

try {
    echo wordpress_requests_text($argv[1] ?? (string) stream_get_contents(STDIN));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'Request profile: ' . $exception->getMessage() . "\n");
    exit(2);
}
