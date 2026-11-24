<?php

function wp_aot_html_codepoint(int $point): string {
    if ($point < 128) return chr($point);
    if ($point < 2048) return chr(192 | ($point >> 6)) . chr(128 | ($point & 63));
    if ($point < 65536) return chr(224 | ($point >> 12)) . chr(128 | (($point >> 6) & 63)) . chr(128 | ($point & 63));
    return chr(240 | ($point >> 18)) . chr(128 | (($point >> 12) & 63)) . chr(128 | (($point >> 6) & 63)) . chr(128 | ($point & 63));
}

function wp_aot_html_reference(string $token, int $flags, int $mode): ?string {
    if ($token[0] === '#') {
        $digits = substr($token, 1, -1);
        $base = 10;
        if ($digits !== '' && ($digits[0] === 'x' || $digits[0] === 'X')) {
            $digits = substr($digits, 1);
            $base = 16;
        }
        if ($digits === '') return null;
        $point = 0;
        for ($at = 0; $at < strlen($digits); ++$at) {
            $byte = ord($digits[$at]);
            if ($byte >= 48 && $byte <= 57) $digit = $byte - 48;
            elseif ($base === 16 && $byte >= 65 && $byte <= 70) $digit = $byte - 55;
            elseif ($base === 16 && $byte >= 97 && $byte <= 102) $digit = $byte - 87;
            else return null;
            $point = $point * $base + $digit;
            if ($point > 0x10FFFF) return null;
        }
        $mask = 0;
        foreach (wp_aot_html_ranges_data() as $range) {
            if ($point >= (int) $range[0] && $point <= (int) $range[1]) {
                $mask = (int) $range[2];
                break;
            }
        }
        if (($mask & $mode) === 0) return null;
        $exceptions = wp_aot_html_exceptions_data();
        $key = $mode . ':' . $point;
        $value = isset($exceptions[$key]) ? (string) $exceptions[$key] : wp_aot_html_codepoint($point);
    } else {
        $names = wp_aot_html_named_data();
        if (!array_key_exists($token, $names)) return null;
        $row = (array) $names[$token];
        if (((int) $row[1] & $mode) === 0) return null;
        $exceptions = wp_aot_html_named_exceptions_data();
        $key = $mode . ':' . $token;
        $value = isset($exceptions[$key]) ? (string) $exceptions[$key] : (string) $row[0];
    }
    if (($value === '"' && ($flags & 2) === 0) || ($value === "'" && ($flags & 1) === 0)) return null;
    return $value;
}

function wp_aot_html_entity_decode(mixed $value, mixed $flags = 11, mixed $encoding = 'UTF-8'): string {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) {
        return (string) wp_aot_native_api('html_entity_decode', array($value, $flags, $encoding));
    }
    $charset = strtolower((string) $encoding);
    if ($charset !== '' && $charset !== 'utf-8' && $charset !== 'utf8') {
        throw new LogicException('The native entity decoder requires UTF-8.');
    }
    $text = (string) $value;
    $at = strpos($text, '&');
    if ($at === false) return $text;
    require_once __DIR__ . '/aot-entities.php';
    $options = (int) $flags;
    $mode = 1 << (($options & 48) >> 4);
    $length = strlen($text);
    $plain = 0;
    $result = '';
    while ($at !== false) {
        $start = (int) $at;
        $end = $start + 1;
        if ($end < $length && $text[$end] === '#') ++$end;
        while ($end < $length) {
            $byte = ord($text[$end]);
            if (!(($byte >= 48 && $byte <= 57) || ($byte >= 65 && $byte <= 90) || ($byte >= 97 && $byte <= 122))) break;
            ++$end;
        }
        if ($end > $start + 1 && $end < $length && $text[$end] === ';') {
            $decoded = wp_aot_html_reference(substr($text, $start + 1, $end - $start), $options, $mode);
            if ($decoded !== null) {
                $result .= substr($text, $plain, $start - $plain) . $decoded;
                $plain = $end + 1;
            }
        }
        $at = strpos($text, '&', max($start + 1, $end));
    }
    return $result . substr($text, $plain);
}

function wp_aot_html_preserved_reference(string $token, int $mode): bool {
    if ($token[0] !== '#') {
        $names = wp_aot_html_named_data();
        if (!array_key_exists($token, $names)) return false;
        $row = (array) $names[$token];
        return ((int) $row[1] & $mode) !== 0;
    }
    $digits = substr($token, 1, -1);
    $base = 10;
    if ($digits !== '' && ($digits[0] === 'x' || $digits[0] === 'X')) {
        $digits = substr($digits, 1);
        $base = 16;
    }
    if ($digits === '') return false;
    $point = 0;
    for ($at = 0; $at < strlen($digits); ++$at) {
        $byte = ord($digits[$at]);
        if ($byte >= 48 && $byte <= 57) $digit = $byte - 48;
        elseif ($base === 16 && $byte >= 65 && $byte <= 70) $digit = $byte - 55;
        elseif ($base === 16 && $byte >= 97 && $byte <= 102) $digit = $byte - 87;
        else return false;
        $point = $point * $base + $digit;
        if ($point > 0x10FFFF) return false;
    }
    // Escaping preserves numeric references even when the decoder leaves them literal.
    return true;
}

function wp_aot_htmlspecialchars(mixed $value, mixed $flags = 11, mixed $encoding = 'UTF-8', mixed $double_encode = true): string {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) {
        return (string) wp_aot_native_api('htmlspecialchars', array($value, $flags, $encoding, $double_encode));
    }
    $charset = strtolower((string) $encoding);
    if ($charset !== '' && $charset !== 'utf-8' && $charset !== 'utf8') {
        throw new LogicException('The native HTML encoder requires UTF-8.');
    }
    $options = (int) $flags;
    if (($options & ~63) !== 0) throw new LogicException('These HTML encoding flags are outside the native fixture.');
    $text = (string) $value;
    if (preg_match('/\A[\s\S]*\z/u', $text) !== 1) {
        throw new LogicException('The native HTML encoder requires valid UTF-8.');
    }
    if ($double_encode) return htmlspecialchars($text, $options, 'UTF-8');
    require_once __DIR__ . '/aot-entities.php';
    $mode = 1 << (($options & 48) >> 4);
    $length = strlen($text);
    $plain = 0;
    $result = '';
    $at = strpos($text, '&');
    while ($at !== false) {
        $start = (int) $at;
        $end = $start + 1;
        if ($end < $length && $text[$end] === '#') ++$end;
        while ($end < $length) {
            $byte = ord($text[$end]);
            if (!(($byte >= 48 && $byte <= 57) || ($byte >= 65 && $byte <= 90) || ($byte >= 97 && $byte <= 122))) break;
            ++$end;
        }
        if ($end > $start + 1 && $end < $length && $text[$end] === ';'
            && wp_aot_html_preserved_reference(substr($text, $start + 1, $end - $start), $mode)) {
            $result .= htmlspecialchars(substr($text, $plain, $start - $plain), $options, 'UTF-8')
                . substr($text, $start, $end - $start + 1);
            $plain = $end + 1;
        }
        $at = strpos($text, '&', max($start + 1, $end));
    }
    return $result . htmlspecialchars(substr($text, $plain), $options, 'UTF-8');
}
