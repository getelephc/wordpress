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
// Exercise the complete compat.php PCRE probe, cached results and deprecated values.
$utf8_deprecations = array();
$out[] = array(_wp_can_use_pcre_u(), $utf8_deprecations);
foreach (array(null, false, true, 0, 'ignored') as $deprecated) {
    $out[] = array(_wp_can_use_pcre_u($deprecated), $utf8_deprecations);
}
$out[] = array(_wp_can_use_pcre_u(), $utf8_deprecations);
require __DIR__ . '/clamp-cases.php';
foreach (clamp_cases() as $row) $out[] = $row;
echo json_encode($out), "\n";
