<?php

namespace app\index\controller;

use app\index\MyController;
use function Qiniu\json_decode;

/**
 * 短信通知
 */
class Wap extends MyController {

    public function test() {
        echo 1;die;
    }
    protected $beforeActionList = [
        //        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'getSupPromote,getJsapiTicket,sendModelMessage'], //除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {get} / 获取推广详情
     * @apiDescription   getSupPromote
     * @apiGroup         index_wap
     * @apiName          getSupPromote
     * @apiParam (入参) {Number} promote_id 活动ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 / 3001:promote_id有误 / 3002:
     * @apiSuccess (返回) {Array} promote 基本属性
     * @apiSuccess (返回) {Array} banner 头部轮播（暂无）
     * @apiSuccess (返回) {Array} detail 详情图片
     * @apiSuccess (promote) {String} title 标题
     * @apiSuccess (promote) {String} big_image 大图
     * @apiSuccess (promote) {String} share_title 微信转发分享标题
     * @apiSuccess (promote) {String} share_image 微信转发分享图片
     * @apiSuccess (promote) {Int} share_count 需要分享次数
     * @apiSuccess (promote) {String} bg_image 分享成功页面图片
     * @apiSuccess (banner) {String} image_path 图片路径
     * @apiSuccess (detail) {String} image_path 标题
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
     * @apiParam (入参) {String} mobile 联系人手机号
     * @apiParam (入参) {String} sex 性别 1 男 2 女
     * @apiParam (入参) {String} age 年龄
     * @apiParam (入参) {String} signinfo 报名内容
     * @apiParam (入参) {String} nick_name 联系人姓名
     * @apiParam (入参) {String} study_name 学员姓名
     * @apiParam (入参) {String} study_mobile 学员手机号
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该姓名已报名参加 / 3006:请填写姓名 / 3007:验证码格式有误 / 3008:验证码错误 / 3009:性别格式不对  / 3010:年龄格式错误 / 3011:signinfo为空 / 3012:study_name为空 / 3013:study_mobile格式错误
     * @apiSuccess (返回) {Array} data
     * @apiSampleRequest /index/wap/SupPromoteSignUp
     * @author rzc
     */
    public function SupPromoteSignUp() {
        $apiName = classBasename($this) . '/' . __function__;
        // $mobile  = trim($this->request->post('mobile'));
        // $vercode = trim($this->request->post('vercode'));
        // $nick_name  = trim($this->request->post('nick_name'));
        $sex          = trim($this->request->post('sex'));
        $age          = trim($this->request->post('age'));
        $signinfo     = trim($this->request->post('signinfo'));
        $promote_id   = trim($this->request->post('promote_id'));
        $conId        = trim($this->request->post('con_id'));
        $study_name   = trim($this->request->post('study_name'));
        $study_mobile = trim($this->request->post('study_mobile'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3003];
        }
        // if (checkMobile($mobile) === false) {
        //     return ['code' => '3004']; //手机号格式错误
        // }
        if (checkMobile($study_mobile) === false) {
            return ['code' => '3013']; //study_mobile手机号格式错误
        }
        // if (checkVercode($vercode) === false) {
        //     return ['code' => '3007'];
        // }
        // if (empty($nick_name)) {
        //     return ['code' => 3006];
        // }
        if (!in_array($sex, [1, 2])) {
            return ['code' => '3009'];
        }

        if (!is_numeric($age)) {
            return ['code' => '3010'];
        }
        $age = intval($age);
        if ($age < 1 || $age > 100) {
            return ['code' => '3010'];
        }
        if (empty($signinfo)) {
            return ['code' => '3011'];
        }
        if (empty($study_name)) {
            return ['code' => '3012'];
        }
        $mobile    = '';
        $nick_name = '';
        $result    = $this->app->wap->SupPromoteSignUp($conId, $mobile, $nick_name, $promote_id, $sex, $age, $signinfo, $study_name, $study_mobile);
        $this->apiLog($apiName, [$conId, $mobile, $nick_name, $promote_id, $sex, $age, $signinfo], $result['code'], '');
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
    public function getPromoteShareNum() {
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
        $result = $this->app->wap->getPromoteShareNum($promote_id, $conId);
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
    public function getJsapiTicket() {
        $url = trim($this->request->get('url'));
        $url = urldecode($url);
        $url = str_replace('&amp;', '&', $url);
        if (empty($url)) {
            return ['code' => 3001];
        }
        $result = $this->app->wap->getJsapiTicket($url);
        return $result;
    }

    /**
     * @api              {post} / 外部公众号发送模板消息
     * @apiDescription  sendModelMessage
     * @apiGroup         index_wap
     * @apiName          sendModelMessage
     * @apiParam (入参) {string} access_token ACCESS_TOKEN
     * @apiParam (入参) {string} template_id 模板消息ID
     * @apiParam (入参) {string} touser 接收者openid
     * @apiParam (入参) {string} [url] 模板跳转链接（海外帐号没有跳转能力）
     * @apiParam (入参) {string} data 模板数据
     * @apiParam (入参) {string} [color] 模板内容字体颜色，不填默认为黑色
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/sendModelMessage
     * @author rzc
     */
    public function sendModelMessage() {
        $access_token = trim($this->request->get('access_token'));
        $template_id  = trim($this->request->get('template_id'));
        $touser       = trim($this->request->get('touser'));
        $url          = trim($this->request->get('url'));
        $data         = trim($this->request->get('data'));
        $color        = trim($this->request->get('color'));
        if (empty($access_token)) {
            return ['code' => '3001','Error' => 'ACCESS_TOKEN is none'];
        }
        if (empty($template_id)) {
            return ['code' => '3002','Error' => 'template_id is none'];
        }
        if (empty($touser)) {
            return ['code' => '3003','Error' => 'touser is none'];
        }
        if (empty($data)) {
            return ['code' => '3004','Error' => 'data is none'];
        }
        $data = json_decode($data,true);
        $result = $this->app->wap->sendModelMessage($access_token, $template_id, $touser, $url, $data, $color);
        return $result;
    }
}