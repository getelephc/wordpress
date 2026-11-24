<?php
define('OBJECT','OBJECT');
function absint(mixed $value):int{return abs((int)$value);}
function get_post_types(mixed ...$arguments):array{return array('post'=>'post','page'=>'page','fixture'=>'fixture');}
class wpdb {
 public mixed $name;public mixed $posts='wp_posts';public mixed $postmeta='wp_postmeta';public mixed $comments='wp_comments';public mixed $commentmeta='wp_commentmeta';public mixed $terms='wp_terms';public mixed $term_taxonomy='wp_term_taxonomy';public mixed $term_relationships='wp_term_relationships';public mixed $users='wp_users';public mixed $usermeta='wp_usermeta';public mixed $prefix='wp_';
 public function __construct(mixed $name){$this->name=$name;}
 public mixed $replace_stage='';public mixed $replacement=null;public mixed $used=false;
 public function placeholder_escape():string{global $wpdb,$query_cache_trace83;$query_cache_trace83[]=array('placeholder',get_class($this),$this->name);if($this->replace_stage==='placeholder'&&!$this->used){$this->used=true;$wpdb=$this->replacement;}return '{'.$this->name.'}';}
 public function remove_placeholder_escape(mixed $value):string{global $wpdb,$query_cache_trace83;$query_cache_trace83[]=array('remove',get_class($this),$this->name,$value);if($this->replace_stage==='remove'&&!$this->used){$this->used=true;$wpdb=$this->replacement;}return str_replace($this->placeholder_escape(),'%',(string)$value);}
 public function query(mixed ...$arguments):mixed{throw new LogicException('Query execution is outside cache-key control.');}
 public function prepare(mixed ...$arguments):mixed{throw new LogicException('SQL preparation is outside cache-key control.');}
 public function get_var(mixed ...$arguments):mixed{throw new LogicException('SQL reads are outside cache-key control.');}
 public function get_col(mixed ...$arguments):mixed{throw new LogicException('SQL reads are outside cache-key control.');}
 public function get_results(mixed ...$arguments):mixed{throw new LogicException('SQL reads are outside cache-key control.');}
 public function esc_like(mixed ...$arguments):mixed{throw new LogicException('SQL matching is outside cache-key control.');}
}
class QueryCacheDatabase83 extends wpdb {public function remove_placeholder_escape(mixed $value):string{global $query_cache_trace83;$query_cache_trace83[]=array('derived-remove',$this->name);return parent::remove_placeholder_escape($value);}}
class WP_Tax_Query {
 public mixed $queries=array();public mixed $queried_terms=array();
 public function __construct(mixed ...$arguments){throw new LogicException('Taxonomy construction is outside cache-key control.');}
 public function get_sql(mixed ...$arguments):array{throw new LogicException('Taxonomy SQL is outside cache-key control.');}
}
class WP_Meta_Query {
 public mixed $queries=array();
 public function __construct(mixed ...$arguments){throw new LogicException('Metadata construction is outside cache-key control.');}
 public function parse_query_vars(mixed ...$arguments):mixed{throw new LogicException('Metadata parsing is outside cache-key control.');}
 public function get_clauses(mixed ...$arguments):array{throw new LogicException('Metadata SQL is outside cache-key control.');}
 public function get_sql(mixed ...$arguments):array{throw new LogicException('Metadata SQL is outside cache-key control.');}
}
class WP_Date_Query {
 public function __construct(mixed ...$arguments){throw new LogicException('Date construction is outside cache-key control.');}
 public function get_sql(mixed ...$arguments):string{throw new LogicException('Date SQL is outside cache-key control.');}
}
class WP_Post {public mixed $ID=0;public mixed $post_author=0;public mixed $post_content='';public mixed $post_date='';public mixed $post_type='post';public mixed $filter='raw';}
class WP_Term {public mixed $term_id=0;public mixed $taxonomy='';public mixed $name='';public mixed $slug='';}
class WP_User {public mixed $ID=0;}
class WP_Post_Type {public mixed $name='';public mixed $labels=null;public mixed $cap=null;}
class WP_Error {}
function wp_parse_args(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_parse_args');}
function wp_checkdate(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_checkdate');}
function get_post_type_object(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_post_type_object');}
function is_admin(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: is_admin');}
function wp_is_serving_rest_request(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_is_serving_rest_request');}
function get_option(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_option');}
function get_page_by_path(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_page_by_path');}
function sanitize_key(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: sanitize_key');}
function do_action_ref_array(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: do_action_ref_array');}
function get_taxonomies(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_taxonomies');}
function wp_basename(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_basename');}
function sanitize_term_field(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: sanitize_term_field');}
function do_action(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: do_action');}
function apply_filters(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: apply_filters');}
function is_user_logged_in(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: is_user_logged_in');}
function _x(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: _x');}
function remove_all_filters(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: remove_all_filters');}
function _deprecated_argument(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: _deprecated_argument');}
function __(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: __');}
function sanitize_title_for_query(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: sanitize_title_for_query');}
function get_post(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_post');}
function apply_filters_ref_array(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: apply_filters_ref_array');}
function get_taxonomies_for_attachments(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_taxonomies_for_attachments');}
function get_object_taxonomies(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_object_taxonomies');}
function get_term_by(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_term_by');}
function wp_slash(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_slash');}
function wp_parse_id_list(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_parse_id_list');}
if(!function_exists('array_last')){function array_last(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: array_last');}}
function get_user_by(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_user_by');}
function wp_post_mime_type_where(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_post_mime_type_where');}
function esc_sql(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: esc_sql');}
function get_current_user_id(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_current_user_id');}
function get_post_stati(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_post_stati');}
function current_user_can(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: current_user_can');}
function wp_cache_get_last_changed(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_cache_get_last_changed');}
function wp_cache_get_salted(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_cache_get_salted');}
function wp_cache_set_salted(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_cache_set_salted');}
function _prime_comment_caches(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: _prime_comment_caches');}
function _prime_post_parent_id_caches(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: _prime_post_parent_id_caches');}
function wp_cache_get_multiple(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_cache_get_multiple');}
function _prime_post_caches(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: _prime_post_caches');}
function wp_cache_add_multiple(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_cache_add_multiple');}
function wp_using_ext_object_cache(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_using_ext_object_cache');}
function wp_list_pluck(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_list_pluck');}
function get_post_status(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_post_status');}
function get_post_status_object(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_post_status_object');}
function current_time(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: current_time');}
function get_posts(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_posts');}
function update_post_caches(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: update_post_caches');}
function update_menu_item_cache(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: update_menu_item_cache');}
function wp_queue_posts_for_term_meta_lazyload(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: wp_queue_posts_for_term_meta_lazyload');}
function update_post_author_caches(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: update_post_author_caches');}
function get_term(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_term');}
function is_wp_error(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: is_wp_error');}
function _make_cat_compat(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: _make_cat_compat');}
function get_userdata(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_userdata');}
function _deprecated_function(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: _deprecated_function');}
function get_default_feed(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_default_feed');}
function get_queried_object_id(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_queried_object_id');}
function get_comment(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: get_comment');}
if(!function_exists('array_last')){function array_last(mixed ...$arguments):mixed{throw new LogicException('Unused query-cache collaborator: array_last');}}
