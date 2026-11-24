<?php

// Finite local configuration, with original WordPress initialization.
if ( ! \wp_aot_bootstrap_defined( 'WP_CONTENT_DIR' ) ) {
    \wp_aot_bootstrap_mark('WP_CONTENT_DIR', define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' ));
}
if ( ! \wp_aot_bootstrap_defined( 'WP_PLUGIN_DIR' ) ) {
    \wp_aot_bootstrap_mark('WP_PLUGIN_DIR', define( 'WP_PLUGIN_DIR', __DIR__ . '/wp-content/plugins' ));
}
if ( ! \wp_aot_bootstrap_defined( 'WPMU_PLUGIN_DIR' ) ) {
    \wp_aot_bootstrap_mark('WPMU_PLUGIN_DIR', define( 'WPMU_PLUGIN_DIR', __DIR__ . '/wp-content/mu-plugins' ));
}
if ( ! \wp_aot_bootstrap_defined( 'WP_LANG_DIR' ) ) {
    \wp_aot_bootstrap_mark('WP_LANG_DIR', define( 'WP_LANG_DIR', __DIR__ . '/wp-content/languages' ));
}
require WP_CONTENT_DIR . '/aot-data.php';
function aot_translation_catalog( string $path ): array {
    throw new RuntimeException( 'No PHP translation catalog is registered in the English fixture: ' . $path );
}
\wp_aot_bootstrap_mark('DB_NAME', define( 'DB_NAME', 'wordpress_fixture' ));
\wp_aot_bootstrap_mark('DB_USER', define( 'DB_USER', '' ));
\wp_aot_bootstrap_mark('DB_PASSWORD', define( 'DB_PASSWORD', '' ));
\wp_aot_bootstrap_mark('DB_HOST', define( 'DB_HOST', '127.0.0.1' ));
\wp_aot_bootstrap_mark('DB_CHARSET', define( 'DB_CHARSET', 'utf8mb4' ));
\wp_aot_bootstrap_mark('DB_COLLATE', define( 'DB_COLLATE', '' ));
\wp_aot_bootstrap_mark('WP_DEBUG', define( 'WP_DEBUG', false ));
// Native fatal diagnostics are emitted directly by Elephc.
\wp_aot_bootstrap_mark('WP_DISABLE_FATAL_ERROR_HANDLER', define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true ));
\wp_aot_bootstrap_mark('WP_CACHE', define( 'WP_CACHE', false ));
\wp_aot_bootstrap_mark('DISABLE_WP_CRON', define( 'DISABLE_WP_CRON', true ));
\wp_aot_bootstrap_mark('WP_HTTP_BLOCK_EXTERNAL', define( 'WP_HTTP_BLOCK_EXTERNAL', true ));
$table_prefix = 'wp_';
require __DIR__ . '/wp-settings.php';
