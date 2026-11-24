<?php
$source = __DIR__ . '/../../upstream/wordpress/';
$results = array();
foreach (array('wp-content/themes/*', 'wp-content/themes/twentytwentyfive/*', 'wp-content/themes/twentytwentyfive/.*', 'wp-content/themes/elephc-missing-*', 'wp-content/themes/*/styles', 'wp-content/themes/twentytwentyfive/*.json', 'wp-content/themes/twentytwentyfive/style.???') as $pattern) {
    foreach (array(0,GLOB_ONLYDIR) as $flags) $results[] = array(glob_call($source . $pattern, $flags));
}
echo json_encode($results), "\n";
