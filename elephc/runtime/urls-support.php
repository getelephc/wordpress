<?php

function wordpress_urls_data(array $data, int $depth = 0): void {
    if ($depth > 16) { throw new InvalidArgumentException('URL query nesting exceeds 16 levels.'); }
    if (count($data) > 256) { throw new InvalidArgumentException('At most 256 query fields per level are allowed.'); }
    foreach ($data as $key => $value) {
        if (is_string($key)) { wordpress_kses_attribute_input((string) $key); }
        if (is_array($value)) { wordpress_urls_data((array) $value, $depth + 1); }
        elseif (is_string($value)) { wordpress_kses_attribute_input((string) $value); }
    }
}

function wordpress_urls_text(string $input): string {
    $request = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request) || !str_starts_with(ltrim($input), '{') || !is_string(json_encode($request))) {
        throw new InvalidArgumentException('URL input must be a JSON-encodable object.');
    }
    $request = (array) $request;
    $op = $request['op'] ?? 'display';
    if (!is_string($op) || !in_array($op, ['display', 'raw', 'sanitize', 'parse', 'relative', 'query', 'encoded-query', 'filtered'], true)) {
        throw new InvalidArgumentException('Unknown URL operation.');
    }
    wordpress_kses_setup();
    remove_all_filters('clean_url');
    if ($op === 'query' || $op === 'encoded-query') {
        if (!isset($request['data']) || !is_array($request['data'])) { throw new InvalidArgumentException('Query data must be an array or object.'); }
        $data = (array) $request['data'];
        wordpress_urls_data($data);
        $separator = $request['separator'] ?? '&';
        if (!is_string($separator)) { throw new InvalidArgumentException('Query separator must be a string.'); }
        wordpress_kses_attribute_input((string) $separator);
        $result = $op === 'query' ? build_query($data) : _http_build_query($data, null, (string) $separator, '', true);
    } else {
        if (!isset($request['url']) || !is_string($request['url'])) { throw new InvalidArgumentException('Missing URL string.'); }
        $url = wordpress_kses_attribute_input((string) $request['url']);
        $protocols = null;
        if (array_key_exists('protocols', $request)) {
            if (!is_array($request['protocols']) || !array_is_list((array) $request['protocols'])) {
                throw new InvalidArgumentException('Protocols must be a list of scheme strings.');
            }
            $protocols = (array) $request['protocols'];
            foreach ($protocols as $protocol) {
                if (is_string($protocol)) { wordpress_kses_attribute_input((string) $protocol); }
                if (!is_string($protocol) || preg_match('/\A[a-zA-Z][a-zA-Z0-9+.\-]*\z/', (string) $protocol) !== 1) {
                    throw new InvalidArgumentException('Protocols must be scheme names.');
                }
            }
        }
        $component = $request['component'] ?? -1;
        if (!is_int($component) || $component < -1 || $component > 7) { throw new InvalidArgumentException('URL component must be an integer from -1 to 7.'); }
        $context = $request['context'] ?? 'display';
        if (!is_string($context) || !in_array($context, ['display', 'db'], true)) { throw new InvalidArgumentException('URL context must be display or db.'); }
        switch ((string) $op) {
            case 'display': $result = esc_url($url, $protocols); break;
            case 'raw': $result = esc_url_raw($url, $protocols); break;
            case 'sanitize': $result = sanitize_url($url, $protocols); break;
            case 'parse': $result = wp_parse_url($url, (int) $component); break;
            case 'relative': $result = wp_make_link_relative($url); break;
            case 'filtered':
                add_filter('clean_url', static function (string $safe, string $original, string $where): string {
                    return $where . '|' . $original . '|' . $safe;
                }, 10, 3);
                $result = esc_url($url, $protocols, (string) $context);
                break;
        }
    }
    $encoded = json_encode(['result' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) { throw new InvalidArgumentException('URL results cannot be encoded as JSON.'); }
    return (string) $encoded;
}
