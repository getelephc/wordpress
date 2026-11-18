<?php

// Begin at the original root loader with the isolated local configuration.
define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
define( 'WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins' );
define( 'WPMU_PLUGIN_DIR', ABSPATH . 'wp-content/mu-plugins' );
define( 'WP_LANG_DIR', ABSPATH . 'wp-content/languages' );
require __DIR__ . '/aot-platform.lfc';
require __DIR__ . '/wp-load.php';
$document = $argv[1] ?? '<!-- wp:site-title /--><!-- wp:site-tagline /--><!-- wp:paragraph --><p>Hello WordPress</p><!-- /wp:paragraph -->';
$pattern_hashes = array();
foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
    $pattern_hashes[ $pattern['name'] ] = hash( 'sha256', $pattern['content'] );
}
echo json_encode( array(
    'version' => $wp_version,
    'booted' => 1 === did_action( 'wp_loaded' ),
    'init' => did_action( 'init' ),
    'theme' => get_stylesheet(),
    'blogname' => get_option( 'blogname' ),
    'post_types' => array_keys( get_post_types() ),
    'taxonomies' => array_keys( get_taxonomies() ),
    'registered_blocks' => count( WP_Block_Type_Registry::get_instance()->get_all_registered() ),
    'rendered' => do_blocks( $document ),
    'pattern_content_sha256' => $pattern_hashes,
) ) . "\n";
