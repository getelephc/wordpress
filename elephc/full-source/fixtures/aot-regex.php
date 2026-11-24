<?php

function wp_aot_pcre_pattern(string $pattern): array {
    if (strlen($pattern) < 2) throw new ValueError('The regex fixture requires delimiters.');
    $length = strlen($pattern);
    $begin = 0;
    while ($begin < $length && str_contains(" \t\n\r\v\f", $pattern[$begin])) ++$begin;
    if ($begin === $length) throw new ValueError('The regex fixture requires delimiters.');
    $delimiter = $pattern[$begin];
    if (!str_contains('!"#$%&\')*+,-./:;=>?@]^_`|}~', $delimiter)) throw new LogicException('The regex fixture requires an unpaired ASCII punctuation delimiter.');
    $end = $begin + 1;
    while ($end < $length) {
        if ($pattern[$end] === '\\' && $end + 1 < $length) ++$end;
        elseif ($pattern[$end] === $delimiter) break;
        ++$end;
    }
    if ($end === $length) throw new ValueError('Missing regex closing delimiter.');
    $modifiers = substr($pattern, $end + 1); $options = 0;
    for ($at = 0; $at < strlen($modifiers); ++$at) {
        $modifier = $modifiers[$at];
        if ($modifier === 'i') $options |= 8;
        elseif ($modifier === 'm') $options |= 1024;
        elseif ($modifier === 's') $options |= 32;
        elseif ($modifier === 'x') $options |= 128;
        elseif ($modifier === 'u') $options |= 655360;
        elseif ($modifier === 'U') $options |= 262144;
        elseif ($modifier === 'D') $options |= 16;
        elseif ($modifier === 'A') $options |= 2147483648;
        elseif ($modifier === ' ' || $modifier === "\n" || $modifier === "\r") continue;
        elseif ($modifier === 'S' || $modifier === 'X') continue;
        elseif ($modifier === 'J') throw new LogicException('Duplicate named regex captures are outside the fixture.');
        else throw new LogicException('Unsupported native regex modifier: ' . $modifier);
    }
    return array(substr($pattern, $begin + 1, $end - $begin - 1), $options);
}

function wp_aot_regex_invoke(string $name, array $arguments): int|false {
    $result = call_user_func_array($name, $arguments);
    if ($result === false) return false;
    return (int) $result;
}
function wp_aot_preg_match(mixed $pattern, mixed $subject, mixed &$matches = null, mixed $flags = 0, mixed $offset = 0): int|false {
    $flags = (int) $flags;
    $offset = (int) $offset;
    $arguments = array((string) $pattern, (string) $subject, $matches, $flags, $offset);
    $output =& $arguments[2];
    $name = defined('WP_AOT_NATIVE_RUNTIME') ? 'wp_aot_pcre_capture' : 'preg_match';
    try { return wp_aot_regex_invoke($name, $arguments); }
    finally { $matches = $output; }
}

function wp_aot_regex_text(mixed $value): string {
    if (is_array($value) || is_resource($value) || (is_object($value) && !$value instanceof Stringable)) throw new TypeError('The native regex fixture requires scalar or Stringable text.');
    return (string) $value;
}

function wp_aot_regex_next(string $pattern, string $subject, mixed &$matches, int $flags, int $offset, int $options): int|false {
    $arguments = array($pattern, $subject, $matches, $flags, $offset, $options);
    $output =& $arguments[2];
    try { return wp_aot_regex_invoke('wp_aot_pcre_capture_options', $arguments); }
    finally { $matches = $output; }
}

