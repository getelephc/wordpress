<?php
// Only the protected schema sanitizer is exercised; unused environment paths throw.
function __(mixed $value,mixed ...$arguments):mixed{return $value;}
function _wp_array_get(mixed $value,mixed $path,mixed $default=null):mixed{foreach((array)$path as $key){if(!is_array($value)||!array_key_exists($key,$value))return $default;$value=$value[$key];}return $value;}
function wp_is_numeric_array(mixed $value):bool{if(!is_array($value))return false;foreach(array_keys($value) as $key){if(!is_int($key))return false;}return true;}
function _wp_array_set(mixed ...$arguments):mixed{throw new LogicException('Theme mutation is outside this schema control.');}
function _wp_to_kebab_case(mixed ...$arguments):string{throw new LogicException('CSS serialization is outside this schema control.');}
function _deprecated_argument(mixed ...$arguments):mixed{throw new LogicException('Deprecated calls are outside this schema control.');}
function _deprecated_function(mixed ...$arguments):mixed{throw new LogicException('Deprecated calls are outside this schema control.');}
function _doing_it_wrong(mixed ...$arguments):mixed{throw new LogicException('Unexpected schema diagnostic.');}
function apply_filters(mixed ...$arguments):mixed{throw new LogicException('Hook execution is outside this schema control.');}
function block_has_support(mixed ...$arguments):bool{throw new LogicException('Block metadata is outside this schema control.');}
function current_theme_supports(mixed ...$arguments):bool{throw new LogicException('Theme capabilities are outside this schema control.');}
function current_user_can(mixed ...$arguments):bool{throw new LogicException('User capabilities are outside this schema control.');}
function esc_attr(mixed ...$arguments):string{throw new LogicException('Escaping is outside this schema control.');}
function esc_html(mixed ...$arguments):string{throw new LogicException('Escaping is outside this schema control.');}
function safecss_filter_attr(mixed ...$arguments):string{throw new LogicException('CSS sanitization is outside this schema control.');}
function sanitize_html_class(mixed ...$arguments):string{throw new LogicException('CSS classes are outside this schema control.');}
function sanitize_title(mixed ...$arguments):string{throw new LogicException('Preset slugs are outside this schema control.');}
function wp_get_block_css_selector(mixed ...$arguments):mixed{throw new LogicException('Block selectors are outside this schema control.');}
function wp_get_layout_definitions(mixed ...$arguments):array{throw new LogicException('Layout generation is outside this schema control.');}
function wp_get_typography_font_size_value(mixed ...$arguments):mixed{throw new LogicException('Typography compilation is outside this schema control.');}
function wp_recursive_ksort(mixed ...$arguments):mixed{throw new LogicException('Theme merging is outside this schema control.');}
function wp_style_engine_get_styles(mixed ...$arguments):array{throw new LogicException('Stylesheet generation is outside this schema control.');}
function wp_trigger_error(mixed ...$arguments):mixed{throw new LogicException('Unexpected schema diagnostic.');}
class WP_Theme_JSON_Schema {public static function migrate(mixed ...$arguments):array{throw new LogicException('Schema migration is outside this control.');}}
class WP_Block_Type {public mixed $supports=array();public mixed $styles=array();public mixed $selectors=array();public mixed $name='';public function __construct(){throw new LogicException('Block construction is outside this schema control.');}}
class WP_Block_Type_Registry {
 public static function get_instance():WP_Block_Type_Registry{throw new LogicException('Block registration is outside this schema control.');}
 public function get_all_registered():array{throw new LogicException('Block registration is outside this schema control.');}
 public function get_registered(mixed $name):mixed{throw new LogicException('Block registration is outside this schema control.');}
}
class WP_Block_Styles_Registry {
 public static function get_instance():WP_Block_Styles_Registry{throw new LogicException('Style registration is outside this schema control.');}
 public function get_all_registered():array{throw new LogicException('Style registration is outside this schema control.');}
 public function get_registered_styles_for_block(mixed $name):array{throw new LogicException('Style registration is outside this schema control.');}
}
class WP_Duotone {public static function get_filter_svg_from_preset(mixed $preset):string{throw new LogicException('SVG generation is outside this schema control.');}}

// WordPress loads its full compatibility file; declaration ordering is not exercised here.
if(!function_exists('array_last')){
 function array_last(mixed $values):mixed{throw new LogicException('CSS declaration ordering is outside this schema control.');}
}
