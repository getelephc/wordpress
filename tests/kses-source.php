<?php

if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/kses-bootstrap.php';
} else {
    define('ABSPATH', __DIR__ . '/../upstream/wordpress/');
    define('WPINC', 'wp-includes');
    require ABSPATH . WPINC . '/plugin.php';
    require ABSPATH . WPINC . '/functions.php';
    require ABSPATH . WPINC . '/compat.php';
    require ABSPATH . WPINC . '/compat-utf8.php';
    require ABSPATH . WPINC . '/utf8.php';
    require ABSPATH . WPINC . '/formatting.php';
    require ABSPATH . WPINC . '/kses.php';
    require __DIR__ . '/../elephc/runtime/hooks-support.php';
    // Original PHP already initializes the table; expose the same setup adapter.
    function _elephc_kses_entity_names(): array {
        global $allowedentitynames;
        return $allowedentitynames;
    }
    require __DIR__ . '/../elephc/runtime/kses-support.php';
    wordpress_kses_setup();
}
