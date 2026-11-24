<?php

// Checked views of the complete core graph, separate from isolated AI controls.
function wp_aot_require_DateTimeZone(mixed $value): \DateTimeZone {
    if ($value instanceof \DateTimeZone) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_internal_string(mixed $value): string {
    if ($value === null || is_string($value) || is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) return (string) $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_weak_string(mixed $value): string {
    if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) return (string) $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_nullable_WP_Post(mixed $value): ?\WP_Post {
    if ($value === null) return null;
    if ($value instanceof \WP_Post) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}


function wp_aot_require_nullable_DateTimeZone(mixed $value): ?\DateTimeZone {
    if ($value === null) return null;
    if ($value instanceof \DateTimeZone) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_DateTimeInterface(mixed $value): \DateTimeInterface {
    if ($value instanceof \DateTimeInterface) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_SimplePie_File(mixed $value): \SimplePie\File {
    if ($value instanceof \SimplePie\File) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_SimplePie_HTTP_Response(mixed $value): \SimplePie\HTTP\Response {
    if ($value instanceof \SimplePie\HTTP\Response) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_SimplePie_HTTP_Client(mixed $value): \SimplePie\HTTP\Client {
    if ($value instanceof \SimplePie\HTTP\Client) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_nullable_string(mixed $value): ?string {
    if ($value === null) return null;
    if (is_string($value)) return (string) $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_SimplePie_Item(mixed $value): \SimplePie\Item {
    if ($value instanceof \SimplePie\Item) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_SimplePie_Cache_DataCache(mixed $value): \SimplePie\Cache\DataCache {
    if ($value instanceof \SimplePie\Cache\DataCache) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_nullable_WP_HTML_Token(mixed $value): ?\WP_HTML_Token {
    if ($value === null) return null;
    if ($value instanceof \WP_HTML_Token) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_WP_AI_Client_Prompt_Builder(mixed $value): \WP_AI_Client_Prompt_Builder {
    if ($value instanceof \WP_AI_Client_Prompt_Builder) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_nullable_WP_Ability(mixed $value): ?\WP_Ability {
    if ($value === null) return null;
    if ($value instanceof \WP_Ability) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_nullable_WP_Block_Type_Registry(mixed $value): ?\WP_Block_Type_Registry {
    if ($value === null) return null;
    if ($value instanceof \WP_Block_Type_Registry) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_DOMText(mixed $value): \DOMText {
    if ($value instanceof \DOMText) return $value;
    throw new TypeError('The value does not satisfy the original DOM text contract.');
}

function wp_aot_require_dependency(mixed $value): \_WP_Dependency {
    if ($value instanceof \_WP_Dependency) return $value;
    throw new TypeError('The original dependency record is required.');
}

function wp_aot_weak_nullable_string(mixed $value): ?string {
    if ($value === null) return null;
    return wp_aot_weak_string($value);
}

function wp_aot_require_WP_Error(mixed $value): \WP_Error {
    if ($value instanceof \WP_Error) return $value;
    throw new \TypeError('The value does not satisfy the original WP_Error contract.');
}

function wp_aot_require_WP_Scripts(mixed $value): \WP_Scripts {
    if ($value instanceof \WP_Scripts) return $value;
    throw new \TypeError('The original WP_Scripts registry is required.');
}

function wp_aot_require_WP_Styles(mixed $value): \WP_Styles {
    if ($value instanceof \WP_Styles) return $value;
    throw new \TypeError('The original WP_Styles registry is required.');
}

function wp_aot_require_WP_Query(mixed $value): \WP_Query {
    if ($value instanceof \WP_Query) return $value;
    throw new \TypeError('The original WP_Query receiver is required.');
}

function wp_aot_require_wpdb(mixed $value): \wpdb {
    if ($value instanceof \wpdb) return $value;
    throw new \TypeError('The original wpdb receiver is required.');
}

function wp_aot_bookmark_link_is_set(): bool {
    if (defined('WP_AOT_NATIVE_RUNTIME')) {
        global $link;
        return isset($link);
    }
    return isset($GLOBALS['link']);
}

function wp_aot_bookmark_link_value(): mixed {
    if (defined('WP_AOT_NATIVE_RUNTIME')) {
        global $link;
        return $link;
    }
    return $GLOBALS['link'];
}

function wp_aot_require_WP_Term(mixed $value): \WP_Term {
    if ($value instanceof \WP_Term) return $value;
    throw new \TypeError('The original WP_Term record is required.');
}

function wp_aot_require_Text_Diff_Op(mixed $value): \Text_Diff_Op {
    if ($value instanceof \Text_Diff_Op) return $value;
    throw new \TypeError('The original text-diff operation is required.');
}

function wp_aot_require_SimplePie_Registry(mixed $value): \SimplePie\Registry {
    if ($value instanceof \SimplePie\Registry) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_WP_Translation_Controller(mixed $value): \WP_Translation_Controller {
    if ($value instanceof \WP_Translation_Controller) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_nullable_WP_Ability_Categories_Registry(mixed $value): ?\WP_Ability_Categories_Registry {
    if ($value === null) return null;
    if ($value instanceof \WP_Ability_Categories_Registry) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}

function wp_aot_require_nullable_WP_Abilities_Registry(mixed $value): ?\WP_Abilities_Registry {
    if ($value === null) return null;
    if ($value instanceof \WP_Abilities_Registry) return $value;
    throw new \TypeError('The value does not satisfy the original declared contract.');
}
