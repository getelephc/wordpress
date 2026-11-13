<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/demo.php';

$path = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$status = 200;
$type = 'application/json; charset=utf-8';
$body = '';

if ($path !== '/' && $path !== '/health' && $path !== '/blocks' && $path !== '/parse') {
    $status = 404;
    $body = '{"error":"Not Found"}' . "\n";
} elseif (($path === '/parse' && $method !== 'POST') || ($path !== '/parse' && $method !== 'GET' && $method !== 'HEAD')) {
    $status = 405;
    header($path === '/parse' ? 'Allow: POST' : 'Allow: GET, HEAD');
    $body = '{"error":"Method Not Allowed"}' . "\n";
} elseif ($path === '/health') {
    $body = '{"status":"ok","wordpress":"7.1.4","profile":"block-parser","database":false}' . "\n";
} else {
    $document = wordpress_demo_document();
    if ($path === '/parse') {
        $document = (string) file_get_contents('php://input');
    }
    $parser = new WP_Block_Parser();
    $blocks = $parser->parse($document);
    $body = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    if ($path === '/') {
        $type = 'text/html; charset=utf-8';
        $body = '<!doctype html><html lang="en"><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>WordPress on Elephc</title>'
            . '<style>body{max-width:68rem;margin:4rem auto;padding:0 1.5rem;font:18px/1.6 system-ui;color:#17252b;background:#faf8f4}'
            . 'h1{font-size:clamp(2rem,6vw,4rem);line-height:1.1}a{color:#0073aa}pre{padding:1.5rem;background:#17252b;color:#e4eff3;overflow:auto;border-radius:8px}'
            . '.label{font:14px monospace;text-transform:uppercase;color:#52666d}</style>'
            . '<p class="label">WordPress 7.1.4 / Elephc 0.27.8 / block parser experiment</p>'
            . '<h1>WordPress blocks.<br>Native execution.</h1>'
            . '<p>This response runs the WordPress Gutenberg block parser on every request, including nested blocks and JSON attributes.</p>'
            . '<p><a href="/blocks">Inspect the parsed demo</a> &middot; <a href="/health">Health check</a></p>'
            . '<h2>Try your own document</h2>'
            . '<pre>curl -X POST --data-binary @document.html http://127.0.0.1:9000/parse</pre>'
            . '<h2>Parsed demo</h2><pre>' . htmlspecialchars($body, ENT_QUOTES, 'UTF-8') . '</pre>'
            . '<p>This profile contains three core parser classes and three compatibility patches. The CMS bootstrap, database, themes, plugins and admin are outside this first experiment.</p>'
            . '</html>';
    }
}

http_response_code($status);
header('Content-Type: ' . $type);
if ($method !== 'HEAD') {
    echo $body;
}
