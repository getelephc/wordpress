<?php

function wp_aot_json_record(mixed $value): stdClass {
    if (!$value instanceof stdClass) throw new TypeError('The native JSON record fixture requires stdClass.');
    return $value;
}

// A persistent object gives static arrays a stable mixed reference cell.
final class WordPressStaticArrayCell {
    public mixed $value = array();
}

// A persistent mixed cell also preserves nullable, self-referential callbacks.
final class WordPressStaticValueCell {
    public mixed $value = null;
}

// String caches retain their original sentinel while their later values stay mixed.
final class WordPressStaticStringCell {
    public mixed $value = '';
}

// Keep the original false sentinel while later cache values remain mixed.
final class WordPressStaticFalseCell {
    public mixed $value = false;
}

// Keep request maps open to values added after their empty initialization.
function wp_aot_empty_request_array(): array {
    return array();
}

// A non-integer seed selects associative storage before sparse numeric keys.
function wp_aot_empty_request_map(): array {
    $map = array('wp-aot-map-seed' => null);
    unset($map['wp-aot-map-seed']);
    return $map;
}

// Property stacks use their boundary values without sharing a PHP array cursor.
function wp_aot_array_last( mixed $values ): mixed {
    if ( ! is_array( $values ) ) {
        throw new TypeError( 'The WordPress stack must be an array.' );
    }
    $array = (array) $values;
    return end( $array );
}

function wp_aot_array_first( mixed $values ): mixed {
    if ( ! is_array( $values ) ) {
        throw new TypeError( 'The WordPress stack must be an array.' );
    }
    $array = (array) $values;
    return reset( $array );
}

function wp_aot_array_first_key( mixed $values ): mixed {
    if ( ! is_array( $values ) ) {
        throw new TypeError( 'The WordPress stack must be an array.' );
    }
    $array = (array) $values;
    reset( $array );
    return key( $array );
}

function wp_aot_strtok(string $string, ?string $delimiters = null): string|false {
    static $buffer = '';
    static $offset = 0;
    if ($delimiters === null) {
        $delimiters = $string;
    } else {
        $buffer = $string;
        $offset = 0;
    }
    $size = strlen($buffer);
    while ($offset < $size && str_contains($delimiters, $buffer[$offset])) ++$offset;
    if ($offset >= $size) return false;
    $start = $offset;
    while ($offset < $size && !str_contains($delimiters, $buffer[$offset])) ++$offset;
    $token = substr($buffer, $start, $offset - $start);
    if ($offset < $size) ++$offset;
    return $token;
}


// The source call site supplies its own variable inventory, including null values.
function wp_aot_compact(array $scope, array $names, int $depth = 0): array {
    if ($depth === 0 && !$names) throw new ArgumentCountError('compact() expects at least 1 argument, 0 given');
    if ($depth > 64) throw new ValueError('Source compact names exceed the fixture depth.');
    $result = wp_aot_empty_request_array();
    foreach ($names as $name) {
        if (is_array($name)) {
            $nested = wp_aot_compact($scope, (array) $name, $depth + 1);
            foreach ($nested as $key => $value) $result[(string) $key] = $value;
        } elseif (is_string($name)) {
            $key = (string) $name;
            if (array_key_exists($key, $scope)) $result[$key] = $scope[$key];
            else trigger_error('compact(): Undefined variable $' . $key, E_USER_WARNING);
        } else {
            throw new TypeError('Source compact names must be strings or nested name arrays.');
        }
    }
    return $result;
}

// No substitute definitions: an executed read of an absent constant still fails.
function wp_aot_undefined_constant(string $name): never {
    throw new Error('Undefined constant "' . $name . '"');
}

// Preserve PHP array boundaries while projecting native typed array operands.
function wp_aot_array_input(mixed $value): array {
    if (!is_array($value)) throw new TypeError('The array operation requires an array value.');
    return (array) $value;
}

