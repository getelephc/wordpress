<?php

// Only original bootstrap/configuration constant names use executed-presence tracking.
function wp_aot_bootstrap_known_constant(string $name): bool {
    switch ($name) {
        case 'ADMIN_COOKIE_PATH':
        case 'AUTH_COOKIE':
        case 'AUTOSAVE_INTERVAL':
        case 'COOKIEHASH':
        case 'COOKIEPATH':
        case 'COOKIE_DOMAIN':
        case 'DAY_IN_SECONDS':
        case 'DB_CHARSET':
        case 'DB_COLLATE':
        case 'DB_HOST':
        case 'DB_NAME':
        case 'DB_PASSWORD':
        case 'DB_USER':
        case 'DISABLE_WP_CRON':
        case 'EB_IN_BYTES':
        case 'EMPTY_TRASH_DAYS':
        case 'FORCE_SSL_ADMIN':
        case 'GB_IN_BYTES':
        case 'HOUR_IN_SECONDS':
        case 'KB_IN_BYTES':
        case 'LOGGED_IN_COOKIE':
        case 'MB_IN_BYTES':
        case 'MEDIA_TRASH':
        case 'MINUTE_IN_SECONDS':
        case 'MONTH_IN_SECONDS':
        case 'MUPLUGINDIR':
        case 'PASS_COOKIE':
        case 'PB_IN_BYTES':
        case 'PLUGINDIR':
        case 'PLUGINS_COOKIE_PATH':
        case 'RECOVERY_MODE_COOKIE':
        case 'SCRIPT_DEBUG':
        case 'SECURE_AUTH_COOKIE':
        case 'SHORTINIT':
        case 'SITECOOKIEPATH':
        case 'STYLESHEETPATH':
        case 'TB_IN_BYTES':
        case 'TEMPLATEPATH':
        case 'TEST_COOKIE':
        case 'USER_COOKIE':
        case 'WEEK_IN_SECONDS':
        case 'WPMU_PLUGIN_DIR':
        case 'WPMU_PLUGIN_URL':
        case 'WP_CACHE':
        case 'WP_CONTENT_DIR':
        case 'WP_CONTENT_URL':
        case 'WP_CRON_LOCK_TIMEOUT':
        case 'WP_DEBUG':
        case 'WP_DEBUG_DISPLAY':
        case 'WP_DEBUG_LOG':
        case 'WP_DEFAULT_THEME':
        case 'WP_DEVELOPMENT_MODE':
        case 'WP_DISABLE_FATAL_ERROR_HANDLER':
        case 'WP_FEATURE_BETTER_PASSWORDS':
        case 'WP_HTTP_BLOCK_EXTERNAL':
        case 'WP_LANG_DIR':
        case 'WP_MAX_MEMORY_LIMIT':
        case 'WP_MEMORY_LIMIT':
        case 'WP_PLUGIN_DIR':
        case 'WP_PLUGIN_URL':
        case 'WP_POST_REVISIONS':
        case 'WP_START_TIMESTAMP':
        case 'YB_IN_BYTES':
        case 'YEAR_IN_SECONDS':
        case 'ZB_IN_BYTES':
            return true;
        default:
            return false;
    }
}


require_once __DIR__ . '/aot-html.php';
require_once __DIR__ . '/aot-regex.php';

// Runtime data constants use a finite string-value view alongside actual definitions.
function wp_aot_bootstrap_string_known(string $name): bool {
    switch ($name) {
        case 'AUTH_COOKIE': case 'LOGGED_IN_COOKIE': case 'SECURE_AUTH_COOKIE':
        case 'USER_COOKIE': case 'PASS_COOKIE': case 'TEST_COOKIE': case 'RECOVERY_MODE_COOKIE':
        case 'COOKIEHASH': case 'COOKIEPATH': case 'SITECOOKIEPATH':
        case 'ADMIN_COOKIE_PATH': case 'PLUGINS_COOKIE_PATH':
        case 'WP_CONTENT_URL': case 'WP_PLUGIN_URL': case 'WPMU_PLUGIN_URL':
        case 'WP_MEMORY_LIMIT': case 'WP_MAX_MEMORY_LIMIT':
            return true;
        default: return false;
    }
}

