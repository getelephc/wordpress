<?php
function _elephc_shortcode_quote(string $text): string {
    $quoted = '';
    for ($index = 0; $index < strlen($text); ++$index) {
        $character = $text[$index];
        if (str_contains('.\\+*?[^]$(){}=!<>|:#-', $character)) { $quoted .= '\\'; }
        $quoted .= $character;
    }
    return $quoted;
}
function _elephc_shortcode_invalid(string $function, string $message, string $version): void {
    throw new InvalidArgumentException($function . ': ' . $message);
}
// The selected core calls use sequential array-search replacement.
function _elephc_replace_many(array $search, mixed $replace, string $subject): string {
    $is_array = is_array($replace);
    $replacements = $is_array ? array_values((array) $replace) : [];
    $index = 0;
    foreach ($search as $needle) {
        $replacement = $is_array ? (string) ($replacements[$index] ?? '') : (string) $replace;
        $subject = str_replace((string) $needle, $replacement, $subject);
        ++$index;
    }
    return $subject;
}
function _elephc_shortcode_unescape(string $text): string {
    $output = '';
    $length = strlen($text);
    for ($index = 0; $index < $length; ++$index) {
        $character = $text[$index];
        if ($character !== '\\' || $index + 1 >= $length) {
            $output .= $character;
            continue;
        }
        $character = $text[++$index];
        switch ($character) {
            case 'n': $output .= "\n"; break;
            case 'r': $output .= "\r"; break;
            case 't': $output .= "\t"; break;
            case 'v': $output .= "\v"; break;
            case 'f': $output .= "\f"; break;
            case 'a': $output .= chr(7); break;
            case 'b': $output .= chr(8); break;
            case 'x':
                $value = 0;
                $digits = 0;
                while ($digits < 2 && $index + 1 < $length) {
                    $digit = strpos('0123456789abcdef', strtolower($text[$index + 1]));
                    if ($digit === false) { break; }
                    $value = $value * 16 + (int) $digit;
                    ++$index;
                    ++$digits;
                }
                $output .= $digits === 0 ? 'x' : chr($value);
                break;
            default:
                if (str_contains('01234567', $character)) {
                    $value = ord($character) - 48;
                    $digits = 1;
                    while ($digits < 3 && $index + 1 < $length && str_contains('01234567', $text[$index + 1])) {
                        $value = $value * 8 + ord($text[++$index]) - 48;
                        ++$digits;
                    }
                    $output .= chr($value % 256);
                } else {
                    $output .= $character;
                }
        }
    }
    return $output;
}
function _elephc_translate_pairs(string $text, array $map): string {
    // Select associative string storage before inserting dynamic keys.
    $strings = ['native-map' => ''];
    unset($strings['native-map']);
    foreach ($map as $key => $value) {
        $strings[(string) $key] = (string) $value;
    }
    return strtr($text, $strings);
}

// Core tag lists contain strings; retain the first list's keys and order.
function _elephc_shortcode_intersect(array $first, array $second): array {
    $result = ['native-tags' => ''];
    unset($result['native-tags']);
    foreach ($first as $key => $value) {
        foreach ($second as $candidate) {
            if ((string) $value === (string) $candidate) {
                $result[$key] = (string) $value;
                break;
            }
        }
    }
    return $result;
}
