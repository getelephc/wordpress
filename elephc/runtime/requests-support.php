<?php

final class WordPressRequestContext {
    public static bool $permalinks = false;
}

final class WordPressRequestAdapter extends WP_REST_Request {
    public function parameter_order(): array { return $this->get_parameter_order(); }
    public function check_json(): mixed { return $this->parse_json_params(); }
    public function sources(): array { return $this->params; }
    public function flags(): array { return ['json' => $this->parsed_json, 'body' => $this->parsed_body]; }
}

function wordpress_request_permalinks(): bool { return WordPressRequestContext::$permalinks; }

function wordpress_request_json_error_message(): string {
    $message = (string) json_last_error_msg();
    $position = strpos($message, ' near location ');
    return $position === false ? $message : substr($message, 0, (int) $position);
}

function wordpress_request_map(array $values = []): array {
    $map = (array) json_decode('{}', true);
    foreach ($values as $key => $value) { $map[$key] = $value; }
    return $map;
}

function wordpress_request_groups(): array {
    $groups = wordpress_request_map();
    foreach (['URL', 'GET', 'POST', 'FILES', 'JSON', 'defaults'] as $name) {
        $groups[$name] = $name === 'JSON' ? null : wordpress_request_map();
    }
    return $groups;
}

function wordpress_request_line(mixed $value): string {
    $text = wordpress_error_string($value);
    if (str_contains($text, "\r") || str_contains($text, "\n")) { throw new InvalidArgumentException('Request metadata must exclude line breaks.'); }
    return $text;
}

function wordpress_request_header_key(mixed $value): string {
    $key = wordpress_request_line($value);
    if ($key === '') { throw new InvalidArgumentException('Header names must be nonempty.'); }
    return $key;
}

function wordpress_request_header_value(mixed $value): mixed {
    if (is_string($value)) { return wordpress_request_line($value); }
    if (!is_array($value) || !array_is_list((array) $value) || count((array) $value) > 256) {
        throw new InvalidArgumentException('Request header values must be strings or lists of at most 256 strings.');
    }
    foreach ((array) $value as $item) { wordpress_request_line($item); }
    return $value;
}

function wordpress_request_headers(mixed $value): array {
    if (!is_array($value) || count((array) $value) > 256) { throw new InvalidArgumentException('Request headers must be an array with at most 256 names.'); }
    foreach ((array) $value as $key => $item) {
        wordpress_request_header_key((string) $key);
        wordpress_request_header_value($item);
    }
    return (array) $value;
}

function wordpress_request_params(mixed $value): array {
    if (!is_array($value)) { throw new InvalidArgumentException('Request parameter groups must be arrays.'); }
    wordpress_error_json($value);
    return (array) $value;
}

function wordpress_requests_supported(WordPressRequestAdapter $request): void {
    $body = $request->get_body();
    if ($body === null || $body === '' || $body === '0') { return; }
    $content_type = $request->get_content_type();
    if ($request->get_method() !== 'POST' && ($content_type === null || ($content_type['value'] ?? '') === 'application/x-www-form-urlencoded')) {
        throw new InvalidArgumentException('Raw form body parsing is outside the request profile; supply decoded parameter groups.');
    }
    if ($request->is_json_content_type()) {
        $value = json_decode((string) $body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if ($value !== null && !is_array($value)) { throw new InvalidArgumentException('JSON request bodies must be objects, lists or null.'); }
            if (!is_string(json_encode($value))) { throw new InvalidArgumentException('JSON body data must be encodable.'); }
            wordpress_error_json($value);
        }
    }
}

function wordpress_requests_budget(WordPressRequestAdapter $request): void {
    $headers = $request->get_headers();
    if (count($headers) > 256) { throw new InvalidArgumentException('Request header state exceeds 256 names.'); }
    foreach ($headers as $values) {
        if (count((array) $values) > 256) { throw new InvalidArgumentException('Request header state exceeds 256 values per name.'); }
    }
    foreach ($request->sources() as $values) {
        if (is_array($values) && count((array) $values) > 256) { throw new InvalidArgumentException('Request source state exceeds 256 fields.'); }
    }
}

