<?php

require __DIR__ . '/../../upstream/wordpress/wp-includes/plugin.php';

add_filter('title', static function (string $value): string { return strtoupper($value); });
echo apply_filters('title', 'WordPress');
