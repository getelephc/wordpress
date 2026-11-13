<?php

require __DIR__ . '/../../upstream/wordpress/wp-includes/class-wp-block-parser-block.php';
require __DIR__ . '/../../upstream/wordpress/wp-includes/class-wp-block-parser-frame.php';
require __DIR__ . '/../../upstream/wordpress/wp-includes/class-wp-block-parser.php';

$parser = new WP_Block_Parser();
echo json_encode($parser->parse('<!-- wp:paragraph {"align":"center"} --><p>Hello from WordPress on Elephc.</p><!-- /wp:paragraph -->')) . "\n";
