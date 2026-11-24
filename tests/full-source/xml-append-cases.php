<?php

// The native platform activates XML dimension dispatch for these ordinary arrays.
function aot_xml_append_empty(): array {
    return array();
}

$aot_xml_append_trace = aot_xml_append_empty();
function aot_xml_append_record(string $tag, array $data = array()): void {
    global $aot_xml_append_trace;
    $aot_xml_append_trace[] = array($tag, $data);
}

$callback = static function(): void {
    aot_xml_append_record('closure', array());
};
call_user_func_array($callback, array());
$first_snapshot = $aot_xml_append_trace;
aot_xml_append_record('after', array(1, 2));
$second_snapshot = $aot_xml_append_trace;
for ($iteration = 0; $iteration < 8; ++$iteration) {
    aot_xml_append_record('loop', array($iteration, null, false, array('nested' => $iteration)));
}
echo json_encode(array($first_snapshot, $second_snapshot, $aot_xml_append_trace), JSON_THROW_ON_ERROR), "\n";
unset($aot_xml_append_trace, $callback, $first_snapshot, $second_snapshot);
