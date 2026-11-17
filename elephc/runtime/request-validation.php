<?php

final class WordPressRequestValidationTrace {
    public static array $events = [];
}

function wordpress_request_callback_record(string $phase, string $rule, mixed $value, WP_REST_Request $request, mixed $key): void {
    $events = WordPressRequestValidationTrace::$events;
    if (count($events) >= 1024) { throw new InvalidArgumentException('At most 1024 request callback events are allowed.'); }
    $events[] = ['phase' => $phase, 'rule' => $rule, 'key' => $key, 'value' => $value, 'method' => $request->get_method(), 'route' => $request->get_route()];
    WordPressRequestValidationTrace::$events = $events;
}

function wordpress_request_callback_error(string $phase, mixed $key): WP_Error {
    $error = new WP_Error('profile_invalid_value', 'Rejected ' . (string) $key . '.', ['status' => 422, 'param' => $key, 'phase' => $phase]);
    $error->add('profile_invalid_value', 'Callback rejected the value.', ['status' => 409, 'param' => $key, 'phase' => $phase]);
    return $error;
}

function wordpress_request_validate_value(string $rule, mixed $value, WP_REST_Request $request, mixed $key): mixed {
    wordpress_request_callback_record('validate', $rule, $value, $request, $key);
    switch ($rule) {
        case 'pass': return true;
        case 'fail': return false;
        case 'error': return wordpress_request_callback_error('validate', $key);
        case 'integer': return rest_is_integer($value);
        case 'boolean': return rest_is_boolean($value);
        case 'string': return is_string($value);
        case 'return-null': return null;
        case 'return-zero': return 0;
    }
    throw new LogicException('Unknown source-defined validator.');
}

function wordpress_request_sanitize_value(string $rule, mixed $value, WP_REST_Request $request, mixed $key): mixed {
    wordpress_request_callback_record('sanitize', $rule, $value, $request, $key);
    switch ($rule) {
        case 'keep': return $value;
        case 'null': return null;
        case 'error': return wordpress_request_callback_error('sanitize', $key);
        case 'boolean': return rest_sanitize_boolean($value);
        case 'trim':
            if (!is_string($value) || str_contains((string) $value, "\0")) { return wordpress_request_callback_error('sanitize', $key); }
            return trim((string) $value);
        case 'upper':
            if (!is_string($value) || str_contains((string) $value, "\0")) { return wordpress_request_callback_error('sanitize', $key); }
            return strtoupper((string) $value);
    }
    throw new LogicException('Unknown source-defined sanitizer.');
}

function wordpress_request_validate_route(string $rule, WP_REST_Request $request): mixed {
    wordpress_request_callback_record('route', $rule, null, $request, null);
    switch ($rule) {
        case 'pass': return true;
        case 'fail': return false;
        case 'error': return new WP_Error('profile_route_invalid', 'Route callback rejected the request.', ['status' => 409, 'route' => $request->get_route()]);
        case 'check-blocked': return $request->get_param('blocked') !== true;
    }
    throw new LogicException('Unknown source-defined route validator.');
}