function wp_aot_array_merge(array $arrays): array {
    $result = wp_aot_empty_request_array();
    foreach ($arrays as $value) {
        $source = wp_aot_array_input($value);
        foreach ($source as $key => $item) {
            if (is_int($key)) $result[] = $item;
            else $result[(string) $key] = $item;
        }
    }
    return $result;
}

function wp_aot_array_map(mixed $callback, mixed $array, mixed ...$arrays): array {
    $first = wp_aot_array_input($array);
    $sources = wp_aot_empty_request_array();
    $sources[] = $first;
    foreach ($arrays as $value) $sources[] = wp_aot_array_input($value);
    if ($callback !== null && !is_callable($callback)) throw new TypeError('The array callback must be callable or null.');
    if (count($sources) === 1 && $callback === null) return $first;
    $result = wp_aot_empty_request_array();
    if (count($sources) === 1) {
        foreach ($first as $key => $value) $result[$key] = call_user_func_array($callback, array($value));
        return $result;
    }
    $lists = wp_aot_empty_request_array();
    $length = 0;
    foreach ($sources as $source) {
        $list = array_values((array) $source);
        $length = max($length, count($list));
        $lists[] = $list;
    }
    for ($at = 0; $at < $length; ++$at) {
        $arguments = wp_aot_empty_request_array();
        foreach ($lists as $list) $arguments[] = $list[$at] ?? null;
        $result[] = $callback === null ? $arguments : call_user_func_array($callback, $arguments);
    }
    return $result;
}

function wp_aot_array_filter(mixed $array, mixed $callback = null, int $mode = 0): array {
    $source = wp_aot_array_input($array);
    if ($callback !== null && !is_callable($callback)) throw new TypeError('The array callback must be callable or null.');
    $result = wp_aot_empty_request_array();
    foreach ($source as $key => $value) {
        if ($callback === null) $keep = (bool) $value;
        elseif ($mode === 2) $keep = (bool) call_user_func_array($callback, array($key));
        elseif ($mode === 1) $keep = (bool) call_user_func_array($callback, array($value, $key));
        else $keep = (bool) call_user_func_array($callback, array($value));
        if ($keep) $result[$key] = $value;
    }
    return $result;
}

// The source profile uses string and numeric uniqueness, with original key retention.
function wp_aot_array_unique(mixed $array, int $flags = 2): array {
    $source = wp_aot_array_input($array);
    if (count($source) <= 1) return $source;
    if ($flags !== 2 && $flags !== 1 && $flags !== 0) throw new LogicException('This array uniqueness sort mode is outside the native fixture.');
    $result = wp_aot_empty_request_array();
    $strings = wp_aot_empty_request_array();
    $numbers = wp_aot_empty_request_array();
    $regular = wp_aot_empty_request_array();
    $regular_family = '';
    foreach ($source as $key => $value) {
        if (is_array($value) || is_object($value) || is_resource($value)) throw new LogicException('Non-scalar string/numeric uniqueness is outside the native fixture.');
        $duplicate = false;
        if ($flags === 2) {
            $label = (string) $value;
            $duplicate = array_key_exists($label, $strings);
            $strings[$label] = true;
        } elseif ($flags === 1) {
            $number = (float) $value;
            foreach ($numbers as $previous) if ($number === (float) $previous) { $duplicate = true; break; }
            $numbers[] = $number;
        } else {
            $family = is_int($value) || is_float($value) ? 'number' : gettype($value);
            if ($regular_family === '') $regular_family = $family;
            if ($family !== $regular_family) throw new LogicException('Mixed comparison families are outside regular uniqueness in the fixture.');
            if (is_float($value) && !is_finite((float) $value)) throw new LogicException('Non-finite regular uniqueness is outside the fixture.');
            if (is_string($value) && is_numeric($value) && !is_finite((float) $value)) throw new LogicException('Overflowing numeric string comparisons are outside the fixture.');
            foreach ($regular as $previous) if ($value == $previous) { $duplicate = true; break; }
            $regular[] = $value;
        }
        if (!$duplicate) $result[$key] = $value;
    }
    return $result;
}

