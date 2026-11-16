<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/kses-bootstrap.php';
require_once __DIR__ . '/shortcodes-support.php';
require_once __DIR__ . '/core/wp-includes/formatting.php';
require_once __DIR__ . '/core/wp-includes/shortcodes.php';

WordPressHookCallbacks::register('_filter_do_shortcode_context', static function (): string {
    return _filter_do_shortcode_context();
});
