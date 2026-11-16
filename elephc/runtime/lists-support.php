<?php

function wordpress_lists_map(): array { return (array) json_decode('{}', true); }

function wordpress_lists_merge(array $entries, callable $compare): array {
    $count = count($entries);
    if ($count < 2) { return $entries; }
    $middle = (int) ($count / 2);
    $left = [];
    $right = [];
    foreach ($entries as $index => $entry) {
        if ($index < $middle) { $left[] = $entry; }
        else { $right[] = $entry; }
    }
    $left = wordpress_lists_merge($left, $compare);
    $right = wordpress_lists_merge($right, $compare);
    $output = [];
    $i = 0;
    $j = 0;
    while ($i < count($left) && $j < count($right)) {
        $a = (array) $left[$i];
        $b = (array) $right[$j];
        if ((int) $compare((array) $a['value'], (array) $b['value']) <= 0) {
            $output[] = $a;
            ++$i;
        } else {
            $output[] = $b;
            ++$j;
        }
    }
    while ($i < count($left)) { $output[] = $left[$i++]; }
    while ($j < count($right)) { $output[] = $right[$j++]; }
    return $output;
}

// Preserve PHP's stable ties while retaining the original core comparator.
function wordpress_lists_sorted(array $items, callable $compare, bool $preserve_keys): array {
    $entries = [];
    foreach ($items as $key => $value) { $entries[] = ['key' => $key, 'value' => $value]; }
    $output = wordpress_lists_map();
    foreach (wordpress_lists_merge($entries, $compare) as $entry) {
        $pair = (array) $entry;
        if ($preserve_keys) { $output[$pair['key']] = $pair['value']; }
        else { $output[] = $pair['value']; }
    }
    return $output;
}

function wordpress_lists_field(mixed $field): mixed {
    if (!is_int($field) && !is_string($field)) {
        throw new InvalidArgumentException('List fields and keys must be integers or strings.');
    }
    if (is_string($field)) {
        $text = (string) $field;
        for ($index = 0; $index < strlen($text); ++$index) {
            if (ord($text[$index]) === 0) { throw new InvalidArgumentException('NUL bytes are outside list keys and comparison text.'); }
        }
    }
    return $field;
}

function wordpress_lists_rows(array $items): void {
    if (count($items) > 256) { throw new InvalidArgumentException('At most 256 list records are allowed.'); }
    foreach ($items as $key => $row) {
        wordpress_lists_field($key);
        if (!is_array($row)) { throw new InvalidArgumentException('List records must be arrays or JSON objects.'); }
        foreach ((array) $row as $field => $value) { wordpress_lists_field($field); }
    }
}

function wordpress_lists_scalar(mixed $value): void {
    if (is_array($value) || is_object($value)) { throw new InvalidArgumentException('List comparisons require scalar values or null.'); }
    if (is_string($value)) { wordpress_lists_field($value); }
}

