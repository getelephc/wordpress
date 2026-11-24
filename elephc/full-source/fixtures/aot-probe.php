<?php

function wp_aot_probe_array_snapshot(mixed $values): array {
    $snapshot = array();
    foreach ((array)$values as $key => $value) $snapshot[$key] = $value;
    return $snapshot;
}

// Begin at the original root loader with the isolated local configuration.
global $wp_registered_widgets, $_wp_deprecated_widgets_callbacks, $_wp_theme_features;
define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
define( 'WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins' );
define( 'WPMU_PLUGIN_DIR', ABSPATH . 'wp-content/mu-plugins' );
define( 'WP_LANG_DIR', ABSPATH . 'wp-content/languages' );
require __DIR__ . '/aot-platform.lfc';
// These four entry definitions executed before the platform inventory was initialized.
wp_aot_bootstrap_mark('WP_CONTENT_DIR', true);
wp_aot_bootstrap_mark('WP_PLUGIN_DIR', true);
wp_aot_bootstrap_mark('WPMU_PLUGIN_DIR', true);
wp_aot_bootstrap_mark('WP_LANG_DIR', true);
require_once ABSPATH . 'aot-arrays.php';
require_once ABSPATH . 'aot-binary.php';
require ABSPATH . 'aot-contracts.php';
require ABSPATH . 'aot-core-contracts.php';
require ABSPATH . 'aot-ai.php';
function wp_aot_post_support_cases(callable $remove): array {
    global $_wp_post_type_features;
    $saved = wp_aot_mixed($_wp_post_type_features);
    $rows = wp_aot_empty_request_array();
    try {
        foreach (array('{}', '{"book":{"autosave":true,"editor":true,"nullable":null},"page":{"editor":false}}', '{"book":{"0":false,"4":{"nested":[1,2]},"08":null,"autosave":true}}', '{"0":{"autosave":[null,false,"é"]},"page":{}}') as $json) {
            $seed = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            foreach (array('book', 'page', 'missing', '0') as $post_type) {
                foreach (array('autosave', 'editor', 'nullable', '0', 4, '08', 'absent', 'é') as $feature) {
                    $_wp_post_type_features = wp_aot_mixed($seed);
                    $alias =& $_wp_post_type_features;
                    $before = json_encode($seed);
                    $returned = $remove($post_type, $feature);
                    $after = wp_aot_mixed($_wp_post_type_features);
                    $rows[] = array($post_type, $feature, $returned, $after, wp_aot_mixed($alias), $seed, $before, json_encode($seed), array_keys((array)$after), array_keys((array)($after[$post_type] ?? array())));
                }
            }
        }
    } finally {
        $_wp_post_type_features = $saved;
    }
    return $rows;
}

// A declared array return keeps empty request maps open to their later values.
$wp_filter = wp_aot_empty_request_array();
$wp_actions = wp_aot_empty_request_array();
$wp_filters = wp_aot_empty_request_array();
$wp_current_filter = wp_aot_empty_request_array();
$wp_plugin_paths = array();
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-includes/class-wp-image-editor.php';
require_once ABSPATH . 'wp-admin/includes/image-edit.php';

class WordPressHistoryEditor extends WP_Image_Editor {
    public array $trace = array();
    public function __construct(mixed $file = 'history-fixture') {
        parent::__construct($file);
        $this->size = array('width' => 1200, 'height' => 600);
    }
    public function load(): mixed {throw new LogicException('The history fixture does not load images.');}
    public function save(mixed $destfilename = null, mixed $mime_type = null): mixed {throw new LogicException('The history fixture does not save images.');}
    public function resize(mixed $max_w, mixed $max_h, mixed $crop = false): mixed {throw new LogicException('The history fixture does not resize images.');}
    public function multi_resize(mixed $sizes): mixed {throw new LogicException('The history fixture does not resize images.');}
    public function stream(mixed $mime_type = null): mixed {throw new LogicException('The history fixture does not stream images.');}
    public function rotate(mixed $angle): mixed {$this->trace[] = array('rotate', $angle); return true;}
    public function flip(mixed $horz, mixed $vert): mixed {$this->trace[] = array('flip', $horz, $vert); return true;}
    public function crop(mixed $src_x, mixed $src_y, mixed $src_w, mixed $src_h, mixed $dst_w = null, mixed $dst_h = null, mixed $src_abs = false): mixed {
        $this->trace[] = array('crop', $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs);
        return true;
    }
}

$history_editor = new WordPressHistoryEditor();
$history_changes = json_decode('[{"r":90},{"r":-90},{"f":1},{"f":1},{"f":2},{"c":{"x":1,"y":2,"w":10,"h":20}},{"c":{"x":2,"y":3,"w":5,"h":6,"r":1.5}},{"r":30},{"r":60},{"r":0,"f":1},{"r":null,"f":1}]');
$history_filtered = wp_aot_empty_request_array();
$history_filter = static function (mixed $editor, array $changes) use (&$history_filtered): mixed {
    $history_filtered = $changes;
    return $editor;
};
add_filter('wp_image_editor_before_change', $history_filter, 10, 2);
$history_result = image_edit_apply_changes($history_editor, $history_changes);
remove_filter('wp_image_editor_before_change', $history_filter, 10);
$image_history = array('identity' => $history_result === $history_editor, 'trace' => $history_editor->trace,
    'filtered' => $history_filtered, 'normalized' => $history_changes);

$theme_scan_names = wp_aot_array_keys(search_theme_directories(true));
$theme_cached_names = wp_aot_array_keys(search_theme_directories());
wp_aot_sort($theme_scan_names);
wp_aot_sort($theme_cached_names);

// A negative id reaches original iterator normalization and validation before database access.
$user_iterator = new ArrayIterator(array('ID' => -1));
$user_iterator_error = wp_insert_user($user_iterator);
$user_iterator_validation = array('error' => $user_iterator_error instanceof WP_Error,
    'code' => $user_iterator_error->get_error_code(), 'message' => $user_iterator_error->get_error_message(),
    'consumed' => !$user_iterator->valid());

// Whitespace avoids POST fallback and returns original validation before user lookup or mail.
$password_validation_error = retrieve_password('   ');
$password_validation = array('error' => $password_validation_error instanceof WP_Error,
    'code' => $password_validation_error->get_error_code(),
    'message' => $password_validation_error->get_error_message());

// Empty credentials return the original two validation errors before account creation.
$registration_validation_error = register_new_user('', '');
$registration_validation = array('error' => $registration_validation_error instanceof WP_Error,
    'codes' => $registration_validation_error->get_error_codes(),
    'messages' => $registration_validation_error->get_error_messages());

// The original guard exits before querying comments when no post is active.
$comments_template_early_return = comments_template() === null;

