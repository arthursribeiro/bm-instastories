<?php

$cipher_key = "x1Fx9EzxACxA1npbxAFnxE86x17~x1Cx";
$cipher_iv = "x03x00ZxA9x91ixB";

function decryptBase64EncryptedPassword($base64EncodedEncryptedPassword) {
    global $cipher_key, $cipher_iv;
    $encryted_password = base64_decode(stripcslashes($base64EncodedEncryptedPassword));
    $password = openssl_decrypt($encryted_password, 'AES-128-CBC', $cipher_key, OPENSSL_RAW_DATA, $cipher_iv);

    return $password;
}

?>