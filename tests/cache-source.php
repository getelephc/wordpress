<?php

if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/cache-bootstrap.php';
} else {
    define('ABSPATH', __DIR__ . '/../upstream/wordpress/');
    define('WPINC', 'wp-includes');
    require __DIR__ . '/../elephc/runtime/cache-support.php';
    // Supply the same isolated site context to the unmodified cache class.
    function is_multisite(): bool { return wordpress_cache_multisite(); }
    function get_current_blog_id(): int { return wordpress_cache_blog_id(); }
    require ABSPATH . WPINC . '/functions.php';
    require ABSPATH . WPINC . '/cache.php';
}