// Both existing-global bookmark branches avoid a link-table lookup.
$bookmark_record = json_decode('{"link_id":101,"link_name":"Native bookmark","filter":"raw"}');
$link = $bookmark_record;
$bookmark_from_empty = get_bookmark(0);
$bookmark_from_id = get_bookmark(101);
$bookmark_globals = array('empty_identity' => $bookmark_from_empty === $bookmark_record,
    'id_identity' => $bookmark_from_id === $bookmark_record,
    'global_identity' => $link === $bookmark_record, 'record' => $bookmark_record);
unset($link);

$style_registry_probe = wp_styles();
$script_registry_probe = wp_scripts();
$registry_contracts = array(
    'styles_type' => $style_registry_probe instanceof WP_Styles,
    'styles_identity' => $style_registry_probe === wp_styles(),
    'scripts_type' => $script_registry_probe instanceof WP_Scripts,
    'scripts_identity' => $script_registry_probe === wp_scripts(),
    'missing_style' => wp_styles()->query('wp-aot-missing-style', 'registered'),
    'missing_script' => wp_scripts()->query('wp-aot-missing-script', 'registered'),
);

wp_enqueue_img_auto_sizes_contain_css_fix();
$image_css_queue = array(
    'first' => wp_styles()->queue[0] ?? null,
    'enqueued' => wp_style_is('wp-img-auto-sizes-contain', 'enqueued'),
    'registered' => wp_styles()->query('wp-img-auto-sizes-contain', 'registered') instanceof _WP_Dependency,
    'inline_css' => wp_styles()->get_data('wp-img-auto-sizes-contain', 'after'),
    'head_hook_removed' => false === has_action('wp_head', 'wp_print_auto_sizes_contain_css_fix'),
);

$cache_sql_term = json_decode('{"term_id":12001,"name":"SQL term","taxonomy":"category","object_id":701}');
$cache_core_term = new WP_Term(json_decode('{"term_id":12002,"name":"Core term","taxonomy":"category","object_id":702}'));
update_term_cache(array($cache_sql_term,$cache_core_term));
$term_cache_records = array();
foreach (array($cache_sql_term,$cache_core_term) as $cache_source_term) {
    $cache_saved_term = wp_cache_get($cache_source_term->term_id, 'terms');
    $term_cache_records[] = array('class' => get_class($cache_saved_term),
        'separate' => $cache_saved_term !== $cache_source_term,
        'source_object_id' => $cache_source_term->object_id,
        'cached_object_id' => isset($cache_saved_term->object_id),
        'name' => $cache_saved_term->name);
}
$cache_sql_term->name = 'changed SQL source';
$cache_core_term->name = 'changed core source';
$cache_sql_value_view = get_object_vars(wp_cache_get(12001, 'terms'));
$cache_core_value_view = get_object_vars(wp_cache_get(12002, 'terms'));
$term_cache_records[] = array((string)$cache_sql_value_view['name'],(string)$cache_core_value_view['name']);

$database_method_inventory = array(
    'server_info' => method_exists(wp_aot_require_wpdb($wpdb), 'db_server_info'),
    'version' => method_exists(wp_aot_require_wpdb($wpdb), 'db_version'),
);

$metadata_category = new WP_Ability_Category('native-property-fixture',array(
    'label'=>'Native category','description'=>'Property metadata fixture.','meta'=>array('fixture'=>true),
));
$metadata_ability = new WP_Ability('native/property-fixture',array(
    'label'=>'Native ability','description'=>'Property metadata fixture.','category'=>'native-property-fixture',
    'execute_callback'=>static function(mixed $input=null): mixed {return $input;},
    'permission_callback'=>static function(mixed $input=null): bool {return true;},
    'meta'=>array('fixture'=>true),
));
$property_metadata = array(
    'category'=>array($metadata_category->get_slug(),$metadata_category->get_label(),$metadata_category->get_description(),$metadata_category->get_meta()),
    'ability'=>array($metadata_ability->get_name(),$metadata_ability->get_label(),$metadata_ability->get_description(),$metadata_ability->get_category(),$metadata_ability->get_meta()),
    'term_id'=>wp_aot_property_exists($cache_core_term,'term_id'),
    'removed_object_id'=>wp_aot_property_exists(wp_cache_get(12002,'terms'),'object_id'),
);

$widget_dep_scripts = wp_scripts();
$widget_dep_styles = wp_styles();
$widget_dep_script_queue = $widget_dep_scripts->queue;
$widget_dep_style_queue = $widget_dep_styles->queue;
$widget_dependency_notices = array();
$widget_dependency_observer = static function(mixed $function_name,mixed $message,mixed $version) use (&$widget_dependency_notices): void {
    $widget_dependency_notices[] = array($function_name,$message,$version);
};
add_action('doing_it_wrong_run',$widget_dependency_observer,10,3);
$widget_dependency_checks = array();
foreach (array(
    array(array(),array()),
    array(array('wp-edit-widgets'),array()),
    array(array('wp-customize-widgets','wp-editor'),array()),
    array(array('wp-edit-widgets','wp-editor'),array('wp-edit-post')),
) as $widget_dependency_case) {
    $widget_dep_scripts->queue = $widget_dependency_case[0];
    $widget_dep_styles->queue = $widget_dependency_case[1];
    $widget_dependency_notices = array();
    $widget_dependency_result = wp_check_widget_editor_deps();
    $widget_dependency_checks[] = array($widget_dependency_result,$widget_dependency_notices,$widget_dep_scripts->queue,$widget_dep_styles->queue);
}
remove_action('doing_it_wrong_run',$widget_dependency_observer,10);
$widget_dep_scripts->queue = $widget_dep_script_queue;
$widget_dep_styles->queue = $widget_dep_style_queue;

$sidebar_cache_before_probe = $_wp_sidebars_widgets;
$_wp_sidebars_widgets = array('first'=>array('native-first','native-second','native-first'),'second'=>array('native-first'));
$widget_conversion = array();
$widget_conversion[] = array(wp_convert_widget_settings('native-first','widget_native_first',array('title'=>'Original')), $_wp_sidebars_widgets);
$widget_conversion[] = array(wp_convert_widget_settings('native-second','widget_native_second',array()), $_wp_sidebars_widgets);
$_wp_sidebars_widgets = $sidebar_cache_before_probe;