function wordpress_request_snapshot(WordPressRequestAdapter $request): array {
    $params = $request->get_params();
    $sources = $request->sources();
    return ['method' => $request->get_method(), 'route' => $request->get_route(), 'headers' => $request->get_headers(), 'content_type' => $request->get_content_type(), 'is_json' => $request->is_json_content_type(), 'body' => $request->get_body(), 'params' => $params, 'json' => $sources['JSON'], 'sources' => $sources, 'attributes' => $request->get_attributes(), 'flags' => $request->flags()];
}

function wordpress_requests_operation(WordPressRequestAdapter $request, array $operation): mixed {
    if (!isset($operation['op']) || !is_string($operation['op'])) { throw new InvalidArgumentException('Each request operation needs a string op.'); }
    $op = (string) $operation['op'];
    $value = array_key_exists('value', $operation) ? $operation['value'] : null;
    switch ($op) {
        case 'validate':
            wordpress_request_validation_ready($request);
            return wordpress_request_validation_result($request->has_valid_params());
        case 'sanitize':
            wordpress_request_validation_ready($request);
            return wordpress_request_validation_result($request->sanitize_params());
        case 'callback-events': return WordPressRequestValidationTrace::$events;
        case 'get-method': return $request->get_method();
        case 'set-method': $request->set_method(wordpress_request_line($value)); return null;
        case 'is-method': return $request->is_method(wordpress_request_line($value));
        case 'get-route': return $request->get_route();
        case 'set-route': $request->set_route(wordpress_error_string($value)); return null;
        case 'get-attributes': return $request->get_attributes();
        case 'set-attributes': $request->set_attributes(wordpress_request_params($value)); return null;
        case 'get-headers': return $request->get_headers();
        case 'get-content-type': return $request->get_content_type();
        case 'is-json': return $request->is_json_content_type();
        case 'canonical-header': return WP_REST_Request::canonicalize_header_name(wordpress_request_header_key($operation['key'] ?? null));
        case 'get-header': return $request->get_header(wordpress_request_header_key($operation['key'] ?? null));
        case 'header-values': return $request->get_header_as_array(wordpress_request_header_key($operation['key'] ?? null));
        case 'set-header': $request->set_header(wordpress_request_header_key($operation['key'] ?? null), wordpress_request_header_value($value)); return null;
        case 'add-header': $request->add_header(wordpress_request_header_key($operation['key'] ?? null), wordpress_request_header_value($value)); return null;
        case 'remove-header': $request->remove_header(wordpress_request_header_key($operation['key'] ?? null)); return null;
        case 'set-headers':
            if (array_key_exists('override', $operation) && !is_bool($operation['override'])) { throw new InvalidArgumentException('override must be boolean.'); }
            $request->set_headers(wordpress_request_headers($value), (bool) ($operation['override'] ?? true));
            return null;
        case 'get-body': return $request->get_body();
        case 'set-body': $request->set_body(wordpress_error_string($value)); return null;
        case 'get-json': return $request->get_json_params();
        case 'json-check':
            $parsed = $request->check_json();
            if ($parsed instanceof WP_Error) { return ['error' => wordpress_error_snapshot($parsed)]; }
            return $parsed;
        case 'order': return $request->parameter_order();
        case 'get-params': return $request->get_params();
        case 'get-query': return $request->get_query_params();
        case 'set-query': $request->set_query_params(wordpress_request_params($value)); return null;
        case 'get-url': return $request->get_url_params();
        case 'set-url': $request->set_url_params(wordpress_request_params($value)); return null;
        case 'get-post': return $request->get_body_params();
        case 'set-post': $request->set_body_params(wordpress_request_params($value)); return null;
        case 'get-files': return $request->get_file_params();
        case 'set-files': $request->set_file_params(wordpress_request_params($value)); return null;
        case 'get-defaults': return $request->get_default_params();
        case 'set-defaults': $request->set_default_params(wordpress_request_params($value)); return null;
        case 'peek': return ['sources' => $request->sources(), 'flags' => $request->flags()];
        case 'snapshot': return wordpress_request_snapshot($request);
    }
    if (!in_array($op, ['get-param', 'has-param', 'set-param', 'offset-get', 'offset-exists', 'offset-set', 'offset-unset'], true)) { throw new InvalidArgumentException('Unknown request operation.'); }
    if (!array_key_exists('key', $operation)) { throw new InvalidArgumentException('Missing request parameter key.'); }
    $key = wordpress_error_code($operation['key']);
    if (($op === 'set-param' || $op === 'offset-set') && !array_key_exists('value', $operation)) { throw new InvalidArgumentException('Missing parameter value.'); }
    wordpress_error_json($value);
    switch ($op) {
        case 'get-param': return $request->get_param($key);
        case 'has-param': return $request->has_param($key);
        case 'set-param': $request->set_param($key, $value); return null;
        case 'offset-get': return $request[$key];
        case 'offset-exists': return isset($request[$key]);
        case 'offset-set': $request[$key] = $value; return null;
        case 'offset-unset': unset($request[$key]); return null;
    }
    throw new InvalidArgumentException('Unknown request operation.');
}

