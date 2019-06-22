<?php

namespace app\index\controller;

use app\index\MyController;

/**
 * 短信通知
 */
class Wap extends MyController {
    

    public function test() {
        echo 1;die;
    }
    protected $beforeActionList = [
        //        'isLogin', //所有方法的前置操作
                'isLogin' => ['except' => 'getSupPromote,getJsapiTicket'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
            ];

    /**
     * @api              {get} / 获取推广详情
     * @apiDescription   getSupPromote
     * @apiGroup         index_wap
     * @apiName          getSupPromote
     * @apiParam (入参) {Number} promote_id 活动ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 / 3001:promote_id有误 / 3002:
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} big_image 大图
     * @apiSuccess (data) {String} share_title 微信转发分享标题
     * @apiSuccess (data) {String} share_image 微信转发分享图片
     * @apiSuccess (data) {Int} share_count 需要分享次数
     * @apiSuccess (data) {String} bg_image 分享成功页面图片
     * @apiSampleRequest /index/wap/getSupPromote
     * @author rzc
     */
    public function getSupPromote() {
        $promote_id = trim($this->request->get('promote_id'));
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3001];
        }
        $result = $this->app->wap->getSupPromote($promote_id);
        return $result;
    }

    /**
     * @api              {post} / 活动报名
     * @apiDescription  SupPromoteSignUp
     * @apiGroup         index_wap
     * @apiName          SupPromoteSignUp
     * @apiParam (入参) {Number} promote_id 活动ID
     * @apiParam (入参) {String} con_id 用户登录ID
     * @apiParam (入参) {String} mobile 手机号
     * @apiParam (入参) {String} nick_name 用户昵称
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {Array} data
     * @apiSampleRequest /index/wap/SupPromoteSignUp
     * @author rzc
     */
    public function SupPromoteSignUp() {
        $apiName    = classBasename($this) . '/' . __function__;
        $mobile     = trim($this->request->post('mobile'));
        $nick_name  = trim($this->request->post('nick_name'));
        $promote_id = trim($this->request->post('promote_id'));
        $conId      = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3003];
        }
        if (checkMobile($mobile) === false) {
            return ['code' => '3004']; //手机号格式错误
        }
        if (empty($nick_name)) {
            return ['code' => 3006];
        }
        $result = $this->app->wap->SupPromoteSignUp($conId, $mobile, $nick_name, $promote_id);
        $this->apiLog($apiName, [$conId, $mobile, $nick_name, $promote_id], $result['code'], '');
        return $result;
    }

    /**
     * @api              {get} / 活动分享次数(调用一次视为分享成功一次)
     * @apiDescription  getPromoteShareNum
     * @apiGroup         index_wap
     * @apiName          getPromoteShareNum
     * @apiParam (入参) {Number} promote_id 活动ID
     * @apiParam (入参) {String} con_id 用户登录ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/getPromoteShareNum
     * @author rzc
     */
    public function getPromoteShareNum(){
        $promote_id = trim($this->request->get('promote_id'));
        $conId      = trim($this->request->get('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3003];
        }
        $result = $this->app->wap->getPromoteShareNum($promote_id,$conId);
        return $result;
    }

    /**
     * @api              {get} / 获取 JSAPI 分享签名包
     * @apiDescription  getJsapiTicket
     * @apiGroup         index_wap
     * @apiName          getJsapiTicket
     * @apiParam (入参) {Number} url 分享页面的当前网页的URL (不包含#及其后面部分)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/getJsapiTicket
     * @author rzc
     */
    public function getJsapiTicket(){
        $url = trim($this->request->get('url'));
        $url = urldecode($url);
        $url = str_replace('&amp;','&',$url);
        if (empty($url)) {
            return ['code' => 3001];
        }
        $result = $this->app->wap->getJsapiTicket($url);
        return $result;
    }
}