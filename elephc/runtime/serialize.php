<?php

require __DIR__ . '/serialize-bootstrap.php';

try {
    $input = $argv[2] ?? (string) stream_get_contents(STDIN);
    echo wordpress_serialize_text($argv[1] ?? 'blocks', $input);
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'Serialization error: ' . $exception->getMessage() . "\n");
    exit(2);
}
