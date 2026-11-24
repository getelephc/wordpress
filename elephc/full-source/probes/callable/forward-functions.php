<?php

echo json_encode(wordpress_probe_forward_map('wordpress_probe_forward_value', array('wordpress'))), "\n";

function wordpress_probe_forward_value(mixed $value): mixed {
    return strtoupper($value);
}
