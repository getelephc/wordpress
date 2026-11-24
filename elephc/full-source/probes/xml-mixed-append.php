<?php
class AppendXmlMetadata extends SimpleXMLElement {}
function appendOpenTrace(): array { return array(); }
$trace = appendOpenTrace();
function appendRecord(string $tag, array $data = array()): void {
    global $trace;
    $trace[] = array($tag, $data);
}
$f = static function(): void { appendRecord('closure', array()); };
call_user_func_array($f, array());
appendRecord('after', array(1, 2));
echo json_encode($trace, JSON_THROW_ON_ERROR), chr(10);
unset($trace, $f);
