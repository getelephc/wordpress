<?php

final class WordPressCacheContext {
    public static bool $multisite = false;
    public static int $blog_id = 1;
}

// Uniform mixed maps avoid native list-to-map storage transitions for integer keys.
function wordpress_cache_map(): array { return (array) json_decode('{}', true); }

function wordpress_cache_multisite(): bool { return WordPressCacheContext::$multisite; }
function wordpress_cache_blog_id(): int { return WordPressCacheContext::$blog_id; }

function wordpress_cache_key(mixed $key): mixed {
    if (!is_int($key) && (!is_string($key) || trim((string) $key) === '')) {
        throw new InvalidArgumentException('Cache keys must be integers or non-empty strings.');
    }
    if (is_string($key)) { wordpress_cache_string($key); }
    return $key;
}

function wordpress_cache_string(string $text): string {
    for ($index = 0; $index < strlen($text); ++$index) {
        if (ord($text[$index]) === 0) {
            throw new InvalidArgumentException('NUL bytes are outside cache keys and groups.');
        }
    }
    return $text;
}

function wordpress_cache_operation(array $operation): mixed {
    if (!isset($operation['op']) || !is_string($operation['op'])) {
        throw new InvalidArgumentException('Each cache operation needs a string op.');
    }
    $op = (string) $operation['op'];
    if (array_key_exists('group', $operation) && !is_string($operation['group'])) {
        throw new InvalidArgumentException('Cache groups must be strings.');
    }
    $group = wordpress_cache_string((string) ($operation['group'] ?? ''));
    foreach (['expire', 'offset', 'blog_id'] as $field) {
        if (array_key_exists($field, $operation) && !is_int($operation[$field])) {
            throw new InvalidArgumentException('Cache ' . $field . ' must be an integer.');
        }
    }
    if (array_key_exists('force', $operation) && !is_bool($operation['force'])) {
        throw new InvalidArgumentException('Cache force must be a boolean.');
    }
    $expire = (int) ($operation['expire'] ?? 0);
    $offset = (int) ($operation['offset'] ?? 1);
    $force = (bool) ($operation['force'] ?? false);
    $key = null;
    if (in_array($op, ['set', 'add', 'replace', 'get', 'delete', 'incr', 'decr'], true)) {
        if (!array_key_exists('key', $operation)) { throw new InvalidArgumentException('Missing cache key.'); }
        $key = wordpress_cache_key($operation['key']);
    }
    if (in_array($op, ['set', 'add', 'replace'], true) && !array_key_exists('value', $operation)) {
        throw new InvalidArgumentException('Missing cache value.');
    }
    $data = [];
    if (in_array($op, ['set_multiple', 'add_multiple'], true)) {
        if (!isset($operation['data']) || !is_array($operation['data'])) {
            throw new InvalidArgumentException('Cache data must be an object or array.');
        }
        $data = (array) $operation['data'];
        foreach ($data as $data_key => $value) { wordpress_cache_key($data_key); }
    }
    $keys = [];
    if (in_array($op, ['get_multiple', 'delete_multiple'], true)) {
        if (!isset($operation['keys']) || !is_array($operation['keys']) || !array_is_list((array) $operation['keys'])) {
            throw new InvalidArgumentException('Cache keys must be a list.');
        }
        $keys = (array) $operation['keys'];
        foreach ($keys as $item) { wordpress_cache_key($item); }
    }
    switch ($op) {
        case 'set': return wp_cache_set($key, $operation['value'], $group, $expire);
        case 'add': return wp_cache_add($key, $operation['value'], $group, $expire);
        case 'replace': return wp_cache_replace($key, $operation['value'], $group, $expire);
        case 'get':
            $found = null;
            $value = wp_cache_get($key, $group, $force, $found);
            return ['value' => $value, 'found' => $found];
        case 'delete': return wp_cache_delete($key, $group);
        case 'incr': return wp_cache_incr($key, $offset, $group);
        case 'decr': return wp_cache_decr($key, $offset, $group);
        case 'set_multiple': return wp_cache_set_multiple($data, $group, $expire);
        case 'add_multiple': return wp_cache_add_multiple($data, $group, $expire);
        case 'get_multiple': return wp_cache_get_multiple($keys, $group, $force);
        case 'delete_multiple': return wp_cache_delete_multiple($keys, $group);
        case 'flush': return wp_cache_flush();
        case 'flush_runtime': return wp_cache_flush_runtime();
        case 'flush_group': return wp_cache_flush_group($group);
        case 'global_groups':
        case 'non_persistent_groups':
            $groups = $operation['groups'] ?? [];
            if (!is_string($groups) && !is_array($groups)) { throw new InvalidArgumentException('Groups must be a string or list.'); }
            foreach ((array) $groups as $item) {
                if (!is_string($item)) { throw new InvalidArgumentException('Group names must be strings.'); }
                wordpress_cache_string((string) $item);
            }
            if ($op === 'global_groups') { wp_cache_add_global_groups($groups); }
            else { wp_cache_add_non_persistent_groups($groups); }
            return null;
        case 'switch_blog':
            if (!array_key_exists('blog_id', $operation)) { throw new InvalidArgumentException('Missing blog_id.'); }
            wp_cache_switch_to_blog((int) $operation['blog_id']);
            return null;
        case 'suspend':
            if (array_key_exists('value', $operation) && $operation['value'] !== null && !is_bool($operation['value'])) {
                throw new InvalidArgumentException('Suspension must be boolean or null.');
            }
            return wp_suspend_cache_addition($operation['value'] ?? null);
        case 'supports':
            if (!isset($operation['feature']) || !is_string($operation['feature'])) { throw new InvalidArgumentException('Missing feature string.'); }
            return wp_cache_supports((string) $operation['feature']);
        case 'close': return wp_cache_close();
        case 'init': wp_cache_init(); return null;
        default: throw new InvalidArgumentException('Unknown cache operation: ' . $op);
    }
}

