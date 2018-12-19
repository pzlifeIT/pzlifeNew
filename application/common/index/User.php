<?php

namespace app\common\index;
class User {

    /**
     * 登录
     */
    public function login() {

    }

    /**
     * 注册
     */
    public function register($openId, $password) {
        $pwd = hash_hmac('sha1', $password, 'userpass');
        return $pwd;
    }
}