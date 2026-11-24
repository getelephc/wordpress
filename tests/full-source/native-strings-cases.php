<?php
// Cron array keys use PHP's integer-to-string comparison coercion.
$natural_keys = array(0, 1, -1, 10, 2000000000, 2147483647, '0', '01', '10', '2000000000', '2147483647', 'item2', 'Item10');
$natural_key_rows = array();
foreach ($natural_keys as $left) foreach ($natural_keys as $right) $natural_key_rows[] = array($left, $right, strnatcasecmp($left, $right) <=> 0);

$rows = $natural_key_rows;
$strings = array('', 'a', 'A', 'a1', 'a01', 'a10', 'a2', '0001', '1', '01', '1 ', ' 1', 'a 7', 'a7', 'a0.5', 'a0.10', 'a0.05', '1.2', '1.12', 'é', 'É', "a\0b", "a\0c", "a\t1", "\0", ' ', "\t", '-1', '-01', '000');
foreach ($strings as $left) {
    foreach ($strings as $right) $rows[] = array(strnatcasecmp($left, $right) <=> 0);
}
foreach (array('', 'abc', "a\0b", 'cafés', "\xff\x80x") as $string) {
    foreach (array('x', 'a', 'bc', "\0", "\x80", "\xa9", 'é', 'abcxyz') as $mask) {
        $found = strpbrk($string, $mask);
        $rows[] = array($found === false ? false : base64_encode((string) $found));
    }
}
try { strpbrk('x', ''); }
catch (ValueError $error) { $rows[] = array($error->getMessage()); }
echo json_encode($rows, JSON_THROW_ON_ERROR) . "\n";
