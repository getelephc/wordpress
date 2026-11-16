<?php

require __DIR__ . '/kses-source.php';
if (($argv[1] ?? '') === 'profile') {
    require __DIR__ . '/../elephc/runtime/functions-bootstrap.php';
} else {
    require ABSPATH . WPINC . '/http.php';
}
require __DIR__ . '/../elephc/runtime/urls-support.php';