function wordpress_lists_parameters(array $items, array $operation): array {
    wordpress_lists_rows($items);
    $kind = $operation['op'] ?? 'filter';
    if (!is_string($kind) || !in_array($kind, ['filter', 'filter-pluck', 'pluck', 'sort'], true)) {
        throw new InvalidArgumentException('Unknown list operation.');
    }
    $args = $operation['args'] ?? [];
    if (!is_array($args)) { throw new InvalidArgumentException('Filter args must be an object or array.'); }
    foreach ((array) $args as $field => $value) {
        wordpress_lists_field($field);
        wordpress_lists_scalar($value);
        foreach ($items as $row) {
            $record = (array) $row;
            if (array_key_exists($field, $record)) { wordpress_lists_scalar($record[$field]); }
        }
    }
    $operator = $operation['operator'] ?? 'AND';
    $order = $operation['order'] ?? 'ASC';
    if (!is_string($operator) || !is_string($order)) { throw new InvalidArgumentException('List operator and order must be strings.'); }
    $orderby = $operation['orderby'] ?? [];
    if (!is_string($orderby) && !is_array($orderby)) { throw new InvalidArgumentException('orderby must be a field or an order map.'); }
    if (is_string($orderby)) { wordpress_lists_field($orderby); }
    $sort_fields = is_array($orderby) ? (array) $orderby : wordpress_lists_map();
    if (is_string($orderby) && $orderby !== '' && $orderby !== '0') { $sort_fields[(string) $orderby] = $order; }
    foreach ($sort_fields as $field => $direction) {
        wordpress_lists_field($field);
        if (!is_string($direction)) { throw new InvalidArgumentException('Sort directions must be strings.'); }
        foreach ($items as $row) {
            $record = (array) $row;
            if (isset($record[$field])) { wordpress_lists_scalar($record[$field]); }
        }
    }
    if (array_key_exists('preserve_keys', $operation) && !is_bool($operation['preserve_keys'])) {
        throw new InvalidArgumentException('preserve_keys must be a boolean.');
    }
    $field = $operation['field'] ?? false;
    if ($kind === 'pluck' && !array_key_exists('field', $operation)) { throw new InvalidArgumentException('Missing pluck field.'); }
    if ($kind === 'pluck' || $field !== false) { wordpress_lists_field($field); }
    $index = $operation['index_key'] ?? null;
    if ($index !== null) { wordpress_lists_field($index); }
    $selected = $kind === 'filter-pluck' ? wp_list_filter($items, (array) $args, (string) $operator) : $items;
    if ($kind === 'pluck' || ($kind === 'filter-pluck' && (bool) $field)) {
        foreach ($selected as $row) {
            $record = (array) $row;
            if (!array_key_exists($field, $record)) { throw new InvalidArgumentException('Every selected record must contain the pluck field.'); }
            if ((bool) $index && isset($record[$index])) { wordpress_lists_field($record[$index]); }
        }
    }
    return ['op' => $kind, 'args' => $args, 'operator' => $operator, 'field' => $field, 'index_key' => $index,
        'orderby' => $orderby, 'order' => $order, 'preserve_keys' => (bool) ($operation['preserve_keys'] ?? false)];
}

function wordpress_lists_apply(array $items, array $parameters): array {
    switch ((string) $parameters['op']) {
        case 'filter': return wp_list_filter($items, (array) $parameters['args'], (string) $parameters['operator']);
        case 'filter-pluck': return wp_filter_object_list($items, (array) $parameters['args'], (string) $parameters['operator'], $parameters['field']);
        case 'pluck': return wp_list_pluck($items, $parameters['field'], $parameters['index_key']);
        case 'sort': return wp_list_sort($items, $parameters['orderby'], (string) $parameters['order'], (bool) $parameters['preserve_keys']);
    }
    throw new InvalidArgumentException('Unknown list operation.');
}

function wordpress_lists_text(string $input): string {
    $request = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request) || !str_starts_with(ltrim($input), '{') || !is_string(json_encode($request))) {
        throw new InvalidArgumentException('List input must be a JSON-encodable object.');
    }
    $request = (array) $request;
    if (!isset($request['items']) || !is_array($request['items'])) { throw new InvalidArgumentException('Missing items array or object.'); }
    $items = (array) $request['items'];
    wordpress_lists_rows($items);
    $steps = [];
    if (($request['op'] ?? '') === 'chain') {
        if (!isset($request['operations']) || !is_array($request['operations']) || !array_is_list((array) $request['operations'])) {
            throw new InvalidArgumentException('Chained operations must be a list.');
        }
        $operations = (array) $request['operations'];
        if (count($operations) > 16) { throw new InvalidArgumentException('At most 16 chained operations are allowed.'); }
        $util = new WP_List_Util($items);
        foreach ($operations as $operation) {
            if (!is_array($operation)) { throw new InvalidArgumentException('Each list operation must be an object.'); }
            $p = wordpress_lists_parameters($util->get_output(), (array) $operation);
            switch ((string) $p['op']) {
                case 'filter': $util->filter((array) $p['args'], (string) $p['operator']); break;
                case 'filter-pluck':
                    $util->filter((array) $p['args'], (string) $p['operator']);
                    if ((bool) $p['field']) { $util->pluck($p['field']); }
                    break;
                case 'pluck': $util->pluck($p['field'], $p['index_key']); break;
                case 'sort': $util->sort($p['orderby'], (string) $p['order'], (bool) $p['preserve_keys']); break;
            }
            $steps[] = $util->get_output();
        }
        $original = $util->get_input();
        $output = $util->get_output();
    } else {
        $original = $items;
        $output = wordpress_lists_apply($items, wordpress_lists_parameters($items, (array) $request));
    }
    $encoded = json_encode(['input' => $original, 'output' => $output, 'steps' => $steps], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) { throw new InvalidArgumentException('List results cannot be encoded as JSON.'); }
    return (string) $encoded;
}
