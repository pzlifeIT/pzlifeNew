<?php

namespace app\wap\controller;

use app\wap\WapController;
use think\App;

/**
 * 短信通知
 */
class Wap extends WapController {
    public function __construct(App $app = null) {
        parent::__construct($app);
    }

    public function test() {
        echo 1;die;
    }

    /**
     * @api              {post} / 微信授权
     * @apiDescription   wxaccredit
     * @apiGroup         wap_wap
     * @apiName          wxaccredit
     * @apiParam (入参) {Number} redirect_uri 授权后重定向的回调链接地址
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 / 3001:redirect_uri跳转路径为空 / 3002:code有误
     * @apiSampleRequest /wap/wap/wxaccredit
     * @author rzc
     */
    public function wxaccredit() {
        // $code         = trim($this->request->post('code'));
        $apiName      = classBasename($this) . '/' . __function__;
        $redirect_uri = trim($this->request->post('redirect_uri'));
        // if (strlen($code) != 32) {
        //     return ['code' => '3002']; //code有误
        // }
        if (empty($redirect_uri)) {
            return ['code' => '3001'];
        }
        $redirect_uri = urlencode($redirect_uri);
        $result       = $this->app->wap->wxaccredit($redirect_uri);
        return $result;
    }

    /**
     * @api              {post} / 微信CODE注册登陆
     * @apiDescription   wxregister
     * @apiGroup         wap_wap
     * @apiName          wxregister
     * @apiParam (入参) {String} code 微信code码
     * @apiParam (入参) {String} mobile 接受验证码的手机号
     * @apiParam (入参) {String} vercode 验证码
     * @apiParam (入参) {String} [buid] 推荐人uid
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机格式有误 / 3002:code码错误 / 3004:验证码格式有误 /3006:验证码错误 / 3007 注册失败 / 3008:手机号已被注册 / 3009:新用户需授权
     * @apiSampleRequest /wap/wap/wxregister
     * @author rzc
     */
    public function wxregister() {
        $apiName = classBasename($this) . '/' . __function__;
        $mobile  = trim($this->request->post('mobile'));
        $code    = trim($this->request->post('code'));
        $vercode = trim($this->request->post('vercode'));
        $buid    = trim($this->request->post('buid'));
        $buid    = empty(deUid($buid)) ? 1 : deUid($buid);
        if (checkMobile($mobile) === false) {
            return ['code' => '3001']; //手机号格式错误
        }
        if (strlen($code) != 32) {
            return ['code' => '3002']; //code有误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }
        $result = $this->app->wap->wxregister($mobile, $vercode, $code, $buid);
        return $result;
    }

    /**
     * @api              {post} / 通过微信code登录
     * @apiDescription   loginUserByWx
     * @apiGroup         wap_wap
     * @apiName          loginUserByWx
     * @apiParam (入参) {String} code 微信code
     * @apiParam (入参) {String} [platform] 1.小程序 2.公众号(默认1)
     * @apiParam (入参) {String} [buid] 推荐人uid
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户或未绑定手机号 / 3001:code码错误 / 3002:没有手机号的老用户 / 3003:登录失败
     * @apiSuccess (data) {String} con_id
     * @apiSampleRequest /wap/wap/loginuserbywx
     * @return array
     * @author zyr
     */
    public function loginUserByWx() {
        $apiName     = classBasename($this) . '/' . __function__;
        $code        = trim($this->request->post('code'));
        $platform    = trim($this->request->post('platform'));
        $buid        = trim($this->request->post('buid'));
        $buid        = empty(deUid($buid)) ? 1 : deUid($buid);
        $platformArr = [1, 2];
        if (strlen($code) != 32) {
            return ['code' => 3001];
        }
        $platform = 2;
        $res      = $this->app->wap->loginUserByWx($code, $platform, $buid);
        $this->apiLog($apiName, [$code, $platform, $buid], $res['code'], '');
        return $res;
    }
}