$rest_context_filters = array();
$rest_context_inputs = array(
    array('{"public":1,"private":2,"nested":{"public":3,"private":4},"unknown":5}',
        '{"type":"object","properties":{"public":{"context":["view","embed"]},"private":{"context":["edit"]},"nested":{"type":"object","context":["view","edit"],"properties":{"public":{"context":["view"]},"private":{"context":["edit"]}}}}}',false),
    array('{"0":"zero","08":"padded","private":"hidden","public":"kept"}',
        '{"type":"object","properties":{"0":{"context":["view"]},"08":{"context":["edit"]},"private":{"context":["edit"]},"public":{"context":["view"]}}}',true),
    array('[{"public":1,"private":2},{"public":3,"private":4}]',
        '{"type":"array","items":{"type":"object","context":["view"],"properties":{"public":{"context":["view"]},"private":{"context":["edit"]}}}}',false),
    array('{"pub_key":1,"private_key":2,"other":3}',
        '{"type":"object","patternProperties":{"^private_":{"context":["edit"]}},"additionalProperties":{"context":["view"]}}',false),
    array('7','{"type":"object","properties":{"unused":{"context":["edit"]}}}',false),
    array('{"keep":1,"drop":2}','{"properties":{"keep":{"context":["view"]},"drop":{"context":["edit"]}}}',false),
    array('[1,2]','{}',false),
);
foreach ($rest_context_inputs as $rest_context_input) {
    foreach (array('view','edit','embed','unknown') as $rest_context_name) {
        $rest_context_data = json_decode($rest_context_input[0],(bool)$rest_context_input[2],512,JSON_THROW_ON_ERROR);
        $rest_context_schema = json_decode($rest_context_input[1],true,512,JSON_THROW_ON_ERROR);
        $rest_context_alias = $rest_context_data;
        $rest_context_before = json_encode($rest_context_data);
        $rest_context_result = rest_filter_response_by_context($rest_context_data,$rest_context_schema,$rest_context_name);
        $rest_context_result_keys = is_array($rest_context_result) || is_object($rest_context_result) ? array_keys((array)$rest_context_result) : array();
        $rest_context_filters[] = array($rest_context_name,$rest_context_before,$rest_context_result,$rest_context_alias,$rest_context_result === $rest_context_data,$rest_context_result_keys);
    }
}

require_once ABSPATH . 'wp-includes/PHPMailer/PHPMailer.php';
$translation_mailer = new \PHPMailer\PHPMailer\PHPMailer();
$mailer_lazy_language = $translation_mailer->getTranslations();
$mailer_language_checks = array();
foreach (array('en','not-a-code','zz','dk') as $mailer_language_code) {
    $mailer_language_found = \PHPMailer\PHPMailer\PHPMailer::setLanguage($mailer_language_code);
    $mailer_language_value = $translation_mailer->getTranslations();
    $mailer_language_copy = $mailer_language_value;
    $mailer_language_copy['authenticate'] = 'local copy';
    $mailer_language_checks[] = array($mailer_language_code,$mailer_language_found,$mailer_language_value,
        $mailer_language_copy['authenticate'],$translation_mailer->getTranslations(),array_keys($mailer_language_value));
}
\PHPMailer\PHPMailer\PHPMailer::setLanguage('en');

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
class WordPressListTableProbe extends WP_List_Table {
    public function __construct() {}
}
$list_table = new WordPressListTableProbe();
$list_table->items = wp_aot_mixed(array('preserved' => 7));
$list_table_items =& $list_table->items;
$list_table_snapshot = $list_table->items;
$list_table_compatibility = array();
foreach (array('_args','_pagination_args','screen','_actions','_pagination') as $list_field) {
    foreach (array(null,42,array('seed'=>1)) as $list_value) {
        $list_table->__set($list_field,$list_value);
        $list_before = array($list_table->__get($list_field),$list_table->__isset($list_field));
        $list_removed = $list_table->__unset($list_field);
        $list_missing = $list_table->__isset($list_field);
        $list_replacement = array('field'=>$list_field);
        $list_table->__set($list_field,$list_replacement);
        $list_table_compatibility[] = array($list_field,$list_value,$list_before,$list_removed,$list_missing,
            $list_table->__get($list_field),$list_table->__isset($list_field),$list_table->items,$list_table_items,$list_table_snapshot);
    }
}
$list_table_items['live'] = 9;
$list_table_compatibility[] = array($list_table->items,$list_table_items,$list_table_snapshot);
unset($list_table_items);

$user_query_compat_probe = new WP_User_Query();
$user_query_compat_defaults = array($user_query_compat_probe->__get('results'),$user_query_compat_probe->__get('total_users'));
$user_query_compat_probe->query_vars = wp_aot_mixed(array('preserved'=>array('x'=>7)));
$user_query_compat_alias =& $user_query_compat_probe->query_vars;
$user_query_compat_snapshot = $user_query_compat_probe->query_vars;
$user_query_compatibility = array();
foreach(array('results','total_users') as $user_query_compat_field) foreach(array(null,false,0,'é',array(),array('nested'=>array('x'=>1))) as $user_query_compat_value) {
    $user_query_compat_probe->__set($user_query_compat_field,$user_query_compat_value);
    $user_query_compat_before=array($user_query_compat_probe->__get($user_query_compat_field),$user_query_compat_probe->__isset($user_query_compat_field));
    $user_query_compat_removed=$user_query_compat_probe->__unset($user_query_compat_field);
    $user_query_compat_missing=$user_query_compat_probe->__isset($user_query_compat_field);
    $user_query_compat_replacement=array('field'=>$user_query_compat_field);
    $user_query_compat_probe->__set($user_query_compat_field,$user_query_compat_replacement);
    $user_query_compatibility[]=array($user_query_compat_field,$user_query_compat_value,$user_query_compat_before,
        $user_query_compat_removed,$user_query_compat_missing,$user_query_compat_probe->__get($user_query_compat_field),
        $user_query_compat_probe->__isset($user_query_compat_field),$user_query_compat_probe->query_vars,
        $user_query_compat_alias,$user_query_compat_snapshot);
}
$user_query_compat_bad=array($user_query_compat_probe->__get('fixture_missing'),$user_query_compat_probe->__set('fixture_missing','ignored'),
    $user_query_compat_probe->__isset('fixture_missing'),$user_query_compat_probe->__unset('fixture_missing'));
$user_query_compat_alias['live']=9;
$user_query_compat_alias_state=array($user_query_compat_probe->query_vars,$user_query_compat_alias,$user_query_compat_snapshot);
unset($user_query_compat_alias);

$user_field_deprecations = array();
$user_field_observer = static function (mixed $function,mixed $message,mixed $version) use (&$user_field_deprecations): void {
    $user_field_deprecations[] = array($function,$message,$version);
};
add_action('deprecated_argument_run',$user_field_observer,10,3);
$user_field_probe = new WP_User();
$user_field_removals = array();
foreach (array('{}','{"final":1,"null_value":null,"false_value":false,"0":"zero","08":"leading","id":7}',
    '{"final":{"nested":[1,null]},"id":null,"only":true}') as $user_record_json) {
    foreach (array('final','null_value','false_value','0','08','missing','id') as $user_field) {
        $user_field_probe->data = json_decode($user_record_json,false,512,JSON_THROW_ON_ERROR);
        $user_field_alias = $user_field_probe->data;
        $user_field_snapshot = (array) $user_field_probe->data;
        $user_field_result = $user_field_probe->__unset($user_field);
        $user_field_removals[] = array($user_record_json,$user_field,$user_field_result,
            json_encode($user_field_probe->data),json_encode($user_field_alias),json_encode($user_field_snapshot),
            $user_field_alias === $user_field_probe->data,array_keys((array)$user_field_probe->data),$user_field_probe->ID);
    }
}
remove_action('deprecated_argument_run',$user_field_observer,10);

