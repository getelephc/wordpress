<?php

function wordpress_response_map(): array { return (array) json_decode('{}', true); }

function wordpress_response_line(mixed $value): string {
    $text = wordpress_error_string($value);
    if (str_contains($text, "\r") || str_contains($text, "\n")) { throw new InvalidArgumentException('Response header text must exclude line breaks.'); }
    return $text;
}

function wordpress_response_status(mixed $value): int {
    if (!is_int($value) || $value < -9999 || $value > 9999) { throw new InvalidArgumentException('Response status must be an integer from -9999 to 9999.'); }
    return (int) $value;
}

function wordpress_response_headers(mixed $value): array {
    if (!is_array($value) || count((array) $value) > 256) { throw new InvalidArgumentException('Headers must be an array with at most 256 entries.'); }
    foreach ((array) $value as $key => $item) {
        if (wordpress_response_line((string) $key) === '') { throw new InvalidArgumentException('Header names must be nonempty.'); }
        wordpress_response_line($item);
    }
    return (array) $value;
}

function wordpress_response_snapshot(WP_HTTP_Response $response): array {
    $view = ['kind' => 'http', 'status' => $response->get_status(), 'headers' => $response->get_headers(), 'data' => $response->get_data(), 'json' => $response->jsonSerialize()];
    if ($response instanceof WP_REST_Response) {
        $view['kind'] = 'rest';
        $view['links'] = $response->get_links();
        $view['route'] = $response->get_matched_route();
        $view['handler'] = $response->get_matched_handler();
        $view['is_error'] = $response->is_error();
        $view['curies'] = $response->get_curies();
    }
    return $view;
}

function wordpress_response_view(mixed $value): mixed {
    if ($value instanceof WP_Error) { return ['kind' => 'error', 'error' => wordpress_error_snapshot($value)]; }
    if ($value instanceof WP_HTTP_Response) { return wordpress_response_snapshot($value); }
    return null;
}

function wordpress_response_error(mixed $operations): WP_Error {
    if (!is_array($operations) || !array_is_list((array) $operations) || count((array) $operations) > 256) {
        throw new InvalidArgumentException('Error operations must be a list with at most 256 entries.');
    }
    $objects = wordpress_error_map();
    $objects['main'] = new WP_Error();
    foreach ((array) $operations as $operation) {
        if (!is_array($operation)) { throw new InvalidArgumentException('Each error operation must be an object.'); }
        wordpress_errors_operation((array) $operation, $objects);
        wordpress_errors_budget($objects);
    }
    return wordpress_error_object($objects, 'main');
}

function wordpress_response_error_record(mixed $record): void {
    if (!is_array($record)) { throw new InvalidArgumentException('REST error records must be objects.'); }
    $record = (array) $record;
    if (!array_key_exists('code', $record) || !array_key_exists('message', $record) || !array_key_exists('data', $record)) {
        throw new InvalidArgumentException('REST error records require code, message and data.');
    }
    wordpress_error_code($record['code']);
    wordpress_error_string($record['message']);
    wordpress_error_json($record['data']);
}

function wordpress_response_as_error(WP_REST_Response $response): mixed {
    if ($response->is_error() && is_array($response->get_data())) {
        $data = (array) $response->get_data();
        wordpress_response_error_record($data);
        if (isset($data['additional_errors'])) {
            if (!is_array($data['additional_errors']) || !array_is_list((array) $data['additional_errors']) || count((array) $data['additional_errors']) > 256) {
                throw new InvalidArgumentException('additional_errors must be a list with at most 256 records.');
            }
            foreach ((array) $data['additional_errors'] as $record) { wordpress_response_error_record($record); }
        }
    }
    return wordpress_response_view($response->as_error());
}

