<?php

$rows = array();
foreach (array('', 'abcabc', "a\0bca\0", '👋wave') as $subject) {
    foreach (array('', 'abc', "a\0bc", "\xF0\x9F\x91\x8B") as $mask) {
        foreach (array(-99, -6, -2, 0, 2, 6, 99) as $offset) {
            foreach (array(null, -99, -2, 0, 2, 99) as $length) {
                $rows[] = array(strcspn((string) $subject, (string) $mask, (int) $offset, $length === null ? null : (int) $length));
            }
        }
    }
}
foreach (array('', 'abcabc', "a\0bca\0", 'cafés', "\xffA\x80a") as $subject) {
    foreach (array('', 'a', 'AB', "\0tail", "\xff", 'é', 'z') as $needle) {
        foreach (array(false, true) as $before) {
            $last = strrchr((string) $subject, (string) $needle, (bool) $before);
            $first = stristr((string) $subject, (string) $needle, (bool) $before);
            $rows[] = array($last === false ? false : base64_encode((string) $last), $first === false ? false : base64_encode((string) $first));
        }
        foreach (array(-20, -1, 0, 1, 5, 20) as $offset) {
            foreach (array(null, -1, 0, 1, 2, 20) as $length) {
                foreach (array(false, true) as $fold) {
                    try {
                        $comparison = substr_compare((string) $subject, (string) $needle, (int) $offset, $length === null ? null : (int) $length, (bool) $fold);
                        // PHP documents only the sign; the raw magnitude varies with the runtime implementation.
                        $rows[] = array($comparison <=> 0);
                    } catch (ValueError $error) {
                        $rows[] = array($error->getMessage());
                    }
                }
            }
        }
    }
}
$bytes = '';
for ($code = 0; $code < 256; ++$code) $bytes .= chr($code);
foreach (array('', 'hello world', "\0\a\b\t\n\r\v\f\\\"\xff", $bytes) as $subject) {
    foreach (array('', "\0..\xff", 'a..zA..Z', "\0..\37\177..\377", "'\"\\", "\t\n\r", 'abc.') as $characters) {
        $escaped = addcslashes((string) $subject, (string) $characters);
        $rows[] = array(base64_encode($escaped), base64_encode(stripcslashes($escaped)));
    }
}
foreach (array('', '\\', '\\\\', '\\a\\b\\f\\n\\r\\t\\v', '\\0\\00\\000\\377\\400\\777', '\\8\\9\\q', '\\x0\\x01\\xFF\\x100\\xg\\x', 'a\\1b\\12c\\123d\\1234e') as $escaped) {
    $rows[] = array(base64_encode(stripcslashes((string) $escaped)));
}
$simple_iri = new \SimplePie\IRI();
$requests_iri = new WordPressRequestsIriByteProbe();
$iri_inputs = array('', 'plain ASCII %80 %ff', 'é汉👋', "a\0b\x80c\xFF", "\xC3", "\x80\x80\xFF", $bytes);
$iri_high_bytes = '';
$iri_random_bytes = '';
for($iri_code=128;$iri_code<256;++$iri_code)$iri_high_bytes.=chr($iri_code);
for($iri_code=0;$iri_code<256;++$iri_code){$iri_inputs[]=chr($iri_code);$iri_random_bytes.=chr(($iri_code*37+11)%256);}
$iri_inputs[]=$iri_high_bytes;
$iri_inputs[]=strrev($iri_high_bytes);
$iri_inputs[]=$iri_random_bytes;
foreach($iri_inputs as $iri_subject) {
    $iri_expected = '';
    for($iri_index=0;$iri_index<strlen($iri_subject);++$iri_index) {
        $iri_ord=ord($iri_subject[$iri_index]);
        $iri_expected.=$iri_ord>=128?sprintf('%%%02X',$iri_ord):$iri_subject[$iri_index];
    }
    $iri_simple_first=$simple_iri->to_uri((string)$iri_subject);
    $iri_requests_first=$requests_iri->encode($iri_subject);
    if($iri_simple_first!==$iri_expected || $iri_requests_first!==$iri_expected)throw new LogicException('IRI byte conversion differs from the independently specified mask.');
    $rows[]=array(base64_encode($iri_subject),base64_encode($iri_simple_first),base64_encode($iri_requests_first),
        base64_encode($simple_iri->to_uri((string)$iri_subject)),base64_encode($requests_iri->encode($iri_subject)));
}
foreach(array(null,false,true,0,37,3.5,array(),new stdClass()) as $iri_invalid) {
    $iri_invalid_result=$requests_iri->encode($iri_invalid);
    if($iri_invalid_result!==false)throw new LogicException('Requests IRI accepts only string conversion inputs.');
    $rows[]=array(gettype($iri_invalid),$iri_invalid_result);
}
echo json_encode($rows, JSON_THROW_ON_ERROR) . "\n";
