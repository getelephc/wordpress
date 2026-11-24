<?php
$result = array();
$patterns = array('/a(b)?(c)?/', '/(?P<first>a)(?P<gap>b)?(?P<last>c)?/', '/(a)|(b)/', '/(?<=a)b/', '/a.b/s', '/^(è|好)+$/u', '/^\\w+$/u', '/a(.)/i');
foreach ($patterns as $pattern) {
    foreach (array('', 'abac', 'a', 'b', 'ac', "a\0b", "a\nb", 'è好', 'abcabc') as $subject) {
        foreach (array(0, 256, 512, 768) as $flags) {
            foreach (array(0, 1, -1) as $offset) {
                if ($offset > strlen($subject)) continue;
                $matches = null;
                $status = probe_match($pattern, $subject, $matches, $flags, $offset);
                $result[] = array($status, $matches);
            }
        }
    }
}
final class RegexSubjectText implements Stringable {
    public function __toString(): string {return 'https://youtu.be/example';}
}
foreach (array('#^https?://(.+\.)?vimeo\.com/.*#', '#^https?://(?:www\.)?(?:youtube\.com/watch|youtu\.be/)#') as $pattern) {
    foreach (array('', 'https://vimeo.com/123', 'https://player.vimeo.com/123',
        'http://www.youtube.com/watch?v=123', 'https://youtu.be/123', 'http://127.0.0.1:9000/sample.mp4',
        "https://vimeo.com/123\0", 0, 12, 1.5, true, false, null,
        array(), new stdClass(), new RegexSubjectText()) as $subject) {
        try {$result[] = array(regex_subject_call($pattern, $subject));}
        catch (TypeError $error) {$result[] = array('TypeError');}
    }
}
function regex_split_snapshot(mixed $value): mixed {
    if (is_string($value)) return base64_encode((string) $value);
    if (!is_array($value)) return $value;
    $encoded = array();
    foreach ((array) $value as $key => $item) $encoded[$key] = regex_split_snapshot($item);
    return $encoded;
}
foreach (array('/( +)/', '/(?=a)/', '/\x00/', '/(?P<space> +)/', '/(a)|(b)/', '/(?:)/', '/(?:)/u', '/(x)?a(y)?/', '/a*|b/', '/^|$/u', '/(é|好)/u') as $option_pattern) {
    foreach (array('', 'a  b a', "a\0b", 'é好a', "a\r\nb") as $option_subject) {
        foreach (array(-2,-1,0,1,2,55,PHP_INT_MAX,PHP_INT_MAX+1,'2','1.5','bad',true,false,null,1.5,INF,NAN,array(),new stdClass()) as $option_limit) {
            foreach (array(0,1,2,3,4,5,6,7,'3','bad',null,true,false,1.5,array(),new stdClass()) as $option_flags) {
                try {$result[] = array(regex_split_snapshot(regex_split_options($option_pattern,$option_subject,$option_limit,$option_flags)));}
                catch (Throwable $error) {$result[] = array(get_class($error));}
            }
        }
    }
}
foreach (str_split('!"#$%&\')*+,-./:;=>?@]^_`|}~') as $delimiter) {
    foreach (array('', ' ', "\t\n\r\v\f") as $leading) {
        foreach (array('', 'i', "i \r\n", 'S', 'X', 'iSX') as $modifiers) {
            foreach (array('a', '\\' . $delimiter . 'a') as $body) {
                $pattern = $leading . $delimiter . $body . $delimiter . $modifiers;
                $matches = null;
                $status = probe_match($pattern, $delimiter . 'a A a', $matches, 256, 0);
                $result[] = array($status, $matches);
                $result[] = array(regex_split_snapshot(regex_split_options($pattern, $delimiter . 'a A a', -1, 4)));
            }
        }
    }
}
echo json_encode($result, JSON_THROW_ON_ERROR), chr(10);
