<?php
$root = dirname(__DIR__, 2);
define('HYBRID_NATIVE', false);
$_SERVER['HTTP_HOST'] = '127.0.0.1:9000';
$_SERVER['SERVER_PORT'] = '9000';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
require $root . '/elephc/build/php-hybrid/wordpress/wp-load.php';
$ref = new ReflectionFunction('elephc_wpautop');
$serializerRef = new ReflectionFunction('elephc_wp_serialize_blocks');
if (!$ref->isInternal() || $ref->getExtensionName() !== 'wp_elephc_hybrid' || !$serializerRef->isInternal() || $serializerRef->getExtensionName() !== 'wp_elephc_hybrid') {
    throw new RuntimeException('Expected an actual PHP internal extension function.');
}
$checks = 0;
foreach (json_decode(file_get_contents($root . '/tests/render-fixtures.json'), true) as $case) {
    if ($case['mode'] !== 'autop') { continue; }
    foreach ([true, false] as $br) {
        if (wpautop($case['input'], $br) !== elephc_wpautop($case['input'], $br)) {
            throw new RuntimeException('Autop mismatch: ' . $case['name']);
        }
        ++$checks;
    }
}
foreach (json_decode(file_get_contents($root . '/tests/fixtures.json'), true) as $case) {
    $blocks = parse_blocks($case['document']);
    if(function_exists('elephc_wp_parse_blocks')) {
        if($blocks!==elephc_wp_parse_blocks($case['document']))throw new RuntimeException('Parser mismatch: '.$case['name']);
        ++$checks;
    }
    $before = serialize($blocks);
    if (serialize_blocks($blocks) !== elephc_wp_serialize_blocks($blocks) || $before !== serialize($blocks)) {
        throw new RuntimeException('Serializer mismatch: ' . $case['name']);
    }
    ++$checks;
}
foreach (json_decode(file_get_contents($root . '/tests/serialize-fixtures.json'), true) as $case) {
    if ($case['mode'] === 'roundtrip') {
        $blocks = parse_blocks($case['input']);
    } elseif ($case['mode'] === 'blocks') {
        $blocks = json_decode($case['input'], true, 512, JSON_THROW_ON_ERROR);
    } else {
        $blocks = [['blockName'=>'core/probe','attrs'=>json_decode($case['input'], true, 512, JSON_THROW_ON_ERROR),'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]];
    }
    if (serialize_blocks($blocks) !== elephc_wp_serialize_blocks($blocks)) {
        throw new RuntimeException('Serializer corpus mismatch: ' . $case['name']);
    }
    ++$checks;
}
$identity = "A\0B 🦣";
if (elephc_wp_identity($identity) !== $identity) { throw new RuntimeException('Binary identity mismatch.'); }
++$checks;
echo json_encode(['checks'=>$checks, 'reflection'=>['internal'=>$ref->isInternal(), 'extension'=>$ref->getExtensionName()], 'native_stats'=>elephc_wp_stats(), 'database_engine'=>DB_ENGINE, 'parser_in_extension'=>function_exists('elephc_wp_parse_blocks')], JSON_PRETTY_PRINT), "\n";
