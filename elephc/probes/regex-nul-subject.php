<?php

// Keep this diagnostic separate from the verified UTF-8 URL profile.
$prefix = (string) ($argv[1] ?? 'http');
$subject = $prefix . chr(0) . 'invalid';
echo json_encode(['length' => strlen($subject), 'matches' => preg_match('/\A[a-zA-Z]+\z/', $subject)]);
