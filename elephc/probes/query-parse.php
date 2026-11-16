<?php

// Compile-only diagnostic, outside the verified native profiles.
$result = [];
parse_str((string) ($argv[1] ?? 'a=1&items[]=x&items[]=y'), $result);
echo json_encode($result);
