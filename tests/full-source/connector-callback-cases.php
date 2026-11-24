<?php
function connector_callback_fields(array $config): array {
    $fields = array();
    foreach ($config['plugin'] as $key => $value) {
        if ($key !== 'is_active') $fields[$key] = $value;
    }
    return $fields;
}
function connector_callback_cases(): array {
    $cases = array();
    $configs = array(array(), array('plugin' => array()),
        array('plugin' => array('file' => 'provider.php')),
        array('name' => 'Unicode', 'plugin' => array('file' => 'ü.php', 'extra' => array(1, false))));
    foreach ($configs as $config) foreach (array('present', 'absent', 'throws') as $id) {
        $connector_registry = new MutationConnectorRegistry();
        $result = mutation_connector_callback((array)$config, $connector_registry, $id);
        $snapshot = $result;
        $connector_saved = $result['plugin']['is_active'];
        $first = $connector_saved();
        $connector_registry->active = true;
        $second = $connector_saved();
        $connector_registry = new MutationConnectorRegistry();
        $third = $connector_saved();
        $result['plugin']['file'] = 'result-only';
        $cases[] = array($config, $id, $first, $second, $third,
            is_callable($connector_saved), $connector_saved === $snapshot['plugin']['is_active'],
            connector_callback_fields($result), connector_callback_fields($snapshot));
    }
    return $cases;
}
