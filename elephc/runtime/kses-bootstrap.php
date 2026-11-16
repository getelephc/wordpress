<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/shortcodes-support.php';
require_once __DIR__ . '/kses-support.php';
define('CUSTOM_TAGS', false);
require_once __DIR__ . '/core/wp-includes/kses.php';
require_once __DIR__ . '/core/wp-includes/http.php';
require_once __DIR__ . '/core/wp-includes/formatting.php';
wordpress_kses_setup();
