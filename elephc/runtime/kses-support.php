<?php

final class WordPressKsesCounter {
    public int $count = 0;
}

function _elephc_kses_parts(string $subject): array {
    $parts = [];
    $start = 0;
    for ($index = 0; $index < strlen($subject); ++$index) {
        if (ord($subject[$index]) === 0) {
            $parts[] = substr($subject, $start, $index - $start);
            $start = $index + 1;
        }
    }
    $parts[] = substr($subject, $start);
    return $parts;
}

// The selected patterns cannot match across a NUL byte. Preserve binary slices.
function _elephc_kses_callback(string $pattern, callable $callback, string $subject): string {
    $parts = _elephc_kses_parts($subject);
    $output = [];
    foreach ($parts as $part) {
        $text = (string) $part;
        $output[] = $text === '' ? '' : (string) preg_replace_callback($pattern, $callback, $text);
    }
    return implode("\0", $output);
}

function _elephc_kses_replace(string $pattern, string $replacement, string $subject): string {
    $output = [];
    foreach (_elephc_kses_parts($subject) as $part) {
        $text = (string) $part;
        $output[] = $text === '' ? '' : (string) preg_replace($pattern, $replacement, $text);
    }
    return implode("\0", $output);
}

function _elephc_kses_remove_nulls(string $subject): string {
    $output = '';
    for ($index = 0; $index < strlen($subject); ++$index) {
        if (ord($subject[$index]) !== 0) { $output .= $subject[$index]; }
    }
    return $output;
}

function wordpress_kses_protocols(): array {
    return ['http', 'https', 'mailto', 'ftp', 'feed'];
}

function wordpress_kses_charset(): string { return 'UTF-8'; }

function wordpress_kses_setup(): void {
    global $allowedposttags, $allowedtags, $allowedentitynames;
    // Ordinary globals are reset between native HTTP requests.
    $allowedentitynames = _elephc_kses_entity_names();
    // Decode the policy into uniform mixed-value maps on the pinned SDK.
    $allowedposttags = (array) json_decode('{
        "a":{"title":true,"class":true,"id":true,"data-*":true,"aria-label":true,"href":true,"rel":true,"target":true,"download":{"valueless":"y"}},
        "img":{"title":true,"class":true,"id":true,"data-*":true,"aria-label":true,"src":true,"alt":true,"width":{"maxval":4096,"minval":1}},
        "div":{"title":true,"class":true,"id":true,"data-*":true,"aria-label":true},
        "span":{"title":true,"class":true,"id":true,"data-*":true,"aria-label":true},
        "p":{"title":true,"class":true,"id":true,"data-*":true,"aria-label":true}
    }', true);
    $allowedtags = $allowedposttags;
    WordPressHookCallbacks::register('wordpress_kses_protocols', wordpress_kses_protocols(...));
    WordPressHookCallbacks::register('wordpress_kses_charset', wordpress_kses_charset(...));
    add_filter('kses_allowed_protocols', 'wordpress_kses_protocols', 10, 0);
    add_filter('pre_option_blog_charset', 'wordpress_kses_charset', 10, 0);
}

function wordpress_kses_utf8(string $input): string {
    foreach (_elephc_kses_parts($input) as $part) {
        if ((string) $part !== '' && preg_match('/\A[\s\S]*\z/u', (string) $part) !== 1) {
            throw new InvalidArgumentException('The attribute profile requires valid UTF-8.');
        }
    }
    return $input;
}

function wordpress_kses_attribute_input(string $input): string {
    wordpress_kses_utf8($input);
    if (count(_elephc_kses_parts($input)) > 1) {
        throw new InvalidArgumentException('NUL bytes are outside the HTML attribute profile.');
    }
    return $input;
}

function _elephc_kses_escape(string $input): string {
    $input = wordpress_kses_attribute_input($input);
    $input = wp_kses_normalize_entities($input);
    // HTML 4.01 does not recognize apos; the remaining core entity table does.
    $input = str_replace('&apos;', '&amp;apos;', $input);
    return strtr($input, ['<' => '&lt;', '>' => '&gt;', '"' => '&quot;', "'" => '&#039;']);
}

function wordpress_kses_text(string $mode, string $input, string $element = 'a'): string {
    wordpress_kses_setup();
    foreach (['wp_kses_uri_attributes', 'wp_kses_allowed_html', 'attribute_escape'] as $hook) {
        remove_all_filters($hook);
    }
    wordpress_kses_utf8($input);
    if (!in_array($mode, ['protocol', 'entities', 'decode'], true)) {
        wordpress_kses_attribute_input($input);
    }
    switch ($mode) {
        case 'protocol': return wp_kses_bad_protocol($input, wordpress_kses_protocols());
        case 'entities': return wp_kses_normalize_entities($input);
        case 'decode': return wp_kses_decode_entities($input);
        case 'escape': return esc_attr($input);
        case 'attribute': return wp_kses_one_attr($input, $element);
        case 'tag-attributes': return (string) json_encode(wp_kses_attr_parse($input), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        case 'attributes': return (string) json_encode(wp_kses_hair_parse($input), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        case 'filtered-uri':
            add_filter('wp_kses_uri_attributes', static function (array $names): array { $names[] = 'data-url'; return $names; });
            return wp_kses_one_attr($input, $element);
        case 'filtered-allowlist':
            add_filter('wp_kses_allowed_html', static function (array $tags, string $context): array {
                $attributes = (array) $tags['a'];
                $attributes['lang'] = true;
                $tags['a'] = $attributes;
                return $tags;
            }, 10, 2);
            return wp_kses_one_attr($input, $element);
        case 'filtered-escape':
            add_filter('attribute_escape', static function (string $safe, string $original): string { return '[' . $safe . ']'; }, 10, 2);
            return esc_attr($input);
        case 'html': throw new InvalidArgumentException('Full HTML filtering requires the HTML API profile.');
        case 'css': throw new InvalidArgumentException('CSS filtering is outside the attribute profile.');
        default: throw new InvalidArgumentException('Unknown KSES mode.');
    }
}