function wordpress_response_bulk_links(mixed $links): array {
    if (!is_array($links)) { throw new InvalidArgumentException('Links must be a relation map.'); }
    wordpress_error_json($links);
    $count = 0;
    foreach ((array) $links as $rel => $set) {
        wordpress_error_string((string) $rel);
        if (!is_array($set)) { throw new InvalidArgumentException('Link entries must be objects or lists.'); }
        $set = (array) $set;
        $entries = array_key_exists('href', $set) ? [$set] : $set;
        foreach ((array) $entries as $entry) {
            if (!is_array($entry) || !array_key_exists('href', (array) $entry)) { throw new InvalidArgumentException('Each link requires href.'); }
            wordpress_error_string($entry['href']);
            ++$count;
        }
    }
    if ($count > 256) { throw new InvalidArgumentException('At most 256 links can be added in one operation.'); }
    return (array) $links;
}

function wordpress_responses_operation(WP_HTTP_Response $response, array $operation): mixed {
    if (!isset($operation['op']) || !is_string($operation['op'])) { throw new InvalidArgumentException('Each response operation needs a string op.'); }
    $op = (string) $operation['op'];
    $value = array_key_exists('value', $operation) ? $operation['value'] : null;
    switch ($op) {
        case 'get-data': return $response->get_data();
        case 'json': return $response->jsonSerialize();
        case 'get-status': return $response->get_status();
        case 'get-headers': return $response->get_headers();
        case 'snapshot': return wordpress_response_snapshot($response);
        case 'set-data':
            if (!array_key_exists('value', $operation)) { throw new InvalidArgumentException('Missing response data.'); }
            wordpress_error_json($value);
            $response->set_data($value);
            return null;
        case 'set-status': $response->set_status(wordpress_response_status($value)); return null;
        case 'set-headers': $response->set_headers(wordpress_response_headers($value)); return null;
        case 'header':
            $key = wordpress_response_line($operation['key'] ?? null);
            if ($key === '') { throw new InvalidArgumentException('Header names must be nonempty.'); }
            if (array_key_exists('replace', $operation) && !is_bool($operation['replace'])) { throw new InvalidArgumentException('replace must be boolean.'); }
            $response->header($key, wordpress_response_line($value), (bool) ($operation['replace'] ?? true));
            return null;
        case 'ensure':
            $ensured = rest_ensure_response($response);
            return ['same' => $ensured === $response, 'response' => wordpress_response_view($ensured)];
        case 'ensure-json':
            if (!array_key_exists('value', $operation)) { throw new InvalidArgumentException('Missing value to wrap.'); }
            wordpress_error_json($value);
            return wordpress_response_view(rest_ensure_response($value));
        case 'ensure-error':
            $error = wordpress_response_error($operation['errors'] ?? null);
            $ensured = rest_ensure_response($error);
            return ['same' => $ensured === $error, 'response' => wordpress_response_view($ensured)];
        case 'convert-error':
            $error = wordpress_response_error($operation['errors'] ?? null);
            if (!$error->has_errors()) { throw new InvalidArgumentException('Converting an error requires at least one message.'); }
            foreach ($error->get_all_error_data() as $data) {
                if (is_array($data) && isset($data['status']) && is_numeric($data['status'])) {
                    if ((float) $data['status'] < -9999 || (float) $data['status'] > 9999) { throw new InvalidArgumentException('Error status candidates must be within -9999 to 9999.'); }
                }
            }
            $converted = rest_convert_error_to_response($error);
            return ['response' => wordpress_response_snapshot($converted), 'as_error' => wordpress_response_as_error($converted)];
    }
    if (!($response instanceof WP_REST_Response)) { throw new InvalidArgumentException('This operation requires a REST response.'); }
    switch ($op) {
        case 'get-links': return $response->get_links();
        case 'get-curies': return $response->get_curies();
        case 'get-route': return $response->get_matched_route();
        case 'get-handler': return $response->get_matched_handler();
        case 'is-error': return $response->is_error();
        case 'as-error': return wordpress_response_as_error($response);
        case 'set-route': $response->set_matched_route(wordpress_error_string($value)); return null;
        case 'set-handler':
            if (!is_array($value)) { throw new InvalidArgumentException('Handler metadata must be an array.'); }
            wordpress_error_json($value);
            $response->set_matched_handler((array) $value);
            return null;
        case 'add-link':
            $attributes = $operation['attributes'] ?? [];
            if (!is_array($attributes)) { throw new InvalidArgumentException('Link attributes must be an array.'); }
            wordpress_error_json($attributes);
            $response->add_link(wordpress_error_string($operation['rel'] ?? null), wordpress_error_string($operation['href'] ?? null), (array) $attributes);
            return null;
        case 'remove-link':
            $href = $operation['href'] ?? null;
            if ($href !== null) { wordpress_error_string($href); }
            $response->remove_link(wordpress_error_string($operation['rel'] ?? null), $href);
            return null;
        case 'add-links': $response->add_links(wordpress_response_bulk_links($value)); return null;
        case 'link-header':
            $other = wordpress_response_headers($operation['other'] ?? []);
            $response->link_header(wordpress_response_line($operation['rel'] ?? null), wordpress_response_line($operation['href'] ?? null), $other);
            return null;
    }
    throw new InvalidArgumentException('Unknown response operation.');
}

