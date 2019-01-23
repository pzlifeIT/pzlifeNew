<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 过滤图片路径的http域名
 * @param $image
 * @return mixed
 * @author zyr
 */
function filtraImage($domain, $image) {
    return str_replace($domain . '/', '', $image);
}

/**
 * 验证手机号
 * @param $mobile
 * @return bool
 * @author zyr
 */
function checkMobile($mobile) {
    if (!empty($mobile) && preg_match('/^1[3-9]{1}\d{9}$/', $mobile)) {
        return true;
    }
    return false;
}

/**
 * 验证验证码格式
 * @param $code
 * @return bool
 * @author zyr
 */
function checkVercode($code) {
    if (!empty($code) && preg_match('/^\d{6}$/', $code)) {
        return true;
    }
    return false;
}

/**
 * 验证密码强度
 * @param $password
 * @return bool
 */
function checkPassword($password) {
    // /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[\s\S]{8,16}$/  至少8-16个字符，至少1个大写字母，1个小写字母和1个数字，其他可以是任意字符：
    if (!empty($password) && preg_match('/^(?=.*[a-zA-Z])(?=.*\d)[\s\S]{6,16}$/', $password)) {//6-16个字符，至少1个字母和1个数字，其他可以是任意字符
        return true;
    }
    return false;
}

/**
 * 获取验证码短信内容
 * @param $code
 * @return string
 * @author zyr
 */
function getVercodeContent($code) {
    return '【品质生活广场】您的验证码是:' . $code . '，在10分钟内有效。如非本人操作请忽略本短信。';
}

/**
 * 随机生成数字字符串
 * @param int $num
 * @return string
 * @author zyr
 */
function randCaptcha($num) {
    $key     = '';
    $pattern = '1234567890';
    for ($i = 0; $i < $num; $i++) {
        $key .= $pattern[mt_rand(0, 9)];
    }
    return $key;
}

/**
 * @param $uid
 * @return int|string
 * @author zyr
 */
function enUid($uid) {
    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-256-CBC');
    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    if (strlen($uid) > 15) {
        return 0;
    }
    $uid     = intval($uid);
    $encrypt = base64_encode(openssl_encrypt($uid, $cryptMethod, $cryptKey, 0, $cryptIv));
    return $encrypt;
}

/**
 * @param $enUid
 * @return int|string
 * @author zyr
 */
function deUid($enUid) {
    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-256-CBC');
    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    $decrypt     = openssl_decrypt(base64_decode($enUid), $cryptMethod, $cryptKey, 0, $cryptIv);
    if ($decrypt) {
        return $decrypt;
    } else {
        return 0;
    }
}
