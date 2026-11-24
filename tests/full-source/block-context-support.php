<?php
class WP_Block_Type {
 public array $uses_context = array();
 public array $provides_context = array();
 public array $attributes = array();
 public array $defaults = array();
 public int $prepares = 0;
 public function prepare_attributes_for_render(mixed $values):mixed { ++$this->prepares; foreach($this->defaults as$key=>$value)if(!array_key_exists($key,$values))$values[$key]=$value;return$values; }
}
class WP_Block_Type_Registry {
 public array $types = array();
 public static function get_instance():mixed { throw new LogicException('This control supplies an explicit registry.'); }
 public function get_registered(mixed $name):mixed {return$this->types[$name]??null;}
}
function wp_aot_require_nullable_WP_Block_Type_Registry(mixed $value):?WP_Block_Type_Registry {if($value===null)return null;if(!($value instanceof WP_Block_Type_Registry))throw new TypeError('A registry instance is required.');return$value;}
class WP_Block_Supports { public static mixed $block_to_render=null; public static function get_instance():mixed {throw new LogicException('Rendering is outside this context control.');} }
class WP_HTML_Processor {
 const CONSTRUCTOR_UNLOCK_CODE='unused';
 protected mixed $bookmarks=array(); protected mixed $lexical_updates=array();
 public function __construct(mixed ...$args){throw new LogicException('HTML processing is outside this context control.');}
 public static function create_fragment(mixed ...$args):mixed {throw new LogicException('HTML processing is outside this context control.');}
 public function next_tag(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function set_bookmark(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function release_bookmark(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function get_tag(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function get_updated_html(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function seek(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function is_tag_closer(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function expects_closer(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function get_current_depth(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
 public function next_token(mixed ...$args):mixed {throw new LogicException("HTML processing is outside this context control.");}
}
class WP_HTML_Tag_Processor extends WP_HTML_Processor {public function set_attribute(mixed ...$args):mixed {throw new LogicException("HTML attributes are outside this context control.");}}
class WP_HTML_Text_Replacement {public function __construct(mixed ...$args){throw new LogicException("HTML replacements are outside this context control.");}}
function get_block_bindings_supported_attributes(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: get_block_bindings_supported_attributes");}
function get_block_bindings_source(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: get_block_bindings_source");}
function wp_kses_post(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_kses_post");}
function did_action(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: did_action");}
function wp_styles(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_styles");}
function wp_scripts(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_scripts");}
function wp_script_modules(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_script_modules");}
function apply_filters(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: apply_filters");}
function wp_parse_args(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_parse_args");}
function wp_enqueue_script(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_enqueue_script");}
function wp_enqueue_script_module(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_enqueue_script_module");}
function wp_enqueue_style(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_enqueue_style");}
function wp_interactivity_process_directives(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_interactivity_process_directives");}
function wp_dequeue_style(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_dequeue_style");}
function wp_dequeue_script(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_dequeue_script");}
function wp_dequeue_script_module(mixed ...$args):mixed {throw new LogicException("This API is outside block context construction: wp_dequeue_script_module");}