function wp_aot_binary_array(): array { return array(); }

function wp_aot_binary_width(string $code): int {
    if ($code === 'c' || $code === 'C') return 1;
    if (str_contains('sSnv', $code)) return 2;
    if (str_contains('iIlLNV', $code)) return 4;
    if (str_contains('qQJP', $code)) return 8;
    throw new LogicException('The binary format is outside the integer/string fixture: ' . $code);
}

function wp_aot_binary_integer(int $value, int $width, bool $big_endian): string {
    $result = '';
    for ($at = 0; $at < $width; ++$at) {
        $shift = 8 * ($big_endian ? $width - 1 - $at : $at);
        $result .= chr(($value >> $shift) & 255);
    }
    return $result;
}

function wp_aot_binary_decode(string $data, int $offset, int $width, bool $big_endian, bool $signed): int {
    $result = 0;
    for ($at = 0; $at < $width; ++$at) {
        $shift = 8 * ($big_endian ? $width - 1 - $at : $at);
        $result |= ord($data[$offset + $at]) << $shift;
    }
    if ($signed && $width < 8 && ($result & (1 << ($width * 8 - 1)))) $result -= 1 << ($width * 8);
    return $result;
}

function wp_aot_pack(mixed $format, array $values): string {
    if (!is_string($format)) throw new TypeError('The binary format must be a string.');
    $pattern = (string) $format;
    $result = ''; $argument = 0;
    for ($at = 0; $at < strlen($pattern); ++$at) {
        $code = $pattern[$at]; $digits = '';
        while ($at + 1 < strlen($pattern) && ctype_digit($pattern[$at + 1])) $digits .= $pattern[++$at];
        $star = $at + 1 < strlen($pattern) && $pattern[$at + 1] === '*';
        if ($star) ++$at;
        $count = $digits === '' ? 1 : (int) $digits;
        if (str_contains('xX@', $code)) {
            if ($star) throw new LogicException('Starred binary padding is outside the fixture.');
            if ($code === 'x') $result .= str_repeat("\0", $count);
            elseif ($code === 'X') {
                if ($count > strlen($result)) throw new ValueError('pack(): Type X: outside of string');
                $result = substr($result, 0, strlen($result) - $count);
            } else $result = substr($result, 0, $count) . str_repeat("\0", max(0, $count - strlen($result)));
            continue;
        }
        if (str_contains('aAZhH', $code)) {
            if ($argument >= count($values)) throw new ValueError('pack(): Not enough arguments');
            $value = $values[$argument++];
            if (!is_string($value)) throw new TypeError('Binary string fields require string values.');
            $text = (string) $value;
            if ($star) $count = strlen($text) + ($code === 'Z' ? 1 : 0);
            if ($code === 'h' || $code === 'H') {
                $count = min($count, strlen($text));
                for ($nibble = 0; $nibble < $count; $nibble += 2) {
                    $first = $nibble < strlen($text) ? $text[$nibble] : '0';
                    $second = $nibble + 1 < $count && $nibble + 1 < strlen($text) ? $text[$nibble + 1] : '0';
                    if (!ctype_xdigit($first) || !ctype_xdigit($second)) throw new ValueError('Only hexadecimal binary string fields are supported.');
                    $result .= chr((int) hexdec($code === 'H' ? $first . $second : $second . $first));
                }
            } elseif ($code === 'Z') {
                if ($count > 0) $result .= substr($text, 0, $count - 1) . str_repeat("\0", max(1, $count - strlen($text)));
            } else $result .= substr($text, 0, $count) . str_repeat($code === 'A' ? ' ' : "\0", max(0, $count - strlen($text)));
            continue;
        }
        $width = wp_aot_binary_width($code);
        if ($star) $count = count($values) - $argument;
        if ($argument + $count > count($values)) throw new ValueError('pack(): Not enough arguments');
        for ($item = 0; $item < $count; ++$item) {
            $value = $values[$argument++];
            if (!is_int($value) && !is_float($value) && !is_bool($value) && $value !== null) throw new TypeError('Binary integer fields require scalar numeric values.');
            $result .= wp_aot_binary_integer((int) $value, $width, str_contains('nNJ', $code));
        }
    }
    return $result;
}

