<?php

final class WordPressErrorTrace {
    public static array $events = [];
}

function wordpress_error_map(): array { return (array) json_decode('{}', true); }

function wordpress_error_string(mixed $value): string {
    if (!is_string($value) || str_contains((string) $value, "\0")) {
        throw new InvalidArgumentException('Names and messages must be strings without NUL.');
    }
    return (string) $value;
}

function wordpress_error_code(mixed $value): mixed {
    if (is_int($value)) { return $value; }
    return wordpress_error_string($value);
}

function wordpress_error_json(mixed $value, int $depth = 0): void {
    if ($depth > 16) { throw new InvalidArgumentException('Error data nesting exceeds 16 levels.'); }
    if (is_array($value)) {
        if (count((array) $value) > 256) { throw new InvalidArgumentException('Error data allows at most 256 fields per level.'); }
        foreach ((array) $value as $key => $item) {
            if (is_string($key)) { wordpress_error_string($key); }
            wordpress_error_json($item, $depth + 1);
        }
    }
}

function wordpress_error_object(array $objects, string $name): WP_Error {
    $object = $objects[$name] ?? null;
    if (!($object instanceof WP_Error)) { throw new InvalidArgumentException('Unknown error object: ' . $name); }
    return $object;
}

function wordpress_error_snapshot(WP_Error $error): array {
    $details = [];
    foreach ($error->get_error_codes() as $code) {
        $details[] = ['code' => $code, 'messages' => $error->get_error_messages($code), 'data' => $error->get_error_data($code), 'history' => $error->get_all_error_data($code)];
    }
    return ['codes' => $error->get_error_codes(), 'first_code' => $error->get_error_code(), 'messages' => $error->get_error_messages(), 'message' => $error->get_error_message(), 'data' => $error->get_error_data(), 'has_errors' => $error->has_errors(), 'errors' => $error->errors, 'error_data' => $error->error_data, 'details' => $details];
}

function wordpress_error_record(array $event): void {
    $events = WordPressErrorTrace::$events;
    if (count($events) >= 1024) { throw new InvalidArgumentException('At most 1024 error hook events are allowed.'); }
    $events[] = $event;
    WordPressErrorTrace::$events = $events;
}

function wordpress_errors_operation(array $operation, array &$objects): mixed {
    if (!isset($operation['op']) || !is_string($operation['op'])) { throw new InvalidArgumentException('Each error operation needs a string op.'); }
    $op = (string) $operation['op'];
    $target = wordpress_error_string($operation['target'] ?? 'main');
    if ($target === '') { throw new InvalidArgumentException('Error object names must be nonempty.'); }
    $code = array_key_exists('code', $operation) ? wordpress_error_code($operation['code']) : '';
    $message = array_key_exists('message', $operation) ? wordpress_error_string($operation['message']) : '';
    $data = array_key_exists('data', $operation) ? $operation['data'] : '';
    wordpress_error_json($data);
    if ($op === 'create') {
        if (!isset($objects[$target]) && count($objects) >= 16) { throw new InvalidArgumentException('At most 16 error objects are allowed.'); }
        $objects[$target] = new WP_Error($code, $message, $data);
        return null;
    }
    if ($op === 'check-value') {
        if (!array_key_exists('value', $operation)) { throw new InvalidArgumentException('Missing value to check.'); }
        return is_wp_error($operation['value']);
    }
    $error = wordpress_error_object($objects, $target);
    switch ($op) {
        case 'add':
            if (!array_key_exists('code', $operation) || !array_key_exists('message', $operation)) { throw new InvalidArgumentException('Adding an error requires code and message.'); }
            $error->add($code, $message, $data);
            return null;
        case 'add-data':
            if (!array_key_exists('data', $operation)) { throw new InvalidArgumentException('Missing error data.'); }
            $error->add_data($data, $code);
            return null;
        case 'remove':
            if (!array_key_exists('code', $operation)) { throw new InvalidArgumentException('Removing an error requires a code.'); }
            $error->remove($code);
            return null;
        case 'get-codes': return $error->get_error_codes();
        case 'get-code': return $error->get_error_code();
        case 'get-messages': return $error->get_error_messages($code);
        case 'get-message': return $error->get_error_message($code);
        case 'get-data': return $error->get_error_data($code);
        case 'get-all-data': return $error->get_all_error_data($code);
        case 'has-errors': return $error->has_errors();
        case 'is-error': return is_wp_error($error);
        case 'snapshot': return wordpress_error_snapshot($error);
        case 'merge':
        case 'export':
            if (!array_key_exists('source', $operation)) { throw new InvalidArgumentException('Missing source error object.'); }
            $source = wordpress_error_object($objects, wordpress_error_string($operation['source']));
            if ($op === 'merge') { $error->merge_from($source); }
            else { $source->export_to($error); }
            return null;
    }
    throw new InvalidArgumentException('Unknown error operation.');
}

