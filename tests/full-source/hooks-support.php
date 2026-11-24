<?php

// Explicit unused environment boundaries; hook algorithms are original.
define('WP_PLUGIN_DIR','/unused-hook-control/plugins');
define('WPMU_PLUGIN_DIR','/unused-hook-control/mu-plugins');
function __(mixed $text):mixed{return $text;}
function wp_normalize_path(mixed ...$args):never{throw new LogicException('Plugin path normalization is outside this hook control.');}
function _deprecated_hook(mixed ...$args):never{throw new LogicException('Deprecated hooks are outside this control.');}
function plugins_url(mixed ...$args):never{throw new LogicException('Plugin discovery is outside this hook control.');}
function get_option(mixed ...$args):never{throw new LogicException('Plugin options are outside this hook control.');}
function update_option(mixed ...$args):never{throw new LogicException('Plugin options are outside this hook control.');}
function trailingslashit(mixed ...$args):never{throw new LogicException('Plugin paths are outside this hook control.');}
function _doing_it_wrong(mixed ...$args):never{throw new LogicException('Unexpected hook-control diagnostic.');}
global $wp_filter,$wp_actions,$wp_filters,$wp_current_filter;
$wp_filter=wp_aot_empty_request_map();$wp_actions=wp_aot_empty_request_map();$wp_filters=wp_aot_empty_request_map();$wp_current_filter=wp_aot_empty_request_array();
