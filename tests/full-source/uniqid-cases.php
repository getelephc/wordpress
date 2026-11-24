<?php

$rows = array();
foreach (array('', 'wp-', 'é汉', str_repeat('p', 200)) as $prefix) {
    foreach (array(0, 1, 2147483647, 2147483648, 4294967295, 4294967296, -1) as $seconds) {
        foreach (array(0, 1, 999999) as $microseconds) {
            foreach (array(null, 0, 1, 2147483647, 2147483648, 4294967294, 4294967295) as $entropy) {
                $rows[] = array(format_uniqid_call((string) $prefix, (int) $seconds, (int) $microseconds,
                    $entropy === null ? null : (int) $entropy));
            }
        }
    }
}
$seen = array();
foreach (array('', 'wp-', '0', 'é汉', str_repeat('p', 200)) as $prefix) {
    foreach (array(false, true) as $more_entropy) {
        for ($repeat = 0; $repeat < 32; ++$repeat) {
            $before = time();
            $id = uniqid((string) $prefix, (bool) $more_entropy);
            $after = time();
            $base = substr($id, strlen($prefix), 13);
            $tail = substr($id, strlen($prefix) + 13);
            $seconds = hexdec(substr($base, 0, 8));
            $microseconds = hexdec(substr($base, 8, 5));
            $rows[] = array(is_string($id), substr($id, 0, strlen($prefix)) === $prefix,
                preg_match('/^[0-9a-f]{13}$/', $base) === 1,
                $more_entropy ? preg_match('/^(?:[0-9]\.[0-9]{8}|10\.00000000)$/', $tail) === 1 : $tail === '',
                $seconds >= $before && $seconds <= $after, $microseconds >= 0 && $microseconds <= 999999,
                !isset($seen[$id]));
            $seen[$id] = true;
        }
    }
}
$wrapped = wp_aot_native_api('uniqid', array('wrapped-', true));
$rows[] = array(is_string($wrapped), substr($wrapped, 0, 8) === 'wrapped-');
echo json_encode($rows), "\n";
