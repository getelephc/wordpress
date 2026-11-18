<?php
$root = dirname(__DIR__, 2);
$site = $root . '/elephc/build/php-hybrid/wordpress';
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/__hybrid_ready') {
    header('Content-Type: application/json');
    echo json_encode(['php'=>PHP_VERSION, 'extension'=>extension_loaded('wp_elephc_hybrid'), 'opcache'=>ini_get('opcache.enable_cli'), 'jit'=>ini_get('opcache.jit'), 'jit_buffer_size'=>ini_get('opcache.jit_buffer_size')]);
    return;
}
define('HYBRID_NATIVE', getenv('HYBRID_MODE') === 'hybrid');
$started = hrtime(true);
ob_start(static function(string $output,int $phase) use($started): string {
    if($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $stats = function_exists('elephc_wp_stats') ? elephc_wp_stats() : ['parser'=>0,'autop'=>0,'identity'=>0,'input_bytes'=>0,'output_bytes'=>0,'calls'=>[]];
        if($path=getenv('HYBRID_PROOF_PATH'))file_put_contents($path,json_encode(['native'=>$stats,'fallbacks'=>$GLOBALS['hybrid_fallbacks']??[]],JSON_PRETTY_PRINT));
        header('X-Hybrid-Native: ' . (HYBRID_NATIVE ? 'yes' : 'no'));
        header('X-Hybrid-Stats: ' . json_encode($stats));
        header('X-Hybrid-Fallbacks: ' . json_encode($GLOBALS['hybrid_fallbacks']??[]));
        header('X-Hybrid-Render-Ms: ' . ((hrtime(true)-$started)/1000000));
        header('X-Hybrid-Peak-Bytes: ' . memory_get_peak_usage(true));
    }
    return $output;
});
require $site . '/index.php';
ob_end_flush();
