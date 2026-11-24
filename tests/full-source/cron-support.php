<?php

define('WP_PLUGIN_DIR', '/unused-cron-control/plugins');
define('WPMU_PLUGIN_DIR', '/unused-cron-control/mu-plugins');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
define('WEEK_IN_SECONDS', 604800);
define('WP_CRON_LOCK_TIMEOUT', 60);
define('DISABLE_WP_CRON', true);
function __(mixed $text): mixed {return $text;}
function absint(mixed $value): int {return abs((int)$value);}
function is_wp_error(mixed $value): bool {return $value instanceof WP_Error;}
function _doing_it_wrong(mixed ...$args): never {throw new LogicException('Unexpected cron diagnostic: ' . json_encode($args));}
function _deprecated_argument(mixed ...$args): never {throw new LogicException('Deprecated cron calls are outside this control.');}
function _deprecated_hook(mixed ...$args): never {throw new LogicException('Deprecated plugin hooks are outside this control.');}
function _deprecated_function(mixed ...$args): never {throw new LogicException('Deprecated plugin functions are outside this control.');}
function plugins_url(mixed ...$args): never {throw new LogicException('Plugin discovery is outside this control.');}
function wp_normalize_path(mixed ...$args): never {throw new LogicException('Plugin discovery is outside this control.');}
function trailingslashit(mixed ...$args): never {throw new LogicException('Plugin discovery is outside this control.');}
function get_transient(mixed ...$args): never {throw new LogicException('Cron process locks are outside this control.');}
function set_transient(mixed ...$args): never {throw new LogicException('Cron process locks are outside this control.');}
function add_query_arg(mixed ...$args): never {throw new LogicException('Cron HTTP transport is outside this control.');}
function site_url(mixed ...$args): never {throw new LogicException('Cron HTTP transport is outside this control.');}
function wp_redirect(mixed ...$args): never {throw new LogicException('Cron HTTP transport is outside this control.');}
function wp_remote_post(mixed ...$args): never {throw new LogicException('Cron HTTP transport is outside this control.');}
function wp_unslash(mixed ...$args): never {throw new LogicException('Cron HTTP transport is outside this control.');}
function wp_ob_end_flush_all(): never {throw new LogicException('Cron response flushing is outside this control.');}
function get_option(mixed $name, mixed $default = false): mixed {
    global $cron_control_options;
    if (!array_key_exists($name, $cron_control_options)) return $default;
    return unserialize((string)$cron_control_options[$name]);
}
function update_option(mixed $name, mixed $value, mixed $autoload = null): bool {
    global $cron_control_options;
    $serialized = serialize($value);
    if (isset($cron_control_options[$name]) && $cron_control_options[$name] === $serialized) return false;
    $cron_control_options[$name] = $serialized;
    return true;
}
function cron_result(mixed $value): mixed {
    if ($value instanceof WP_Error) return array('error', $value->get_error_code(), $value->get_error_message());
    return $value;
}
$cron_control_options = wp_aot_empty_request_array();
$wp_filter = wp_aot_empty_request_array();
$wp_actions = wp_aot_empty_request_array();
$wp_filters = wp_aot_empty_request_array();
$wp_current_filter = wp_aot_empty_request_array();
