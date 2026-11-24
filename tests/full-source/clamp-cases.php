<?php
// Keep mixed comparison precedence, selected value types and bound error order.
function clamp_case_value(mixed $value): array {
    if (is_float($value)) {
        if (is_nan($value)) return array('float', 'NAN');
        if (is_infinite($value)) return array('float', $value > 0 ? 'INF' : '-INF');
        return array('float', (string)$value);
    }
    if (is_int($value)) return array('int', $value);
    if (is_string($value)) return array('string', $value);
    if (is_bool($value)) return array('bool', $value);
    if ($value === null) return array('null');
    return array('array', $value);
}
function clamp_cases(): array {
    $values = array(null, false, true, -1, 0, 1, 200, 600, PHP_INT_MIN, PHP_INT_MAX,
        -1.5, 0.0, 1.5, INF, -INF, NAN, '', '0', '200', '0600', '1.5', 'foo');
    $cases = array();
    foreach ($values as $value) {
        foreach ($values as $minimum) {
            foreach ($values as $maximum) {
                try { $cases[] = array('ok', clamp_case_value(clamp_call($value, $minimum, $maximum))); }
                catch (ValueError $error) { $cases[] = array('error', 'ValueError', $error->getMessage()); }
            }
        }
    }
    return $cases;
}
