<?php
$result = array();
foreach (array('', 'abcabc', "a\0bca\0", '👋wave') as $subject) {
    foreach (array('', 'abc', "a\0bc", "\xF0\x9F\x91\x8B") as $mask) {
        foreach (array(-99,-6,-2,0,2,6,99) as $offset) {
            foreach (array(null,-99,-2,0,2,99) as $length) {
                $result[] = strspn($subject, $mask, $offset, $length);
            }
        }
    }
}
echo json_encode($result), "\n";
