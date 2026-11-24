<?php
require __DIR__ . '/pclzip-copy-cases.php';
if (isset($argv[1])) {
    echo json_encode(pclzip_copy_cases()), "\n";
    return;
}
$results = array();
foreach (array('',"a\nb","one\r\ntwo\n", "\0a\n\0b", 'éè汉字') as $content) foreach (array(null,1,2,3,4,8,64) as $length) {
    $stream = fopen('php://memory','w+');
    fwrite($stream,$content);rewind($stream);
    $trace = array();
    for ($at=0;$at<8;++$at) {
        $value = read_line_call($stream,$length);
        $trace[] = array($value===false?false:base64_encode($value),ftell($stream),feof($stream));
        if ($value===false) break;
    }
    $results[] = array($trace,close_call($stream));
}
foreach (array('',"a\0b\n",'éètest') as $content) foreach (array(1,2,4,64) as $length) {
    $stream=fopen('php://memory','w+');fwrite($stream,$content);rewind($stream);
    $value=read_bytes_call($stream,$length);$results[]=array($value===false?false:base64_encode($value),ftell($stream),feof($stream),close_call($stream));
}
foreach (array(0,-1) as $bad) {
    $stream=fopen('php://memory','w+');
    try {read_line_call($stream,$bad);$results[]='missing error';} catch (ValueError $error) {$results[]='ValueError';}
    try {read_bytes_call($stream,$bad);$results[]='missing error';} catch (ValueError $error) {$results[]='ValueError';}
    close_call($stream);
}
require __DIR__ . '/avif-io-cases.php';
foreach (avif_io_cases() as $row) $results[] = $row;
require __DIR__ . '/pomo-stream-cases.php';
foreach (pomo_stream_cases() as $row) $results[] = $row;
foreach (pclzip_copy_cases() as $row) $results[] = $row;
echo json_encode($results),"\n";
