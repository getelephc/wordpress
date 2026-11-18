<?php
$out = array();
foreach (array('', '00616263', '6369616f', 'c3a8', 'f09f918b', 'efbfbf', 'efbfbe', 'edbfbf', 'ff', 'c0af', 'e282', 'f4908080', '6162ff6364', 'ed9fbf', 'f48fbfbd') as $hex) {
    $bytes = hex2bin($hex);
    $out[] = array(
        _wp_is_valid_utf8_fallback($bytes),
        bin2hex(_wp_scrub_utf8_fallback($bytes)),
        _wp_utf8_codepoint_count($bytes),
        _wp_has_noncharacters_fallback($bytes),
        bin2hex(_wp_utf8_encode_fallback($bytes)),
        bin2hex(_wp_utf8_decode_fallback($bytes)),
    );
}
echo json_encode($out), "\n";
