<?php
$rows = array();
$numbers = array(-4294967297, -2147483649, -32769, -129, -1, 0, 1, 127, 128, 255, 256, 32767, 32768, 65535, 65536, 2147483647, 2147483648, 4294967295, 4294967296, PHP_INT_MIN, PHP_INT_MAX);
foreach (array('c', 'C', 's', 'S', 'i', 'I', 'l', 'L', 'n', 'N', 'v', 'V', 'q', 'Q', 'J', 'P') as $code) {
    foreach ($numbers as $number) {
        $packed = aot_test_pack((string) $code, array((int) $number));
        $rows[] = array(bin2hex($packed), aot_test_unpack((string) $code, $packed));
    }
    foreach (array('2', '*', '0') as $repeat) {
        $packed = aot_test_pack((string) $code . (string) $repeat, $repeat === '0' ? array() : array(-1, 2));
        $rows[] = array(bin2hex($packed), aot_test_unpack((string) $code . (string) $repeat . 'item', $packed));
    }
}
foreach (array('a', 'A', 'Z') as $code) {
    foreach (array('', 'abc', "a\0b", "\0 \t\n\r\v\f", "\xff\x80") as $text) {
        foreach (array('0', '1', '2', '5', '*') as $count) {
            $format = (string) $code . (string) $count;
            $packed = aot_test_pack($format, array((string) $text));
            $unpacked = aot_test_unpack($format . 'field', $packed);
            $encoded = array();
            foreach ((array) $unpacked as $key => $value) $encoded[$key] = bin2hex((string) $value);
            $rows[] = array(bin2hex($packed), $encoded);
        }
    }
}
foreach (array('h', 'H') as $code) {
    foreach (array('', 'a', 'abc', '00ff7aE1') as $text) {
        foreach (array('0', '1', '2', '5', '*') as $count) {
            $format = (string) $code . (string) $count;
            $packed = aot_test_pack($format, array((string) $text));
            $rows[] = array(bin2hex($packed), aot_test_unpack($format . 'field', $packed));
        }
    }
}
foreach (array('C2/C2', 'Cfirst/Csecond', 'C2same/Csame', 'a2text/Vnumber', 'x2/nvalue', 'Cfirst/X1/Csecond', '@2/Cvalue', 'C*all', 'V*all', 'Nvalue') as $format) {
    foreach (array('', "\x01\x02\x03\x04\x05\x06\x07\x08") as $bytes) {
        $value = aot_test_unpack((string) $format, (string) $bytes);
        $encoded = array();
        foreach ((array) $value as $key => $item) $encoded[$key] = is_string($item) ? bin2hex((string) $item) : $item;
        $rows[] = array($value === false, $encoded);
    }
}
foreach (array(0, 1, 3, 8) as $offset) {
    $rows[] = array(aot_test_unpack('C*key', "\x01\x02\x03\x04\x05\x06\x07\x08", (int) $offset));
}
echo json_encode($rows, JSON_THROW_ON_ERROR) . "\n";
