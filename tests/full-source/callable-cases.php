<?php

function fixture_call_reference(callable $callback, array $node, ?array $parent): array {
    $text = $callback($node, $parent);
    return array($text, $node, $parent);
}
function fixture_mutate_reference(array &$node, ?array &$parent): string {
    $node['count'] = ($node['count'] ?? 0) + 1;
    $parent = array('child' => $node['count']);
    return 'mutated';
}
class FixtureReferenceCallback {
    public function visit(array &$node, ?array &$parent): string {
        return fixture_mutate_reference($node, $parent);
    }
    public function __invoke(array &$node, ?array &$parent): string {
        return fixture_mutate_reference($node, $parent);
    }
}
$captured = 0;
$closure = function (array &$node, ?array &$parent) use (&$captured): string {
    ++$captured;
    return fixture_mutate_reference($node, $parent);
};
$object = new FixtureReferenceCallback();
$results = array();
$results[] = fixture_call_reference(fixture_mutate_reference(...), array(), null);
$results[] = fixture_call_reference($closure, array('count' => 2), array('old' => true));
$results[] = fixture_call_reference(array($object, 'visit'), array('count' => 3), null);
$results[] = fixture_call_reference($object, array('count' => 5), null);
$results[] = $captured;
class WordPressControlEditorBase {public function multi_resize(): array {return array();}}
class WordPressControlEditor extends WordPressControlEditorBase {public function make_subsize(): array {return array();}}
class WordPressControlEditorChild extends WordPressControlEditor {}
foreach (array(new WordPressControlEditor(),new WordPressControlEditorChild(),new WordPressControlEditorBase(),new stdClass()) as $editor_control) $results[] = editor_method_call($editor_control);
$theme_forward_rows = require __DIR__ . '/theme-support-forward-cases.php';
foreach ($theme_forward_rows as $row) $results[] = $row;
echo json_encode($results), "\n";