function wp_aot_array_search(mixed $needle, mixed $array, bool $strict = false): mixed {
    $source = wp_aot_array_input($array);
    foreach ($source as $key => $value) {
        if ($strict ? $needle === $value : $needle == $value) return $key;
    }
    return false;
}

// Multi-array set operations retain the first array's keys and iteration order.
function wp_aot_array_set(array $arrays, bool $intersection, bool $compare_keys): array {
    if (count($arrays) < 2) throw new ArgumentCountError('The array set operation requires at least two arrays.');
    $sources = wp_aot_empty_request_array();
    foreach ($arrays as $array) $sources[] = wp_aot_array_input($array);
    $first = wp_aot_array_input($sources[0]);
    $result = wp_aot_empty_request_array();
    foreach ($first as $key => $value) {
        if (!$compare_keys && (is_array($value) || is_object($value) || is_resource($value))) throw new LogicException('Non-scalar value set comparisons are outside the native fixture.');
        $include = true;
        for ($at = 1; $at < count($sources); ++$at) {
            $other = wp_aot_array_input($sources[$at]);
            $found = false;
            if ($compare_keys) $found = array_key_exists($key, $other);
            else {
                foreach ($other as $candidate) {
                    if (is_array($candidate) || is_object($candidate) || is_resource($candidate)) throw new LogicException('Non-scalar value set comparisons are outside the native fixture.');
                    if ((string) $value === (string) $candidate) { $found = true; break; }
                }
            }
            if ($intersection ? !$found : $found) { $include = false; break; }
        }
        if ($include) $result[$key] = $value;
    }
    return $result;
}

function wp_aot_array_diff(array $arrays): array { return wp_aot_array_set($arrays, false, false); }
function wp_aot_array_intersect(array $arrays): array { return wp_aot_array_set($arrays, true, false); }
function wp_aot_array_diff_key(array $arrays): array { return wp_aot_array_set($arrays, false, true); }
function wp_aot_array_intersect_key(array $arrays): array { return wp_aot_array_set($arrays, true, true); }

function wp_aot_array_slice(mixed $array, int $offset, ?int $length = null, bool $preserve_keys = false): array {
    $source = wp_aot_array_input($array);
    $size = count($source);
    $start = $offset < 0 ? max(0, $size + $offset) : min($offset, $size);
    $end = $length === null ? $size : ($length < 0 ? max($start, $size + $length) : min($size, $start + $length));
    $result = wp_aot_empty_request_array(); $position = 0;
    foreach ($source as $key => $value) {
        if ($position >= $start && $position < $end) {
            if (!$preserve_keys && is_int($key)) $result[] = $value;
            else $result[$key] = $value;
        }
        ++$position;
    }
    return $result;
}

// This value-array splice preserves object handles; external element references are outside the fixture.
function wp_aot_array_splice_values(mixed &$array,int $offset,?int $length=null,array $replacement=array()): array {
    $source = wp_aot_array_input($array);
    $size = count($source);
    $start = $offset < 0 ? max(0,$size+$offset) : min($offset,$size);
    if ($length === null) $end = $size;
    elseif ($length < 0) $end = max($start,$size+$length);
    else $end = $length > $size-$start ? $size : $start+$length;
    $result = wp_aot_empty_request_array();
    $removed = wp_aot_empty_request_array();
    $position = 0;
    $inserted = false;
    foreach ($source as $key=>$value) {
        if ($position === $start) {
            foreach ($replacement as $item) $result[] = $item;
            $inserted = true;
        }
        if ($position >= $start && $position < $end) {
            if (is_int($key)) $removed[] = $value;
            else $removed[(string)$key] = $value;
        } else {
            if (is_int($key)) $result[] = $value;
            else $result[(string)$key] = $value;
        }
        ++$position;
    }
    if (!$inserted) foreach ($replacement as $item) $result[] = $item;
    $array = $result;
    return $removed;
}