$query_reset_probe = new WP_Query();
$query_reset_probe->queried_object = json_decode('{"label":"old-query"}',false,512,JSON_THROW_ON_ERROR);
$query_reset_probe->queried_object_id = 7;
$query_reset_probe->post = json_decode('{"label":"old-post"}',false,512,JSON_THROW_ON_ERROR);
$query_reset_probe->comment = json_decode('{"label":"old-comment"}',false,512,JSON_THROW_ON_ERROR);
$query_reset_probe->comments = array('old-comments');
$query_reset_probe->posts = array('old-posts');
$query_reset_probe->query = 'old-query';
$query_reset_probe->request = 'old-sql';
$query_reset_object =& $query_reset_probe->queried_object;
$query_reset_id =& $query_reset_probe->queried_object_id;
$query_reset_post =& $query_reset_probe->post;
$query_reset_comment =& $query_reset_probe->comment;
$query_reset_comments =& $query_reset_probe->comments;
$query_reset_snapshot = array((array)$query_reset_object,$query_reset_id,(array)$query_reset_post,(array)$query_reset_comment,wp_aot_probe_array_snapshot($query_reset_comments));
$query_reset_result = $query_reset_probe->init();
// Inspect the original public property view after the original reset.
$query_reset_vars = get_object_vars($query_reset_probe);
$query_reset_presence = array(isset($query_reset_vars['queried_object']),isset($query_reset_vars['queried_object_id']),
    isset($query_reset_vars['post']),isset($query_reset_vars['comment']),isset($query_reset_vars['comments']),
    isset($query_reset_vars['posts']),isset($query_reset_vars['query']),isset($query_reset_vars['request']));
$query_reset_keys = array();
foreach (array('queried_object','queried_object_id','post','comment','comments','posts','query','request') as $query_reset_name) {
    $query_reset_keys[] = array_key_exists($query_reset_name,$query_reset_vars);
}
$query_reset_old_aliases = array(json_encode($query_reset_object),$query_reset_id,json_encode($query_reset_post),json_encode($query_reset_comment),wp_aot_probe_array_snapshot($query_reset_comments));
$query_reset_probe->queried_object = json_decode('{"fresh":true}',false,512,JSON_THROW_ON_ERROR);
$query_reset_probe->queried_object_id = 99;
$query_reset_probe->post = json_decode('{"fresh":"post"}',false,512,JSON_THROW_ON_ERROR);
$query_reset_probe->comment = json_decode('{"fresh":"comment"}',false,512,JSON_THROW_ON_ERROR);
$query_reset_probe->comments = array('fresh-comments');
$query_reset_object->label = 'detached-query';
$query_reset_id = 8;
$query_reset_post->label = 'detached-post';
$query_reset_comment->label = 'detached-comment';
$query_reset_comments[] = 'detached-comments';
$query_reset_state = array($query_reset_result,$query_reset_presence,$query_reset_keys,$query_reset_snapshot,$query_reset_old_aliases,
    $query_reset_probe->queried_object,$query_reset_probe->queried_object_id,$query_reset_probe->post,$query_reset_probe->comment,$query_reset_probe->comments,
    $query_reset_object,$query_reset_id,$query_reset_post,$query_reset_comment,$query_reset_comments,
    $query_reset_probe->query_vars,$query_reset_probe->post_count,$query_reset_probe->current_post,$query_reset_probe->in_the_loop,$query_reset_probe->before_loop,
    $query_reset_probe->comment_count,$query_reset_probe->current_comment,$query_reset_probe->found_posts,$query_reset_probe->max_num_pages,$query_reset_probe->max_num_comment_pages,
    $query_reset_probe->is_single,$query_reset_probe->is_archive,$query_reset_probe->is_home);
unset($query_reset_object,$query_reset_id,$query_reset_post,$query_reset_comment,$query_reset_comments);

require_once ABSPATH . 'wp-includes/class-wp-customize-setting.php';
require_once ABSPATH . 'wp-includes/customize/class-wp-customize-nav-menu-item-setting.php';
class WordPressMenuItemSettingProbe extends WP_Customize_Nav_Menu_Item_Setting {
    public function __construct(mixed $payload,int $post_id) {
        $this->post_id = $post_id;
        $this->value = wp_aot_array_merge(array($this->default,(array)$payload));
    }
    public function value(): mixed {return wp_aot_array_input($this->value);}
}
$menu_item_trace = array();
$menu_item_attr_filter = static function(mixed $value) use (&$menu_item_trace): mixed {
    $menu_item_trace[] = array('attribute',$value);
    return (string)$value . '|attr';
};
$menu_item_desc_filter = static function(mixed $value) use (&$menu_item_trace): mixed {
    $menu_item_trace[] = array('description',$value);
    return (string)$value . '|desc';
};
$menu_item_post_filter = static function(WP_Post $post) use (&$menu_item_trace): mixed {
    $menu_item_trace[] = array('post',$post->ID,$post->post_type);
    return $post;
};
add_filter('nav_menu_attr_title',$menu_item_attr_filter);
add_filter('nav_menu_description',$menu_item_desc_filter);
add_filter('wp_setup_nav_menu_item',$menu_item_post_filter);
$menu_item_conversions = array();
foreach (array(
    array(17,array('title'=>'Menu','original_title'=>'Original','status'=>'publish','position'=>3,'nav_menu_term_id'=>9,
        'classes'=>'one  two','description'=>'one two','attr_title'=>'hint','url'=>'https://example.test/menu','post_author'=>'7','type_label'=>'Custom link')),
    array(-7,array('title'=>'','original_title'=>'Original &amp; title','status'=>'draft','position'=>0,'nav_menu_term_id'=>2,
        'classes'=>'','description'=>str_repeat('word ',205),'attr_title'=>'','url'=>'https://example.test/draft','post_author'=>'7','type_label'=>'Custom link')),
    array(23,array('title'=>'Array classes','original_title'=>'','status'=>'private','position'=>2,'nav_menu_term_id'=>4,
        'classes'=>array('first','second'),'description'=>'','attr_title'=>'array','url'=>'https://example.test/array','post_author'=>'7','type_label'=>'Custom link'))
) as $menu_item_case) {
    $menu_item_setting = new WordPressMenuItemSettingProbe($menu_item_case[1],$menu_item_case[0]);
    $menu_item_input = $menu_item_setting->value();
    $menu_item_before = json_encode($menu_item_input);
    $menu_item_post = $menu_item_setting->value_as_wp_post_nav_menu_item();
    $menu_item_vars = get_object_vars($menu_item_post);
    $menu_item_conversions[] = array($menu_item_case[0],$menu_item_before,get_class($menu_item_post),$menu_item_vars,
        json_encode($menu_item_setting->value()),$menu_item_input,array_keys($menu_item_vars));
}
remove_filter('nav_menu_attr_title',$menu_item_attr_filter);
remove_filter('nav_menu_description',$menu_item_desc_filter);
remove_filter('wp_setup_nav_menu_item',$menu_item_post_filter);

