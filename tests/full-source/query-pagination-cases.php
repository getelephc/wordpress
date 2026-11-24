<?php

$pagination_callback = pagination_initial_callback();
$pagination_trace = pagination_empty_trace();
$rows = array();
foreach (array(1, 10, 100) as $base) {
    $outer = array('blockName' => 'core/query', 'attrs' => array('enhancedPagination' => true, 'queryId' => $base));
    $inner = array('blockName' => 'core/query', 'attrs' => array('enhancedPagination' => true, 'queryId' => $base + 1));
    $rows[] = array(block_core_query_disable_enhanced_pagination($outer), is_callable($pagination_callback));
    $rows[] = array(block_core_query_disable_enhanced_pagination($inner), is_callable($pagination_callback));
    $saved = pagination_current_callback();
    $plain = array('blockName' => 'core/query', 'attrs' => array('enhancedPagination' => false));
    $rows[] = array($saved('plain', $plain), $saved === $pagination_callback);
    foreach (array('plugin/compatible', 'plugin/interactive', 'plugin/incompatible') as $name) {
        $block = array('blockName' => $name, 'attrs' => array());
        $rows[] = array(block_core_query_disable_enhanced_pagination($block), $saved === $pagination_callback);
    }
    $rows[] = array($saved('inner 🐘', $inner), $saved === $pagination_callback);
    $rows[] = array($saved('outer', $outer), $pagination_callback === null);
    $rows[] = array($pagination_trace);
}
// A clean query must not inherit the dirty marks from earlier query ids.
$clean = array('blockName' => 'core/query', 'attrs' => array('enhancedPagination' => true, 'queryId' => 1));
$rows[] = array(block_core_query_disable_enhanced_pagination($clean));
$saved = pagination_current_callback();
$rows[] = array($saved('clean', $clean), $pagination_callback === null, $pagination_trace);
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
