<?php

// Development helper: edit a generated file, then record its complete diff.
$objectCallbacks = $argc === 3 && $argv[2] === '--object-callbacks';
$htmlOnly = $argc === 3 && $argv[2] === '--html-only';
if (($argc !== 2 && !$objectCallbacks && !$htmlOnly) || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php scripts/lower-wordpress-syntax.php GENERATED_FILE [--object-callbacks|--html-only]\n");
    exit(2);
}
$buildRoot = realpath(__DIR__ . '/../elephc/build/full-source/wordpress');
$target = realpath($argv[1]);
if ($buildRoot === false || !str_starts_with($target, $buildRoot . DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "Only generated full-source WordPress files may be edited.\n");
    exit(2);
}

$tokens = token_get_all(file_get_contents($argv[1]));
$output = '';
$opened = false;
$inStatic = false;
$depth = 0;
foreach ($tokens as $index => $token) {
    $text = is_array($token) ? $token[1] : $token;
    if ($objectCallbacks && $text === '&') {
        $previous = $index - 1;
        $next = $index + 1;
        while ($previous >= 0 && is_array($tokens[$previous]) && $tokens[$previous][0] === T_WHITESPACE) --$previous;
        while (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) ++$next;
        $after = $next + 1;
        while (isset($tokens[$after]) && is_array($tokens[$after]) && $tokens[$after][0] === T_WHITESPACE) ++$after;
        $beforeText = $previous >= 0 ? (is_array($tokens[$previous]) ? $tokens[$previous][1] : $tokens[$previous]) : '';
        if (isset($tokens[$next], $tokens[$after]) && is_array($tokens[$next])
            && $tokens[$next][0] === T_VARIABLE && $tokens[$next][1] === '$this'
            && in_array($beforeText, ['(', ',', '=>', '['], true)
            && in_array($tokens[$after], [',', ')', ']'], true)) {
            continue;
        }
    }
    if (!is_array($token)) {
        if ($inStatic) {
            if (str_contains('([{', $token)) ++$depth;
            if (str_contains(')]}', $token)) --$depth;
            if ($token === ',' && $depth === 0) {
                $output .= '; static ';
                continue;
            }
            if ($token === ';' && $depth === 0) $inStatic = false;
        }
        $output .= $token;
        continue;
    }
    if (!$htmlOnly && $token[0] === T_STATIC) {
        $next = $index + 1;
        while (isset($tokens[$next]) && is_array($tokens[$next])
            && in_array($tokens[$next][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) ++$next;
        $previous = $index - 1;
        while ($previous >= 0 && is_array($tokens[$previous])
            && in_array($tokens[$previous][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) --$previous;
        $propertyModifier = $previous >= 0 && is_array($tokens[$previous])
            && in_array($tokens[$previous][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE], true);
        $inStatic = !$propertyModifier && isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_VARIABLE;
        $depth = 0;
    }
    if ($token[0] === T_OPEN_TAG) {
        if (!$opened) { $output .= $token[1]; $opened = true; }
    } elseif ($token[0] === T_OPEN_TAG_WITH_ECHO) {
        if (!$opened) { $output .= '<?php '; $opened = true; }
        $output .= 'echo ';
    } elseif ($token[0] === T_CLOSE_TAG) {
        $output .= ";\n";
    } elseif ($token[0] === T_INLINE_HTML) {
        if (!$opened) { $output .= '<?php '; $opened = true; }
        $output .= 'echo ' . var_export($token[1], true) . ";\n";
    } else {
        $output .= $token[1];
    }
}
file_put_contents($argv[1], $output);
