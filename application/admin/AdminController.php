<?php

namespace app\admin;

use cache\Phpredis;
use think\App;
use think\Controller;
use Env;
use Config;

class AdminController extends Controller {

    public function __construct(App $app = null) {
        parent::__construct($app);
        if (Config::get('app.deploy') == 'development') {
            header('Access-Control-Allow-Origin:*');
            header("Access-Control-Allow-Methods:*");
            header('Access-Control-Allow-Headers:x-requested-with,content-type');
        }
        if (Config::get('deploy') == 'production') {//生产环境
            header('Access-Control-Allow-Origin:*');
        }
        $checkRes = $this->checkApi();
        if ($checkRes['code'] !== 200) {
            exit(json_encode($checkRes));
        }
    }

    /**
     * api验证
     * @return array
     */
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

    /**
     * 接口时间戳验证
     * @param int $timestamp
     * @return bool
     */
    private function checkTimestamp($timestamp = 0) {
        $nowTime  = time();
        $timeDiff = bcsub($nowTime, $timestamp, 0);
        if ($timeDiff > Config::get('conf.timeout') || $timeDiff < 0) {
            return false;
        }
        return true;
    }

    /**
     * 接口签名验证
     * @param $sign
     * @param $params
     * @return bool
     */
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