require_once ABSPATH . 'wp-includes/customize/class-wp-customize-nav-menu-setting.php';
class WordPressMenuSettingProbe extends WP_Customize_Nav_Menu_Setting {
    private mixed $fixture_value;
    public int $fixture_reads = 0;
    public function __construct(mixed $payload,int $term_id,int $previous_id,int $blog_id) {
        $this->term_id = $term_id;
        $this->previous_term_id = $previous_id;
        $this->_previewed_blog_id = $blog_id;
        $this->fixture_value = $payload === false ? false : wp_aot_array_merge(array($this->default,(array)$payload));
    }
    public function options_value(mixed $options,mixed $menu_id,mixed $auto_add): mixed {
        return $this->filter_nav_menu_options_value($options,$menu_id,$auto_add);
    }
    public function value(): mixed {
        ++$this->fixture_reads;
        return $this->fixture_value === false ? false : wp_aot_array_input($this->fixture_value);
    }
}
$menu_previews = array();
foreach (array(
    array('replace',2,0,array('name'=>'Updated'),array(),false,false,false),
    array('insert',9,0,array('name'=>'Inserted'),array(),false,false,false),
    array('empty',7,0,array('name'=>'Only'),array(),true,false,false),
    array('previous',17,-2,array('name'=>'Previous'),array(),false,true,false),
    array('delete',2,0,false,array(),false,false,false),
    array('delete-absent',8,0,false,array(),false,false,false),
    array('delete-previous',17,-2,false,array(),false,true,false),
    array('sort',2,0,array('name'=>'Zulu'),array('orderby'=>'name'),false,false,false),
    array('other-blog',2,0,array('name'=>'Ignored'),array('orderby'=>'name'),false,false,true)
) as $menu_preview_case) {
    $menu_preview_first = new WP_Term(json_decode('{"term_id":1,"name":"Alpha"}',false,512,JSON_THROW_ON_ERROR));
    $menu_preview_middle = new stdClass();
    $menu_preview_middle->term_id = $menu_preview_case[6] ? -2 : 2;
    $menu_preview_middle->name = 'Beta';
    $menu_preview_last = new WP_Term(json_decode('{"term_id":3,"name":"Gamma"}',false,512,JSON_THROW_ON_ERROR));
    $menu_preview_input = $menu_preview_case[5] ? array() : array($menu_preview_first,$menu_preview_middle,$menu_preview_last);
    $menu_preview_snapshot = $menu_preview_input;
    $menu_preview_before = json_encode($menu_preview_input,JSON_THROW_ON_ERROR);
    $menu_preview_setting = new WordPressMenuSettingProbe($menu_preview_case[3],$menu_preview_case[1],$menu_preview_case[2],
        get_current_blog_id()+($menu_preview_case[7]?1:0));
    $menu_preview_result = $menu_preview_setting->filter_wp_get_nav_menus($menu_preview_input,$menu_preview_case[4]);
    $menu_preview_ids = array();$menu_preview_names = array();$menu_preview_classes = array();$menu_preview_identity = array();
    foreach ($menu_preview_result as $menu_preview_record) {
        $menu_preview_ids[] = $menu_preview_record->term_id;
        $menu_preview_names[] = $menu_preview_record->name;
        $menu_preview_classes[] = get_class($menu_preview_record);
        $menu_preview_identity[] = array($menu_preview_record===$menu_preview_first,$menu_preview_record===$menu_preview_middle,$menu_preview_record===$menu_preview_last);
    }
    $menu_previews[] = array('case'=>$menu_preview_case[0],'ids'=>$menu_preview_ids,'names'=>$menu_preview_names,
        'classes'=>$menu_preview_classes,'keys'=>array_keys($menu_preview_result),'identity'=>$menu_preview_identity,
        'input_before'=>$menu_preview_before,'input_after'=>json_encode($menu_preview_input,JSON_THROW_ON_ERROR),
        'snapshot'=>json_encode($menu_preview_snapshot,JSON_THROW_ON_ERROR),'reads'=>$menu_preview_setting->fixture_reads);
}

$menu_option_cases = array();
foreach (array('{}','{"keep":{"x":1}}','{"auto_add":[],"keep":"original"}',
    '{"auto_add":[2,3,2]}','{"auto_add":[0,"2",false,null,2]}','{"auto_add":null,"keep":false}',
    '{"auto_add":{"8":2,"12":3},"keep":"sparse"}','{"auto_add":{"-2":2,"4":17},"keep":"negative"}') as $menu_option_json) {
    foreach (array(2,'2',17,-2) as $menu_option_id) foreach (array(false,true) as $menu_option_auto) foreach (array(2,17) as $menu_option_term) {
        $menu_option_input = json_decode($menu_option_json,true,512,JSON_THROW_ON_ERROR);
        $menu_option_snapshot = $menu_option_input;
        $menu_option_before = json_encode($menu_option_input,JSON_THROW_ON_ERROR);
        $menu_option_setting = new WordPressMenuSettingProbe(array(),$menu_option_term,0,get_current_blog_id());
        $menu_option_result = $menu_option_setting->options_value($menu_option_input,$menu_option_id,$menu_option_auto);
        $menu_option_cases[] = array('input'=>$menu_option_json,'menu_id'=>$menu_option_id,'auto_add'=>$menu_option_auto,'term_id'=>$menu_option_term,
            'result'=>$menu_option_result,'keys'=>array_keys($menu_option_result['auto_add']),'values'=>array_values($menu_option_result['auto_add']),
            'before'=>$menu_option_before,'after'=>json_encode($menu_option_input,JSON_THROW_ON_ERROR),
            'snapshot'=>json_encode($menu_option_snapshot,JSON_THROW_ON_ERROR),'reads'=>$menu_option_setting->fixture_reads);
    }
}

