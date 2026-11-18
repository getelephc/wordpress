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
return $results;
