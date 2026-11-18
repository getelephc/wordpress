<?php
$root = dirname(__DIR__, 2);
$site = $root . '/elephc/build/php-hybrid/wordpress';
define('HYBRID_NATIVE', false);
define('WP_INSTALLING', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1:9000';
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '9000';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
require $site . '/wp-load.php';
if (DB_ENGINE !== 'sqlite' || realpath(dirname(DB_PATH)) !== realpath($root . '/elephc/build/php-hybrid')) {
    throw new RuntimeException('Unexpected benchmark database target.');
}
require ABSPATH . 'wp-admin/includes/upgrade.php';
if (!is_blog_installed()) {
    wp_install('Elephc hybrid experiment', 'hybrid-bench', 'hybrid@example.invalid', false, '', bin2hex(random_bytes(24)));
}
switch_theme('twentytwentyfive');
update_option('blog_public', '0');
update_option('posts_per_page', 5);
update_option('permalink_structure', '');
update_option('default_ping_status', 'closed');
update_option('default_comment_status', 'closed');
$block = '<!-- wp:heading --><h2 class="wp-block-heading">Native WordPress blocks</h2><!-- /wp:heading -->' . "\n" . '<!-- wp:paragraph {"align":"left"} --><p class="has-text-align-left">A real WordPress page with UTF-8: caffè 🦣.</p><!-- /wp:paragraph -->' . "\n" . '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Nested content and ordinary theme rendering.</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
$classic = "A classic WordPress paragraph with caffè and ordinary text.\nSecond line with a break.\n\nAnother paragraph, with <strong>formatting</strong> and a <a href=\"http://127.0.0.1:9000/\">local link</a>.\n\n<pre>Preformatted\ncontent</pre>\n\n";
$ids = [];
foreach (['blocks-small' => $block, 'blocks-large' => str_repeat($block, 25), 'classic-small' => $classic, 'classic-large' => str_repeat($classic, 40)] as $name => $content) {
    $existing = get_page_by_path($name, OBJECT, 'post');
    $fields = ['post_title' => $name, 'post_name' => $name, 'post_status' => 'publish', 'post_type' => 'post', 'post_content' => $content, 'post_date' => '2026-01-15 12:00:00', 'comment_status' => 'closed', 'ping_status' => 'closed'];
    if ($existing) { $fields['ID'] = $existing->ID; }
    $id = wp_insert_post($fields, true);
    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
    $ids[$name] = $id;
}
file_put_contents($root . '/elephc/build/php-hybrid/posts.json', json_encode($ids, JSON_PRETTY_PRINT));
echo json_encode(['wordpress' => $GLOBALS['wp_version'], 'database_engine' => DB_ENGINE, 'database_path' => DB_PATH, 'theme' => get_stylesheet(), 'posts' => $ids], JSON_PRETTY_PRINT), "\n";
