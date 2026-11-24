<?php

$results = array();
foreach (array_keys(wp_aot_html_named_data()) as $name) {
    foreach (array(0, 16, 32, 48) as $mode) {
        foreach (array(0, 1, 2, 3) as $quotes) {
            $results[] = base64_encode(entity_decode_call('before&' . $name . 'after', $mode | $quotes | 8, 'UTF-8'));
        }
    }
}
$points = array(0, 1, 9, 10, 12, 13, 34, 39, 127, 128, 255, 256, 65535, 65536, 0x10FFFF, 0x110000);
foreach (wp_aot_html_ranges_data() as $range) {
    foreach (array(-1, 0, 1) as $delta) {
        $points[] = (int) $range[0] + $delta;
        $points[] = (int) $range[1] + $delta;
    }
}
$seed = 193;
for ($index = 0; $index < 256; ++$index) {
    $seed = ($seed * 1103515245 + 12345) & 2147483647;
    $points[] = $seed % 0x110000;
}
foreach ($points as $point) {
    if ($point < 0) continue;
    foreach (array(0, 16, 32, 48) as $mode) {
        foreach (array(0, 1, 2, 3) as $quotes) {
            $flags = $mode | $quotes | 8;
            $results[] = base64_encode(entity_decode_call('&#' . $point . ';', $flags, 'UTF-8'));
            $results[] = base64_encode(entity_decode_call('&#x' . dechex($point) . ';', $flags, 'UTF-8'));
        }
    }
}
foreach (array('', 'plain', '&', '&&amp;', '&amp;&lt;&unknown;', '&amp', '&#;', '&#x;', '&#X00041;', '&#999999999999999999999999;', '&amp;lt;', "a\0&amp;b", '&lang;&rang;', '&notin;&notin', 'a&invalid name;&copy;') as $text) {
    foreach (array(0, 16, 32, 48) as $mode) {
        $results[] = base64_encode(entity_decode_call($text, $mode | 3 | 8, 'UTF-8'));
    }
}
// Preserved references use encoder rules, which differ from decoder code-point validity.
foreach (array_keys(wp_aot_html_named_data()) as $name) {
    foreach (array(0, 16, 32, 48) as $mode) {
        foreach (array(0, 1, 2, 3) as $quotes) {
            $text = '<\'"&' . $name . '&unknown;>';
            $results[] = base64_encode(entity_escape_call($text, $mode | $quotes, 'UTF-8', false));
        }
    }
}
foreach (array(0, 1, 9, 13, 128, 0xD800, 0xDFFF, 0xFFFF, 0x10FFFF, 0x110000) as $point) {
    foreach (array(0, 16, 32, 48) as $mode) {
        foreach (array(0, 1, 2, 3) as $quotes) {
            foreach (array(false, true) as $double) {
                $text = '&#' . $point . ';|&#x' . dechex($point) . ';';
                $results[] = base64_encode(entity_escape_call($text, $mode | $quotes, 'UTF-8', $double));
            }
        }
    }
}
foreach (array('', 'plain', '<>\'"&', 'é汉😀', "a\0<&amp;b", '&', '&&amp;', '&amp', '&#;', '&#x;', '&#X00041;', '&#000000000000039;', '&#99999999999999999999;', '&amp;lt;', '&apos;&acE;&notin;', 'a&invalid name;&copy;') as $text) {
    foreach (array(0, 16, 32, 48) as $mode) {
        foreach (array(0, 1, 2, 3) as $quotes) {
            foreach (array(0, 4, 8, 12) as $errors) {
                foreach (array(false, true) as $double) {
                    $results[] = base64_encode(entity_escape_call($text, $mode | $quotes | $errors, 'UTF-8', $double));
                }
            }
        }
    }
}
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