function wp_aot_regex_replacement(string $replacement, array $matches): string {
    $result = '';
    $length = strlen($replacement);
    for ($at = 0; $at < $length; ++$at) {
        $byte = $replacement[$at];
        if ($byte === '\\' && $at + 1 < $length && ($replacement[$at+1] === '\\' || $replacement[$at+1] === '$')) {
            $result .= $replacement[++$at];
            continue;
        }
        if (($byte === '$' || $byte === '\\') && $at + 1 < $length) {
            $begin = $at + 1;
            $braced = $byte === '$' && $replacement[$begin] === '{';
            if ($braced) ++$begin;
            if ($begin < $length && $replacement[$begin] >= '0' && $replacement[$begin] <= '9') {
                $number = ord($replacement[$begin]) - 48;
                $end = $begin + 1;
                if ($end < $length && $replacement[$end] >= '0' && $replacement[$end] <= '9') {
                    $number = $number * 10 + ord($replacement[$end]) - 48;
                    ++$end;
                }
                if (!$braced || ($end < $length && $replacement[$end] === '}')) {
                    $capture = $matches[$number] ?? array('', -1);
                    $result .= (string) $capture[0];
                    $at = $braced ? $end : $end - 1;
                    continue;
                }
            }
        }
        $result .= $byte;
    }
    return $result;
}

function wp_aot_regex_replace_one(string $pattern, mixed $replacement, string $subject, int $limit, mixed &$count, bool $callback, int $flags): ?string {
    $result = '';
    $copy_from = 0;
    $search_at = 0;
    $options = 0;
    $matched = 0;
    $length = strlen($subject);
    $parsed = wp_aot_pcre_pattern($pattern);
    $unicode = (((int) $parsed[1]) & 524288) !== 0;
    while ($search_at <= $length && ($limit < 0 || $matched < $limit)) {
        $captures = wp_aot_mixed(array());
        $status = wp_aot_regex_next($pattern, $subject, $captures, 256 | ($flags & 512), $search_at, $options);
        if ($status === false) return null;
        if ($status === 0) {
            if ($options === 0 || $search_at === $length) break;
            $options = 0;
            ++$search_at;
            if ($unicode) while ($search_at < $length && (ord($subject[$search_at]) & 192) === 128) ++$search_at;
            continue;
        }
        $full = $captures[0];
        $start = (int) $full[1];
        $size = strlen((string) $full[0]);
        $result .= substr($subject, $copy_from, $start - $copy_from);
        if ($callback) {
            $arguments = wp_aot_empty_request_array();
            foreach ((array) $captures as $key => $capture) $arguments[$key] = ($flags & 256) !== 0 ? $capture : $capture[0];
            $result .= wp_aot_regex_text(call_user_func_array($replacement, array($arguments)));
        } else $result .= wp_aot_regex_replacement((string) $replacement, (array) $captures);
        ++$matched;
        ++$count;
        $copy_from = $start + $size;
        $search_at = $copy_from;
        $options = $size === 0 ? 2147483656 : 0;
    }
    return $result . substr($subject, $copy_from);
}

function wp_aot_regex_replace_subject(mixed $patterns, mixed $replacement, string $subject, int $limit, mixed &$count, bool $callback, int $flags): ?string {
    if (!is_array($patterns)) {
        if (!$callback && is_array($replacement)) throw new TypeError('An array replacement requires an array pattern.');
        return wp_aot_regex_replace_one(wp_aot_regex_text($patterns), $callback ? $replacement : wp_aot_regex_text($replacement), $subject, $limit, $count, $callback, $flags);
    }
    $replacements = is_array($replacement) && !$callback ? array_values((array) $replacement) : wp_aot_empty_request_array();
    $index = 0;
    foreach ((array) $patterns as $pattern) {
        $value = $callback || !is_array($replacement) ? $replacement : ($replacements[$index] ?? '');
        $next = wp_aot_regex_replace_one(wp_aot_regex_text($pattern), $callback ? $value : wp_aot_regex_text($value), $subject, $limit, $count, $callback, $flags);
        if ($next === null) return null;
        $subject = (string) $next;
        ++$index;
    }
    return $subject;
}

function wp_aot_regex_replace_native(mixed $pattern, mixed $replacement, mixed $subject, int $limit, mixed &$count, bool $callback, int $flags): mixed {
    if (!is_array($subject)) $subject = wp_aot_regex_text($subject);
    $count = 0;
    if ($callback && !is_callable($replacement)) throw new TypeError('The regex replacement callback must be callable.');
    if (($flags & ~768) !== 0) throw new ValueError('Unsupported native regex callback flags.');
    if (!is_array($subject)) return wp_aot_regex_replace_subject($pattern, $replacement, wp_aot_regex_text($subject), $limit, $count, $callback, $flags);
    $result = wp_aot_empty_request_array();
    foreach ((array) $subject as $key => $value) {
        $next = wp_aot_regex_replace_subject($pattern, $replacement, wp_aot_regex_text($value), $limit, $count, $callback, $flags);
        if ($next !== null) $result[$key] = $next;
    }
    return $result;
}

