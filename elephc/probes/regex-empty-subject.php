<?php

// Compile a copy inside a native project with locked PCRE2 support.
// Diagnostic: bypass the empty-subject guards used by the content profile.
$subject = (string) ($argv[1] ?? '');
echo json_encode(preg_replace('/x/', 'y', $subject)) . "\n";
