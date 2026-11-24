<?php

$mode = $argv[1] ?? 'unset';
if ($mode === 'subdomain-false' || $mode === 'both-conflict') wp_aot_subdomain_define('SUBDOMAIN_INSTALL', false);
if ($mode === 'subdomain-true' || $mode === 'both-match') wp_aot_subdomain_define('SUBDOMAIN_INSTALL', true);
if ($mode === 'subdomain-int') wp_aot_subdomain_define('SUBDOMAIN_INSTALL', 1);
if ($mode === 'vhost-yes' || $mode === 'both-match' || $mode === 'both-conflict') wp_aot_subdomain_define('VHOST', 'yes');
if ($mode === 'vhost-no') wp_aot_subdomain_define('VHOST', 'no');
if ($mode === 'vhost-other') wp_aot_subdomain_define('VHOST', 'other');
$subdomain_trace = array();
function subdomain_state(): array {
    return array(wp_aot_subdomain_defined('SUBDOMAIN_INSTALL'), wp_aot_subdomain_defined('VHOST'),
        wp_aot_subdomain_defined('SUBDOMAIN_INSTALL') ? wp_aot_subdomain_constant('SUBDOMAIN_INSTALL') : null,
        wp_aot_subdomain_defined('VHOST') ? wp_aot_subdomain_constant('VHOST') : null);
}
$rows = array(subdomain_state());
ms_subdomain_constants();
$rows[] = subdomain_state();
ms_subdomain_constants();
$rows[] = subdomain_state();
$rows[] = $subdomain_trace;
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
