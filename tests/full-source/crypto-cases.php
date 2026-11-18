<?php
$out = array();
foreach (array('', 'WordPress', "A\0B", '👋') as $message) {
    foreach (array('', 'wp_fast_hash_6.8+') as $key) {
        foreach (array(16, 30, 32, 64) as $length) {
            $out[] = bin2hex(sodium_crypto_generichash($message, $key, $length));
        }
    }
    foreach (array(1,3,5,7) as $variant) $out[] = sodium_bin2base64($message, $variant);
}
$keypair = hex2bin('b8512da3546661cb8e81ae0834dca3444166fe2b35b288821732ef692a236c8dc3c490800a4fa9abb5d55cc5578269e889fcb05fe2f1edbb9cd9fdc4a9c2014d');
$out[] = bin2hex(sodium_crypto_box("WordPress\0Elephc", str_repeat('n',24), $keypair));
$signature = hex2bin('77cd5175661beb49ebae0acb2bd51a744ae8c939a049d518e5046a334c9940fb67c10b4eba85cf9c64e78caf6297d41288ae0262f727da5930837056f873d705');
$public = hex2bin('fec1428673f6afe89264b3807344ae8b269d125a503855202687db5a7128579f');
$out[] = sodium_crypto_sign_verify_detached($signature,"WordPress\0Elephc",$public);
$out[] = sodium_crypto_sign_verify_detached($signature,'changed',$public);
echo json_encode($out), "\n";
