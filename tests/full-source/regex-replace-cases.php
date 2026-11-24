<?php
function regex_count_seed(): mixed { return 999; }
function regex_callback(array $matches): string {
    $result = '';
    foreach ($matches as $key => $value) {
        $result .= (is_int($key) ? 'i' : 's') . (string) $key . ':';
        if (is_array($value)) $result .= ($value[0] === null ? 'null' : base64_encode((string) $value[0])) . '@' . (string) $value[1];
        else $result .= $value === null ? 'null' : base64_encode((string) $value);
        $result .= ';';
    }
    return $result;
}
function regex_encoded(mixed $value): mixed {
    if ($value === null) return null;
    if (!is_array($value)) return base64_encode((string) $value);
    $result = array();
    foreach ((array) $value as $key => $text) $result[$key] = base64_encode((string) $text);
    return $result;
}
$results = array();
$patterns = array('/a/', '/(a)(b)?/', '/(?P<letter>a)(b)?/', '/^|$/', '/(?<=a)/', '/x*|a/', '/(?:)/u', '/\w+/u', '/\x00/', '/(a*)/', '/a|$/', '/(b)?a/');
$subjects = array('', 'ababa', 'aab', 'éèa', "\0a\0", "a\r\na");
$replacements = array('', '$0', '$1', '$12', '${1}0', '\1', '\\1', '\$1', '${100}', '$99', '$01', '${01}', '\01', '\x', 'prefix$0suffix');
foreach ($patterns as $pattern) foreach ($subjects as $subject) foreach ($replacements as $replacement) foreach (array(-2,-1,0,1,2) as $limit) {
    $count = regex_count_seed();
    $value = regex_replace_call($pattern,$replacement,$subject,$limit,$count);
    $results[] = array(regex_encoded($value),$count);
}
foreach ($patterns as $pattern) foreach ($subjects as $subject) foreach (array(0,256,512,768) as $flags) foreach (array(-1,0,1,2) as $limit) {
    $count = regex_count_seed();
    $value = regex_callback_call($pattern,'regex_callback',$subject,$limit,$count,$flags);
    $results[] = array(regex_encoded($value),$count);
}
foreach (array(array(),array('/a/','/b/'),array('/a/','/x/')) as $patterns_array) foreach (array(array(),array('x'),array('x','y'),'z') as $array_replacement) foreach (array('ababa',array('first'=>'aba',4=>'ba','empty'=>'')) as $array_subject) foreach (array(-1,0,1,2) as $limit) {
    $count = regex_count_seed();
    $value = regex_replace_call($patterns_array,$array_replacement,$array_subject,$limit,$count);
    $results[] = array(regex_encoded($value),$count);
}
class RegexReplacementStringValue { public function __toString(): string { return 'https://example.test/plugins'; } }
foreach (array('', 'https://example.test/wp-content/plugins', 'http://example.test/blog/', 0, 42, -3, 1.5, false, true, null, new RegexReplacementStringValue(), array('named'=>'https://example.test/a',9=>42), new stdClass()) as $scalar_subject) {
    $count = regex_count_seed();
    try { $value = regex_replace_call('|https?://[^/]+|i','',$scalar_subject,-1,$count); $results[] = array(regex_encoded($value),$count); }
    catch (Throwable $error) { $results[] = array(get_class($error),$count); }
}
foreach (str_split('!"#$%&\')*+,-./:;=>?@]^_`|}~') as $delimiter) {
    foreach (array('', ' ', "\t\n\r\v\f") as $leading) {
        foreach (array('', 'i', "i \r\n", 'S', 'X', 'iSX') as $modifiers) {
            foreach (array('a', '\\' . $delimiter . 'a') as $body) {
                $pattern = $leading . $delimiter . $body . $delimiter . $modifiers;
                $count = regex_count_seed();
                $results[] = array(regex_encoded(regex_replace_call($pattern, 'x', $delimiter . 'a A a', -1, $count)), $count);
                $count = regex_count_seed();
                $results[] = array(regex_encoded(regex_callback_call($pattern, 'regex_callback', $delimiter . 'a A a', -1, $count, 256)), $count);
            }
        }
    }
}
foreach (array('', '<script>x</script>', 'before<STYLE type="text/css">a\nb</STYLE>after', '<script>x</script><style>y</style>', '<p>kept</p>') as $html) {
    $count = regex_count_seed();
    $results[] = array(regex_encoded(regex_replace_call('@<(script|style)[^>]*?>.*?</\\1>@si', '', $html, -1, $count)), $count);
}
foreach (array("\n\n\$1", "\$1\n\n", "\$10 and \$2", "\$1\$2", "end\$9") as $replacement) {
    $count = regex_count_seed();
    $results[] = array(regex_encoded(regex_replace_call('/(one)(two)/', $replacement, 'onetwo', -1, $count)), $count);
}
echo json_encode($results), "\n";
