<?php

require __DIR__ . '/functions-bootstrap.php';

try {
    echo wordpress_lists_text($argv[1] ?? (string) stream_get_contents(STDIN));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'List error: ' . $exception->getMessage() . "\n");
    exit(2);
}