function wp_aot_sort_natural(string $left, string $right, bool $ignore_case): int {
    if ($left === $right) return 0;
    $forward = array($left => true, $right => false);
    ksort($forward, $ignore_case ? 14 : 6);
    foreach ($forward as $left_first) { if (!$left_first) return 1; break; }
    $backward = array($right => false, $left => true);
    ksort($backward, $ignore_case ? 14 : 6);
    foreach ($backward as $left_first) { if ($left_first) return -1; break; }
    return 0;
}
function wp_aot_sort_compare(mixed $left, mixed $right, int $flags): int {
    if ($flags === 0) return $left <=> $right;
    if ($flags === 1) return (float) $left <=> (float) $right;
    if ($flags === 2) return strcmp((string) $left, (string) $right);
    if ($flags === 10) return strcasecmp((string) $left, (string) $right);
    if ($flags === 6 || $flags === 14) return wp_aot_sort_natural((string) $left, (string) $right, $flags === 14);
    throw new LogicException('This locale or sorting mode is outside the fixture.');
}
// Stable bottom-up merge ordering keeps ties in their original sequence.
function wp_aot_array_order(mixed &$array, int $flags, bool $reverse, bool $by_key, bool $keep_keys): bool {
    $source = wp_aot_array_input($array);
    $keys = array_keys($source);
    $length = count($keys);
    for ($width = 1; $width < $length; $width *= 2) {
        $ordered = array();
        for ($begin = 0; $begin < $length; $begin += 2 * $width) {
            $middle = min($begin + $width, $length);
            $limit = min($begin + 2 * $width, $length);
            $left = $begin; $right = $middle;
            while ($left < $middle && $right < $limit) {
                $left_key = $keys[$left]; $right_key = $keys[$right];
                $comparison = wp_aot_sort_compare($by_key ? $left_key : $source[$left_key], $by_key ? $right_key : $source[$right_key], $flags);
                if ($reverse) $comparison = -$comparison;
                if ($comparison <= 0) { $ordered[] = $left_key; ++$left; }
                else { $ordered[] = $right_key; ++$right; }
            }
            while ($left < $middle) { $ordered[] = $keys[$left]; ++$left; }
            while ($right < $limit) { $ordered[] = $keys[$right]; ++$right; }
        }
        $keys = $ordered;
    }
    $result = wp_aot_empty_request_array();
    foreach ($keys as $key) {
        if ($keep_keys) $result[$key] = $source[$key];
        else $result[] = $source[$key];
    }
    $array = $result;
    return true;
}
function wp_aot_sort(mixed &$array, int $flags = 0): bool { return wp_aot_array_order($array, $flags, false, false, false); }
function wp_aot_rsort(mixed &$array, int $flags = 0): bool { return wp_aot_array_order($array, $flags, true, false, false); }
function wp_aot_asort(mixed &$array, int $flags = 0): bool { return wp_aot_array_order($array, $flags, false, false, true); }
function wp_aot_arsort(mixed &$array, int $flags = 0): bool { return wp_aot_array_order($array, $flags, true, false, true); }
function wp_aot_ksort(mixed &$array, int $flags = 0): bool { return wp_aot_array_order($array, $flags, false, true, true); }
function wp_aot_krsort(mixed &$array, int $flags = 0): bool { return wp_aot_array_order($array, $flags, true, true, true); }

// Open storage preserves original local variables that change PHP value kinds.
function wp_aot_mixed(mixed $value): mixed { return $value; }

