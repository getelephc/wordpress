<?php

if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/responses-bootstrap.php';
} else {
    require __DIR__ . '/errors-source.php';
    require ABSPATH . WPINC . '/class-wp-list-util.php';
    require ABSPATH . WPINC . '/functions.php';
    require ABSPATH . WPINC . '/class-wp-http-response.php';
    require ABSPATH . WPINC . '/rest-api/class-wp-rest-response.php';
    require ABSPATH . WPINC . '/rest-api.php';
    require __DIR__ . '/../elephc/runtime/responses-support.php';
}
