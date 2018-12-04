<?php

namespace app\index;

use think\App;
use think\Controller;
use Env;
use Config;

class MyController extends Controller {
    public function __construct(App $app = null) {
        parent::__construct($app);
        $checkRes = $this->checkApi();
        if ($checkRes['code'] !== 200) {
            exit(json_encode($checkRes));
//            return $checkRes;
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
//        ksort($params);
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