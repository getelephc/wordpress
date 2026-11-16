<?php

if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/errors-bootstrap.php';
} else {
    define('ABSPATH', __DIR__ . '/../upstream/wordpress/');
    define('WPINC', 'wp-includes');
    require ABSPATH . WPINC . '/plugin.php';
    require ABSPATH . WPINC . '/class-wp-error.php';
    require ABSPATH . WPINC . '/load.php';
    require __DIR__ . '/../elephc/runtime/errors-support.php';
}
