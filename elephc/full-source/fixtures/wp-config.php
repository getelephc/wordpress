<?php

// Finite local configuration, with original WordPress initialization.
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
    define( 'WP_PLUGIN_DIR', __DIR__ . '/wp-content/plugins' );
}
if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
    define( 'WPMU_PLUGIN_DIR', __DIR__ . '/wp-content/mu-plugins' );
}
if ( ! defined( 'WP_LANG_DIR' ) ) {
    define( 'WP_LANG_DIR', __DIR__ . '/wp-content/languages' );
}
require WP_CONTENT_DIR . '/aot-data.php';
function aot_translation_catalog( string $path ): array {
    throw new RuntimeException( 'No PHP translation catalog is registered in the English fixture: ' . $path );
}
define( 'DB_NAME', 'wordpress_fixture' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '127.0.0.1' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'WP_DEBUG', false );
define( 'WP_CACHE', false );
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
$table_prefix = 'wp_';
require __DIR__ . '/wp-settings.php';
