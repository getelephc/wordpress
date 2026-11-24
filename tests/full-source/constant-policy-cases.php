<?php

$rows = array(array(CASE_UPPER, EXTR_SKIP, HTML_ENTITIES, INFO_MODULES, PASSWORD_BCRYPT, PATH_SEPARATOR, FILTER_VALIDATE_EMAIL, FILTER_VALIDATE_IP));
$rows[] = array(defined('WP_HOME'), defined('WP_SITEURL'), defined('IS_GUTENBERG_PLUGIN'));
if (defined('WP_HOME')) $rows[] = array(aot_test_absent_constant('WP_HOME'));
else $rows[] = array('guard preserved');
foreach (array('WP_HOME', 'WP_SITEURL', 'IS_GUTENBERG_PLUGIN', 'AOT_UNKNOWN_OPTION') as $name) {
    try { $rows[] = array(aot_test_absent_constant((string) $name)); }
    catch (Error $error) { $rows[] = array($error->getMessage()); }
}

// Conditional definitions must remain visible on the second call, including false values.
function aot_test_bootstrap_constant_presence(): array {
    $before = wp_aot_bootstrap_defined('WP_DEBUG_LOG');
    $defined = null;
    if (!$before) $defined = wp_aot_bootstrap_mark('WP_DEBUG_LOG', define('WP_DEBUG_LOG', false));
    return array($before, $defined, wp_aot_bootstrap_defined('WP_DEBUG_LOG'), WP_DEBUG_LOG);
}
$rows[] = array(aot_test_bootstrap_constant_presence(), aot_test_bootstrap_constant_presence());
// Runtime data views preserve the first evaluated String definition.
function aot_runtime_constant_input(mixed $value): mixed { return $value; }
try { wp_aot_bootstrap_string('WP_CONTENT_URL'); throw new LogicException('An absent String constant was accepted.'); }
catch (Error $error) { $rows[] = array($error->getMessage()); }
wp_aot_bootstrap_mark('AUTH_COOKIE', define('AUTH_COOKIE', wp_aot_bootstrap_capture_string('AUTH_COOKIE', (string) aot_runtime_constant_input(""))));
wp_aot_bootstrap_mark('LOGGED_IN_COOKIE', define('LOGGED_IN_COOKIE', wp_aot_bootstrap_capture_string('LOGGED_IN_COOKIE', (string) aot_runtime_constant_input("/wordpress/"))));
wp_aot_bootstrap_mark('SECURE_AUTH_COOKIE', define('SECURE_AUTH_COOKIE', wp_aot_bootstrap_capture_string('SECURE_AUTH_COOKIE', (string) aot_runtime_constant_input("https://example.test/é汉?q=1"))));
wp_aot_bootstrap_mark('USER_COOKIE', define('USER_COOKIE', wp_aot_bootstrap_capture_string('USER_COOKIE', (string) aot_runtime_constant_input("cookie-value"))));
wp_aot_bootstrap_mark('PASS_COOKIE', define('PASS_COOKIE', wp_aot_bootstrap_capture_string('PASS_COOKIE', (string) aot_runtime_constant_input("256M"))));
wp_aot_bootstrap_mark('TEST_COOKIE', define('TEST_COOKIE', wp_aot_bootstrap_capture_string('TEST_COOKIE', (string) aot_runtime_constant_input(""))));
wp_aot_bootstrap_mark('RECOVERY_MODE_COOKIE', define('RECOVERY_MODE_COOKIE', wp_aot_bootstrap_capture_string('RECOVERY_MODE_COOKIE', (string) aot_runtime_constant_input("/wordpress/"))));
wp_aot_bootstrap_mark('COOKIEHASH', define('COOKIEHASH', wp_aot_bootstrap_capture_string('COOKIEHASH', (string) aot_runtime_constant_input("https://example.test/é汉?q=1"))));
wp_aot_bootstrap_mark('COOKIEPATH', define('COOKIEPATH', wp_aot_bootstrap_capture_string('COOKIEPATH', (string) aot_runtime_constant_input("cookie-value"))));
wp_aot_bootstrap_mark('SITECOOKIEPATH', define('SITECOOKIEPATH', wp_aot_bootstrap_capture_string('SITECOOKIEPATH', (string) aot_runtime_constant_input("256M"))));
wp_aot_bootstrap_mark('ADMIN_COOKIE_PATH', define('ADMIN_COOKIE_PATH', wp_aot_bootstrap_capture_string('ADMIN_COOKIE_PATH', (string) aot_runtime_constant_input(""))));
wp_aot_bootstrap_mark('PLUGINS_COOKIE_PATH', define('PLUGINS_COOKIE_PATH', wp_aot_bootstrap_capture_string('PLUGINS_COOKIE_PATH', (string) aot_runtime_constant_input("/wordpress/"))));
wp_aot_bootstrap_mark('WP_CONTENT_URL', define('WP_CONTENT_URL', wp_aot_bootstrap_capture_string('WP_CONTENT_URL', (string) aot_runtime_constant_input("https://example.test/é汉?q=1"))));
wp_aot_bootstrap_mark('WP_PLUGIN_URL', define('WP_PLUGIN_URL', wp_aot_bootstrap_capture_string('WP_PLUGIN_URL', (string) aot_runtime_constant_input("cookie-value"))));
wp_aot_bootstrap_mark('WPMU_PLUGIN_URL', define('WPMU_PLUGIN_URL', wp_aot_bootstrap_capture_string('WPMU_PLUGIN_URL', (string) aot_runtime_constant_input("256M"))));
wp_aot_bootstrap_mark('WP_MEMORY_LIMIT', define('WP_MEMORY_LIMIT', wp_aot_bootstrap_capture_string('WP_MEMORY_LIMIT', (string) aot_runtime_constant_input(""))));
wp_aot_bootstrap_mark('WP_MAX_MEMORY_LIMIT', define('WP_MAX_MEMORY_LIMIT', wp_aot_bootstrap_capture_string('WP_MAX_MEMORY_LIMIT', (string) aot_runtime_constant_input("/wordpress/"))));
foreach (array('AUTH_COOKIE','LOGGED_IN_COOKIE','SECURE_AUTH_COOKIE','USER_COOKIE','PASS_COOKIE','TEST_COOKIE','RECOVERY_MODE_COOKIE','COOKIEHASH','COOKIEPATH','SITECOOKIEPATH','ADMIN_COOKIE_PATH','PLUGINS_COOKIE_PATH','WP_CONTENT_URL','WP_PLUGIN_URL','WPMU_PLUGIN_URL','WP_MEMORY_LIMIT','WP_MAX_MEMORY_LIMIT') as $name) {
    $before = wp_aot_bootstrap_string($name);
    $returned = wp_aot_bootstrap_capture_string($name, 'second-value');
    $rows[] = array($name, $before, $returned, wp_aot_bootstrap_string($name), wp_aot_bootstrap_defined($name));
}
define('MINUTE_IN_SECONDS', (int) (aot_runtime_constant_input(60)));
define('HOUR_IN_SECONDS', (int) (60 * MINUTE_IN_SECONDS));
define('DAY_IN_SECONDS', (int) (24 * HOUR_IN_SECONDS));
define('WEEK_IN_SECONDS', (int) (7 * DAY_IN_SECONDS));
define('MONTH_IN_SECONDS', (int) (30 * DAY_IN_SECONDS));
define('YEAR_IN_SECONDS', (int) (365 * DAY_IN_SECONDS));
$rows[] = array(MINUTE_IN_SECONDS,HOUR_IN_SECONDS,DAY_IN_SECONDS,WEEK_IN_SECONDS,MONTH_IN_SECONDS,YEAR_IN_SECONDS);
class AotNonNullConstantMap {
    const VALUES = array('link'=>array(':hover',':focus'),'button'=>array(':active'));
}
foreach (array('link','heading','h1','button','caption','textInput','select','') as $name) {
    $rows[] = array($name,aot_test_nonnull_constant_key($name),AotNonNullConstantMap::VALUES[$name]??array(),array_keys(AotNonNullConstantMap::VALUES));
}
echo json_encode($rows, JSON_THROW_ON_ERROR) . "\n";
