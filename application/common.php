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
 * @author zyr
 */
function checkPassword($password) {
    // /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[\s\S]{8,16}$/  至少8-16个字符，至少1个大写字母，1个小写字母和1个数字，其他可以是任意字符：
    if (!empty($password) && preg_match('/^(?=.*[a-zA-Z])(?=.*\d)[\s\S]{6,16}$/', $password)) { //6-16个字符，至少1个字母和1个数字，其他可以是任意字符
        return true;
    }
    return false;
}

/**
 * cms验证密码强度
 * @param $password
 * @return bool
 * @author zyr
 */
function checkCmsPassword($password) {
    if (!empty($password) && preg_match('/^(?=.*)[\s\S]{6,16}$/', $password)) { //6-16个字符,可以是任意字符
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
    $str    = 'AcEgIkMoQs';
    $newuid = strrev($uid);
    $newStr = '';
    for ($i = 0; $i < strlen($newuid); $i++) {
        $newStr .= $str[$newuid[$i]];
    }
    $tit    = getOneNum($newuid);
    $result = $str[getOneNum($tit)] . $newStr;
    return $result;
//    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-256-CBC');
    //    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    //    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    //    if (strlen($uid) > 15) {
    //        return 0;
    //    }
    //    $uid     = intval($uid);
    //    $encrypt = base64_encode(openssl_encrypt($uid, $cryptMethod, $cryptKey, 0, $cryptIv));
    //    return $encrypt;
}

/**
 * @param $enUid
 * @return int|string
 * @author zyr
 */
function deUid($enUid) {
    if (empty($enUid)) {
        return '';
    }
    $str      = 'AcEgIkMoQs';
    $newEnUid = substr($enUid, 1);
    if (empty($newEnUid)) {
        return '';
    }
    $id = '';
    for ($i = 0; $i < strlen($newEnUid); $i++) {
        $f = strpos($str, $newEnUid[$i]);
        if ($f === false) {
            return '';
        }
        $id .= $f;
    }
    if ($str[getOneNum($id)] != substr($enUid, 0, 1)) {
        return '';
    }
    return strrev($id);
//    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-256-CBC');
    //    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    //    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    //    $decrypt     = openssl_decrypt(base64_decode($enUid), $cryptMethod, $cryptKey, 0, $cryptIv);
    //    if ($decrypt) {
    //        return $decrypt;
    //    } else {
    //        return 0;
    //    }
}

/**
 * @param $adminId
 * @return int|string
 * @author zyr
 */
function enAdminId($adminId) {
    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-128-CBC');
    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    if (strlen($adminId) > 15) {
        return 0;
    }
    $adminId = intval($adminId);
    $encrypt = base64_encode(openssl_encrypt($adminId, $cryptMethod, $cryptKey, 0, $cryptIv));
    return $encrypt;
}

/**
 * @param $enAdminId
 * @return int|string
 * @author zyr
 */
function deAdminId($enAdminId) {
    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-128-CBC');
    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    $decrypt     = openssl_decrypt(base64_decode($enAdminId), $cryptMethod, $cryptKey, 0, $cryptIv);
    if ($decrypt) {
        return $decrypt;
    } else {
        return 0;
    }
}

function getOneNum($num) {
    if ($num < 10) {
        return $num;
    }
    $res = 0;
    for ($i = 0; $i < strlen($num); $i++) {
        $res = bcadd($num[$i], $res, 0);
    }
    return getOneNum($res);
}

/**
 * 发送请求
 * @param $requestUrl
 * @param string $method
 * @param $data
 * @return array|mixed
 * @author zyr
 */
function sendRequest($requestUrl, $method = 'get', $data = []) {
    $methonArr = ['get', 'post'];
    if (!in_array(strtolower($method), $methonArr)) {
        return [];
    }
    if ($method == 'post') {
        if (!is_array($data) || empty($data)) {
            return [];
        }
    }
    $curl = curl_init(); // 初始化一个 cURL 对象
    curl_setopt($curl, CURLOPT_URL, $requestUrl); // 设置你需要抓取的URL
    curl_setopt($curl, CURLOPT_HEADER, 0); // 设置header 响应头是否输出
    if ($method == 'post') {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
    // 1如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    $res = curl_exec($curl); // 运行cURL，请求网页
    curl_close($curl); // 关闭URL请求
    return $res; // 显示获得的数据
}

/**
 * 获取所在的项目入口(index,admin)
 * @param $file
 * @return bool|string
 * @author zyr
 */
function controllerBaseName($file) {
    $path  = dirname(dirname($file));
    $index = intval(strrpos($path, '/'));
    return substr($path, bcadd($index, 1, 0));
}

/**
 * 获取不包含命名空间的类名
 * @param $class
 * @return string
 * @author zyr
 */
function classBasename($class) {
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}

/**
 * 创建唯一订单号
 * @param $prefix (1.odr:购买商品订单 2.mem:购买会员订单 3.wpy:微信支付订单号)
 * @return string
 * @author zyr
 */
function createOrderNo($prefix = 'odr') {
    $orderNo = $prefix . date('ymdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    return $orderNo;
}

/**
 * 快递编码公司对应物流
 * @return array
 * @author rzc
 */
function getExpressList() {
    $ExpressList = [
        'shunfeng'       => '顺丰速运',
        'zhongtong'      => '中通快递',
        'shentong'       => '申通快递',
        'yunda'          => '韵达快递',
        'tiantian'       => '天天快递',
        'huitongkuaidi'  => '百世快递',
        'ems'            => 'EMS',
        'youshuwuliu'    => '优速物流',
        'kuayue'         => '跨越速运',
        'debangwuliu'    => '德邦物流',
        'yuantong'       => '圆通速递',
        'jiuyescm'       => '九曳快递',
        'zhaijibian'     => '黑猫宅急便(宅急便)',
        'ane66'          => '安能快递',
        'youzhengguonei' => '中国邮政',
        'rufengda'       => '如风达',
        'wanxiangwuliu'  => '万象物流',
        'SJPS'           => '商家派送',
    ];
    return $ExpressList;
}

/**
 * 检测银行卡号是否合法
 * @return array
 * @author rzc
 */
function checkBankCard($cardNum) {
    $arr_no = str_split($cardNum);
    $last_n = $arr_no[count($arr_no) - 1];
    krsort($arr_no);
    $i     = 1;
    $total = 0;
    foreach ($arr_no as $n) {
        if ($i % 2 == 0) {
            $ix = $n * 2;
            if ($ix >= 10) {
                $nx = 1 + ($ix % 10);
                $total += $nx;
            } else {
                $total += $ix;
            }
        } else {
            $total += $n;
        }
        $i++;
    }
    $total -= $last_n;
    $x = 10 - ($total % 10);

    if ($x == 10) {
        $x = 0;
    }

    if ($x == $last_n) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取银行卡号银行信息
 * @return array
 * @author rzc
 */
function getBancardKey($cardNo) {
    $url = 'https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardNo=';
    $url .= $cardNo;
    $url .= "&cardBinCheck=true";
    $cardmessage = sendRequest($url);
    $cardmessage = json_decode($cardmessage, true);
    // print_r($cardmessage);die;
    if (!isset($cardmessage['bank'])) {
        return false;
    }
    return ['bank' => $cardmessage['bank'], 'cardNo' => $cardNo];
}

/**
 * 校验身份证号码
 * @return array
 * @author rzc
 */
function checkIdcard($idcard) {
    $idcard    = strtoupper($idcard);
    $regx      = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
    $arr_split = array();
    if (!preg_match($regx, $idcard)) {
        return false;
    }

    if (15 == strlen($idcard)) //检查15位
    {
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

        @preg_match($regx, $idcard, $arr_split);
        //检查生日日期是否正确
        $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if (!strtotime($dtm_birth)) {
            return false;
        } else {
            return true;
        }
    } else //检查18位
    {
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";

        @preg_match($regx, $idcard, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];

        if (!strtotime($dtm_birth)) //检查生日日期是否正确
        {
            return false;
        } else {
            //检验18位身份证的校验码是否正确。
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            $arr_ch  = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sign    = 0;

            for ($i = 0; $i < 17; $i++) {
                $b = (int) $idcard{$i};
                $w = $arr_int[$i];
                $sign += $b * $w;
            }

            $n       = $sign % 11;
            $val_num = $arr_ch[$n];

            if ($val_num != substr($idcard, 17, 1)) {
                return false;
            } else {
                return true;
            }
        }
    }
}

