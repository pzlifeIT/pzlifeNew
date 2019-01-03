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
        }
        $checkRes = $this->checkApi();
        if ($checkRes['code'] !== 200) {
            exit(json_encode($checkRes));
        }
    }

    /**
     * 获取所在的项目入口(index,admin)
     * @param $file
     * @return bool|string
     */
    protected function controllerBaseName($file) {
        $path  = dirname(dirname($file));
        $index = intval(strrpos($path, '/'));
        return substr($path, bcadd($index, 1, 0));
    }

    /**
     * 获取不包含命名空间的类名
     * @param $class
     * @return string
     */
    protected function classBasename($class) {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * 发送请求
     * @param $requestUrl
     * @param $data
     * @param string $method
     * @return array|mixed
     */
    protected function sendRequest($requestUrl, $data, $method = 'post') {
        $methonArr = ['get', 'post'];
        if (!in_array(strtolower($method), $methonArr)) {
            return [];
        }
        if (!is_array($data) || empty($data)) {
            return [];
        }
        $curl = curl_init();// 初始化一个 cURL 对象
        curl_setopt($curl, CURLOPT_URL, $requestUrl);// 设置你需要抓取的URL
        curl_setopt($curl, CURLOPT_HEADER, 0);// 设置header 响应头是否输出
        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // 1如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);// 运行cURL，请求网页
        curl_close($curl);// 关闭URL请求
        return $res;// 显示获得的数据
    }

    /**
     * 验证手机格式
     * @param $mobile
     * @return bool
     */
    protected function checkMobile($mobile) {
        if (!empty($mobile) && preg_match('/^1[3-9]{1}\d{9}$/', $mobile)) {
            return true;
        }
        return false;
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