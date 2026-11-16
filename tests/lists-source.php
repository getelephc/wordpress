<?php

if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/functions-bootstrap.php';
} else {
    define('ABSPATH', __DIR__ . '/../upstream/wordpress/');
    define('WPINC', 'wp-includes');
    require ABSPATH . WPINC . '/class-wp-list-util.php';
    require ABSPATH . WPINC . '/functions.php';
    require __DIR__ . '/../elephc/runtime/lists-support.php';
}
