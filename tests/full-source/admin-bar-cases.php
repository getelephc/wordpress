<?php

class WordPressAdminBarTreeProbe extends WP_Admin_Bar {
    public function seed(array $node): void {$this->_set_node($node);}
    public function bind_tree(): mixed {return $this->_bind();}
    public function raw_nodes(): mixed {return $this->_get_nodes();}
}

function admin_bar_flatten(stdClass $node): array {
    $row = array($node->id, $node->type, $node->parent, $node->meta,
        array_key_exists('group', (array) $node), array());
    foreach ($node->children as $child) {
        $row[5][] = admin_bar_flatten($child);
    }
    return $row;
}

function admin_bar_cases(): array {
    $rows = array();
    $scenarios = array(
        array(),
        array(array('a', false, false), array('b', false, false)),
        array(array('a', false, false), array('b', 'a', false)),
        array(array('g', false, true), array('a', 'g', false)),
        array(array('g', false, true), array('h', 'g', true), array('a', 'g', false), array('b', 'h', false)),
        array(array('orphan', 'absent', false)),
        array(array('root', 'absent', true), array('a', false, false)),
        array(array('g', false, true), array('h', 'g', true), array('i', 'h', true), array('a', 'i', false)),
    );
    foreach ($scenarios as $case => $records) {
        $bar = new WordPressAdminBarTreeProbe();
        foreach ($records as $record) {
            $bar->seed(array('id' => $record[0], 'parent' => $record[1], 'group' => $record[2],
                'title' => 'Title ' . $record[0], 'href' => false, 'meta' => array('class' => 'custom')));
        }
        $before = $bar->get_nodes();
        $before_json = json_encode($before, JSON_THROW_ON_ERROR);
        $aliases = $bar->raw_nodes();
        $root = $bar->bind_tree();
        $tree = admin_bar_flatten($root);
        $alias_rows = array();
        foreach ($aliases as $id => $record) {
            if ($id === 'root') continue;
            $alias_rows[] = array($id, $record->type, $record->parent, array_key_exists('group', (array) $record));
            if (array_key_exists('group', (array) $record)) {
                throw new LogicException('Toolbar normalization retained the group property.');
            }
        }
        if ($tree[0] !== 'root' || $tree[1] !== 'item' || $tree[2] !== 'root' || $tree[4] !== false) {
            throw new LogicException('Toolbar root normalization differs.');
        }
        $top = array();
        foreach ($tree[5] as $child) $top[] = $child[0];
        $expected_top = array(array(), array('root-default'), array('root-default'), array('g'),
            array('g-container'), array(), array('root-default'), array('g-container'));
        if ($top !== $expected_top[$case]) {
            throw new LogicException('Toolbar top-level grouping or container replacement differs.');
        }
        $rows[] = array($case, $tree, $alias_rows, json_encode($before, JSON_THROW_ON_ERROR) === $before_json,
            $bar->bind_tree(), $bar->get_nodes(), $bar->get_node('a'), $bar->raw_nodes());
        if ($rows[count($rows) - 1][3] !== true) {
            throw new LogicException('Toolbar binding changed cloned public snapshots.');
        }
    }
    return $rows;
}
