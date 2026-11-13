<?php

// Diagnostic only. Compile-check this entry point; do not execute it.
define('ABSPATH', __DIR__ . '/../../upstream/wordpress/');
define('SHORTINIT', true);
require __DIR__ . '/../../upstream/wordpress/wp-settings.php';