function wordpress_response_create(string $kind, mixed $data, int $status, array $headers): WP_HTTP_Response {
    if ($kind === 'rest') { return new WP_REST_Response($data, $status, $headers); }
    return new WP_HTTP_Response($data, $status, $headers);
}

function wordpress_responses_text(string $input): string {
    $request = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request) || !str_starts_with(ltrim($input), '{') || !is_string(json_encode($request))) {
        throw new InvalidArgumentException('Response input must be a JSON-encodable object.');
    }
    $request = (array) $request;
    $kind = $request['kind'] ?? 'rest';
    if (!is_string($kind) || !in_array($kind, ['rest', 'http'], true)) { throw new InvalidArgumentException('Unknown response kind.'); }
    if (!isset($request['operations']) || !is_array($request['operations']) || !array_is_list((array) $request['operations']) || count((array) $request['operations']) > 256) {
        throw new InvalidArgumentException('Response operations must be a list with at most 256 entries.');
    }
    $data = array_key_exists('data', $request) ? $request['data'] : null;
    wordpress_error_json($data);
    $status = wordpress_response_status($request['status'] ?? 200);
    $headers = wordpress_response_headers($request['headers'] ?? []);
    remove_all_filters('rest_response_link_curies');
    remove_all_filters('wp_error_added');
    remove_all_filters('is_wp_error_instance');
    WordPressErrorTrace::$events = [];
    if (array_key_exists('curies', $request)) {
        $extra = $request['curies'];
        if (!is_array($extra) || !array_is_list((array) $extra) || count((array) $extra) > 256) { throw new InvalidArgumentException('CURIEs must be a list with at most 256 records.'); }
        foreach ((array) $extra as $curie) {
            if (!is_array($curie)) { throw new InvalidArgumentException('Each CURIE must be an object.'); }
            wordpress_error_json($curie);
            wordpress_error_string($curie['name'] ?? null);
            wordpress_error_string($curie['href'] ?? null);
            if (array_key_exists('templated', (array) $curie) && !is_bool($curie['templated'])) { throw new InvalidArgumentException('CURIE templated must be boolean.'); }
        }
        $extra = (array) $extra;
        add_filter('rest_response_link_curies', static function (array $current) use ($extra): array { return (array) $extra; });
    }
    $response = wordpress_response_create((string) $kind, $data, $status, $headers);
    $results = [];
    foreach ((array) $request['operations'] as $operation) {
        if (!is_array($operation)) { throw new InvalidArgumentException('Each response operation must be an object.'); }
        $results[] = wordpress_responses_operation($response, (array) $operation);
        if (count($response->get_headers()) > 256) { throw new InvalidArgumentException('Response header state exceeds 256 entries.'); }
        if ($response instanceof WP_REST_Response) {
            $count = 0;
            foreach ($response->get_links() as $links) { $count += count((array) $links); }
            if ($count > 1024) { throw new InvalidArgumentException('Response link state exceeds 1024 entries.'); }
        }
    }
    $encoded = json_encode(['results' => $results, 'response' => wordpress_response_snapshot($response)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) { throw new InvalidArgumentException('Response results cannot be encoded as JSON.'); }
    return (string) $encoded;
}
