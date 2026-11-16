<?php

require __DIR__ . '/responses-bootstrap.php';

try {
    echo wordpress_responses_text($argv[1] ?? (string) stream_get_contents(STDIN));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, 'Response profile: ' . $exception->getMessage() . "\n");
    exit(2);
}
