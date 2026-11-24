<?php
function shutdown_empty_trace(): array { return array(); }
$shutdown_trace = shutdown_empty_trace();
function shutdown_record(string $tag, array $data = array()): void {
    global $shutdown_trace; $shutdown_trace[] = array($tag, $data);
}
class WordPressShutdownRecorder { public function record(string $tag): void { shutdown_record($tag, array()); } }
register_shutdown_function(static function(): void {
    shutdown_record('first', array());
    register_shutdown_function('shutdown_record', 'late', array('nested' => array(null, false, 1)));
    register_shutdown_function(static function(): void {
        global $shutdown_trace; echo json_encode($shutdown_trace, JSON_THROW_ON_ERROR), "\n";
    });
});
register_shutdown_function('shutdown_record', 'second', array('value' => 42));
register_shutdown_function(array(new WordPressShutdownRecorder(), 'record'), 'third');
try { register_shutdown_function('missing_wordpress_shutdown_callback'); }
catch (TypeError $error) { shutdown_record('invalid', array(get_class($error))); }
if (defined('WP_AOT_NATIVE_RUNTIME')) wp_aot_run_shutdown();
