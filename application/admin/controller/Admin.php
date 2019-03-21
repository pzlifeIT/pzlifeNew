<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Admin extends AdminController {
    protected $beforeActionList = [
//        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 后台登录
     * @apiDescription   login
     * @apiGroup         admin_admin
     * @apiName          login
     * @apiParam (入参) {String} admin_name
     * @apiParam (入参) {String} passwd 密码
     * @apiSuccess (返回) {String} code 200:成功 / 3001:账号密码不能为空 / 3002:用户不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSampleRequest /admin/admin/login
     * @return array
     * @author zyr
     */
    public function login() {
        $adminName = trim($this->request->post('admin_name'));
        $passwd    = trim($this->request->post('passwd'));
        if (empty($adminName) || empty($passwd)) {
            return ['code' => '3001'];
        }
        $result = $this->app->admin->login($adminName, $passwd);
        return $result;
    }

    /**
     * @api              {post} / 获取登录用户信息
     * @apiDescription   getAdminInfo
     * @apiGroup         admin_admin
     * @apiName          getAdminInfo
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功  / 5000:请重新登录 2.5001:账号已停用
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (返回) {String} admin_name 管理员名
     * @apiSuccess (返回) {Int} stype 用户类型 1.后台管理员 2.超级管理员
     * @apiSampleRequest /admin/admin/getadmininfo
     * @return array
     * @author zyr
     */
    public function getAdminInfo() {
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->getAdminInfo($cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加后台管理员
     * @apiDescription   addAdmin
     * @apiGroup         admin_admin
     * @apiName          addAdmin
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} admin_name 添加的用户名
     * @apiParam (入参) {String} [passwd] 默认为:123456
     * @apiParam (入参) {Int} [stype] 添加的管理员类型 1.管理员 2超级管理员  默认为:1
     * @apiSuccess (返回) {String} code 200:成功 / 3001:账号不能为空 / 3002:密码必须为6-16个任意字符 / 3003:只有root账户可以添加超级管理员 / 3004:该账号已存在 / 3005:没有操作权限 / 3006:添加失败
     * @apiSampleRequest /admin/admin/addadmin
     * @return array
     * @author zyr
     */
    public function addAdmin() {
        $cmsConId  = trim($this->request->post('cms_con_id'));
        $adminName = trim($this->request->post('admin_name'));
        $passwd    = trim($this->request->post('passwd'));
        $stype     = trim($this->request->post('stype'));
        $stypeArr  = [1, 2];
        if (empty($adminName)) {
            return ['code' => '3001'];
        }
        if (!in_array($stype, $stypeArr)) {
            $stype = 1;
        }
        $passwd = $passwd ?: '123456';
        if (checkCmsPassword($passwd) === false) {
            return ['code' => '3002'];//密码必须为6-16个任意字符
        }
        $result = $this->app->admin->addAdmin($cmsConId, $adminName, $passwd, $stype);
        return $result;
    }

    /**
     * @api              {post} / 修改密码
     * @apiDescription   midifyPasswd
     * @apiGroup         admin_admin
     * @apiName          midifyPasswd
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} passwd 用户密码
     * @apiParam (入参) {String} new_passwd1 新密码
     * @apiParam (入参) {String} new_passwd2 确认密码
     * @apiParam (入参) {Int} [stype] 添加的管理员类型 1.管理员 2超级管理员  默认为:1
     * @apiSuccess (返回) {String} code 200:成功 / 3001:密码错误 / 3002:密码必须为6-16个任意字符 / 3003:老密码不能为空 / 3004:密码确认有误  / 3005:修改密码失败
     * @apiSampleRequest /admin/admin/midifypasswd
     * @return array
     * @author zyr
     */
    public function midifyPasswd() {
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $passwd     = trim($this->request->post('passwd'));
        $newPasswd1 = trim($this->request->post('new_passwd1'));
        $newPasswd2 = trim($this->request->post('new_passwd2'));
        if ($newPasswd1 !== $newPasswd2) {
            return ['code' => '3004'];//密码确认有误
        }
        if (checkCmsPassword($newPasswd1) === false) {
            return ['code' => '3007'];//密码必须为6-16个任意字符
        }
        if (empty($passwd)) {
            return ['code' => '3003'];//老密码不能为空
        }
        $result = $this->app->admin->midifyPasswd($cmsConId, $passwd, $newPasswd1);
        return $result;
    }
}