function wp_aot_unpack(string $format, string $data, int $offset = 0): array|false {
    if ($offset < 0 || $offset > strlen($data)) throw new ValueError('unpack(): Argument #3 ($offset) must be contained in argument #2 ($data)');
    $result = wp_aot_binary_array(); $position = $offset;
    foreach (explode('/', $format) as $field) {
        $specification = (string) $field;
        if ($specification === '') continue;
        $code = $specification[0]; $at = 1; $digits = '';
        while ($at < strlen($specification) && ctype_digit($specification[$at])) $digits .= $specification[$at++];
        $star = $at < strlen($specification) && $specification[$at] === '*';
        if ($star) ++$at;
        $count = $digits === '' ? 1 : (int) $digits; $name = substr($specification, $at);
        if (str_contains('xX@', $code)) {
            if ($star) $count = $code === 'x' ? strlen($data) - $position : 0;
            if ($code === 'x') $position += $count;
            elseif ($code === 'X') $position -= $count;
            else $position = $offset + $count;
            if ($position < $offset || $position > strlen($data)) return false;
            continue;
        }
        if (str_contains('aAZhH', $code)) {
            if ($star) $count = (strlen($data) - $position) * (($code === 'h' || $code === 'H') ? 2 : 1);
            $bytes = $code === 'h' || $code === 'H' ? intdiv($count + 1, 2) : $count;
            if ($position + $bytes > strlen($data)) return false;
            $text = substr($data, $position, $bytes); $position += $bytes;
            if ($code === 'A') $text = rtrim($text, " \0\t\n\r\v");
            elseif ($code === 'Z') {
                $nul = strpos($text, "\0"); if ($nul !== false) $text = substr($text, 0, (int) $nul);
            } elseif ($code === 'h' || $code === 'H') {
                $hex = bin2hex($text);
                if ($code === 'h') {
                    $reversed = '';
                    for ($i = 0; $i < strlen($hex); $i += 2) $reversed .= $hex[$i + 1] . $hex[$i];
                    $hex = $reversed;
                }
                $text = substr($hex, 0, $count);
            }
            $result[$name === '' ? 1 : $name] = $text;
            continue;
        }
        $width = wp_aot_binary_width($code);
        if ($star) $count = intdiv(strlen($data) - $position, $width);
        if ($position + $count * $width > strlen($data)) return false;
        for ($item = 1; $item <= $count; ++$item) {
            $key = $name === '' ? $item : $name . (($count > 1 || $star) ? (string) $item : '');
            $value = wp_aot_binary_decode($data, $position, $width, str_contains('nNJ', $code), str_contains('csilq', $code));
            $result[$key] = $value; $position += $width;
        }
    }
    return $result;
}

function wp_aot_replace_one(string $search, string $replace, string $subject, mixed &$count): string {
    if ($search === '') return $subject;
    $result = ''; $at = 0; $size = strlen($search);
    while (true) {
        $found = strpos($subject, $search, $at);
        if ($found === false) return $result . substr($subject, $at);
        $position = (int) $found;
        $result .= substr($subject, $at, $position - $at) . $replace;
        ++$count; $at = $position + $size;
    }
}
function wp_aot_replace_subject(array $search, array $replace, string $subject, mixed &$count): string {
    foreach ($search as $at => $needle) $subject = wp_aot_replace_one((string) $needle, (string) ($replace[$at] ?? ''), $subject, $count);
    return $subject;
}
function wp_aot_str_replace(mixed $search, mixed $replace, mixed $subject, mixed &$count = null): mixed {
    $count = 0;
    if (!is_array($search) && is_array($replace)) throw new TypeError('An array replacement requires an array search.');
    $needles = is_array($search) ? array_values((array) $search) : array($search);
    $replacements = wp_aot_empty_request_array();
    if (is_array($replace)) $replacements = array_values((array) $replace);
    else foreach ($needles as $unused) $replacements[] = (string) $replace;
    if (!is_array($subject)) return wp_aot_replace_subject($needles, $replacements, (string) $subject, $count);
    $result = wp_aot_empty_request_array();
    foreach ((array) $subject as $key => $value) {
        if (is_array($value) || is_object($value)) throw new LogicException('Nested or object replacement subjects are outside the native fixture.');
        $result[$key] = wp_aot_replace_subject($needles, $replacements, (string) $value, $count);
    }
    return $result;
}

