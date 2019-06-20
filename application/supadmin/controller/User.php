<?php

namespace app\supadmin\controller;

use app\supadmin\SupAdminController;

class User extends SupAdminController {
    protected $beforeActionList = [
//        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 后台登录
     * @apiDescription   sup_login
     * @apiGroup         supadmin_user
     * @apiName          sup_login
     * @apiParam (入参) {String} sup_name 名称
     * @apiParam (入参) {String} passwd 密码
     * @apiSuccess (返回) {String} code 200:成功 / 3001:账号密码不能为空 / 3002:用户不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSampleRequest /supadmin/user/login
     * @return array
     * @author zyr
     */
    public function login() {
        $apiName = classBasename($this) . '/' . __function__;
        $supName = trim($this->request->post('sup_name'));
        $passwd  = trim($this->request->post('passwd'));
        if (empty($supName) || empty($passwd)) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->login($supName, $passwd);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 添加推广
     * @apiDescription   addPromote
     * @apiGroup         supadmin_user
     * @apiName          addPromote
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} big_image 大图
     * @apiParam (入参) {String} share_title 微信转发分享标题
     * @apiParam (入参) {String} share_image 微信转发分享图片
     * @apiParam (入参) {String} share_count 需要分享次数
     * @apiParam (入参) {String} bg_image 分享成功页面图片
     * @apiSuccess (返回) {String} code 200:成功 / 3001:title不能为空 / 3002:share_title不能为空 / 3003:big_image未上传 / 3004:share_image未上传 / 3005:bg_image未上传 / 3006:big_image图片没有上传过 / 3007:share_image图片没有上传过 / 3008:bg_image图片没有上传过 / 3009:share_count有误 / 3010:添加失败
     * @apiSampleRequest /supadmin/user/addpromote
     * @return array
     * @author zyr
     */
    public function addPromote() {
        $apiName    = classBasename($this) . '/' . __function__;
        $supConId   = trim($this->request->post('sup_con_id'));
        $title      = trim($this->request->post('title'));
        $bigImage   = trim($this->request->post('big_image'));
        $shareTitle = trim($this->request->post('share_title'));
        $shareImage = trim($this->request->post('share_image'));
        $shareCount = trim($this->request->post('share_count'));
        $bgImage    = trim($this->request->post('bg_image'));
        if (empty($title)) {
            return ['code' => '3001'];//title不能为空
        }
        if (empty($shareTitle)) {
            return ['code' => '3002'];//share_title不能为空
        }
        if (empty($bigImage)) {
            return ['code' => '3003'];//big_image未上传
        }
        if (empty($shareImage)) {
            return ['code' => '3004'];//share_image未上传
        }
        if (empty($bgImage)) {
            return ['code' => '3005'];//bg_image未上传
        }
        if (!is_numeric($shareCount) || $shareCount < 0) {
            return ['code' => '3009'];//share_count有误
        }
        $shareCount = intval($shareCount);
        $result     = $this->app->user->addPromote($title, $bigImage, $shareTitle, $shareImage, $shareCount, $bgImage);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 编辑推广
     * @apiDescription   editPromote
     * @apiGroup         supadmin_user
     * @apiName          editPromote
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {Int} id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} big_image 大图
     * @apiParam (入参) {String} share_title 微信转发分享标题
     * @apiParam (入参) {String} share_image 微信转发分享图片
     * @apiParam (入参) {Int} share_count 需要分享次数
     * @apiParam (入参) {String} bg_image 分享成功页面图片
     * @apiSuccess (返回) {String} code 200:成功 /3000:推广活动不存在 / 3001:title不能为空 / 3002:share_title不能为空 / 3006:big_image图片没有上传过 / 3007:share_image图片没有上传过 / 3008:bg_image图片没有上传过 / 3009:share_count有误 / 3010:修改失败
     * @apiSampleRequest /supadmin/user/editpromote
     * @return array
     * @author zyr
     */
    public function editPromote() {
        $apiName    = classBasename($this) . '/' . __function__;
        $supConId   = trim($this->request->post('sup_con_id'));
        $id         = trim($this->request->post('id'));
        $title      = trim($this->request->post('title'));
        $bigImage   = trim($this->request->post('big_image'));
        $shareTitle = trim($this->request->post('share_title'));
        $shareImage = trim($this->request->post('share_image'));
        $shareCount = trim($this->request->post('share_count'));
        $bgImage    = trim($this->request->post('bg_image'));
        if (empty($title)) {
            return ['code' => '3001'];//title不能为空
        }
        if (empty($shareTitle)) {
            return ['code' => '3002'];//share_title不能为空
        }
        if (!is_numeric($shareCount) || $shareCount < 0) {
            return ['code' => '3009'];//share_count有误
        }
        $shareCount = intval($shareCount);
        $result     = $this->app->user->editPromote($id, $title, $bigImage, $shareTitle, $shareImage, $shareCount, $bgImage);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 推广活动列表
     * @apiDescription   getPromoteList
     * @apiGroup         supadmin_user
     * @apiName          getPromoteList
     * @apiParam (入参) {String} sup_con_id
     * @apiParam (入参) {String} page 页数
     * @apiParam (入参) {String} [page_num] 每页条数(默认10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:列表为空 / 3001:page错误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} big_image 大图
     * @apiSuccess (data) {String} share_title 微信转发分享标题
     * @apiSuccess (data) {String} share_image 微信转发分享图片
     * @apiSuccess (data) {Int} share_count 需要分享次数
     * @apiSuccess (data) {String} bg_image 分享成功页面图片
     * @apiSampleRequest /supadmin/user/getpromoteList
     * @return array
     * @author zyr
     */
    public function getPromoteList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $supConId = trim($this->request->post('sup_con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('page_num'));
        if (!is_numeric($page) || $page < 1) {
            return ['code' => '3001'];//page错误
        }
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 10;
        }
        $page    = intval($page);
        $pageNum = intval($pageNum);
        $result  = $this->app->user->getPromoteList($page, $pageNum);
//        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
        return $result;
    }
}
