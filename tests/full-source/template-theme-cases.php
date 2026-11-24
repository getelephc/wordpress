<?php

function wordpress_template_fixture_stylesheet($stylesheet) {
    $GLOBALS['wordpress_template_fixture_calls']++;
    return 'fixture-theme-' . $GLOBALS['wordpress_template_fixture_calls'];
}

$templates = array(
    '', 'Plain text', '<!-- wp:paragraph --><p>Text</p><!-- /wp:paragraph -->',
    '<!-- wp:template-part {"slug":"header"} /-->',
    '<!-- wp:template-part { "slug" : "header", "theme" : "custom" } /-->',
    '<!-- wp:template-part {"slug":"header","theme":null} /-->',
    '<!-- wp:template-part {"slug":"header","theme":""} /-->',
    '<!-- wp:group --><!-- wp:template-part {"slug":"nested"} /--><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
    '<!-- wp:group --><!-- wp:template-part {"slug":"nested","theme":"custom"} /--><!-- /wp:group --><!-- wp:template-part {"slug":"footer","theme":"other"} /-->',
);
$results = array();
foreach (array(false, true) as $varying_theme) {
    if ($varying_theme) add_filter('stylesheet', 'wordpress_template_fixture_stylesheet');
    foreach ($templates as $content) {
        $GLOBALS['wordpress_template_fixture_calls'] = 0;
        $injected = _inject_theme_attribute_in_block_template_content($content);
        $inject_calls = $GLOBALS['wordpress_template_fixture_calls'];
        $GLOBALS['wordpress_template_fixture_calls'] = 0;
        $removed = _remove_theme_attribute_in_block_template_content($content);
        $results[] = array($injected, $removed, $inject_calls, $GLOBALS['wordpress_template_fixture_calls']);
    }
    if ($varying_theme) remove_filter('stylesheet', 'wordpress_template_fixture_stylesheet');
}

// A PHP pattern file must render independently for each registered pattern.
$repeat_pattern_registry = new WP_Block_Patterns_Registry();
$repeat_pattern_properties = array('title' => 'Repeated source pattern', 'filePath' => WP_CONTENT_DIR . '/themes/twentytwentyfive/patterns/comments.php');
foreach (array('aot-repeat/first', 'aot-repeat/second', 'aot-repeat/first') as $repeat_name) {
    if ($repeat_pattern_registry->is_registered($repeat_name)) $repeat_pattern_registry->unregister($repeat_name);
    $registered = $repeat_pattern_registry->register($repeat_name, $repeat_pattern_properties);
    $repeated = $repeat_pattern_registry->get_registered($repeat_name);
    $results[] = array($registered, strlen($repeated['content']), hash('sha256', $repeated['content']));
}
// The deprecated base constructor must not redispatch to the subclass constructor.
class WordPressLegacyConstructorFixture extends WP_Widget {
    public $constructor_calls = 0;
    public function __construct() {
        ++$this->constructor_calls;
        parent::WP_Widget('AOT-Legacy', 'Legacy widget', array('custom' => true), array('width' => 321));
    }
}
$legacy_widget = new WordPressLegacyConstructorFixture();
$results[] = array($legacy_widget->constructor_calls, $legacy_widget->id_base, $legacy_widget->name, $legacy_widget->option_name, $legacy_widget->widget_options, $legacy_widget->control_options);
$base_widget = new WP_Widget('AOT-Base', 'Base widget');
$results[] = array($base_widget->id_base, $base_widget->name, $base_widget->option_name, $base_widget->widget_options, $base_widget->control_options);
return $results;