function wp_aot_array_reverse(mixed $array, bool $preserve_keys = false): array {
    $source = wp_aot_array_input($array); $keys = array_keys($source); $result = wp_aot_empty_request_array();
    for ($i = count($keys) - 1; $i >= 0; --$i) {
        $key = $keys[$i];
        if ($preserve_keys || !is_int($key)) $result[$key] = $source[$key];
        else $result[] = $source[$key];
    }
    return $result;
}
function wp_aot_array_fill_keys(mixed $keys, mixed $value): array {
    $source = wp_aot_array_input($keys); $result = wp_aot_empty_request_array();
    foreach ($source as $key) {
        if (is_array($key) || is_object($key) || is_resource($key)) throw new LogicException('The native fixture accepts scalar array keys.');
        $label = is_int($key) ? $key : (string) $key;
        $result[$label] = $value;
    }
    return $result;
}
function wp_aot_array_change_key_case(mixed $array, int $case = 0): array {
    $source = wp_aot_array_input($array); $result = wp_aot_empty_request_array();
    foreach ($source as $key => $value) {
        if (is_int($key)) $result[(int) $key] = $value;
        else $result[$case !== 0 ? strtoupper((string) $key) : strtolower((string) $key)] = $value;
    }
    return $result;
}
function wp_aot_array_replace_all(array $arrays, bool $recursive, int $depth = 0): array {
    if (!$arrays) throw new ArgumentCountError('Array replacement needs at least one source array.');
    if ($depth > 64) throw new LogicException('Recursive array replacement exceeds the fixture depth.');
    $result = wp_aot_empty_request_array();
    foreach ($arrays as $array) {
        foreach (wp_aot_array_input($array) as $key => $value) {
            if ($recursive && array_key_exists($key, $result) && is_array($result[$key]) && is_array($value))
                $result[$key] = wp_aot_array_replace_all(array($result[$key], $value), true, $depth + 1);
            else $result[$key] = $value;
        }
    }
    return $result;
}
function wp_aot_array_replace(array $arrays): array { return wp_aot_array_replace_all($arrays, false); }
function wp_aot_array_replace_recursive(array $arrays): array { return wp_aot_array_replace_all($arrays, true); }

// Stable bottom-up merge ordering keeps ties in their original sequence.
function wp_aot_callback_order(mixed &$array, mixed $callback, bool $by_key, bool $keep_keys): bool {
    if (!is_callable($callback)) throw new TypeError('The sort callback must be callable.');
    $source = wp_aot_array_input($array);
    $keys = array_keys($source);
    $length = count($keys);
    for ($width = 1; $width < $length; $width *= 2) {
        $ordered = array();
        for ($begin = 0; $begin < $length; $begin += 2 * $width) {
            $middle = min($begin + $width, $length);
            $limit = min($begin + 2 * $width, $length);
            $left = $begin; $right = $middle;
            while ($left < $middle && $right < $limit) {
                $left_key = $keys[$left]; $right_key = $keys[$right];
                $answer = call_user_func_array($callback, array($by_key ? $left_key : $source[$left_key], $by_key ? $right_key : $source[$right_key]));
                if (!is_int($answer)) throw new LogicException('Native sort callbacks must return integer comparisons.');
                $comparison = (int) $answer;
                if ($comparison <= 0) { $ordered[] = $left_key; ++$left; }
                else { $ordered[] = $right_key; ++$right; }
            }
            while ($left < $middle) { $ordered[] = $keys[$left]; ++$left; }
            while ($right < $limit) { $ordered[] = $keys[$right]; ++$right; }
        }
        $keys = $ordered;
    }
    $result = wp_aot_empty_request_array();
    foreach ($keys as $key) {
        if ($keep_keys) $result[$key] = $source[$key];
        else $result[] = $source[$key];
    }
    $array = $result;
    return true;
}
function wp_aot_usort(mixed &$array, mixed $callback): bool { return wp_aot_callback_order($array, $callback, false, false); }
function wp_aot_uasort(mixed &$array, mixed $callback): bool { return wp_aot_callback_order($array, $callback, false, true); }
function wp_aot_uksort(mixed &$array, mixed $callback): bool { return wp_aot_callback_order($array, $callback, true, true); }