// PHP controls use their real API; native unsupported services fail at the call site.

function wp_aot_native_headers_sent(mixed &$filename = null, mixed &$line = null): mixed {
    if (defined('WP_AOT_NATIVE_RUNTIME')) throw new LogicException('This PHP API is unavailable in the native source fixture: headers_sent');
    $arguments = array($filename, $line);
    $file_output =& $arguments[0]; $line_output =& $arguments[1];
    try { return wp_aot_native_api('headers_sent', $arguments); }
    finally { $filename = $file_output; $line = $line_output; }
}

function wp_aot_native_openssl_sign(mixed $data, mixed &$signature, mixed $key, mixed $algorithm = 1): mixed {
    if (defined('WP_AOT_NATIVE_RUNTIME')) throw new LogicException('This PHP API is unavailable in the native source fixture: openssl_sign');
    $arguments = array($data, $signature, $key, $algorithm);
    $output =& $arguments[1];
    try { return wp_aot_native_api('openssl_sign', $arguments); }
    finally { $signature = $output; }
}

function wp_aot_native_openssl_random_pseudo_bytes(mixed $length, mixed &$strong_result = null): mixed {
    if (defined('WP_AOT_NATIVE_RUNTIME')) throw new LogicException('This PHP API is unavailable in the native source fixture: openssl_random_pseudo_bytes');
    $arguments = array($length, $strong_result);
    $output =& $arguments[1];
    try { return wp_aot_native_api('openssl_random_pseudo_bytes', $arguments); }
    finally { $strong_result = $output; }
}

function wp_aot_fwrite(mixed $stream, mixed $data, ?int $length = null): int|false {
    if (!is_resource($stream)) throw new TypeError('fwrite(): stream must be an open resource.');
    if (is_array($data) || is_object($data)) throw new TypeError('The native stream write fixture requires scalar data.');
    if ($length !== null && $length < 0) $length = 0;
    $bytes = (string) $data;
    if ($length !== null) $bytes = substr($bytes, 0, $length);
    return fwrite($stream, $bytes);
}

function wp_aot_metadata_defined(string $name): bool {
    if (str_starts_with($name, '\\')) $name = substr($name, 1);
    $split = strpos($name, '::');
    if ($split === false) return array_key_exists($name, get_defined_constants());
    return (bool) wp_aot_native_api('defined', array($name));
}
function wp_aot_metadata_constant(string $name): mixed {
    if (str_starts_with($name, '\\')) $name = substr($name, 1);
    $split = strpos($name, '::');
    if ($split === false) {
        $constants = get_defined_constants();
        if (array_key_exists($name, $constants)) return $constants[$name];
        throw new Error('Undefined constant "' . $name . '"');
    }
    return wp_aot_native_api('constant', array($name));
}
function wp_aot_declared_name(array $names, string $name): bool {
    if (str_starts_with($name, '\\')) $name = substr($name, 1);
    $name = strtolower($name);
    foreach ($names as $candidate) if (strtolower((string) $candidate) === $name) return true;
    return false;
}
function wp_aot_metadata_class_exists(string $name, bool $autoload = true): bool {
    if (wp_aot_declared_name(get_declared_classes(), $name)) return true;
    if (!$autoload || wp_aot_declared_name(get_declared_interfaces(), $name) || wp_aot_declared_name(get_declared_traits(), $name)) return false;
    spl_autoload_call($name);
    return wp_aot_declared_name(get_declared_classes(), $name);
}
function wp_aot_metadata_interface_exists(string $name, bool $autoload = true): bool {
    if (wp_aot_declared_name(get_declared_interfaces(), $name)) return true;
    if (!$autoload || wp_aot_declared_name(get_declared_classes(), $name) || wp_aot_declared_name(get_declared_traits(), $name)) return false;
    spl_autoload_call($name);
    return wp_aot_declared_name(get_declared_interfaces(), $name);
}
function wp_aot_function_exists(string $name): bool {
    if (str_contains($name, '::')) return false;
    return is_callable($name);
}
function wp_aot_extension_loaded(string $name): bool {
    $name = strtolower($name);
    foreach (get_loaded_extensions() as $candidate) if (strtolower((string) $candidate) === $name) return true;
    return false;
}


