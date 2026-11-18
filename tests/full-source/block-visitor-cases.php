<?php

function fixture_block_before(&$block, &$parent, $sibling) {
    $block['attrs']['before'] = true;
    if ($parent !== null) $parent['attrs']['children_seen'] = ($parent['attrs']['children_seen'] ?? 0) + 1;
    $GLOBALS['fixture_block_trace'][] = array('before', $block['blockName'], $parent['blockName'] ?? null, $sibling['blockName'] ?? null);
    return '<before />';
}
function fixture_block_after(&$block, &$parent, $sibling) {
    $block['attrs']['after'] = true;
    $GLOBALS['fixture_block_trace'][] = array('after', $block['blockName'], $parent['blockName'] ?? null, $sibling['blockName'] ?? null);
    return '<after />';
}
function fixture_block_hook(&$block, $position, $hooked_blocks, $context) {
    $block['attrs'][$position] = count($hooked_blocks);
    $GLOBALS['fixture_block_trace'][] = array('hook', $block['blockName'], $position, $context['name']);
    return '<hook />';
}
$documents = array(
    '',
    '<!-- wp:paragraph --><p>One</p><!-- /wp:paragraph -->',
    '<!-- wp:group --><div><!-- wp:paragraph --><p>One</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Two</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
    '<!-- wp:group --><main><!-- wp:group --><div><!-- wp:template-part {"slug":"header"} /--></div><!-- /wp:group --></main><!-- /wp:group --><!-- wp:separator /-->',
);
$results = array();
foreach ($documents as $document) {
    foreach (array('none', 'before', 'both', 'factory') as $mode) {
        $tree = parse_blocks($document);
        $before = null;
        $after = null;
        if ($mode === 'before' || $mode === 'both') $before = 'fixture_block_before';
        if ($mode === 'both') $after = 'fixture_block_after';
        if ($mode === 'factory') {
            $before = make_before_block_visitor(array('marker'), array('name' => 'fixture'), 'fixture_block_hook');
            $after = make_after_block_visitor(array('marker'), array('name' => 'fixture'), 'fixture_block_hook');
        }
        $GLOBALS['fixture_block_trace'] = array();
        $text = traverse_and_serialize_blocks($tree, $before, $after);
        $results[] = array($mode, $text, $GLOBALS['fixture_block_trace'], $tree);
    }
}
return $results;
