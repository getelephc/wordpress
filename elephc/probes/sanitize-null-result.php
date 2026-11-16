<?php

// Runtime diagnostic, outside the verified native profiles.
require __DIR__ . '/../runtime/kses-bootstrap.php';

function wordpress_probe_sanitize_text(string $input): string {
    $request = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request) || !str_starts_with(ltrim($input), '{')) {
        throw new InvalidArgumentException('Sanitization input must be a JSON object.');
    }
    $request = (array) $request;
    $op = $request['op'] ?? 'field';
    if (!is_string($op) || !in_array($op, ['field', 'textarea', 'strip-tags', 'less-than', 'html', 'key', 'class', 'hex', 'hex-no-hash'], true)) {
        throw new InvalidArgumentException('Unknown sanitization operation.');
    }
    if (!isset($request['text']) || !is_string($request['text'])) { throw new InvalidArgumentException('Missing text string.'); }
    $text = wordpress_kses_attribute_input((string) $request['text']);
    $fallback = $request['fallback'] ?? '';
    if (!is_string($fallback)) { throw new InvalidArgumentException('Class fallback must be a string.'); }
    $fallback = wordpress_kses_attribute_input((string) $fallback);
    foreach (['remove_breaks', 'filtered'] as $flag) {
        if (array_key_exists($flag, $request) && !is_bool($request[$flag])) { throw new InvalidArgumentException($flag . ' must be a boolean.'); }
    }
    wordpress_kses_setup();
    foreach (['sanitize_text_field', 'sanitize_textarea_field', 'sanitize_key', 'sanitize_html_class', 'esc_html'] as $hook) {
        remove_all_filters($hook);
    }
    if ((bool) ($request['filtered'] ?? false)) {
        $hook = '';
        switch ((string) $op) {
            case 'field': $hook = 'sanitize_text_field'; break;
            case 'textarea': $hook = 'sanitize_textarea_field'; break;
            case 'key': $hook = 'sanitize_key'; break;
            case 'html': $hook = 'esc_html'; break;
            case 'class': $hook = 'sanitize_html_class'; break;
            default: throw new InvalidArgumentException('This operation has no sanitization filter.');
        }
        if ($op === 'class') {
            add_filter($hook, static function (string $safe, string $original, string $fallback): string {
                return $original . '|' . $fallback . '|' . $safe;
            }, 10, 3);
        } else {
            add_filter($hook, static function (string $safe, string $original): string { return $original . '|' . $safe; }, 10, 2);
        }
    }
    switch ((string) $op) {
        case 'field': $result = sanitize_text_field($text); break;
        case 'textarea': $result = sanitize_textarea_field($text); break;
        case 'strip-tags': $result = wp_strip_all_tags($text, (bool) ($request['remove_breaks'] ?? false)); break;
        case 'less-than': $result = wp_pre_kses_less_than($text); break;
        case 'html': $result = esc_html($text); break;
        case 'key': $result = sanitize_key($text); break;
        case 'class': $result = sanitize_html_class($text, (string) $fallback); break;
        case 'hex': $result = sanitize_hex_color($text); break;
        case 'hex-no-hash': $result = sanitize_hex_color_no_hash($text); break;
    }
    return (string) json_encode(['result' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

echo wordpress_probe_sanitize_text((string) ($argv[1] ?? '{"op":"hex","text":"#1234"}')) . "\n";

function wordpress_probe_encoded_color(mixed $value): string {
    return (string) json_encode(['result' => $value]);
}

$color = (string) ($argv[2] ?? '#1234');
echo json_encode(['direct' => sanitize_hex_color($color)]) . "\n";
echo wordpress_probe_encoded_color(sanitize_hex_color($color)) . "\n";