function wp_aot_value_array_union(mixed $left, mixed $right): array {
    $result = wp_aot_array_input($left);
    $source = wp_aot_array_input($right);
    if (!$result && $source) return $source;
    foreach ($source as $key => $value) {
        if (!array_key_exists($key, $result)) $result[$key] = $value;
    }
    return $result;
}

function wp_aot_string_xor(string $left, string $right): string {
    $result = '';
    $length = min(strlen($left), strlen($right));
    for ($at = 0; $at < $length; ++$at) $result .= chr(ord($left[$at]) ^ ord($right[$at]));
    return $result;
}

function wp_aot_strtr(mixed $value, mixed $from, mixed $to = null): string {
    if (is_array($value) || is_object($value)) throw new TypeError('The native translation fixture requires scalar text.');
    $text = (string) $value;
    $result = '';
    if (is_array($from)) {
        if ($to !== null) throw new TypeError('Three argument translation requires string byte tables.');
        $map = wp_aot_array_input($from);
        for ($at = 0; $at < strlen($text);) {
            $matched = 0;
            $replacement = '';
            foreach ($map as $key => $candidate) {
                $needle = (string) $key;
                $length = strlen($needle);
                if ($length === 0) throw new LogicException('Empty translation keys are outside the native fixture.');
                if ($length > $matched && substr($text, $at, $length) === $needle) {
                    if (is_array($candidate) || is_object($candidate)) throw new TypeError('The native translation fixture requires scalar replacements.');
                    $matched = $length;
                    $replacement = (string) $candidate;
                }
            }
            if ($matched > 0) { $result .= $replacement; $at += $matched; }
            else $result .= $text[$at++];
        }
        return $result;
    }
    if (!is_string($from) || !is_string($to)) throw new TypeError('Byte translation requires two string tables.');
    $source = (string) $from;
    $target = (string) $to;
    $length = min(strlen($source), strlen($target));
    for ($at = 0; $at < strlen($text); ++$at) {
        $translated = $text[$at];
        for ($map_at = 0; $map_at < $length; ++$map_at) {
            if ($source[$map_at] === $text[$at]) $translated = $target[$map_at];
        }
        $result .= $translated;
    }
    return $result;
}

function wp_aot_global_table_prefix_exists(): bool { global $table_prefix; return isset($table_prefix); }
function wp_aot_set_global_table_prefix(mixed $value): void { global $table_prefix; $table_prefix = $value; }

function wp_aot_native_socket(mixed $address, mixed &$error_code = null, mixed &$error_message = null, mixed $timeout = null, mixed $flags = 4, mixed $context = null): mixed {
    $arguments = array($address, $error_code, $error_message, $timeout, $flags, $context);
    $code_output =& $arguments[1]; $message_output =& $arguments[2];
    try { return wp_aot_native_api('stream_socket_client', $arguments); }
    finally { $error_code = $code_output; $error_message = $message_output; }
}