function wp_aot_array_push(mixed &$array, array $values): int {
    $source = wp_aot_array_input($array);
    if (!$values) return count($source);
    foreach ($values as $value) $source[] = $value;
    $array = $source; return count($source);
}
// Nullable list accumulation preserves the original null until the first append.
function wp_aot_nullable_array_append(mixed &$array, mixed $value): void {
    if ($array === null) $array = wp_aot_empty_request_array();
    wp_aot_array_push($array, array($value));
}
function wp_aot_array_unshift(mixed &$array, array $values): int {
    $source = wp_aot_array_input($array); $result = wp_aot_empty_request_array();
    foreach ($values as $value) $result[] = $value;
    foreach ($source as $key => $value) {
        if (is_int($key)) $result[] = $value;
        else $result[(string) $key] = $value;
    }
    $array = $result; return count($result);
}
function wp_aot_array_shift(mixed &$array): mixed {
    $source = wp_aot_array_input($array);
    if (!$source) return null;
    $result = wp_aot_empty_request_array(); $first = true; $removed = null;
    foreach ($source as $key => $value) {
        if ($first) { $removed = $value; $first = false; continue; }
        if (is_int($key)) $result[] = $value;
        else $result[(string) $key] = $value;
    }
    $array = $result; return $removed;
}
function wp_aot_array_pop(mixed &$array): mixed {
    $source = wp_aot_array_input($array);
    if (!$source) return null;
    // Preserve the hidden next integer key, including keys removed before this call.
    $probe = $source; $probe[] = null;
    $probe_keys = array_keys($probe); $next = (int) $probe_keys[count($probe_keys) - 1];
    $local = array();
    foreach ($source as $key => $value) $local[$key] = $value;
    if (!array_key_exists($next - 1, $local)) { $local[$next - 1] = null; unset($local[$next - 1]); }
    $removed = array_pop($local);
    $array = $local; return $removed;
}

function wp_aot_recursive_merge_operand(mixed $value): array {
    $result = wp_aot_empty_request_array();
    if (is_array($value)) {
        foreach ((array) $value as $key => $item) $result[$key] = $item;
    } else {
        $result[] = $value;
    }
    return $result;
}
function wp_aot_recursive_merge_value(array $arrays, int $depth = 0, bool $preserve_first = false): mixed {
    if ($depth > 64) throw new LogicException('Recursive array merging exceeds the fixture depth.');
    $result = wp_aot_empty_request_array();
    $first = true;
    foreach ($arrays as $array) {
        if ($first && $preserve_first) { foreach (wp_aot_array_input($array) as $original_key => $original_value) $result[$original_key] = $original_value; $first = false; continue; }
        $first = false;
        foreach (wp_aot_array_input($array) as $key => $value) {
            if (is_int($key)) $result[] = $value;
            elseif (!array_key_exists((string) $key, $result)) $result[(string) $key] = $value;
            else {
                $previous = $result[(string) $key];
                if (is_object($previous) || is_object($value)) throw new LogicException('Object conversion in recursive array merging is outside the fixture.');
                $left = wp_aot_recursive_merge_operand($previous);
                $right = wp_aot_recursive_merge_operand($value);
                $result[(string) $key] = wp_aot_recursive_merge_value(array($left, $right), $depth + 1, true);
            }
        }
    }
    return $result;
}

function wp_aot_array_merge_recursive(array $arrays): array { return wp_aot_array_input(wp_aot_recursive_merge_value($arrays)); }


// Keep callbacks alive until normal completion or an explicit exit.
$wp_aot_shutdown_callbacks = wp_aot_empty_request_array();
$wp_aot_shutdown_started = false;

function wp_aot_enqueue_shutdown(mixed $callback, array $arguments): void {
    global $wp_aot_shutdown_callbacks;
    if (!is_callable($callback)) throw new TypeError('The shutdown callback must be callable.');
    $queue = wp_aot_array_input($wp_aot_shutdown_callbacks);
    $queue[] = array($callback, $arguments);
    $wp_aot_shutdown_callbacks = $queue;
}

