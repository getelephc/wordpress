<?php

if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/requests-bootstrap.php';
} else {
    define('ABSPATH', __DIR__ . '/../upstream/wordpress/');
    define('WPINC', 'wp-includes');
    require ABSPATH . WPINC . '/plugin.php';
    require ABSPATH . WPINC . '/functions.php';
    require ABSPATH . WPINC . '/load.php';
    require ABSPATH . WPINC . '/class-wp-error.php';
    require ABSPATH . WPINC . '/class-wp-http.php';
    require ABSPATH . WPINC . '/rest-api/class-wp-rest-request.php';
    require __DIR__ . '/../elephc/runtime/errors-support.php';
    require __DIR__ . '/../elephc/runtime/requests-support.php';
    // The fixture uses the original option filter and a fixed English locale.
    function __(string $text): string { return $text; }
    add_filter('pre_option_permalink_structure', static function (): string { return wordpress_request_permalinks() ? '/%postname%/' : ''; });
}
