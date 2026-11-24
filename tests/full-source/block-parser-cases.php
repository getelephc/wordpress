<?php
$documents = array(
    '',
    'Plain text è好',
    '<!-- wp:site-title /-->',
    '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->',
    '<!-- wp:paragraph {"align":"center","nested":{"items":[1,null,false]}} --><p>Content</p><!-- /wp:paragraph -->',
    'before<!-- wp:group --><div><!-- wp:paragraph --><p>Nested</p><!-- /wp:paragraph --></div><!-- /wp:group -->after',
    '<!-- wp:example/card {"title":"è好","active":false} /-->',
    '<!-- wp:paragraph --><p>Unclosed',
    '<!-- /wp:paragraph -->tail',
    '<!-- wp:paragraph {invalid} --><p>Invalid JSON</p><!-- /wp:paragraph -->',
    "before\0<!-- wp:separator /-->after",
    '<!-- wp:group --><!-- wp:separator /--><!-- /wp:group -->',
);
$results = array();
$parser = new WP_Block_Parser();
foreach ($documents as $document) $results[] = $parser->parse($document);
for ($index = count($documents) - 1; $index >= 0; --$index) $results[] = $parser->parse($documents[$index]);
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