function wordpress_request_validate_pass(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_validate_value('pass', $value, $request, $key); }
function wordpress_request_validate_fail(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_validate_value('fail', $value, $request, $key); }
function wordpress_request_validate_error(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_validate_value('error', $value, $request, $key); }
function wordpress_request_validate_integer(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_validate_value('integer', $value, $request, $key); }
function wordpress_request_validate_boolean(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_validate_value('boolean', $value, $request, $key); }
function wordpress_request_validate_string(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_validate_value('string', $value, $request, $key); }
function wordpress_request_validate_return_null(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_validate_value('return-null', $value, $request, $key); }
function wordpress_request_validate_return_zero(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_validate_value('return-zero', $value, $request, $key); }
function wordpress_request_sanitize_keep(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_sanitize_value('keep', $value, $request, $key); }
function wordpress_request_sanitize_trim(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_sanitize_value('trim', $value, $request, $key); }
function wordpress_request_sanitize_upper(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_sanitize_value('upper', $value, $request, $key); }
function wordpress_request_sanitize_boolean(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_sanitize_value('boolean', $value, $request, $key); }
function wordpress_request_sanitize_null(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_sanitize_value('null', $value, $request, $key); }
function wordpress_request_sanitize_error(mixed $value, WP_REST_Request $request, mixed $key): mixed { return wordpress_request_sanitize_value('error', $value, $request, $key); }
function wordpress_request_route_pass(WP_REST_Request $request): mixed { return wordpress_request_validate_route('pass', $request); }
function wordpress_request_route_fail(WP_REST_Request $request): mixed { return wordpress_request_validate_route('fail', $request); }
function wordpress_request_route_error(WP_REST_Request $request): mixed { return wordpress_request_validate_route('error', $request); }
function wordpress_request_route_check_blocked(WP_REST_Request $request): mixed { return wordpress_request_validate_route('check-blocked', $request); }

function wordpress_request_callback_names(string $phase): array {
    switch ($phase) {
        case 'validate': return ['pass' => 'wordpress_request_validate_pass', 'fail' => 'wordpress_request_validate_fail', 'error' => 'wordpress_request_validate_error', 'integer' => 'wordpress_request_validate_integer', 'boolean' => 'wordpress_request_validate_boolean', 'string' => 'wordpress_request_validate_string', 'return-null' => 'wordpress_request_validate_return_null', 'return-zero' => 'wordpress_request_validate_return_zero'];
        case 'sanitize': return ['keep' => 'wordpress_request_sanitize_keep', 'trim' => 'wordpress_request_sanitize_trim', 'upper' => 'wordpress_request_sanitize_upper', 'boolean' => 'wordpress_request_sanitize_boolean', 'null' => 'wordpress_request_sanitize_null', 'error' => 'wordpress_request_sanitize_error'];
        case 'route': return ['pass' => 'wordpress_request_route_pass', 'fail' => 'wordpress_request_route_fail', 'error' => 'wordpress_request_route_error', 'check-blocked' => 'wordpress_request_route_check_blocked'];
    }
    throw new LogicException('Unknown callback phase.');
}

function wordpress_request_validation_setup(): void {
    WordPressRequestValidationTrace::$events = [];
    WordPressHookCallbacks::register('wordpress_request_validate_pass', wordpress_request_validate_pass(...));
    WordPressHookCallbacks::register('wordpress_request_validate_fail', wordpress_request_validate_fail(...));
    WordPressHookCallbacks::register('wordpress_request_validate_error', wordpress_request_validate_error(...));
    WordPressHookCallbacks::register('wordpress_request_validate_integer', wordpress_request_validate_integer(...));
    WordPressHookCallbacks::register('wordpress_request_validate_boolean', wordpress_request_validate_boolean(...));
    WordPressHookCallbacks::register('wordpress_request_validate_string', wordpress_request_validate_string(...));
    WordPressHookCallbacks::register('wordpress_request_validate_return_null', wordpress_request_validate_return_null(...));
    WordPressHookCallbacks::register('wordpress_request_validate_return_zero', wordpress_request_validate_return_zero(...));
    WordPressHookCallbacks::register('wordpress_request_sanitize_keep', wordpress_request_sanitize_keep(...));
    WordPressHookCallbacks::register('wordpress_request_sanitize_trim', wordpress_request_sanitize_trim(...));
    WordPressHookCallbacks::register('wordpress_request_sanitize_upper', wordpress_request_sanitize_upper(...));
    WordPressHookCallbacks::register('wordpress_request_sanitize_boolean', wordpress_request_sanitize_boolean(...));
    WordPressHookCallbacks::register('wordpress_request_sanitize_null', wordpress_request_sanitize_null(...));
    WordPressHookCallbacks::register('wordpress_request_sanitize_error', wordpress_request_sanitize_error(...));
    WordPressHookCallbacks::register('wordpress_request_route_pass', wordpress_request_route_pass(...));
    WordPressHookCallbacks::register('wordpress_request_route_fail', wordpress_request_route_fail(...));
    WordPressHookCallbacks::register('wordpress_request_route_error', wordpress_request_route_error(...));
    WordPressHookCallbacks::register('wordpress_request_route_check_blocked', wordpress_request_route_check_blocked(...));
}

