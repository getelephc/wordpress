<?php

// PHP reference checks use the complete classes and their actual callbacks.
class WordPressLexicalPrefixerProbe extends WP_URL_Pattern_Prefixer {
    public static function escape_pattern_string(string $value): string {
        throw new LogicException('The private lexical escaping target must remain in the parent.');
    }
}

class WordPressLexicalBookmarkProbe extends WP_HTML_Processor {
    public function seed_bookmarks(): void {
        $this->bookmarks = array('probe' => new WP_HTML_Span(0, 1), '_probe' => new WP_HTML_Span(1, 1));
    }
    public function bookmark_names(): array {return array_keys($this->bookmarks);}
    public function release_bookmark($name): bool {
        throw new LogicException('The internal callback must call the lexical parent directly.');
    }
}

$results = array();
$contexts = array('home' => '/blog+*/', 'site' => '/', 'custom' => '/città:?#/');
$prefixer = new WP_URL_Pattern_Prefixer($contexts);
$child_prefixer = new WordPressLexicalPrefixerProbe($contexts);
foreach (array('', '/', 'post/*', '/post/*', '/blog+*/post', '/città:?#/post', 'literal?x=1') as $path) {
    foreach (array_keys($contexts) as $context) {
        $value = $prefixer->prefix_path_pattern($path, $context);
        $child_value = $child_prefixer->prefix_path_pattern($path, $context);
        if ($value !== $child_value) throw new LogicException('Inherited prefix construction changed its lexical target.');
        $results[] = array('prefix', $path, $context, $value, $child_value);
    }
}

$processor = new WordPressLexicalBookmarkProbe('<p>probe</p>', WP_HTML_Processor::CONSTRUCTOR_UNLOCK_CODE);
$processor->seed_bookmarks();
$callback_property = new ReflectionProperty(WP_HTML_Processor::class, 'release_internal_bookmark_on_destruct');
$release = $callback_property->getValue($processor);
foreach (array('probe', 'missing', '_probe') as $name) {
    $returned = $release($name);
    $remaining = $processor->bookmark_names();
    if ($returned !== null || $remaining !== ($name === '_probe' ? array() : array('_probe'))) {
        throw new LogicException('Internal bookmark release changed its exact key or void return.');
    }
    $results[] = array('bookmark', $name, $returned, $remaining);
}

// Register only the real attachment routes in a request-local server. Do not dispatch uploads.
$saved_server = $GLOBALS['wp_rest_server'] ?? null;
$GLOBALS['wp_rest_server'] = new WP_REST_Server();
add_filter('wp_client_side_media_processing_enabled', '__return_true');
$controller = new WP_REST_Attachments_Controller('attachment');
$controller->register_routes();
$routes = $GLOBALS['wp_rest_server']->get_routes();
foreach (array('sideload' => 'image_size', 'finalize' => 'sub_sizes') as $suffix => $field) {
    $route = '/wp/v2/media/(?P<id>[\d]+)/' . $suffix;
    $endpoint = $routes[$route][0];
    $request = new WP_REST_Request('POST');
    $request->set_attributes(array('args' => $endpoint['args']));
    $callback = $endpoint['args'][$field]['validate_callback'];
    $values = $field === 'image_size'
        ? array('thumbnail', 'original', array('thumbnail', 'medium'), array('original'), 'unknown', '', null, 7, array())
        : array(array(), array(array('image_size' => 'thumbnail')), array(array('image_size' => 'original')),
                array(array('image_size' => array('thumbnail', 'medium'))), array(array('image_size' => 'unknown')),
                array(array('image_size' => null)), array(array()), null, 7);
    foreach ($values as $value) {
        $result = $callback($value, $request, $field);
        $results[] = array('attachment', $suffix, $value, is_wp_error($result)
            ? array($result->get_error_codes(), $result->get_error_messages(), $result->get_error_data()) : $result);
    }
}
remove_filter('wp_client_side_media_processing_enabled', '__return_true');
$GLOBALS['wp_rest_server'] = $saved_server;

class WordPressFontSourceCallbackProbe extends WP_REST_Font_Faces_Controller {
    public array $trace = array();
    public string $prefix = 'initial';
    protected function sanitize_src($value): mixed {
        $this->trace[] = $value;
        return $this->prefix . ':' . $value;
    }
}

