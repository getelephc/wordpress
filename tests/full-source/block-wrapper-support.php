<?php
/** Finite block type, safe CSS and UTF-8 escaping collaborators for the complete wrapper file. */
class WP_Block_Type {
 public mixed $attributes=array();
 public function prepare_attributes_for_render(mixed $attributes):mixed{return $attributes;}
}
class WP_Block_Type_Registry {
 private static mixed $instance=null;
 public static function get_instance():mixed{if(self::$instance===null)self::$instance=new self();return self::$instance;}
 public function get_registered(mixed $name):mixed{if($name==='fixture/wrapper')return new WP_Block_Type();return null;}
 public function get_all_registered():array{throw new LogicException('Attribute registration is outside wrapper component.');}
}
function wrapper_control_apply(mixed $block_type,mixed $attributes):mixed{return $attributes['support']??array();}
function safecss_filter_attr(mixed $css):string{
 if(!in_array($css,array('','color:red','color:blue','padding:0','color:red;padding:0','padding:0;color:blue','color:red;color:blue'),true))throw new LogicException('CSS is outside finite wrapper component: '.(string)$css);
 return (string)$css;
}
function esc_attr(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
