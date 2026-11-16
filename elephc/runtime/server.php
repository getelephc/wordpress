<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/demo.php';
require __DIR__ . '/hooks-demo.php';
require __DIR__ . '/render-bootstrap.php';
require __DIR__ . '/render-demo.php';
require __DIR__ . '/serialize-bootstrap.php';
require __DIR__ . '/cache-bootstrap.php';

$path = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$status = 200;
$type = 'application/json; charset=utf-8';
$body = '';
$post_route = $path === '/parse' || $path === '/filter' || $path === '/serialize' || $path === '/kses' || $path === '/cache';
$allowed = $path === '/render' ? ['GET', 'HEAD', 'POST'] : ($post_route ? ['POST'] : ['GET', 'HEAD']);

if ($path !== '/' && $path !== '/health' && $path !== '/blocks' && $path !== '/parse' && $path !== '/hooks' && $path !== '/filter' && $path !== '/render' && $path !== '/serialize' && $path !== '/kses' && $path !== '/cache') {
    $status = 404;
    $body = '{"error":"Not Found"}' . "\n";
} elseif (!in_array($method, $allowed, true)) {
    $status = 405;
    header('Allow: ' . implode(', ', $allowed));
    $body = '{"error":"Method Not Allowed"}' . "\n";
} elseif ($path === '/health') {
    $body = '{"status":"ok","wordpress":"7.1.4","profile":"blocks-serialization-hooks-content-attribute-kses-cache","database":false}' . "\n";
} elseif ($path === '/cache') {
    try {
        $body = wordpress_cache_text((string) file_get_contents('php://input'));
    } catch (InvalidArgumentException $exception) {
        $status = 400;
        $body = json_encode(['error' => $exception->getMessage()]) . "\n";
    }
} elseif ($path === '/kses') {
    $mode = (string) ($_GET['mode'] ?? 'attribute');
    $input = (string) file_get_contents('php://input');
    try {
        $body = wordpress_kses_text($mode, $input, (string) ($_GET['element'] ?? 'a'));
        $type = $mode === 'decode' ? 'application/octet-stream'
            : (in_array($mode, ['attributes', 'tag-attributes'], true) ? 'application/json; charset=utf-8' : 'text/plain; charset=utf-8');
    } catch (InvalidArgumentException $exception) {
        $status = 400;
        $body = json_encode(['error' => $exception->getMessage()]) . "\n";
    }
} elseif ($path === '/serialize') {
    $mode = (string) ($_GET['mode'] ?? 'blocks');
    $input = (string) file_get_contents('php://input');
    try {
        $body = wordpress_serialize_text($mode, $input);
        $type = 'text/plain; charset=utf-8';
    } catch (InvalidArgumentException $exception) {
        $status = 400;
        $body = json_encode(['error' => $exception->getMessage()]) . "\n";
    }
} elseif ($path === '/render') {
    $mode = (string) ($_GET['mode'] ?? 'pipeline');
    $input = $method === 'POST' ? (string) file_get_contents('php://input') : wordpress_content_demo_input();
    try {
        $body = wordpress_render_text($mode, $input);
        $type = in_array($mode, ['attributes', 'inspect', 'context', 'registration'], true)
            ? 'application/json; charset=utf-8' : 'text/html; charset=utf-8';
    } catch (InvalidArgumentException $exception) {
        $status = 400;
        $message = str_starts_with($exception->getMessage(), 'Unknown render mode:')
            ? 'Unknown render mode' : $exception->getMessage();
        $body = json_encode(['error' => $message]) . "\n";
    }
} elseif ($path === '/hooks' || $path === '/filter') {
    $scenario = (string) ($_GET['case'] ?? 'priority');
    $input = $path === '/filter' ? (string) file_get_contents('php://input') : (string) ($_GET['value'] ?? 'WordPress');
    try {
        $body = json_encode(wordpress_hooks_demo($scenario, $input), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    } catch (InvalidArgumentException $exception) {
        $status = 400;
        $body = '{"error":"Unknown hook scenario"}' . "\n";
    }
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
            . '<p class="label">WordPress 7.1.4 / Elephc 0.27.8 / native core experiment</p>'
            . '<h1>WordPress blocks.<br>Native execution.</h1>'
            . '<p>This response runs the WordPress Gutenberg block parser on every request, including nested blocks and JSON attributes.</p>'
            . '<p><a href="/blocks">Parsed blocks</a> &middot; <a href="/hooks">Actions and filters</a> &middot; <a href="/render">Rendered content</a> &middot; <a href="/health">Health check</a></p>'
            . '<h2>Try your own document</h2>'
            . '<pre>curl -X POST --data-binary @document.html http://127.0.0.1:9000/parse</pre>'
            . '<h2>Serialize edited blocks</h2><p>Send the JSON block tree back to WordPress core to rebuild its comment-delimited document.</p>'
            . '<pre>curl --data-binary @blocks.json http://127.0.0.1:9000/serialize</pre>'
            . '<h2>Parsed demo</h2><pre>' . htmlspecialchars($body, ENT_QUOTES, 'UTF-8') . '</pre>'
            . '<h2>WordPress hooks</h2><p>Real actions and filters, including priorities, callback removal and nested execution.</p>'
            . '<pre>curl -X POST --data-binary "Hello WordPress" http://127.0.0.1:9000/filter</pre>'
            . '<h2>Shortcodes and paragraphs</h2><p>Core WordPress formatting and shortcodes, running through the content filter pipeline.</p>'
            . '<pre>curl --data-binary "Welcome [greet name=Elephc]" http://127.0.0.1:9000/render</pre>'
            . '<h2>Filter an HTML attribute</h2><p>The selected KSES policy filters individual attributes and URL protocols. Full HTML and CSS filtering remain outside this profile.</p>'
            . '<pre>curl --data-binary \'title="Ada &amp; Bob"\' http://127.0.0.1:9000/kses</pre>'
            . '<h2>Request-local object cache</h2><p>Send a JSON operation sequence to exercise the WordPress memory cache. Each request starts a fresh cache.</p>'
            . '<pre>curl --data-binary @cache-operations.json http://127.0.0.1:9000/cache</pre>'
            . '<p>This profile includes block parsing and serialization, actions, filters, shortcodes and selected formatting. The CMS bootstrap, database, plugin discovery, themes and admin remain outside the profile.</p>'
            . '</html>';
    }
}

http_response_code($status);
header('Content-Type: ' . $type);
if ($method !== 'HEAD') {
    echo $body;
}