function wp_aot_fread(mixed $stream, int $length): string|false {
    if (!is_resource($stream)) throw new TypeError('fread(): stream must be an open resource.');
    if ($length < 1) throw new ValueError('fread(): length must be greater than zero.');
    $result = fread($stream, $length);
    if (defined('WP_AOT_NATIVE_RUNTIME') && $result !== false && strlen((string) $result) < $length) {
        $metadata = stream_get_meta_data($stream);
        if (!empty($metadata['seekable'])) fread($stream, 1);
    }
    return $result;
}
function wp_aot_fclose(mixed $stream): bool {
    if (!is_resource($stream)) throw new TypeError('fclose(): stream must be an open resource.');
    return fclose($stream);
}
function wp_aot_fgets(mixed $stream, ?int $length = null): string|false {
    if (!is_resource($stream)) throw new TypeError('fgets(): stream must be an open resource.');
    if ($length !== null && $length < 1) throw new ValueError('fgets(): length must be greater than zero.');
    if ($length === 1) return false;
    $result = '';
    while ($length === null || strlen($result) < $length - 1) {
        $byte = fread($stream, 1);
        if ($byte === false || $byte === '') break;
        $result .= (string) $byte;
        if ($byte === "\n") break;
    }
    return $result === '' ? false : $result;
}

