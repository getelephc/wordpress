<?php

final class NumberFormatSeparator implements Stringable {
    public function __toString(): string {return '::';}
}

function number_format_cases(): array {
    $rows = array();
    $values = array(null, false, true, 0, 1, -1, -0.0, 0.0049, -0.0049,
        0.005, -0.005, 0.285, 1.005, 2.5, -2.5, 999.995, -999.995,
        1234567.891, -1234567.891, PHP_INT_MAX, PHP_INT_MIN, 1e100, 1e300,
        INF, -INF, NAN, '1.005', "\t1234.5\n", '1e3', '1e309',
        4503599627370496.0, 9007199254740992.0, 9007199254740993,
        9223372036854774784.0, -9223372036854775808.0,
        '9223372036854775807', '+0009223372036854775807', '-9223372036854775808',
        '9223372036854775808', '-9223372036854775809',
        '', 'foo', '0x12', "1\0", "12\0a", array(), new stdClass(), new NumberFormatSeparator());
    $separators = array(array('.', ','), array(',', ' '), array('', ''),
        array('<d>', '🙂'), array("\0", "g\0"));
    foreach ($values as $number) foreach (array(-22, -19, -18, -8, -2, -1, 0, 1, 2, 3, 8, 14, 22) as $precision) {
        foreach ($separators as $pair) {
            try {$rows[] = array('ok', format_number_call($number, $precision, $pair[0], $pair[1]));}
            catch (TypeError $error) {$rows[] = array('error', 'TypeError');}
        }
    }
    foreach (array(null, false, true, '2', '2e0', 1.5, 'bad', array()) as $precision) {
        foreach (array(array(null, null), array(false, true), array(1, 0),
            array(new NumberFormatSeparator(), new NumberFormatSeparator()),
            array(array(), ','), array('.', new stdClass())) as $pair) {
            try {$rows[] = array('ok', format_number_call('1234.567', $precision, $pair[0], $pair[1]));}
            catch (TypeError $error) {$rows[] = array('error', 'TypeError');}
        }
    }
    return $rows;
}
