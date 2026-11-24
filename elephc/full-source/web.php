<?php

function wordpress_web_response(int $status, string $type, string $body): void {
    http_response_code($status);
    header('Content-Type: ' . $type);
    header('X-WordPress-Execution: elephc-native');
    echo $body;
}

function wordpress_web_page(array $state): string {
    $title = htmlspecialchars((string) $state['blogname'], ENT_QUOTES, 'UTF-8');
    return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $title . '</title>'
        . '<style>body{margin:0;background:#f6f5f1;color:#222;font:18px/1.6 system-ui,sans-serif}'
        . 'main{max-width:760px;margin:8vh auto;padding:36px;background:white;border-radius:12px}'
        . 'a{color:#3858e9}h1{line-height:1.2}.wp-block-site-tagline{color:#555}'
        . 'footer{margin-top:40px;padding-top:20px;border-top:1px solid #ddd;font-size:14px;color:#666}'
        . '</style></head><body><main class="wp-site-blocks">'
        . (string) $state['rendered']
        . '<footer>Rendered by the compiled WordPress core.</footer></main></body></html>';
}

function wordpress_web_output(string $buffer, int $phase): string {
    $state = json_decode($buffer, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($state) || ($state['booted'] ?? false) !== true) {
        throw new RuntimeException('The original WordPress bootstrap did not complete.');
    }
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    header('X-WordPress-Execution: elephc-native');
    return wordpress_web_page((array) $state);
}

$web_path = explode('?', (string) ($_SERVER['REQUEST_URI'] ?? '/'))[0];
$web_method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($web_path !== '/' && $web_path !== '/index.php') {
    wordpress_web_response(404, 'text/plain; charset=utf-8', "Not Found\n");
    return;
}
if ($web_method !== 'GET' && $web_method !== 'HEAD') {
    header('Allow: GET, HEAD');
    wordpress_web_response(405, 'text/plain; charset=utf-8', "Method Not Allowed\n");
    return;
}

// PHP web SAPIs provide PHP_SELF. Supply the same finite entrypoint identity
// when the native request bridge omits it, so core server normalization stays quiet.
if (!isset($_SERVER['PHP_SELF'])) {
    $_SERVER['PHP_SELF'] = $web_path === '/' ? '/index.php' : $web_path;
}

$argv = array('wordpress-web');
if (array_key_exists('document', $_GET)) {
    $web_input = $_GET['document'];
    if (!is_string($web_input)) {
        wordpress_web_response(400, 'text/plain; charset=utf-8', "Expected a UTF-8 block document of at most 16384 bytes.\n");
        return;
    }
    $web_document = (string) $web_input;
    if (strlen($web_document) > 16384 || strpos($web_document, "\0") !== false
        || preg_match('//u', $web_document) !== 1) {
        wordpress_web_response(400, 'text/plain; charset=utf-8', "Expected a UTF-8 block document of at most 16384 bytes.\n");
        return;
    }
    $argv[] = $web_document;
}

// Execute the same complete WordPress bootstrap and render pipeline as the CLI.
// WordPress flushes buffers during shutdown. Transform the live inspection
// envelope at that flush, retaining the original shutdown callbacks.
ob_start('wordpress_web_output');
require __DIR__ . '/wordpress/aot-probe.php';
if (ob_get_level() > 0) {
    ob_end_flush();
}
