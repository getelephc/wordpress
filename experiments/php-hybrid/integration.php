<?php
// Loaded identically in every mode. Network effects are disabled for the fixture.
add_filter('pre_wp_mail', static function () { return true; });
add_filter('pre_http_request', static function () { return new WP_Error('bench_offline', 'Benchmark has no external HTTP.'); });
