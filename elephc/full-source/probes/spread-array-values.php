<?php

function wordpress_spread_values_seed(mixed $value): mixed { return $value; }
function wordpress_spread_values_collect(array $values): array { return $values; }

$groups = wordpress_spread_values_seed(array('first' => array(1), 'second' => array(2)));
echo json_encode(wordpress_spread_values_collect(array(...array_values($groups)))), '|';
echo json_encode(wordpress_spread_values_collect(array_values($groups)));
