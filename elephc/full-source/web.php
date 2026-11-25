<?php

function wordpress_web_response(int $status, string $type, string $body): void {
    http_response_code($status);
    header('Content-Type: ' . $type);
    header('X-WordPress-Execution: elephc-native');
    echo $body;
}

function wordpress_web_page(string $title, string $content, bool $article, bool $block_styles = false): string {
    $title = esc_html($title);
    $site = esc_html((string) get_bloginfo('name'));
    $version = esc_html((string) get_bloginfo('version'));
    $logo = base64_encode((string) file_get_contents(ABSPATH . 'wp-includes/images/w-logo-gray-white-bg.svg'));
    $heading = $article ? '<h1>' . $title . '</h1>' : '';
    $back = $article ? '<p class="back"><a href="/">All posts</a></p>' : '';
    $stylesheet = $block_styles ? '<link rel="stylesheet" href="/wp-includes/css/dist/block-library/style.min.css">' : '';
    $body = $article ? '<div class="post-content">' . $content . '</div>' : $content;
    return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $title . '</title>' . $stylesheet
        . '<style>*{box-sizing:border-box}body{margin:0;background:#f1f5f8;color:#18262e;font:18px/1.6 system-ui,sans-serif}'
        . '.shell{max-width:820px;margin:clamp(20px,6vw,76px) auto;padding:0 16px}'
        . '.masthead{display:flex;align-items:center;gap:14px;margin-bottom:22px;color:#243640}'
        . '.masthead img{width:48px;height:48px}.masthead a{font-weight:700;color:inherit}.masthead small{display:block;color:#465d68}'
        . 'main{padding:clamp(22px,5vw,52px);background:white;border:1px solid #d5e1e8;border-radius:12px}'
        . 'a{color:#14577c;text-underline-offset:3px}a:focus-visible{outline:2px solid #14577c;outline-offset:4px}'
        . 'h1,h2{line-height:1.2}.wp-block-site-tagline{color:#465d68}.wp-block-latest-posts{padding:0;list-style:none}'
        . '.wp-block-latest-posts li{border-top:1px solid #d5e1e8;padding:14px 0}.back,footer{font-size:15px}'
        . 'footer{margin-top:32px;padding-top:16px;border-top:1px solid #d5e1e8;color:#465d68}'
        . '</style></head><body><div class="shell"><header class="masthead">'
        . '<img alt="" src="data:image/svg+xml;base64,' . $logo . '"><div><a href="/">' . $site . '</a>'
        . '<small>WordPress ' . $version . ' · Compiled with Elephc</small></div></header>'
        . '<main class="wp-site-blocks">' . $heading . $body . $back
        . '<footer>Rendered by the compiled WordPress core.</footer></main></div></body></html>';
}

function wordpress_web_seed_posts(wpdb $database): void {
    // Generated with WordPress 7.1.4's wp.blockLibrary and wp.blocks.serialize().
    $complex_post = <<<'GUTENBERG'
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"24%"} -->
<div class="wp-block-column" style="flex-basis:24%"><!-- wp:paragraph -->
<p>Gutenberg keeps the layout in the post content.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"52%"} -->
<div class="wp-block-column" style="flex-basis:52%"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>This paragraph sits inside the nested columns.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading -->
<h2 class="wp-block-heading">Inside the layout</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Save the block tree.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Render the saved document.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"24%"} -->
<div class="wp-block-column" style="flex-basis:24%"><!-- wp:paragraph -->
<p>Each column is a block with its own children.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="/wp-includes/images/w-logo-blue.png" alt="WordPress logo"/><figcaption class="wp-element-caption">The WordPress logo bundled with the release.</figcaption></figure>
<!-- /wp:image -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>A block document can describe both content and structure.</p>
<!-- /wp:paragraph --><cite>WordPress demo</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->
GUTENBERG;

    $articles = array(
        array(1004, '2026-01-15 09:00:00', 'gutenberg-blocks-in-depth', 'Gutenberg blocks in depth', $complex_post),
        array(1001, '2026-01-12 09:00:00', 'first-wordpress-post', 'A first WordPress post',
            '<!-- wp:paragraph --><p>This post comes from the WordPress posts table.</p><!-- /wp:paragraph -->'),
        array(1002, '2026-01-13 09:00:00', 'gutenberg-blocks', 'How WordPress renders blocks',
            '<!-- wp:paragraph --><p>Gutenberg stores the block document in the database.</p><!-- /wp:paragraph -->'
            . '<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>WordPress generates this HTML on every request.</p><!-- /wp:paragraph --></blockquote><!-- /wp:quote -->'),
        array(1003, '2026-01-14 09:00:00', 'from-sqlite-to-the-page', 'From SQLite to the page',
            '<!-- wp:paragraph --><p>SQLite stores the three posts for the duration of the request.</p><!-- /wp:paragraph -->'
            . '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/">Read another post</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'),
    );
    foreach ($articles as $article) {
        $insert = $database->prepare(
            "INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count) VALUES (%d, 0, %s, %s, %s, %s, '', 'publish', 'closed', 'closed', '', %s, '', '', %s, %s, '', 0, %s, 0, 'post', '', 0)",
            (int) $article[0], (string) $article[1], (string) $article[1], (string) $article[4],
            (string) $article[3], (string) $article[2], (string) $article[1], (string) $article[1],
            (string) home_url('/?p=' . (int) $article[0]),
        );
        if ($database->query($insert) !== 1) {
            throw new RuntimeException('Could not seed the local WordPress post fixture.');
        }
    }
}

