<?php

define('SCRIPT_DEBUG',false);
function sanitize_key(mixed ...$args):mixed{throw new LogicException('CSS serialization is outside this parse control.');}
function wp_json_encode(mixed ...$args):mixed{throw new LogicException('CSS compilation is outside this parse control.');}


function _wp_array_get(mixed $value,mixed $path,mixed $default=null):mixed{foreach((array)$path as$key){if(!is_array($value)||!array_key_exists($key,$value))return$default;$value=$value[$key];}return$value;}
function _wp_to_kebab_case(mixed $value):string{$text=(string)$value;if(!preg_match('/^[A-Za-z0-9-]+$/D',$text))throw new LogicException('Only ASCII style keys and preset slugs are used in this style control.');return strtolower(preg_replace('/([a-z0-9])([A-Z])/','$1-$2',$text));}
function wp_parse_args(mixed ...$args):mixed{throw new LogicException('CSS compilation options are outside this parse control.');}
function wp_strip_all_tags(mixed ...$args):mixed{throw new LogicException('CSS serialization is outside this parse control.');}
function safecss_filter_attr(mixed ...$args):mixed{throw new LogicException('CSS sanitization is outside this parse control.');}
function __ (mixed $text,mixed ...$args):mixed{return$text;}
function _doing_it_wrong(mixed ...$args):mixed{throw new LogicException('Unexpected style control diagnostic.');}

class WP_Style_Engine_CSS_Rules_Store {
 public static function get_store(mixed $name):mixed { throw new LogicException('Style stores are outside this parse control.'); }
 public function add_rule(mixed $selector,mixed $group):mixed { throw new LogicException('Style stores are outside this parse control.'); }
}
class WP_Style_Engine_CSS_Declarations {
 public function __construct(mixed $value) { throw new LogicException('CSS serialization is outside this parse control.'); }
 public function get_declarations_string(mixed ...$options):mixed { throw new LogicException('CSS serialization is outside this parse control.'); }
 public function add_declarations(mixed $values):mixed { throw new LogicException('CSS serialization is outside this parse control.'); }
}
class WP_Style_Engine_CSS_Rule {
 public function __construct(mixed $selector,mixed $values) { throw new LogicException('CSS rules are outside this parse control.'); }
 public function get_css(mixed ...$options):mixed { throw new LogicException('CSS rules are outside this parse control.'); }
}
class WP_Style_Engine_Processor {
 public function add_rules(mixed $rules):mixed { throw new LogicException('CSS compilation is outside this parse control.'); }
 public function get_css(mixed $options):mixed { throw new LogicException('CSS compilation is outside this parse control.'); }
}
