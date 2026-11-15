<?php

// Diagnostic: bypass the explicit named-callback manifest on the pinned SDK.
require __DIR__ . '/../runtime/hooks-support.php';
require __DIR__ . '/../runtime/core/wp-includes/plugin.php';
require __DIR__ . '/../runtime/hooks-demo.php';

add_filter('title', 'wordpress_hook_early');
echo apply_filters('title', 'WordPress');
