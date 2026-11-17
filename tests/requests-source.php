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
    require ABSPATH . WPINC . '/class-wp-http-response.php';
    require ABSPATH . WPINC . '/rest-api/class-wp-rest-response.php';
    require ABSPATH . WPINC . '/rest-api.php';
    require __DIR__ . '/../elephc/runtime/hooks-support.php';
    require __DIR__ . '/../elephc/runtime/errors-support.php';
    require __DIR__ . '/../elephc/runtime/requests-support.php';
    require __DIR__ . '/../elephc/runtime/request-validation.php';
    require __DIR__ . '/../elephc/runtime/rest-locale.php';
    require __DIR__ . '/../elephc/runtime/schema-support.php';
    mb_internal_encoding('UTF-8');
    function _n(string $single, string $plural, mixed $number): string { return wordpress_scalar_plural($single, $plural, $number); }
    // The fixture uses the original option filter and a fixed English locale.
    function __(string $text): string { return $text; }
    add_filter('pre_option_permalink_structure', static function (): string { return wordpress_request_permalinks() ? '/%postname%/' : ''; });
}