function wp_aot_run_shutdown(): void {
    global $wp_aot_shutdown_callbacks, $wp_aot_shutdown_started;
    if ($wp_aot_shutdown_started) return;
    $wp_aot_shutdown_started = true;
    $at = 0;
    while ($at < count(wp_aot_array_input($wp_aot_shutdown_callbacks))) {
        $queue = wp_aot_array_input($wp_aot_shutdown_callbacks);
        $entry = wp_aot_array_input($queue[$at]);
        ++$at;
        call_user_func_array($entry[0], wp_aot_array_input($entry[1]));
    }
}

function wp_aot_exit(mixed $status = 0): never {
    if (is_string($status)) {
        echo (string) $status;
        $status = 0;
    } elseif (is_array($status) || is_object($status) || is_resource($status)) {
        throw new TypeError('The exit status must be a scalar or null.');
    }
    if (defined('WP_AOT_NATIVE_RUNTIME')) wp_aot_run_shutdown();
    exit((int) $status);
}

function wp_aot_array_keys(mixed $values, mixed $search = null, bool $strict = false, bool $filter = false): array {
    $array = wp_aot_array_input($values);
    $result = wp_aot_empty_request_array();
    foreach ($array as $key => $value) if (!$filter || ($strict ? $value === $search : $value == $search)) $result[] = $key;
    return $result;
}

function wp_aot_array_combine(mixed $keys, mixed $values): array {
    $key_list = array_values(wp_aot_array_input($keys));
    $value_list = array_values(wp_aot_array_input($values));
    if (count($key_list) !== count($value_list)) throw new ValueError('array_combine(): Both arguments should have an equal number of elements');
    $result = wp_aot_empty_request_array();
    foreach ($key_list as $at => $key) {
        if (is_array($key) || is_object($key) || is_resource($key)) throw new LogicException('The native combine fixture requires scalar keys.');
        $index = is_int($key) ? $key : (string) $key;
        $result[$index] = $value_list[$at];
    }
    return $result;
}

// Flip valid PHP key values; invalid-value warning paths are outside this native fixture.
function wp_aot_array_flip(mixed $values): array {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return wp_aot_array_input(wp_aot_native_api('array_flip', array($values)));
    $source = wp_aot_array_input($values);
    $result = wp_aot_empty_request_array();
    foreach ($source as $key => $value) {
        if (is_int($value)) $result[(int) $value] = $key;
        elseif (is_string($value)) $result[(string) $value] = $key;
        else throw new LogicException('Array flip requires integer or string values in the native fixture.');
    }
    return $result;
}

function wp_aot_column_key(mixed $key): mixed {
    if ($key === null || is_int($key) || is_string($key)) return $key;
    if (is_bool($key) || is_float($key)) return (int) $key;
    throw new TypeError('Array column keys must be strings, integers or null.');
}
function wp_aot_array_column(mixed $values, mixed $column_key, mixed $index_key = null): array {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return wp_aot_array_input(wp_aot_native_api('array_column', array($values, $column_key, $index_key)));
    $array = wp_aot_array_input($values);
    $column = wp_aot_column_key($column_key);
    $index = wp_aot_column_key($index_key);
    $result = wp_aot_empty_request_array();
    foreach ($array as $row) {
        if (is_object($row)) throw new LogicException('Object rows are outside the native column fixture.');
        if ($column === null) $value = wp_aot_mixed($row);
        elseif (!is_array($row) || !array_key_exists($column, (array) $row)) continue;
        else $value = $row[$column];
        if ($index === null || !is_array($row) || !array_key_exists($index, (array) $row)) $result[] = $value;
        else {
            $key = $row[$index];
            if (!is_int($key) && !is_string($key) && !is_bool($key) && !is_float($key) && $key !== null) throw new TypeError('Illegal offset type');
            $result[$key] = $value;
        }
    }
    return $result;
}

function wp_aot_count(mixed $value): int {
    if (is_array($value)) return count((array) $value);
    if ($value instanceof Countable) return count($value);
    throw new TypeError('count(): Argument #1 must be of type Countable|array');
}

