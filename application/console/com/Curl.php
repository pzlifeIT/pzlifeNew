<?php

namespace app\console\com;
use app\console\Pzlife;
use Env;

class Curl extends Pzlife {
    public function test($url, $params) {
        $paramsArr = explode('/', $params);
        if (isset($paramsArr['sign'])) {
            unset($paramsArr['sign']);
        }
        if (isset($paramsArr['timestamp'])) {
            unset($paramsArr['timestamp']);
        }
        $urlParam = implode('/', $paramsArr);
//        $urlParam = '';
//        foreach ($paramsArr as $k => $v) {
//            if ($k % 2 == 1) {
//                $urlParam .= $v;
//            }
//        }
        $requestUrl = $url . '/' . $urlParam;
        if (Env::get('debug.checkSign')) {
            $requestString = implode('', $paramsArr);
            $paramHash     = hash_hmac('sha1', $requestString, 'pzlife');
            $requestUrl    .= '/' . $paramHash;
        }
        if (Env::get('debug.checkTimestamp')) {
            $requestUrl .= '/' . time();
        }
        // 初始化一个 cURL 对象
        $curl = curl_init();
        // 设置你需要抓取的URL
//        curl_setopt($curl, CURLOPT_URL, 'http://local.pzlife.com/hello/aaa/' . $paramHash);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        // 设置header 响应头是否输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // 1如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 运行cURL，请求网页
        $data = curl_exec($curl);
        // 关闭URL请求
        curl_close($curl);
        // 显示获得的数据
        print_r($data);
        die;
    }

}