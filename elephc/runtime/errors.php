<?php

require __DIR__ . '/errors-bootstrap.php';

try {
    echo wordpress_errors_text($argv[1] ?? (string) stream_get_contents(STDIN));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'Error profile: ' . $exception->getMessage() . "\n");
    exit(2);
}
