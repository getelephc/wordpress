<?php

function wordpress_probe_forward_map(mixed $callback, array $values): array {
    echo json_encode(array('callable' => is_callable($callback))), "\n";
    $result = array();
    foreach ($values as $value) {
        $result[] = call_user_func_array($callback, array($value));
    }
    return $result;
}

require __DIR__ . '/callable/forward-functions.php';