function wordpress_web_prepare_query(mixed $query): void {
    if ($query instanceof WP_Query) {
        // The request-local fixture has no postmeta table or persistent cache.
        $query->set('cache_results', false);
        $query->set('update_post_meta_cache', false);
        $query->set('update_post_term_cache', false);
    }
}

function wordpress_web_render(string $document): string {
    add_action('pre_get_posts', 'wordpress_web_prepare_query');
    $html = (string) do_shortcode(do_blocks($document));
    remove_action('pre_get_posts', 'wordpress_web_prepare_query');
    return $html;
}

function wordpress_web_show(?string $document, int $post_id): void {
    if ($post_id > 0) {
        $article = get_post($post_id);
        if (!($article instanceof WP_Post) || $article->post_status !== 'publish' || $article->post_type !== 'post') {
            wordpress_web_response(404, 'text/plain; charset=utf-8', "Post not found\n");
            return;
        }
        $title = (string) get_the_title($article);
        $html = wordpress_web_render((string) $article->post_content);
        wordpress_web_response(200, 'text/html; charset=utf-8', wordpress_web_page($title, $html, true, $post_id === 1004));
        return;
    }

    $document = $document ?? '<!-- wp:site-title /--><!-- wp:site-tagline /-->'
        . '<!-- wp:heading --><h2 class="wp-block-heading">Latest posts</h2><!-- /wp:heading -->'
        . '<!-- wp:latest-posts {"postsToShow":4} /-->';
    $title = (string) get_bloginfo('name');
    wordpress_web_response(200, 'text/html; charset=utf-8', wordpress_web_page($title, wordpress_web_render($document), false));
}

$web_path = explode('?', (string) ($_SERVER['REQUEST_URI'] ?? '/'))[0];
$web_method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$web_asset_type = match ($web_path) {
    '/wp-includes/css/dist/block-library/style.min.css' => 'text/css; charset=utf-8',
    '/wp-includes/images/w-logo-blue.png' => 'image/png',
    default => '',
};
if ($web_path !== '/' && $web_path !== '/index.php' && $web_asset_type === '') {
    wordpress_web_response(404, 'text/plain; charset=utf-8', "Not Found\n");
    return;
}
if ($web_method !== 'GET' && $web_method !== 'HEAD') {
    header('Allow: GET, HEAD');
    wordpress_web_response(405, 'text/plain; charset=utf-8', "Method Not Allowed\n");
    return;
}
if ($web_asset_type !== '') {
    wordpress_web_response(200, $web_asset_type, (string) file_get_contents(__DIR__ . '/wordpress' . $web_path));
    return;
}

// PHP web SAPIs provide PHP_SELF. Supply the same finite entrypoint identity
// when the native request bridge omits it, so core server normalization stays quiet.
if (!isset($_SERVER['PHP_SELF'])) {
    $_SERVER['PHP_SELF'] = $web_path === '/' ? '/index.php' : $web_path;
}

$web_document = null;
if (array_key_exists('document', $_GET)) {
    $web_input = $_GET['document'];
    if (!is_string($web_input)) {
        wordpress_web_response(400, 'text/plain; charset=utf-8', "Expected a UTF-8 block document of at most 16384 bytes.\n");
        return;
    }
    $web_document = (string) $web_input;
    if (strlen($web_document) > 16384 || strpos($web_document, "\0") !== false
        || preg_match('//u', $web_document) !== 1) {
        wordpress_web_response(400, 'text/plain; charset=utf-8', "Expected a UTF-8 block document of at most 16384 bytes.\n");
        return;
    }
}

$web_post_id = 0;
if (array_key_exists('p', $_GET)) {
    $web_post = $_GET['p'];
    if ($web_document !== null || !is_string($web_post) || preg_match('/^[1-9][0-9]{0,8}$/D', (string) $web_post) !== 1) {
        wordpress_web_response(400, 'text/plain; charset=utf-8', "Expected one positive post ID or one block document.\n");
        return;
    }
    $web_post_id = (int) $web_post;
}

// Initialize the real core with the same options and SQL drop-in as the CLI.
global $wp_registered_widgets, $_wp_deprecated_widgets_callbacks, $_wp_theme_features, $wpdb;
define('ABSPATH', __DIR__ . '/wordpress/');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins');
define('WPMU_PLUGIN_DIR', ABSPATH . 'wp-content/mu-plugins');
define('WP_LANG_DIR', ABSPATH . 'wp-content/languages');
require __DIR__ . '/wordpress/aot-platform.lfc';
foreach (array('WP_CONTENT_DIR', 'WP_PLUGIN_DIR', 'WPMU_PLUGIN_DIR', 'WP_LANG_DIR') as $web_constant) {
    wp_aot_bootstrap_mark($web_constant, true);
}
require_once ABSPATH . 'aot-arrays.php';
require_once ABSPATH . 'aot-binary.php';
require ABSPATH . 'aot-contracts.php';
require ABSPATH . 'aot-core-contracts.php';
require ABSPATH . 'aot-ai.php';
$wp_filter = wp_aot_empty_request_array();
$wp_actions = wp_aot_empty_request_array();
$wp_filters = wp_aot_empty_request_array();
$wp_current_filter = wp_aot_empty_request_array();
$wp_plugin_paths = array();
require __DIR__ . '/wordpress/wp-load.php';

wordpress_web_seed_posts(wp_aot_require_wpdb($wpdb));
wordpress_web_show($web_document, $web_post_id);
if (defined('WP_AOT_NATIVE_RUNTIME')) {
    wp_aot_run_shutdown();
}