require_once ABSPATH . 'wp-includes/class-wp-customize-panel.php';
require_once ABSPATH . 'wp-includes/class-wp-customize-section.php';
class WordPressThemeSettingProbe extends WP_Customize_Setting {
    public function __construct(mixed $supports) {
        $this->capability = '';
        $this->theme_supports = $supports;
    }
}
class WordPressThemePanelProbe extends WP_Customize_Panel {
    public function __construct(mixed $supports) {$this->capability='';$this->theme_supports=$supports;}
}
class WordPressThemeSectionProbe extends WP_Customize_Section {
    public function __construct(mixed $supports) {$this->capability='';$this->theme_supports=$supports;}
}
$theme_features_saved = $_wp_theme_features;
$_wp_theme_features = wp_aot_mixed(array('aot-plain'=>true,'aot-context'=>array('context-data'),'aot-deny'=>false,
    'html5'=>array(array('search-form','comment-list')),'post-thumbnails'=>array(array('post','page')),
    'custom-logo'=>array(array('flex-width'=>true,'flex-height'=>false))));
$theme_check_trace = wp_aot_mixed(array());
$theme_check_filter = static function(mixed $supports,mixed $args,mixed $data) use (&$theme_check_trace): mixed {
    $theme_check_trace[] = array($supports,$args,$data);
    return $data === false ? 0 : $supports;
};
foreach (array('aot-plain','aot-context','aot-deny') as $theme_check_feature) {
    add_filter('current_theme_supports-'.$theme_check_feature,$theme_check_filter,10,3);
}
$theme_capability_cases = array();
foreach (array(
    array('empty-string',''),array('zero-string','0'),array('null',null),array('false',false),array('empty-array',array()),
    array('missing','aot-missing'),array('plain','aot-plain'),array('html5-present',array('html5','search-form')),
    array('html5-missing',array('html5','missing')),array('thumbnails-present',array('post-thumbnails','post')),
    array('thumbnails-missing',array('post-thumbnails','book')),array('logo-present',array('custom-logo','flex-width')),
    array('logo-false',array('custom-logo','flex-height')),array('context-full',array('aot-context',null,1,true,array('x'=>2))),
    array('context-named',array('feature'=>'aot-context','extra'=>7,'flag'=>false)),
    array('context-mixed',array(0=>'aot-context',1=>2,'named'=>null)),
    array('context-long',array('aot-context',1,2,3,4,5,6,7,8,9,10,11)),array('deny','aot-deny'),
    array('named-prefix',array('feature'=>'aot-context')),array('support-array',array('aot-plain'))
) as $theme_capability_case) {
    foreach (array('setting','panel','section') as $theme_check_kind) {
        if ($theme_check_kind === 'setting') $theme_check_setting = wp_aot_mixed(new WordPressThemeSettingProbe($theme_capability_case[1]));
        elseif ($theme_check_kind === 'panel') $theme_check_setting = wp_aot_mixed(new WordPressThemePanelProbe($theme_capability_case[1]));
        else $theme_check_setting = wp_aot_mixed(new WordPressThemeSectionProbe($theme_capability_case[1]));
        $theme_check_start = count($theme_check_trace);
        $theme_capability_cases[] = array('case'=>$theme_capability_case[0],'kind'=>$theme_check_kind,'result'=>$theme_check_setting->check_capabilities(),
            'trace'=>wp_aot_array_slice($theme_check_trace,$theme_check_start));
    }
}
foreach (array('aot-plain','aot-context','aot-deny') as $theme_check_feature) {
    remove_filter('current_theme_supports-'.$theme_check_feature,$theme_check_filter,10);
}
$_wp_theme_features = $theme_features_saved;

require_once ABSPATH . 'wp-includes/class-wp-customize-manager.php';
require_once ABSPATH . 'wp-includes/class-wp-customize-control.php';
function wp_aot_template_probe_record(string $event,mixed $object,mixed $args=null): void {
    global $template_probe_trace,$template_probe_manager;
    $template_probe_trace[] = array($event,get_class($object),$object->id,$object->manager===$template_probe_manager,
        $args,$object->type,is_callable($object->active_callback));
}
class WordPressTemplatePanelProbe extends WP_Customize_Panel {
    public function __construct(mixed $manager,mixed $id,mixed $args=array()) {
        parent::__construct($manager,$id,$args);wp_aot_template_probe_record('construct',$this,$args);
    }
    protected function content_template(): mixed {wp_aot_template_probe_record('panel-content',$this);echo 'probe-panel-content';return null;}
    protected function render_template(): mixed {wp_aot_template_probe_record('panel-container',$this);echo 'probe-panel-container';return null;}
}
class WordPressTemplateSectionProbe extends WP_Customize_Section {
    public function __construct(mixed $manager,mixed $id,mixed $args=array()) {
        parent::__construct($manager,$id,$args);wp_aot_template_probe_record('construct',$this,$args);
    }
    protected function render_template(): mixed {wp_aot_template_probe_record('section-container',$this);echo 'probe-section-container';return null;}
}
class WordPressTemplateControlProbe extends WP_Customize_Control {
    public function __construct(mixed $manager,mixed $id,mixed $args=array()) {
        parent::__construct($manager,$id,$args);wp_aot_template_probe_record('construct',$this,$args);
    }
    protected function content_template(): mixed {wp_aot_template_probe_record('control-content',$this);echo 'probe-control-content';return null;}
}
$template_probe_reflection = new ReflectionClass(WP_Customize_Manager::class);
$template_constructor_cases = array();
foreach (array(array(),array('panel','section','control'),array('panel','panel','section','control','control'),array('panel','section')) as $template_probe_registration) {
    $template_probe_manager = $template_probe_reflection->newInstanceWithoutConstructor();
    foreach ($template_probe_registration as $template_probe_kind) {
        if ($template_probe_kind==='panel') $template_probe_manager->register_panel_type('WordPressTemplatePanelProbe');
        elseif ($template_probe_kind==='section') $template_probe_manager->register_section_type('WordPressTemplateSectionProbe');
        else $template_probe_manager->register_control_type('WordPressTemplateControlProbe');
    }
    foreach (array('panel','section','control') as $template_probe_kind) {
        $template_probe_trace = wp_aot_mixed(array());
        ob_start();
        if ($template_probe_kind==='panel') $template_probe_return=$template_probe_manager->render_panel_templates();
        elseif ($template_probe_kind==='section') $template_probe_return=$template_probe_manager->render_section_templates();
        else $template_probe_return=$template_probe_manager->render_control_templates();
        $template_probe_html = ob_get_clean();
        $template_constructor_cases[] = array('registered'=>$template_probe_registration,'kind'=>$template_probe_kind,
            'return'=>$template_probe_return,'trace'=>$template_probe_trace,'html'=>$template_probe_html);
    }
}

