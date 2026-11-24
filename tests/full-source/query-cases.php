<?php

$rows = array();
$cases = array(
    array(),
    array('space key' => 'a b', 'tilde' => '~', 'unicode' => 'caffè'),
    array('ids' => array(2, 3), 'omit' => null, 'empty' => array()),
    array(3 => 'third', -2 => false, '03' => true),
    array(2 => array('x y' => 'nested value', 0 => true), -1 => array('next' => 'ok')),
    array('nested' => array('x y' => array(1, null, 'a+b')), 'zero' => 0),
    array('a&b' => '=/?#[]', 'float' => 1.5, 'bytes' => "a\0b"),
);
foreach ($cases as $data) {
    foreach (array('', 'n_', 'prefix space ') as $prefix) {
        foreach (array('&', ';', '&amp;', '') as $separator) {
            foreach (array(PHP_QUERY_RFC1738, PHP_QUERY_RFC3986) as $encoding) {
                $rows[] = array(http_build_query($data, $prefix, $separator, $encoding));
            }
        }
    }
    $rows[] = array(http_build_query($data));
}
foreach (array(null, false, true, 0, 1, -1, 1.0, 0.0, 2.5, '', '1', '0', 'TRUE', 'false', ' on ', 'off', 'yes', 'no', 'maybe', " true\0", array()) as $value) {
    $rows[] = array(filter_var($value, FILTER_VALIDATE_BOOLEAN));
}
foreach (array(array('a b  c', ' '), array(',one;two|three', ',;|'), array("a\0b\0", "\0"), array('', ','), array('whole', ''), array('leading:middle;last', ':;')) as $pair) {
    $row = array(aot_test_token($pair[0], $pair[1]));
    for ($i = 0; $i < 4; ++$i) $row[] = aot_test_token($pair[1]);
    $rows[] = $row;
}
$rows[] = array(aot_test_token('a,b;c:d', ','), aot_test_token(';'), aot_test_token(':'), aot_test_token(''));
$queries = array('', 'a=1&a=2', 'a=one&a[]=two', 'a[]=two&a=one', 'x[-5]=one&x[]=two', ' a.b[c d]=z', '0=a&2=b&flag&empty=', 'q=a=b&encoded=%C3%A8+%2B', 'a[][b]=x&a[][c]=y');
foreach (array('root', 'other', 'a.b', ' space name', '0', '01', '-2') as $root) {
    foreach (array('', '[x]', '[]', '[-5]', '[x][]', '[][b]', '[x][c d]', '[00][2]') as $path) {
        foreach (array('', 'hello world', 'è+one', 'a=b&c', '0') as $value) {
            $name = urlencode($root . $path);
            $queries[] = $name . '=' . urlencode($value) . '&' . $name . '=replacement&' . urlencode($root . '[last]') . '=tail';
        }
    }
}
foreach ($queries as $query) {
    $parsed = array('stale' => 'removed');
    parse_str($query, $parsed);
    $rows[] = array($query, $parsed);
}
foreach (array('a[b=1', 'a[b]tail=1', 'a[b[c]]=1', "a=\0", 'a=%00', 'a=%FF', 'a' . str_repeat('[x]', 65) . '=v', str_repeat('a=1&', 1001), str_repeat('a', 1048577)) as $invalid) {
    $output = array('original' => 'unchanged');
    try {
        aot_test_parse($invalid, $output);
        throw new RuntimeException('The query profile boundary did not reject its input.');
    } catch (LogicException | ValueError $error) {
        $rows[] = array(get_class($error), $error->getMessage(), $output);
    }
}
echo json_encode($rows, JSON_THROW_ON_ERROR) . "\n";
