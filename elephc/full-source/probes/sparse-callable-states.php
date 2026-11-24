<?php

function sparse_callable_user(string $text): string {
    return '[' . strrev($text) . ']';
}

function sparse_callable_branch(bool $known, string $runtime): string {
    $callback = 'strtoupper';
    if (!$known) $callback = $runtime;
    return $callback('AbC');
}

function sparse_callable_loop(int $count): string {
    $callback = 'strtoupper';
    $output = '';
    for ($index = 0; $index < $count; ++$index) {
        $output .= $callback('AbC');
        $callback = $index % 2 === 0 ? 'strtolower' : 'strtoupper';
    }
    return $output;
}

function sparse_callable_handler(bool $fail, string $runtime): string {
    $callback = 'strtoupper';
    try {
        if ($fail) throw new RuntimeException('Choose the runtime callable.');
        $callback = 'strtolower';
    } catch (RuntimeException $error) {
        $callback = $runtime;
    }
    return $callback('AbC');
}

function sparse_callable_reference(string $runtime): string {
    $callback = 'strtoupper';
    $replace = function () use (&$callback): void {$callback = 'strtolower';};
    $replace();
    $first = $callback('AbC');
    unset($callback);
    $callback = $runtime;
    return $first . ':' . $callback('AbC');
}

$runtime = $argv[1] ?? 'sparse_callable_user';
foreach (array('strtolower', $runtime) as $name) {
    echo sparse_callable_branch(true, $name), ':', sparse_callable_branch(false, $name), "\n";
    echo sparse_callable_handler(false, $name), ':', sparse_callable_handler(true, $name), "\n";
    echo sparse_callable_reference($name), "\n";
}
echo sparse_callable_loop(0), ':', sparse_callable_loop(1), ':', sparse_callable_loop(5), "\n";
