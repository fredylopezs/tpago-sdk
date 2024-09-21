<?php
namespace FMLS\TPago\Utils;

class Auth {
    public static function getBasicAuthHeader($publicKey, $privateKey) {
        return 'Basic ' . base64_encode("apps/$publicKey:$privateKey");
    }
}