function wordpress_errors_budget(array $objects): void {
    $size = 0;
    foreach ($objects as $object) {
        if (!($object instanceof WP_Error)) { throw new InvalidArgumentException('Invalid error object.'); }
        $size += count($object->error_data);
        foreach ($object->get_error_codes() as $code) {
            $size += count($object->get_error_messages($code)) + count($object->get_all_error_data($code));
        }
    }
    if ($size > 1024) { throw new InvalidArgumentException('Error object state exceeds 1024 message/data entries.'); }
}

function wordpress_errors_text(string $input): string {
    $request = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request) || !str_starts_with(ltrim($input), '{') || !is_string(json_encode($request))) {
        throw new InvalidArgumentException('Error input must be a JSON-encodable object.');
    }
    $request = (array) $request;
    if (!isset($request['operations']) || !is_array($request['operations']) || !array_is_list((array) $request['operations'])) {
        throw new InvalidArgumentException('Error operations must be a list.');
    }
    if (count((array) $request['operations']) > 256) { throw new InvalidArgumentException('At most 256 error operations are allowed.'); }
    if (array_key_exists('hooks', $request) && !is_bool($request['hooks'])) { throw new InvalidArgumentException('hooks must be a boolean.'); }
    remove_all_filters('wp_error_added');
    remove_all_filters('is_wp_error_instance');
    WordPressErrorTrace::$events = [];
    if ((bool) ($request['hooks'] ?? false)) {
        add_action('wp_error_added', static function (mixed $code, string $message, mixed $data, WP_Error $error): void {
            wordpress_error_record(['event' => 'added', 'code' => $code, 'message' => $message, 'data' => $data, 'latest' => $error->get_error_data($code), 'has_errors' => $error->has_errors()]);
        }, 10, 4);
        add_action('is_wp_error_instance', static function (WP_Error $error): void {
            wordpress_error_record(['event' => 'checked', 'codes' => $error->get_error_codes(), 'has_errors' => $error->has_errors()]);
        });
    }
    $objects = wordpress_error_map();
    $objects['main'] = new WP_Error();
    $results = [];
    foreach ((array) $request['operations'] as $operation) {
        if (!is_array($operation) || !isset($operation['op'])) { throw new InvalidArgumentException('Each error operation must be an object.'); }
        $results[] = wordpress_errors_operation((array) $operation, $objects);
        wordpress_errors_budget($objects);
    }
    $snapshots = wordpress_error_map();
    foreach ($objects as $name => $object) {
        if (!($object instanceof WP_Error)) { throw new InvalidArgumentException('Invalid error object.'); }
        $snapshots[$name] = wordpress_error_snapshot($object);
    }
    $encoded = json_encode(['results' => $results, 'objects' => $snapshots, 'events' => WordPressErrorTrace::$events], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) { throw new InvalidArgumentException('Error results cannot be encoded as JSON.'); }
    return (string) $encoded;
}