foreach (array(new WP_REST_Font_Faces_Controller('wp_font_face'),
               new WordPressFontSourceCallbackProbe('wp_font_face')) as $font_controller) {
    $schema = $font_controller->get_item_schema();
    $cached_schema = $font_controller->get_item_schema();
    $src_path = $schema['properties']['font_face_settings']['properties']['src']['arg_options'];
    $cached_path = $cached_schema['properties']['font_face_settings']['properties']['src']['arg_options'];
    $sanitize = $src_path['sanitize_callback'];
    if ($sanitize !== $cached_path['sanitize_callback']) throw new LogicException('The cached schema must retain its callback.');
    if ($font_controller instanceof WordPressFontSourceCallbackProbe) $font_controller->prefix = 'live';
    // File keys and non-URL strings avoid DNS, HTTP and uploads.
    foreach (array('', '  file-key', '/fonts/città.woff2', 'javascript:font', array(),
                   array('  first', 'second'), array(4 => ' sparse', 'named' => 'é')) as $input) {
        $snapshot = $input;
        $value = $sanitize($input);
        $results[] = array('font-source', get_class($font_controller), $input, $value, $input === $snapshot,
            $font_controller instanceof WordPressFontSourceCallbackProbe ? $font_controller->trace : array());
    }
}

// The isolated PHP process intercepts AJAX termination and seeds only local class caches.
class WordPressDependencyJsonStop extends RuntimeException {}
function wordpress_dependency_die_handler(): never {throw new WordPressDependencyJsonStop();}
$dependency_die_filter = static function () {return 'wordpress_dependency_die_handler';};
add_filter('wp_die_ajax_handler', $dependency_die_filter);
if (!defined('DOING_AJAX')) define('DOING_AJAX', true);
$saved_post = $_POST;
$saved_request = $_REQUEST;
$dependency_properties = array();
$dependency_saved = array();
foreach (array('plugins', 'plugin_dirnames', 'dependencies', 'initialized', 'dependency_api_data') as $name) {
    $property = new ReflectionProperty(WP_Plugin_Dependencies::class, $name);
    $dependency_properties[$name] = $property;
    $dependency_saved[$name] = $property->getValue();
}
try {
    $plugin_file = 'fixture-main/fixture-main.php';
    $dependency_properties['plugins']->setValue(null, array($plugin_file => array('Name' => 'Fixture Main')));
    $dependency_properties['plugin_dirnames']->setValue(null, array('fixture-main' => $plugin_file, 'dep-one' => false, 'dep-two' => false));
    $dependency_properties['dependencies']->setValue(null, array($plugin_file => array('dep-one', 'dep-two')));
    $dependency_properties['initialized']->setValue(null, true);
    foreach (array(
        array('dep-one' => array('Name' => 'Plugin Uno'), 'dep-two' => array('Name' => 'Città 🙂')),
        array('dep-two' => array('Name' => null)),
        array('dep-one' => array('Name' => false), 'dep-two' => array('Name' => '')),
        array('dep-one' => array('Name' => '0'), 'dep-two' => array('Name' => 0)),
    ) as $cache) {
        $dependency_properties['dependency_api_data']->setValue(null, $cache);
        $_POST = array('slug' => 'fixture-main');
        $_REQUEST = array('_ajax_nonce' => wp_create_nonce('updates'));
        ob_start();
        try {
            WP_Plugin_Dependencies::check_plugin_dependencies_during_ajax();
            throw new LogicException('The inactive dependency branch must terminate through wp_die.');
        } catch (WordPressDependencyJsonStop $stop) {
            $response = json_decode(ob_get_contents(), true, 512, JSON_THROW_ON_ERROR);
            if ($response['success'] !== false || $response['data']['errorCode'] !== 'inactive_dependencies') {
                throw new LogicException('The seeded AJAX check must reject inactive dependencies.');
            }
            $results[] = array('inactive-dependencies', $cache, $response);
        } finally {
            ob_end_clean();
        }
    }
} finally {
    foreach ($dependency_properties as $name => $property) $property->setValue(null, $dependency_saved[$name]);
    $_POST = $saved_post;
    $_REQUEST = $saved_request;
    remove_filter('wp_die_ajax_handler', $dependency_die_filter);
}
return $results;