class WordPressThemeJsonCacheProbe extends WP_Theme_JSON_Resolver {
    public static function probe_files(mixed $path): mixed {return parent::read_json_file($path);}
    public static function probe_blocks(mixed $origin): mixed {return parent::has_same_registered_blocks($origin);}
    public static function probe_state(): mixed {return array(wp_aot_mixed(static::$theme_json_file_cache),wp_aot_mixed(static::$blocks_cache));}
    public static function probe_seed(mixed $files,mixed $blocks): void {static::$theme_json_file_cache=$files;static::$blocks_cache=$blocks;}
}
class WordPressIsolatedThemeJsonCacheProbe extends WordPressThemeJsonCacheProbe {
    protected static $theme_json_file_cache=array();
    protected static $blocks_cache=array();
}
function wp_aot_theme_cache_state(bool $isolated): mixed {
    return $isolated?WordPressIsolatedThemeJsonCacheProbe::probe_state():WordPressThemeJsonCacheProbe::probe_state();
}
function wp_aot_theme_cache_seed(bool $isolated,mixed $files,mixed $blocks): void {
    if($isolated) WordPressIsolatedThemeJsonCacheProbe::probe_seed($files,$blocks);
    else WordPressThemeJsonCacheProbe::probe_seed($files,$blocks);
}
function wp_aot_theme_cache_read(bool $isolated,mixed $path): mixed {
    return $isolated?WordPressIsolatedThemeJsonCacheProbe::probe_files($path):WordPressThemeJsonCacheProbe::probe_files($path);
}
function wp_aot_theme_cache_blocks(bool $isolated,string $origin): mixed {
    return $isolated?WordPressIsolatedThemeJsonCacheProbe::probe_blocks($origin):WordPressThemeJsonCacheProbe::probe_blocks($origin);
}
$theme_cache_saved = WordPressThemeJsonCacheProbe::probe_state();
$theme_json_cache_cases = array();
foreach(array(false,true) as $theme_cache_isolated) {
    foreach(array('cached-file','0') as $theme_cache_path) foreach(array(null,false,32,'cached',array(),array('nested'=>array('x'=>1))) as $theme_cache_value) {
        wp_aot_theme_cache_seed($theme_cache_isolated,array($theme_cache_path=>$theme_cache_value),array('core'=>array()));
        $theme_cache_first=wp_aot_theme_cache_read($theme_cache_isolated,$theme_cache_path);
        $theme_cache_copy=$theme_cache_first;
        if(is_array($theme_cache_copy))$theme_cache_copy['copy-only']=true;
        $theme_json_cache_cases[]=array('kind'=>'hit','isolated'=>$theme_cache_isolated,'path'=>$theme_cache_path,
            'input'=>$theme_cache_value,'first'=>$theme_cache_first,'copy'=>$theme_cache_copy,
            'second'=>wp_aot_theme_cache_read($theme_cache_isolated,$theme_cache_path),
            'state'=>wp_aot_theme_cache_state($theme_cache_isolated));
    }
    foreach(array('wp-includes/theme.json','wp-content/themes/twentytwentyfive/theme.json') as $theme_cache_relative) {
        wp_aot_theme_cache_seed($theme_cache_isolated,array(),array('core'=>array()));
        $theme_cache_first=wp_aot_theme_cache_read($theme_cache_isolated,ABSPATH.$theme_cache_relative);
        $theme_cache_second=wp_aot_theme_cache_read($theme_cache_isolated,ABSPATH.$theme_cache_relative);
        $theme_cache_state=wp_aot_theme_cache_state($theme_cache_isolated);
        $theme_json_cache_cases[]=array('kind'=>'file','isolated'=>$theme_cache_isolated,'path'=>$theme_cache_relative,
            'first_sha256'=>hash('sha256',json_encode($theme_cache_first)),
            'second_sha256'=>hash('sha256',json_encode($theme_cache_second)),
            'cached_sha256'=>hash('sha256',json_encode($theme_cache_state[0][ABSPATH.$theme_cache_relative])),
            'keys'=>array_keys($theme_cache_first),'cache_size'=>count($theme_cache_state[0]));
    }
    $theme_cache_block_names=array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    foreach(array('core','theme','invalid') as $theme_cache_origin) foreach(array('empty','complete','extra') as $theme_cache_mode) {
        $theme_cache_registered=array();
        if($theme_cache_mode!=='empty')foreach($theme_cache_block_names as $theme_cache_name)$theme_cache_registered[$theme_cache_name]=true;
        if($theme_cache_mode==='extra')$theme_cache_registered['fixture/extra']=true;
        wp_aot_theme_cache_seed($theme_cache_isolated,array(),array('core'=>$theme_cache_registered,'theme'=>$theme_cache_registered));
        $theme_cache_first=wp_aot_theme_cache_blocks($theme_cache_isolated,$theme_cache_origin);
        $theme_cache_second=wp_aot_theme_cache_blocks($theme_cache_isolated,$theme_cache_origin);
        $theme_cache_state=wp_aot_theme_cache_state($theme_cache_isolated);
        $theme_json_cache_cases[]=array('kind'=>'blocks','isolated'=>$theme_cache_isolated,'origin'=>$theme_cache_origin,
            'mode'=>$theme_cache_mode,'first'=>$theme_cache_first,'second'=>$theme_cache_second,
            'registered'=>$theme_cache_block_names,'cache'=>$theme_cache_state[1]);
    }
}
WordPressThemeJsonCacheProbe::probe_seed($theme_cache_saved[0],$theme_cache_saved[1]);

// Exercise parser state transitions and live capability maps through original public APIs.
$html_state_cases = array();
foreach (array(
    '<template><caption>A</caption></template>',
    '<template><colgroup><col></colgroup></template>',
    '<template><tbody><tr><td>0</td></tr></tbody></template>',
    '<template><tr><th>é漢</th></tr></template>',
    '<template><!-- note --><style>.a{color:red}</style><p>text</p></template>',
    '<template><template><div data-x="0">nested</div></template></template>'
) as $html_state_input) {
    $html_state_processor = WP_HTML_Processor::create_fragment($html_state_input);
    if (!($html_state_processor instanceof WP_HTML_Processor)) {
        throw new LogicException('The HTML state fixture requires the standard fragment context.');
    }
    $html_state_tokens = array();
    while ($html_state_processor->next_token()) {
        if (count($html_state_tokens) >= 128) throw new LogicException('HTML state fixture token budget exceeded.');
        $html_state_tag = $html_state_processor->get_tag();
        $html_state_tokens[] = array($html_state_processor->get_token_type(),
            is_string($html_state_tag) ? $html_state_tag : '',
            $html_state_processor->is_tag_closer() ? 'close' : 'open',
            $html_state_processor->get_namespace(), $html_state_processor->get_current_depth());
    }
    $html_state_cases[] = array($html_state_input, $html_state_tokens, $html_state_processor->get_last_error());
    unset($html_state_processor);
}
$capability_view_cases = array();
$capability_view_type = get_post_type_object('post');
if (!($capability_view_type instanceof WP_Post_Type)) throw new LogicException('Post type fixture is unavailable.');
$capability_view_flag = (bool) $capability_view_type->map_meta_cap;
$capability_view_map = $capability_view_type->cap;
if (!($capability_view_map instanceof stdClass)) throw new LogicException('Capability map fixture is unavailable.');
$capability_view_edit = (string) $capability_view_map->edit_post;
$capability_view_post = new WP_Post((object) array('ID' => 901, 'post_type' => 'post',
    'post_status' => 'publish', 'post_author' => '7', 'post_parent' => 0, 'filter' => 'raw'));
