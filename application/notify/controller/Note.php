<?php

namespace app\notify\controller;

use app\notify\NotifyController;
use think\App;

/**
 * 短信通知
 */
class Note extends NotifyController {
    public function __construct(App $app = null) {
        parent::__construct($app);
    }

    /**
     * @api              {post} / 短信验证码发送
     * @apiDescription   sendSms
     * @apiGroup         notify_note
     * @apiName          sendSms
     * @apiParam (入参) {Number} phone 手机号
     * @apiParam (入参) {Number} code 验证码
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 / 3001:手机号格式有误
     * @apiSampleRequest /notify/note/sendSms
     * @author zyr
     */
    public function sendSms() {
        $phone = trim($this->request->post('phone'));//手机号
        if (!$this->checkMobile($phone)) {
            return ['code' => 3001];
        }
        $code   = trim($this->request->post('code'));//验证码
        $result = $this->app->note->sendSms($phone, $code);
        return $result;
    }

    /**
     * @api              {post} / 查询短信记录
     * @apiDescription   getSms
     * @apiGroup         notify_note
     * @apiName          getSms
     * @apiParam (入参) {Number} phone 手机号
     * @apiParam (入参) {String} date 日期Ymd
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式有误 / 3002:日期不符合规范
     * @apiSuccess (返回) {json} data
     * @apiSampleRequest /notify/note/getSms
     * @author zyr
     */
    public function getSms() {
        $phone = trim($this->request->post('phone'));//手机号
        if (!$this->checkMobile($phone)) {
            return ['code' => 3001];
        }
        $date = trim($this->request->post('date'));//Ymd
        if ($date > date('Ymd')) {
            return ['code' => 3002];
        }
        $result = $this->app->note->getSms($phone, $date);
        return $result;
    }
}