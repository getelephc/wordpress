<?php

// A declared array return keeps the empty child open to boxed PHP values.
function wp_aot_empty_query_array(): array {
    return array();
}

// Parse the UTF-8, NUL-free bracket grammar emitted by PHP query builders.
function wp_aot_query_parts(string $name): array {
    $first = strpos($name, '[');
    $root = $first === false ? $name : substr($name, 0, (int) $first);
    $root = str_replace(array('.', ' '), '_', ltrim($root, ' '));
    if ($root === '') return array();
    $parts = array($root);
    if ($first === false) return $parts;
    $offset = (int) $first;
    while ($offset < strlen($name)) {
        if ($name[$offset] !== '[') throw new LogicException('Malformed query brackets are outside the native profile.');
        $close = strpos($name, ']', $offset + 1);
        if ($close === false) throw new LogicException('Unclosed query brackets are outside the native profile.');
        $segment = substr($name, $offset + 1, (int) $close - $offset - 1);
        if (str_contains($segment, '[')) throw new LogicException('Nested bracket names are outside the native profile.');
        $parts[] = $segment;
        if (count($parts) > 65) throw new ValueError('Query nesting exceeds 64 bracket levels.');
        $offset = (int) $close + 1;
    }
    return $parts;
}

// Copy each parent back instead of creating references inside an array literal.
function wp_aot_query_assign(array $result, array $parts, int $position, string $value): array {
    $key = (string) $parts[$position];
    $last = $position + 1 === count($parts);
    if ($key === '') {
        if ($last) {
            $result[] = $value;
        } else {
            $result[] = wp_aot_query_assign(array(), $parts, $position + 1, $value);
        }
    } elseif ($last) {
        $result[$key] = $value;
    } else {
        $previous = $result[$key] ?? null;
        $child = wp_aot_empty_query_array();
        if (is_array($previous)) $child = (array) $previous;
        $result[$key] = wp_aot_query_assign((array) $child, $parts, $position + 1, $value);
    }
    return (array) $result;
}

function wp_aot_parse_query(string $query): array {
    if (strlen($query) > 1048576) throw new ValueError('Query input exceeds one MiB.');
    if (str_contains($query, "\0") || preg_match('//u', $query) !== 1) {
        throw new ValueError('Query input must be UTF-8 and NUL-free.');
    }
    $result = array();
    $fields = 0;
    foreach (explode('&', $query) as $field) {
        if ($field === '') continue;
        if (++$fields > 1000) throw new ValueError('Query input exceeds 1000 fields.');
        $equals = strpos($field, '=');
        $name = urldecode($equals === false ? $field : substr($field, 0, (int) $equals));
        $value = $equals === false ? '' : urldecode(substr($field, (int) $equals + 1));
        if (str_contains($name, "\0") || str_contains($value, "\0") || preg_match('//u', $name) !== 1 || preg_match('//u', $value) !== 1) {
            throw new ValueError('Decoded query fields must be UTF-8 and NUL-free.');
        }
        $parts = wp_aot_query_parts($name);
        if (!empty($parts)) $result = wp_aot_query_assign((array) $result, $parts, 0, $value);
    }
    return (array) $result;
}
