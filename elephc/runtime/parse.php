<?php

require __DIR__ . '/bootstrap.php';

$parser = new WP_Block_Parser();
echo json_encode($parser->parse($argv[1] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
