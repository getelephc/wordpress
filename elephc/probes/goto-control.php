<?php

// Standalone control flow only; this does not bootstrap WordPress.
function wordpress_probe_goto_scan(string $bytes): bool {
    for ($i = 0; $i < strlen($bytes); ++$i) {
        if (ord($bytes[$i]) > 127) { goto invalid_utf8; }
    }
    return true;

    invalid_utf8:
    return false;
}

function wordpress_probe_goto_flush(string $input): string {
    $result = '';
    for ($i = 0; $i < strlen($input); ++$i) {
        $next = $input[$i];
        if ($next === 'x') { goto flush_sub_part; }
        $next = strtoupper($next);
        flush_sub_part:
        $result .= $next;
    }
    return $result;
}

echo json_encode([wordpress_probe_goto_scan('abc'), wordpress_probe_goto_scan("a\x80"), wordpress_probe_goto_flush('axb')]);
