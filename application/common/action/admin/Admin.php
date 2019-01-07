<?php

namespace app\common\action\admin;

use Config;

class Admin {
    /**
     * @param $str 加密的内容
     * @param $key
     * @return string
     */
    private function getPassword($str, $key) {
        $algo   = Config::get('app.cipher_algo');
        $md5    = hash_hmac('md5', $str, $key);
        $key2   = strrev($key);
        $result = hash_hmac($algo, $md5, $key2);
        return $result;
    }
}