function wordpress_request_callback_name(string $phase, mixed $rule): string {
    $names = wordpress_request_callback_names($phase);
    if (!is_string($rule) || !isset($names[(string) $rule])) { throw new InvalidArgumentException('Unknown request callback rule.'); }
    return (string) $names[(string) $rule];
}

function wordpress_request_rules(WordPressRequestAdapter $request, array $spec): void {
    $attributes = $request->get_attributes();
    if (array_key_exists('rules', $spec)) {
        if (!is_array($spec['rules']) || count((array) $spec['rules']) > 64) { throw new InvalidArgumentException('Request rules allow at most 64 fields.'); }
        $args = wordpress_request_map();
        foreach ((array) $spec['rules'] as $key => $rule) {
            wordpress_error_code($key);
            if (!is_array($rule)) { throw new InvalidArgumentException('Each request rule must be an object.'); }
            $arg = wordpress_request_map();
            foreach ((array) $rule as $option => $value) {
                switch ((string) $option) {
                    case 'required':
                        if (!is_bool($value)) { throw new InvalidArgumentException('required must be boolean.'); }
                        $arg['required'] = $value;
                        break;
                    case 'validate':
                    case 'sanitize':
                        if ($value === false || $value === null) { break; }
                        $arg[(string) $option . '_callback'] = wordpress_request_callback_name((string) $option, $value);
                        break;
                    default: throw new InvalidArgumentException('Unknown request rule option.');
                }
            }
            $args[$key] = $arg;
        }
        $attributes['args'] = $args;
    }
    if (array_key_exists('route_validate', $spec)) {
        $attributes['validate_callback'] = $spec['route_validate'] === null ? null : wordpress_request_callback_name('route', $spec['route_validate']);
    }
    $request->set_attributes($attributes);
}

function wordpress_request_callback_allowed(string $phase, mixed $callback): bool {
    return is_string($callback) && in_array($callback, array_values(wordpress_request_callback_names($phase)), true);
}

function wordpress_request_validation_ready(WP_REST_Request $request): void {
    $attributes = $request->get_attributes();
    $args = $attributes['args'] ?? [];
    if (!is_array($args) || count((array) $args) > 64) { throw new InvalidArgumentException('Request callback args allow at most 64 fields.'); }
    foreach ((array) $args as $key => $arg) {
        wordpress_error_code($key);
        if (!is_array($arg)) { throw new InvalidArgumentException('Each callback arg must be an object.'); }
        foreach ((array) $arg as $option => $value) {
            if ($option === 'required') {
                if (!is_bool($value)) { throw new InvalidArgumentException('required must be boolean.'); }
            } elseif ($option === 'validate_callback' || $option === 'sanitize_callback') {
                $phase = $option === 'validate_callback' ? 'validate' : 'sanitize';
                if ($value !== null && $value !== false && !wordpress_request_callback_allowed($phase, $value)) { throw new InvalidArgumentException('Callbacks must belong to the source-defined request manifest.'); }
            } else { throw new InvalidArgumentException('General schema attributes are outside the request callback profile.'); }
        }
    }
    if (isset($attributes['validate_callback']) && !wordpress_request_callback_allowed('route', $attributes['validate_callback'])) { throw new InvalidArgumentException('Route callbacks must belong to the source-defined request manifest.'); }
}

function wordpress_request_validation_result(mixed $value): mixed {
    if ($value instanceof WP_Error) {
        $response = rest_convert_error_to_response($value);
        return ['error' => wordpress_error_snapshot($value), 'response' => ['status' => $response->get_status(), 'data' => $response->get_data()]];
    }
    return $value;
}
