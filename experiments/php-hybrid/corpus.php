<?php
// Run the existing operation corpora against a complete WordPress bootstrap.
$root=dirname(__DIR__,2);
define('HYBRID_NATIVE',($argv[1]??'php')==='hybrid');
$_SERVER['HTTP_HOST']='127.0.0.1:9000';$_SERVER['SERVER_PORT']='9000';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';
set_error_handler(static function(int $severity,string $message,string $file,int $line): bool { throw new ErrorException($message,0,$severity,$file,$line); });
require $root.'/elephc/build/php-hybrid/wordpress/wp-load.php';
foreach(['hooks-support.php','errors-support.php','responses-support.php','requests-support.php','request-validation.php','schema-support.php','lists-support.php','cache-support.php','kses-support.php','urls-support.php','sanitize-support.php'] as $file)require $root.'/elephc/runtime/'.$file;
function _elephc_kses_entity_names(): array { return $GLOBALS['allowedentitynames']; }
add_filter('pre_option_permalink_structure',static function(): string { return wordpress_request_permalinks()?'/%postname%/':''; });
$groups=['errors'=>'wordpress_errors_text','responses'=>'wordpress_responses_text','requests'=>'wordpress_requests_text','request-validation'=>'wordpress_requests_text','scalar-schema'=>'wordpress_requests_text','collection-schema'=>'wordpress_requests_text','unique-schema'=>'wordpress_requests_text','lists'=>'wordpress_lists_text','cache'=>'wordpress_cache_text','urls'=>'wordpress_urls_text','sanitize'=>'wordpress_sanitize_text','kses'=>static function(array $case): string { return json_encode(base64_encode(wordpress_kses_text($case['mode'],$case['input'],$case['element']??'a'))); }];
$output=[];
foreach($groups as $group=>$handler) {
    $output[$group]=[];
    foreach(json_decode(file_get_contents($root.'/tests/'.$group.'-fixtures.json'),true,512,JSON_THROW_ON_ERROR) as $case) {
        try { $output[$group][$case['name']]=json_decode($handler($group==='kses'?$case:$case['input']),true,512,JSON_THROW_ON_ERROR); }
        catch(Throwable $error) { fwrite(STDERR,$group.':'.$case['name'].': '.$error."\n");exit(1); }
    }
    fwrite(STDERR,$group.': '.count($output[$group])." cases\n");
}
require __DIR__.'/extra-cases.php';
$output['hybrid-boundaries']=hybrid_extra_cases();
file_put_contents($root.'/elephc/build/php-hybrid/corpus-'.($argv[1]??'php').'.json',json_encode(['results'=>$output,'native'=>function_exists('elephc_wp_stats')?elephc_wp_stats():null,'fallbacks'=>$GLOBALS['hybrid_fallbacks']??[]],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