function wp_aot_internal_int(mixed $value): int {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return (int) wp_aot_native_api('intdiv', array($value, 1));
    if ($value === null || is_bool($value) || is_int($value)) return (int) $value;
    $number = 0.0;
    if (is_string($value)) {
        $text = trim((string) $value, " \t\n\r\v\f");
        if (str_contains($text, "\0")) throw new TypeError('A numeric scalar integer value is required.');
        if (preg_match('/^[+-]?[0-9]+\z/', $text)) {
            $negative = $text[0] === '-';
            $digits = ltrim(ltrim($text, '+-'), '0');
            $limit = $negative ? '9223372036854775808' : '9223372036854775807';
            if (strlen($digits) < 19 || (strlen($digits) === 19 && strcmp($digits, $limit) <= 0)) return (int) $text;
        }
        if (!preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?\z/', $text)) throw new TypeError('A numeric scalar integer value is required.');
        $number = (float) $text;
    } elseif (is_float($value)) $number = (float) $value;
    else throw new TypeError('A numeric scalar integer value is required.');
    if (!is_finite($number) || $number < -9223372036854775808.0 || $number >= 9223372036854775808.0) throw new TypeError('The integer value is outside the signed 64-bit range.');
    return (int) $number;
}
function wp_aot_nullable_int(mixed $value): ?int { return $value === null ? null : wp_aot_internal_int($value); }

function wp_aot_calendar_component(mixed $value): int {
    if ($value === null) throw new LogicException('Current-date calendar defaults are outside this fixture.');
    return wp_aot_internal_int($value);
}

function wp_aot_gmdate(mixed $format, mixed $timestamp = null): string {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return (string) wp_aot_native_api('gmdate', array($format, $timestamp));
    $pattern = wp_aot_regex_text($format);
    if (str_contains($pattern, "\0")) throw new LogicException('NUL date-format patterns are outside this fixture.');
    $optional = wp_aot_nullable_int($timestamp);
    $stamp = $optional === null ? time() : (int) $optional;
    if ($stamp !== -1 && !str_contains($pattern, 'U') && !str_ends_with($pattern, '\\')) return gmdate($pattern, $stamp);
    // The SDK reserves -1 for the clock. UTC fields at -2 differ only in seconds and Unix time.
    $base = (int) ($stamp === -1 ? $stamp - 1 : $stamp);
    $seconds = (int) gmdate('s', $base) + ($stamp === -1 ? 1 : 0);
    $second_text = ($seconds < 10 ? '0' : '') . (string) $seconds;
    $result = '';
    $length = strlen($pattern);
    for ($at = 0; $at < $length; ++$at) {
        $char = $pattern[$at];
        if ($char === '\\') {
            ++$at;
            $result .= $at < $length ? $pattern[$at] : "\0";
        } elseif ($char === 's') $result .= $second_text;
        elseif ($char === 'U') $result .= (string) $stamp;
        elseif ($char === 'c') $result .= wp_aot_gmdate('Y-m-d\\TH:i:sP', $stamp);
        elseif ($char === 'r') $result .= wp_aot_gmdate('D, d M Y H:i:s O', $stamp);
        else $result .= gmdate($char, $base);
    }
    return $result;
}

function wp_aot_number_decimal_increment(string $digits): string {
    $result = ''; $carry = 1;
    for ($at = strlen($digits) - 1; $at >= 0; --$at) {
        $digit = ord($digits[$at]) - 48 + $carry;
        $carry = $digit > 9 ? 1 : 0;
        $result = chr(48 + ($digit % 10)) . $result;
    }
    return ($carry === 1 ? '1' : '') . $result;
}

function wp_aot_number_format(mixed $num, mixed $decimals = 0, mixed $decimal_separator = '.', mixed $thousands_separator = ','): string {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return (string) wp_aot_native_api('number_format', array($num, $decimals, $decimal_separator, $thousands_separator));
    $integer_text = null;
    if (is_int($num)) $integer_text = (string) $num;
    if (is_string($num)) {
        $text = trim((string) $num, " \t\n\r\v\f");
        if (str_contains($text, "\0")) throw new TypeError('The native number formatter requires a numeric scalar.');
        if (!preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?\z/', $text)) throw new TypeError('The native number formatter requires a numeric scalar.');
        if (preg_match('/^[+-]?[0-9]+\z/', $text)) {
            $negative = $text[0] === '-';
            $digits = ltrim(ltrim($text, '+-'), '0');
            $limit = $negative ? '9223372036854775808' : '9223372036854775807';
            if (strlen($digits) < 19 || (strlen($digits) === 19 && strcmp($digits, $limit) <= 0)) {
                $integer_text = $digits === '' ? '0' : ($negative ? '-' : '') . $digits;
            }
        }
    } elseif ($num !== null && !is_bool($num) && !is_int($num) && !is_float($num)) {
        throw new TypeError('The native number formatter requires a numeric scalar.');
    }
    $number = (float) $num;
    if ($integer_text === null && ($number >= 4503599627370496.0 || $number <= -4503599627370496.0)
        && $number >= -9223372036854775808.0 && $number < 9223372036854775808.0) {
        $integer_text = (string) (int) $number;
    }
    $precision = wp_aot_internal_int($decimals);
    $point = $decimal_separator === null ? '.' : wp_aot_regex_text($decimal_separator);
    $separator = $thousands_separator === null ? ',' : wp_aot_regex_text($thousands_separator);
    if ($precision < -22 || $precision > 22) throw new LogicException('The native number formatter supports decimal precision from -22 through 22.');
    if (is_nan($number)) return 'nan';
    if (is_infinite($number)) return 'inf';
    $places = $precision > 0 ? $precision : 0;
    if ($integer_text !== null) {
        $integer = (string) $integer_text;
        $fraction = str_repeat('0', $places);
        if ($precision < 0) {
            $negative = str_starts_with($integer, '-');
            $magnitude = $negative ? substr($integer, 1) : $integer;
            $cut = -$precision;
            $length = strlen($magnitude);
            $integer = '0';
            if ($cut <= $length) {
                $head = substr($magnitude, 0, $length - $cut);
                if (ord($magnitude[$length - $cut]) >= 53) $head = wp_aot_number_decimal_increment($head);
                if ($head !== '' && $head !== '0') $integer = $head . str_repeat('0', $cut);
            }
            if ($negative && $integer !== '0') $integer = '-' . $integer;
        }
    } else {
        $rounded = round($number, $precision, 1);
        if ($rounded == 0.0) $rounded = 0.0;
        $plain = number_format($rounded, $places, '.', '');
        $dot = strpos($plain, '.');
        $integer = $dot === false ? $plain : substr($plain, 0, (int) $dot);
        $fraction = $dot === false ? '' : substr($plain, (int) $dot + 1);
    }
    $sign = '';
    if (str_starts_with($integer, '-')) {$sign = '-'; $integer = substr($integer, 1);}
    $length = strlen($integer);
    $first = $length % 3;
    if ($first === 0) $first = 3;
    $result = $sign . substr($integer, 0, $first);
    for ($at = $first; $at < $length; $at += 3) $result .= $separator . substr($integer, $at, 3);
    if ($places > 0) $result .= $point . $fraction;
    return $result;
}

function wp_aot_chgrp(mixed $file, mixed $group): bool {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return (bool) wp_aot_native_api('chgrp', array($file, $group));
    if (is_string($group)) return chgrp(wp_aot_regex_text($file), (string) $group);
    return chgrp(wp_aot_regex_text($file), wp_aot_internal_int($group));
}
function wp_aot_chown(mixed $file, mixed $owner): bool {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return (bool) wp_aot_native_api('chown', array($file, $owner));
    if (is_string($owner)) return chown(wp_aot_regex_text($file), (string) $owner);
    return chown(wp_aot_regex_text($file), wp_aot_internal_int($owner));
}

function wp_aot_walker_call(mixed $walker, string $method, mixed &$output, array $arguments): void {
    $values = array($output);
    $reference =& $values[0];
    foreach ($arguments as $value) $values[] = $value;
    try { call_user_func_array(array($walker, $method), $values); }
    finally { $output = $reference; }
}

function wp_aot_kses_global_is_array(string $name): bool {
    global $allowedposttags, $allowedtags, $allowedentitynames, $allowedxmlentitynames;
    switch ($name) {
        case 'allowedposttags': return is_array($allowedposttags);
        case 'allowedtags': return is_array($allowedtags);
        case 'allowedentitynames': return is_array($allowedentitynames);
        case 'allowedxmlentitynames': return is_array($allowedxmlentitynames);
    }
    throw new LogicException('The KSES global name is outside the original fixed inventory.');
}
function wp_aot_glob(string $pattern, int $flags = 0): array|false {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return wp_aot_native_api('glob', array($pattern, $flags));
    if ($flags !== 0 && $flags !== GLOB_ONLYDIR) throw new LogicException('The native glob fixture supports default flags and GLOB_ONLYDIR.');
    $matches = glob($pattern);
    if ($matches === false) return false;
    if ($flags === 0) return (array) $matches;
    $result = array();
    foreach ($matches as $match) if (is_dir((string) $match)) $result[] = $match;
    return $result;
}

function wp_aot_weak_int(mixed $value): int {
    if ($value === null) throw new TypeError('The original non-null integer contract does not accept null.');
    return wp_aot_internal_int($value);
}

function wp_aot_touch(mixed $file, mixed $mtime = null, mixed $atime = null): bool {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return (bool) wp_aot_native_api('touch', array($file, $mtime, $atime));
    $path = wp_aot_regex_text($file);
    $modified = wp_aot_nullable_int($mtime);
    $accessed = wp_aot_nullable_int($atime);
    if ($modified === null) {
        if ($accessed !== null) throw new ValueError('touch(): Argument #2 ($mtime) cannot be null when argument #3 ($atime) is an integer');
        return touch($path);
    }
    if ($accessed === null) return touch($path, (int) $modified);
    return touch($path, (int) $modified, (int) $accessed);
}

function wp_aot_nullable_float(mixed $value): ?float {
    if ($value === null) return null;
    if (is_float($value) || is_int($value) || is_bool($value)) return (float)$value;
    if (is_string($value) && is_numeric($value)) return (float)$value;
    throw new TypeError('A nullable numeric scalar float value is required.');
}

function wp_aot_fsockopen(mixed $hostname, mixed $port = -1, mixed &$error_code = null, mixed &$error_message = null, mixed $timeout = null): mixed {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) {
        $arguments=array($hostname,$port,$error_code,$error_message,$timeout);
        $code_output =& $arguments[2]; $message_output =& $arguments[3];
        try {return wp_aot_native_api('fsockopen',$arguments);}
        finally {$error_code=$code_output;$error_message=$message_output;}
    }
    $host=wp_aot_regex_text($hostname);
    $socket_port=wp_aot_internal_int($port);
    $socket_timeout=wp_aot_nullable_float($timeout);
    $error_code=0;
    $error_message='';
    if ($socket_timeout === null) return fsockopen($host,$socket_port,$error_code,$error_message);
    return fsockopen($host,$socket_port,$error_code,$error_message,(float)$socket_timeout);
}