function wp_aot_preg_replace(mixed $pattern, mixed $replacement, mixed $subject, int $limit = -1, mixed &$count = null): mixed {
    if (defined('WP_AOT_NATIVE_RUNTIME')) return wp_aot_regex_replace_native($pattern, $replacement, $subject, $limit, $count, false, 0);
    $arguments = array($pattern, $replacement, $subject, $limit, $count);
    $output =& $arguments[4];
    try { return call_user_func_array('preg_replace', $arguments); }
    finally { $count = $output; }
}

function wp_aot_preg_replace_callback(mixed $pattern, mixed $callback, mixed $subject, int $limit = -1, mixed &$count = null, int $flags = 0): mixed {
    if (defined('WP_AOT_NATIVE_RUNTIME')) return wp_aot_regex_replace_native($pattern, $callback, $subject, $limit, $count, true, $flags);
    $arguments = array($pattern, $callback, $subject, $limit, $count, $flags);
    $output =& $arguments[4];
    try { return call_user_func_array('preg_replace_callback', $arguments); }
    finally { $count = $output; }
}

function wp_aot_preg_split(mixed $pattern, mixed $subject, mixed $limit = -1, mixed $flags = 0): array|false {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) {
        $value = wp_aot_native_api('preg_split', array($pattern, $subject, $limit, $flags));
        return $value === false ? false : (array) $value;
    }
    $pattern_text = wp_aot_regex_text($pattern);
    $subject_text = wp_aot_regex_text($subject);
    $remaining = wp_aot_internal_int($limit);
    $split_flags = wp_aot_internal_int($flags);
    $parsed = wp_aot_pcre_pattern($pattern_text);
    $unicode = (((int) $parsed[1]) & 524288) !== 0;
    $no_empty = ($split_flags & 1) !== 0;
    $capture_delimiters = ($split_flags & 2) !== 0;
    $capture_offsets = ($split_flags & 4) !== 0;
    if ($remaining === 0) $remaining = -1;
    $result = wp_aot_empty_request_array();
    $last = 0;
    $search_at = 0;
    $options = 0;
    $length = strlen($subject_text);
    while ($search_at <= $length && ($remaining === -1 || $remaining > 1)) {
        $captures = wp_aot_mixed(array());
        $status = wp_aot_regex_next($pattern_text, $subject_text, $captures, 256, $search_at, $options);
        if ($status === false) return false;
        if ($status === 0) {
            if ($options === 0 || $search_at === $length) break;
            $options = 0;
            ++$search_at;
            if ($unicode) while ($search_at < $length && (ord($subject_text[$search_at]) & 192) === 128) ++$search_at;
            continue;
        }
        $full = $captures[0];
        $start = (int) $full[1];
        $size = strlen((string) $full[0]);
        if (!$no_empty || $start !== $last) {
            $piece = substr($subject_text, $last, $start - $last);
            $result[] = $capture_offsets ? array($piece, $last) : $piece;
            if ($remaining !== -1) --$remaining;
        }
        if ($capture_delimiters) {
            foreach ((array) $captures as $key => $capture) {
                if (!is_int($key) || $key === 0) continue;
                $piece = (string) $capture[0];
                if (!$no_empty || $piece !== '') $result[] = $capture_offsets ? array($piece, (int) $capture[1]) : $piece;
            }
        }
        $last = $start + $size;
        $search_at = $last;
        $options = $size === 0 ? 2147483656 : 0;
    }
    if (!$no_empty || $last < $length) {
        $piece = substr($subject_text, $last);
        $result[] = $capture_offsets ? array($piece, $last) : $piece;
    }
    return $result;
}
