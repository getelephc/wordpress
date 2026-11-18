<?php

require __DIR__ . '/callable/functions.php';
function wordpress_probe_invoke(callable $callback): string {
    return $callback('wordpress');
}
echo wordpress_probe_invoke('wordpress_probe_value'), "\n";
