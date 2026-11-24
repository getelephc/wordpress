<?php

// Test widget dependencies isolate the complete original factory registry.
class WP_Widget {
    public string $id_base;
    public string $marker;
    public function __construct(string $base = 'shared', string $marker = 'class') {
        $this->id_base = $base;
        $this->marker = $marker;
    }
    public function _register(): mixed {
        throw new LogicException('Widget option and callback registration is outside this control.');
    }
}
class WordPressFactoryAlpha extends WP_Widget {}
class WordPressFactoryBeta extends WP_Widget {}

function add_action(mixed $hook, mixed $callback, mixed $priority = 10): bool {
    global $widget_factory_hook_trace;
    $widget_factory_hook_trace = array($hook, $callback, $priority);
    return true;
}
function _deprecated_constructor(mixed ...$arguments): never {
    throw new LogicException('Legacy widget constructors are outside this control.');
}
function _get_widget_id_base(mixed $value): never {
    throw new LogicException('Widget callback registration is outside this control.');
}

function widget_factory_cases(): array {
    global $widget_factory_hook_trace;
    $rows = array();
    foreach (array('shared', '0', 'Unicode π', 'dash-name') as $base) {
        $factory = new WP_Widget_Factory();
        $hook = $widget_factory_hook_trace;
        $widget_factory_hook_trace = null;
        $hook_row = array($hook[0], $hook[1][0] === $factory, $hook[1][1], $hook[2]);
        if ($hook_row !== array('widgets_init', true, '_register_widgets', 100)) {
            throw new LogicException('Factory constructor hook differs.');
        }
        $rows[] = $hook_row;
        $first = new WordPressFactoryAlpha((string) $base, 'first');
        $second = new WordPressFactoryBeta((string) $base, 'second');
        $unknown = new WordPressFactoryAlpha((string) $base, 'unknown');
        $first_key = 'spl_object_id:' . wp_aot_object_id($first);
        $second_key = 'spl_object_id:' . wp_aot_object_id($second);
        $registered_first = $factory->register($first);
        $registered_again = $factory->register($first);
        $registered_second = $factory->register($second);
        $registry_alias =& $factory->widgets;
        $snapshot = $factory->widgets;
        $instance_row = array($registered_first, $registered_again, $registered_second,
            count($factory->widgets), $first_key !== $second_key,
            array_keys($factory->widgets) === array($first_key, $second_key),
            $factory->widgets[$first_key] === $first, $factory->widgets[$second_key] === $second,
            $factory->get_widget_object($base) === $first, $factory->get_widget_key($base) === $first_key);
        if ($instance_row !== array(null, null, null, 2, true, true, true, true, true, true)) {
            throw new LogicException('Factory instance identity or order differs.');
        }
        $rows[] = $instance_row;
        $removed_unknown = $factory->unregister($unknown);
        $removed_first = $factory->unregister($first);
        $removed_again = $factory->unregister($first);
        $removed_row = array($removed_unknown, $removed_first, $removed_again,
            array_keys($factory->widgets) === array($second_key), $registry_alias === $factory->widgets,
            $factory->get_widget_object($base) === $second, $factory->get_widget_key($base) === $second_key,
            array_keys($snapshot) === array($first_key, $second_key), $snapshot[$first_key] === $first);
        if ($removed_row !== array(null, null, null, true, true, true, true, true, true)) {
            throw new LogicException('Factory instance removal changed aliases or snapshots.');
        }
        $rows[] = $removed_row;
        $factory->register('WordPressFactoryAlpha');
        $original_class = $factory->widgets['WordPressFactoryAlpha'];
        $factory->register('WordPressFactoryAlpha');
        $replacement_class = $factory->widgets['WordPressFactoryAlpha'];
        $class_row = array(count($factory->widgets), $original_class !== $replacement_class,
            get_class($replacement_class), $replacement_class->marker,
            array_keys($factory->widgets) === array($second_key, 'WordPressFactoryAlpha'),
            $factory->widgets[$second_key] === $second, count($snapshot));
        if ($class_row !== array(2, true, 'WordPressFactoryAlpha', 'class', true, true, 2)) {
            throw new LogicException('Factory class registration differs.');
        }
        $rows[] = $class_row;
        $factory->unregister('WordPressFactoryAlpha');
        $factory->unregister('WordPressFactoryAlpha');
        $factory->unregister($second);
        $empty_row = array($factory->widgets, $registry_alias, count($snapshot),
            $factory->get_widget_key($base), $factory->get_widget_object($base));
        if ($empty_row !== array(array(), array(), 2, '', null)) {
            throw new LogicException('Factory empty registry or previous snapshot differs.');
        }
        $rows[] = $empty_row;
        unset($registry_alias);
    }
    return $rows;
}