try {
    $capability_view_type->map_meta_cap = false;
    foreach (array('before', 'after') as $capability_view_phase) {
        if ($capability_view_phase === 'after') $capability_view_map->edit_post = 'edit_fixture_post';
        foreach (array('edit_post', 'read_post', 'delete_post') as $capability_view_cap) {
            $capability_view_cases[] = array($capability_view_phase, $capability_view_cap,
                map_meta_cap($capability_view_cap, 99, $capability_view_post));
        }
    }
} finally {
    $capability_view_type->map_meta_cap = $capability_view_flag;
    $capability_view_map->edit_post = $capability_view_edit;
}
unset($capability_view_post);

$document = $argv[1] ?? '<!-- wp:site-title /--><!-- wp:site-tagline /--><!-- wp:paragraph --><p>Hello WordPress</p><!-- /wp:paragraph -->';
$pattern_hashes = array();
foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
    $pattern_hashes[ $pattern['name'] ] = hash( 'sha256', $pattern['content'] );
}
$normalized_paths = array();
foreach ( array( '', '/', '///', '//server///share//a', 'c:\\foo\\\\bar\\', '\\\\server\\share\\dir', 'é/汉//x', './a//../b' ) as $path_input ) {
    $normalized_paths[] = array( wp_normalize_path( $path_input ), wp_normalize_path( $path_input ) );
}
$extra_header_filter = static function ( mixed $headers ): array {
    return array( 'Requires at least', 'Requires PHP' );
};
add_filter( 'extra_wp_aot_probe_headers', $extra_header_filter );
$theme_headers = get_file_data(
    ABSPATH . 'wp-content/themes/twentytwentyfive/style.css',
    array( 'Name' => 'Theme Name', 'Version' => 'Version' ),
    'wp_aot_probe'
);
remove_filter( 'extra_wp_aot_probe_headers', $extra_header_filter );
$version_before_probe = $wp_version;
$wp_version = 'modified-for-cache-probe';
$version_cache = array( wp_get_wp_version(), $wp_version, wp_get_wp_version() );
$wp_version = $version_before_probe;
echo json_encode( array(
    'version' => $wp_version,
    'version_cache' => $version_cache,
    'booted' => 1 === did_action( 'wp_loaded' ),
    'init' => did_action( 'init' ),
    'theme' => get_stylesheet(),
    'blogname' => get_option( 'blogname' ),
    'post_types' => array_keys( get_post_types() ),
    'post_support_mutations' => wp_aot_post_support_cases('remove_post_type_support'),
    'taxonomies' => array_keys( get_taxonomies() ),
    'registered_blocks' => count( WP_Block_Type_Registry::get_instance()->get_all_registered() ),
    'registered_widgets' => array_keys($wp_registered_widgets),
    'deprecated_widget_callbacks' => $_wp_deprecated_widgets_callbacks,
    'localized_numbers' => array(
        number_format_i18n( 2.5 ),
        number_format_i18n( 1.005, 2 ),
        number_format_i18n( '1234567.891', 2 ),
        number_format_i18n( -0.004, 2 ),
        number_format_i18n( 999.995, 2 ),
        number_format_i18n( 1234.567, -2 ),
    ),
    'week_ranges' => array(
        get_weekstartend( '2024-02-29 12:34:56', 1 ),
        get_weekstartend( '2026-01-01 00:00:00', 0 ),
    ),
    'normalized_paths' => $normalized_paths,
    'cookie_paths' => array( \wp_aot_bootstrap_string('COOKIEPATH'), \wp_aot_bootstrap_string('SITECOOKIEPATH'), \wp_aot_bootstrap_string('ADMIN_COOKIE_PATH'), \wp_aot_bootstrap_string('PLUGINS_COOKIE_PATH') ),
    'trimmed_words' => array( wp_trim_words( 'one two three', 2, '…' ), wp_trim_words( "uno\t due\ntré", 1, '...' ), wp_trim_words( 'one', 0, '...' ), wp_trim_words( 'one two', 55, '' ) ),
    'image_history' => $image_history,
    'theme_headers' => $theme_headers,
    'theme_directories' => array('forced' => $theme_scan_names, 'cached' => $theme_cached_names),
    'user_iterator_validation' => $user_iterator_validation,
    'password_validation' => $password_validation,
    'registration_validation' => $registration_validation,
    'comments_template_early_return' => $comments_template_early_return,
    'bookmark_globals' => $bookmark_globals,
    'registry_contracts' => $registry_contracts,
    'image_css_queue' => $image_css_queue,
    'term_cache_records' => $term_cache_records,
    'database_method_inventory' => $database_method_inventory,
    'property_metadata' => $property_metadata,
    'widget_conversion' => $widget_conversion,
    'widget_dependency_checks' => $widget_dependency_checks,
    'rest_context_filters' => $rest_context_filters,
    'mailer_language' => array($mailer_lazy_language,$mailer_language_checks),
    'list_table_compatibility' => $list_table_compatibility,
    'user_query_compatibility' => array($user_query_compat_defaults,$user_query_compatibility,$user_query_compat_bad,$user_query_compat_alias_state),
    'user_field_removals' => array($user_field_removals,$user_field_deprecations),
    'query_reset_state' => $query_reset_state,
    'menu_item_conversions' => array($menu_item_conversions,$menu_item_trace),
    'menu_previews' => $menu_previews,
    'menu_option_cases' => $menu_option_cases,
    'theme_capability_cases' => $theme_capability_cases,
    'template_constructor_cases' => $template_constructor_cases,
    'theme_json_cache_cases' => $theme_json_cache_cases,
    'html_state_cases' => $html_state_cases,
    'capability_view_cases' => $capability_view_cases,
    'post_format_slugs' => get_post_format_slugs(),
    'rendered' => do_shortcode( do_blocks( $document ) ),
    'pattern_content_sha256' => $pattern_hashes,
) ) . "\n";

if (defined( 'WP_AOT_NATIVE_RUNTIME' )) wp_aot_run_shutdown();
