<?php

require __DIR__ . '/kses-bootstrap.php';

try {
    $input = $argv[2] ?? (string) stream_get_contents(STDIN);
    echo wordpress_kses_text($argv[1] ?? 'attribute', $input, $argv[3] ?? 'a');
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'KSES error: ' . $exception->getMessage() . "\n");
    exit(2);
}