function wp_aot_natcasesort(mixed &$value): bool { return wp_aot_asort($value, 14); }
function wp_aot_natsort(mixed &$value): bool { return wp_aot_asort($value, 6); }
function wp_aot_shuffle(mixed &$value): bool {
    $array = array_values(wp_aot_array_input($value));
    $result = shuffle($array);
    $value = $array;
    return $result;
}

function wp_aot_php_compare(mixed $a, mixed $b): int { return $a <=> $b; }

// Native value-boundary reads use fresh array cursors. PHP keeps its actual cursor.
function wp_aot_array_current_value(mixed $value): mixed {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return wp_aot_native_api('current', array($value));
    if (!is_array($value)) throw new TypeError('current(): Argument #1 must be of type array');
    $array = (array) $value;
    return current($array);
}
function wp_aot_array_reset_property(mixed &$value): mixed {
    if (!defined('WP_AOT_NATIVE_RUNTIME')) {
        $arguments = array($value); $output =& $arguments[0];
        try { return wp_aot_native_api('reset', $arguments); }
        finally { $value = $output; }
    }
    if (!is_array($value)) throw new TypeError('reset(): Argument #1 must be of type array');
    $array = (array) $value;
    $result = reset($array);
    $value = $array;
    return $result;
}
function wp_aot_iterator_values(mixed $value, bool $preserve_keys = true): array {
    if (is_array($value)) return (array) ($preserve_keys ? (array) $value : array_values((array) $value));
    if (!$value instanceof Traversable) throw new TypeError('iterator_to_array(): Argument #1 must be of type Traversable|array');
    $result = wp_aot_empty_request_array();
    if ($preserve_keys) {
        foreach ($value as $key => $item) {
            if (is_array($key) || is_object($key)) throw new TypeError('Illegal offset type');
            $result[$key] = $item;
        }
    } else {
        foreach ($value as $item) $result[] = $item;
    }
    return $result;
}
function wp_aot_strlen(mixed $value): int { return strlen(wp_aot_regex_text($value)); }

function wp_aot_array_diff_assoc(array $arrays): array {
    if (count($arrays) < 2) throw new ArgumentCountError('array_diff_assoc requires at least two arrays.');
    if (!defined('WP_AOT_NATIVE_RUNTIME')) return (array) wp_aot_native_api('array_diff_assoc', $arrays);
    $sources = wp_aot_empty_request_array();
    foreach ($arrays as $array) $sources[] = wp_aot_array_input($array);
    $first = wp_aot_array_input($sources[0]);
    $result = wp_aot_empty_request_array();
    foreach ($first as $key => $value) {
        if (is_array($value) || is_object($value) || is_resource($value)) throw new LogicException('Native associative difference accepts scalar or null values.');
        $include = true;
        for ($at = 1; $at < count($sources); ++$at) {
            $other = wp_aot_array_input($sources[$at]);
            if (!array_key_exists($key, $other)) continue;
            $candidate = $other[$key];
            if (is_array($candidate) || is_object($candidate) || is_resource($candidate)) throw new LogicException('Native associative difference accepts scalar or null values.');
            if ((string) $value === (string) $candidate) { $include = false; break; }
        }
        if ($include) $result[$key] = $value;
    }
    return $result;
}


// Array-valued JSON record properties use int/string keys and value snapshots.
function wp_aot_array_without_key(mixed $values,mixed $remove_key): array {
    if (!is_int($remove_key) && !is_string($remove_key)) throw new LogicException('This array deletion fixture requires an integer or string key.');
    $without=wp_aot_mixed(wp_aot_array_input($values));
    unset($without[$remove_key]);
    return (array)$without;
}

// Retain original hook keys while providing a native builtin descriptor.
function wp_aot_hook_callable(mixed $callback): mixed {
    if (is_string($callback)) {
        $name = strtolower($callback);
        if ($name === 'strip_tags' || $name === '\\strip_tags') return strip_tags(...);
    }
    return $callback;
}
