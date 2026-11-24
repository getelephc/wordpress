<?php
class WP_User { public int $ID=0; public function __construct(mixed $id) {throw new LogicException('User loading is outside this capability control.');} public function init(mixed $data):mixed {throw new LogicException('User loading is outside this capability control.');} public function has_cap(mixed ...$args):mixed {throw new LogicException('User loading is outside this capability control.');} }
class WP_Roles {public function __construct() {throw new LogicException('Role loading is outside this capability control.');}}
$capability_type=json_decode('{"map_meta_cap":false,"cap":{"edit_post":"edit_post","read_post":"read_post","delete_post":"delete_post"}}');
$capability_trace=array();
function get_post(mixed $value):mixed {if(!is_object($value))throw new LogicException('This control passes a loaded post object.');return$value;}
function get_post_type_object(mixed $name):mixed {global $capability_type;if($name!=='post')throw new LogicException('This control uses the post type.');return$capability_type;}
function get_option(mixed $name):mixed {if($name==='page_for_posts'||$name==='page_on_front')return 0;throw new LogicException('Other options are outside this capability control.');}
function is_multisite():bool{return false;}
function __(mixed $text):mixed{return$text;}
function apply_filters(mixed $name,mixed $value,mixed ...$args):mixed {global $capability_trace;if($name!=='map_meta_cap')throw new LogicException('Other filters are outside this capability control.');$capability_trace[]=array($name,$value,$args[0],$args[1],count($args[2]));return$value;}
function _doing_it_wrong(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: _doing_it_wrong');}
function get_post_meta(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_post_meta');}
function get_post_status_object(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_post_status_object');}
function get_post_status(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_post_status');}
function get_object_subtype(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_object_subtype');}
function is_protected_meta(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: is_protected_meta');}
function has_filter(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: has_filter');}
function apply_filters_deprecated(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: apply_filters_deprecated');}
function get_comment(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_comment');}
function wp_is_file_mod_allowed(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: wp_is_file_mod_allowed');}
function get_site_option(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_site_option');}
function get_term(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_term');}
function is_wp_error(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: is_wp_error');}
function get_taxonomy(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_taxonomy');}
function get_post_type_capabilities(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_post_type_capabilities');}
function wp_get_current_user(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: wp_get_current_user');}
function switch_to_blog(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: switch_to_blog');}
function restore_current_blog(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: restore_current_blog');}
function get_userdata(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: get_userdata');}
function do_action(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: do_action');}
function update_site_option(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: update_site_option');}
function _x(mixed ...$args):mixed{throw new LogicException('This API is outside the capability control: _x');}
