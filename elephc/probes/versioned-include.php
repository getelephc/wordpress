<?php

// The legacy loader is unreachable with the PHP 8.4 compilation profile.
if (PHP_VERSION_ID < 70000) {
    function wordpress_probe_legacy_include(string $file): void {
        require_once $file;
    }
}
echo "guarded\n";
