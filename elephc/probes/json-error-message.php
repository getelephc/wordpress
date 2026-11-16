<?php

// Runtime diagnostic, outside the verified application profiles.
json_decode((string) ($argv[1] ?? '{'), true);
echo json_encode(['code' => json_last_error(), 'message' => json_last_error_msg()]);
