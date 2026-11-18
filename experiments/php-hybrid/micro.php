<?php
$root = dirname(__DIR__, 2);
define('HYBRID_NATIVE', ($argv[1] ?? '') === 'hybrid');
$_SERVER['HTTP_HOST'] = '127.0.0.1:9000';
$_SERVER['SERVER_PORT'] = '9000';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
require $root . '/elephc/build/php-hybrid/wordpress/wp-load.php';
$kind = $argv[2];
$loops = (int) $argv[3];
$ids = json_decode(file_get_contents($root . '/elephc/build/php-hybrid/posts.json'), true);
if ($kind === 'identity') {
    $value = str_repeat('x', 16384);
    $run = HYBRID_NATIVE ? static fn() => elephc_wp_identity($value) : static fn() => $value;
} elseif (str_starts_with($kind, 'autop-')) {
    $value = get_post($ids[str_replace('autop-', 'classic-', $kind)])->post_content;
    $run = static fn() => wpautop($value);
} elseif (str_starts_with($kind, 'serialize-')) {
    $value = parse_blocks(get_post($ids[str_replace('serialize-', 'blocks-', $kind)])->post_content);
    $run = static fn() => serialize_blocks($value);
} else { throw new RuntimeException('Unknown microbenchmark.'); }
for ($i = 0; $i < 10; ++$i) { $result = $run(); }
$start = hrtime(true);
for ($i = 0; $i < $loops; ++$i) { $result = $run(); }
$elapsed = hrtime(true)-$start;
echo json_encode(['kind'=>$kind,'loops'=>$loops,'elapsed_ns'=>$elapsed,'ns_per_call'=>$elapsed/$loops,'sha256'=>hash('sha256',$result),'native_stats'=>function_exists('elephc_wp_stats')?elephc_wp_stats():null,'php_peak_bytes'=>memory_get_peak_usage(true)]), "\n";
