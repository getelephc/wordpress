<?php

require_once ABSPATH . WPINC . '/class-wp-customize-setting.php';

class WordPressSettingTreeProbe extends WP_Customize_Setting {
    public function __construct() {}

    public function read_tree($root, $keys) {
        return $this->multidimensional_get($root, $keys, 'missing');
    }

    public function write_tree($root, $keys, $value) {
        return $this->multidimensional_replace($root, $keys, $value);
    }

    public function has_tree($root, $keys) {
        return $this->multidimensional_isset($root, $keys);
    }
}

$probe = new WordPressSettingTreeProbe();
$results = array();
$roots = array(null, false, 0, 42, 'abc', array(), array('a' => null),
    array('a' => false), array('a' => 42), array('a' => 'xyz'),
    array('a' => array()), array('a' => array('b' => 1)), array(2 => array('b' => 1)));
$paths = array(array(), array('a'), array('a', 'b'), array('a', 'b', 'c'), array(2, 'b'), array('2', 'b'));
foreach ($roots as $root) {
    foreach ($paths as $keys) {
        $results[] = array($probe->read_tree($root, $keys), $probe->has_tree($root, $keys));
        foreach (array(null, false, 0, '', array('nested' => array(1, 2))) as $value) {
            try {
                $results[] = $probe->write_tree($root, $keys, $value);
            } catch (Throwable $error) {
                $results[] = array('error_class' => get_class($error));
            }
        }
    }
}
return $results;
