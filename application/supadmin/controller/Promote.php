<?php

namespace app\supadmin\controller;

use app\supadmin\SupAdminController;

class Promote extends SupAdminController {
    protected $beforeActionList = [
//        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'login'], //除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 推广活动报名列表
     * @apiDescription   getSupPromoteSignUp
     * @apiGroup         supadmin_promote
     * @apiName          getSupPromoteSignUp
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {String} promote_id 活动ID
     * @apiParam (入参) {String} page 页数
     * @apiParam (入参) {String} [page_num] 每页条数(默认10)
     * @apiParam (入参) {String} [nick_name] 姓名
     * @apiParam (入参) {String} [mobile] 每页条数(默认10)
     * @apiParam (入参) {String} [start_time] 开始时间
     * @apiParam (入参) {String} [end_time] 结束时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:列表为空 / 3001:page错误 / 3002:promote_id错误 / 3003:时间格式错误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Int} id
     * @apiSuccess (data) {String} nick_name 姓名
     * @apiSuccess (data) {String} mobile 手机号
     * @apiSuccess (data) {String} create_time 报名时间
     * @apiSampleRequest /supadmin/promote/getSupPromoteSignUp
     * @return array
     * @author rzc
     */
    public function getSupPromoteSignUp() {
        $apiName    = classBasename($this) . '/' . __function__;
        $supConId   = trim($this->request->post('sup_con_id'));
        $promote_id = trim($this->request->post('promote_id'));
        $page       = trim($this->request->post('page'));
        $pageNum    = trim($this->request->post('page_num'));
        $nick_name  = trim($this->request->post('nick_name'));
        $mobile     = trim($this->request->post('mobile'));
        $start_time = trim($this->request->post('start_time'));
        $end_time   = trim($this->request->post('end_time'));
        if (!is_numeric($page) || $page < 1) {
            return ['code' => '3001']; //page错误
        }
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 10;
        }
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3002];
        }
        $page    = intval($page);
        $pageNum = intval($pageNum);
        $preg = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)?$/';
        if (!empty($start_time)) {
            if (preg_match($preg, $start_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3003'];
                }
            } else {
                return ['code' => '3003'];
            }
            $start_time = strtotime($start_time);
        }
        if (!empty($end_time)) {
            if (preg_match($preg, $end_time, $parts2)) {
                if (checkdate($parts2[2], $parts2[3], $parts2[1]) == false) {
                    return ['code' => '3003'];
                }
            } else {
                return ['code' => '3003'];
            }
            $end_time = strtotime($end_time);
        }
        $result  = $this->app->promote->getSupPromoteSignUp($promote_id, $page, $pageNum , $nick_name, $mobile, $start_time ,$end_time);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }
}
