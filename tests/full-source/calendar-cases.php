<?php

function calendar_cases(): array {
    date_default_timezone_set('UTC');
    $rows = array();
    $parts = array(
        array(0,0,0,1,1,1970), array(23,59,59,12,31,1969),
        array('0','0','0','2','29','2024'), array(0,0,0,'2','29','2023'),
        array(25,61,61,13,32,2023), array(-1,-1,-1,0,0,2024),
        array(0,0,0,2,28,1900), array(0,0,0,2,29,2000),
        array(3,14,8,1,19,2038), array(0,0,0,12,31,9999),
        array(1.5,2.5,3.5,'3','15','2024'), array(false,true,true,3,15,2024),
    );
    foreach (array(0,1,69,70,99,100,101) as $year) $parts[] = array(0,0,0,1,1,$year);
    foreach (array('bad','',array(),INF) as $invalid) {
        for ($position=0;$position<6;++$position) {
            $values = array(0,0,0,2,29,2024);
            $values[$position] = $invalid;
            $parts[] = $values;
        }
    }
    foreach (array(false,true) as $utc) foreach ($parts as $values) {
        try {
            $timestamp = calendar_call($utc,(array)$values);
            $rows[] = array('ok',$timestamp,$timestamp===false?false:calendar_format_call('Y-m-d H:i:s',(int)$timestamp));
        } catch (TypeError $error) {
            $rows[] = array('error','TypeError');
        }
    }
    foreach (array(-2,-1,0,1709164800) as $timestamp) {
        foreach (array('', 'Y-m-d H:i:s', 'U', 's', 'c', 'r',
            'D, d M Y H:i:s \\G\\M\\T', 'Y\\Y', '\\U U', 'u v \\s s', '\\', 's\\') as $pattern) {
            $rows[] = array('format',calendar_format_call($pattern,$timestamp));
        }
    }
    return $rows;
}
