<?php
$results = array();
$file = TOUCH_TEST_FILE;
try {
    foreach (array(array(1234567,7654321),array(0,0),array(-3,12),array(null,null),array(1234567,null),array(null,7654321),array('1234567','7654321'),array(false,true),array(1.5,-1.5)) as $arguments) {
        file_put_contents($file,'owned timestamp fixture');
        $before = time();
        $result = false;
        $error_type = '';
        try { $result = filesystem_touch_call($file,$arguments[0],$arguments[1]); }
        catch (ValueError $error) { $error_type = 'ValueError'; }
        $after = time();
        filesystem_touch_cache($file);
        $modified = filemtime($file);
        $accessed = fileatime($file);
        $results[] = array($result, $error_type, $modified >= $before && $modified <= $after ? 'now' : $modified, $accessed >= $before && $accessed <= $after ? 'now' : $accessed);
        unlink($file);
    }
} finally {
    if (is_file($file)) unlink($file);
}
echo json_encode($results), "\n";