function wordpress_cache_text(string $input): string {
    $request = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request) || !str_starts_with(ltrim($input), '{')) {
        throw new InvalidArgumentException('Cache input must be a JSON object.');
    }
    $request = (array) $request;
    if (!is_string(json_encode($request))) { throw new InvalidArgumentException('Cache input must contain JSON-encodable values.'); }
    if (array_key_exists('multisite', $request) && !is_bool($request['multisite'])) {
        throw new InvalidArgumentException('multisite must be a boolean.');
    }
    if (array_key_exists('blog_id', $request) && (!is_int($request['blog_id']) || $request['blog_id'] < 0)) {
        throw new InvalidArgumentException('Initial blog_id must be a non-negative integer.');
    }
    if (!isset($request['operations']) || !is_array($request['operations']) || !array_is_list((array) $request['operations'])) {
        throw new InvalidArgumentException('Cache operations must be a list.');
    }
    $operations = (array) $request['operations'];
    if (count($operations) > 256) { throw new InvalidArgumentException('At most 256 cache operations are allowed.'); }
    WordPressCacheContext::$multisite = (bool) ($request['multisite'] ?? false);
    WordPressCacheContext::$blog_id = (int) ($request['blog_id'] ?? 1);
    wp_suspend_cache_addition(false);
    wp_cache_init();
    $results = [];
    foreach ($operations as $operation) {
        if (!is_array($operation)) { throw new InvalidArgumentException('Cache operations must be objects.'); }
        $results[] = wordpress_cache_operation((array) $operation);
    }
    global $wp_object_cache;
    if (!$wp_object_cache instanceof WP_Object_Cache) { throw new LogicException('Cache is not initialized.'); }
    $encoded = json_encode(['results' => $results, 'hits' => $wp_object_cache->cache_hits, 'misses' => $wp_object_cache->cache_misses], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) { throw new InvalidArgumentException('Cache results cannot be represented as JSON.'); }
    return (string) $encoded;
}
