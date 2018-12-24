<?php

namespace app\admin;

use cache\Phpredis;
use think\App;
use think\Controller;
use Env;

class AdminController extends Controller {
    protected $redis;
    protected $cryptMethod;
    protected $cryptKey;
    protected $cryptIv;
    protected $iv = '00000000';

    public function __construct(App $app = null) {
        parent::__construct($app);
        header('Access-Control-Allow-Origin:*');
        $checkRes = $this->checkApi();
        if ($checkRes['code'] !== 200) {
            exit(json_encode($checkRes));
        }
        $this->redis       = Phpredis::getConn();
        $this->cryptMethod = Env::get('cipher.userAesMethod', 'AES-256-CBC');
        $this->cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
        $this->cryptIv     = Env::get('cipher.userAesIv', '11111111');
    }

    /**
     * @param $uid
     * @param $ex
     * @return int|string
     */
    protected function enUid($uid, $ex = false) {
        if (strlen($uid) > 15) {
            return 0;
        }
        $iv = $this->iv;
        if ($ex !== false) {
            $iv = date('Ymd');
        }
        $uid = intval($uid);
        return $this->encrypt($uid, $iv);
    }

    /**
     * @param $enUid
     * @param bool $ex
     * @return int|string
     */
    protected function deUid($enUid, $ex = false) {
        $iv = $this->iv;
        if ($ex !== false) {
            $iv = date('Ymd');
        }
        return $this->decrypt($enUid, $iv);
    }

    protected function encrypt($str, $iv) {
        $encrypt = base64_encode(openssl_encrypt($str, $this->cryptMethod, $this->cryptKey, 0, $this->cryptIv . $iv));
        return $encrypt;
    }

    protected function decrypt($encrypt, $iv) {
        $decrypt = openssl_decrypt(base64_decode($encrypt), $this->cryptMethod, $this->cryptKey, 0, $this->cryptIv . $iv);
        if ($decrypt) {
            return $decrypt;
        } else {
            return 0;
        }
    }

    private function checkApi() {
        $params = $this->request->param();
        if (Env::get('debug.checkTimestamp')) {
            if (!isset($params['timestamp']) || !$this->checkTimestamp($params['timestamp'])) {
                return ['code' => 2000, 'msg' => '请求超时'];
            }
        }
        if (Env::get('debug.checkSign')) {//签名验证
            if (!isset($params['sign']) || !$this->checkSign($params['sign'], $params)) {
                return ['code' => 2001, 'msg' => '签名错误'];
            }
        }
        return ['code' => 200];
    }

    private function checkTimestamp($timestamp = 0) {
        $nowTime  = time();
        $timeDiff = bcsub($nowTime, $timestamp, 0);
        if ($timeDiff > Config::get('conf.timeout') || $timeDiff < 0) {
            return false;
        }
        return true;
    }

    private function checkSign($sign, $params) {
        unset($params['timestamp']);
        unset($params['sign']);
        $requestString = '';
        foreach ($params as $k => $v) {
            if (!is_array($v)) {
                $requestString .= $k . $v;
            }
        }
        $paramHash = hash_hmac('sha1', $requestString, 'pzlife');
        if ($paramHash === $sign) {
            return true;
        }
        return false;
    }

}