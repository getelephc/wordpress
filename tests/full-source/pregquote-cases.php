<?php

$inputs = array('', 'plain text', '.\\+*?[^]$(){}=!<>|:-#/', "before\0after", 'caffè/日本語');
$bytes = '';
for ($byte = 0; $byte < 256; ++$byte) {
    $bytes .= chr($byte);
    $inputs[] = chr($byte);
    $inputs[] = chr($byte) . '.#/\\' . chr(($byte + 73) % 256);
}
$inputs[] = $bytes;
$delimiters = array(null, '', '/', '~', '#', '!', ':', '"', "'", '\\', "\0", '.', 'xy', 'é');
$results = array();
foreach ($inputs as $input) {
    $row = array();
    foreach ($delimiters as $delimiter) {
        $row[] = bin2hex(preg_quote($input, $delimiter));
    }
    $results[] = $row;
}
echo json_encode($results), "\n";
