<?php

// Explicit environment boundaries for valid queries, without replacing wpdb methods.
define('WP_PLUGIN_DIR', '/unused-database-control/plugins');
define('WPMU_PLUGIN_DIR', '/unused-database-control/mu-plugins');
define('WP_CONTENT_DIR', '/unused-database-control');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
function plugins_url(mixed ...$args): never {throw new LogicException('Plugin discovery is outside this SQL control.');}
function _deprecated_hook(mixed ...$args): never {throw new LogicException('Deprecated hooks are outside this SQL control.');}
function _deprecated_function(mixed ...$args): never {throw new LogicException('Deprecated functions are outside this SQL control.');}
function dead_db(mixed ...$args): never {throw new LogicException('Database connection recovery is outside this SQL control.');}
function mbstring_binary_safe_encoding(mixed ...$args): never {throw new LogicException('MySQL charset discovery is outside this SQL control.');}
function reset_mbstring_encoding(mixed ...$args): never {throw new LogicException('MySQL charset discovery is outside this SQL control.');}
function wp_get_wp_version(mixed ...$args): never {throw new LogicException('Database server validation is outside this SQL control.');}
function absint(mixed $value): int {return abs((int) $value);}
function is_wp_error(mixed $value): bool {return $value instanceof WP_Error;}
function get_option(mixed ...$args): never {throw new LogicException('Plugin options are outside this SQL control.');}
function update_option(mixed ...$args): never {throw new LogicException('Plugin options are outside this SQL control.');}
function wp_normalize_path(mixed ...$args): never {throw new LogicException('Plugin paths are outside this SQL control.');}
function trailingslashit(mixed ...$args): never {throw new LogicException('Plugin paths are outside this SQL control.');}
function __(mixed $text): mixed {return $text;}
function _doing_it_wrong(mixed ...$args): never {throw new LogicException('Unexpected SQL diagnostic: ' . json_encode($args));}
function wp_load_translations_early(): void {}
function wp_debug_backtrace_summary(mixed ...$args): never {throw new LogicException('SQL debug backtraces are outside this control.');}
function wp_die(mixed ...$args): never {throw new LogicException('SQL connection errors are outside this control.');}
function is_multisite(): bool {return false;}
function wp_json_encode(mixed $data, mixed $flags = 0, mixed $depth = 512): mixed {return json_encode($data, (int) $flags, (int) $depth);}
$wp_filter = wp_aot_empty_request_array();
$wp_actions = wp_aot_empty_request_array();
$wp_filters = wp_aot_empty_request_array();
$wp_current_filter = wp_aot_empty_request_array();