function wordpress_requests_text(string $input): string {
    $spec = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($spec) || !str_starts_with(ltrim($input), '{') || !is_string(json_encode($spec))) { throw new InvalidArgumentException('Request input must be a JSON-encodable object.'); }
    $spec = (array) $spec;
    if (!isset($spec['operations']) || !is_array($spec['operations']) || !array_is_list((array) $spec['operations']) || count((array) $spec['operations']) > 256) { throw new InvalidArgumentException('Request operations must be a list with at most 256 entries.'); }
    if (array_key_exists('permalinks', $spec) && !is_bool($spec['permalinks'])) { throw new InvalidArgumentException('permalinks must be boolean.'); }
    wordpress_request_validation_setup();
    WordPressRequestContext::$permalinks = (bool) ($spec['permalinks'] ?? false);
    remove_all_filters('rest_request_parameter_order');
    remove_all_filters('wp_error_added');
    remove_all_filters('is_wp_error_instance');
    if (array_key_exists('order', $spec)) {
        $order = $spec['order'];
        if (!is_array($order) || !array_is_list((array) $order) || count((array) $order) < 1 || count((array) $order) > 6) { throw new InvalidArgumentException('order must contain one to six source labels.'); }
        foreach ((array) $order as $label) {
            if (!is_string($label) || !in_array($label, ['JSON', 'POST', 'GET', 'URL', 'FILES', 'defaults'], true)) { throw new InvalidArgumentException('Unknown request source label.'); }
        }
        $order = (array) $order;
        add_filter('rest_request_parameter_order', static function (array $original, WP_REST_Request $request) use ($order): array { return (array) $order; }, 10, 2);
    }
    $request = new WordPressRequestAdapter(wordpress_request_line($spec['method'] ?? ''), wordpress_error_string($spec['route'] ?? ''), wordpress_request_params($spec['attributes'] ?? []));
    $request->set_headers(wordpress_request_headers($spec['headers'] ?? []));
    foreach (['query', 'url', 'post', 'files', 'defaults'] as $source) {
        if (!array_key_exists($source, $spec)) { continue; }
        $values = wordpress_request_params($spec[$source]);
        switch ($source) {
            case 'query': $request->set_query_params($values); break;
            case 'url': $request->set_url_params($values); break;
            case 'post': $request->set_body_params($values); break;
            case 'files': $request->set_file_params($values); break;
            case 'defaults': $request->set_default_params($values); break;
        }
    }
    if (array_key_exists('body', $spec)) { $request->set_body(wordpress_error_string($spec['body'])); }
    wordpress_request_rules($request, (array) $spec);
    wordpress_requests_supported($request);
    $results = [];
    foreach ((array) $spec['operations'] as $operation) {
        if (!is_array($operation)) { throw new InvalidArgumentException('Each request operation must be an object.'); }
        $results[] = wordpress_requests_operation($request, (array) $operation);
        wordpress_requests_supported($request);
        wordpress_requests_budget($request);
    }
    $encoded = json_encode(['results' => $results, 'request' => wordpress_request_snapshot($request)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) { throw new InvalidArgumentException('Request results cannot be encoded as JSON.'); }
    return (string) $encoded;
}
