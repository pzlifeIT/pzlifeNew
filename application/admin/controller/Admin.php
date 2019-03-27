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
     * @api              {post} / 获取后台管理员信息
     * @apiDescription   getAdminUsers
     * @apiGroup         admin_admin
     * @apiName          getAdminUsers
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功  / 5000:请重新登录 2.5001:账号已停用
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (返回) {String} admin_name 管理员名
     * @apiSuccess (返回) {data} stype 用户类型 1.后台管理员 2.超级管理员
     * @apiSampleRequest /admin/admin/getAdminUsers
     * @return array
     * @author rzc
     */
    public function getAdminUsers(){
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->getAdminUsers();
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
            return ['code' => '3002'];//密码必须为6-16个任意字符
        }
        if (empty($passwd)) {
            return ['code' => '3003'];//老密码不能为空
        }
        $result = $this->app->admin->midifyPasswd($cmsConId, $passwd, $newPasswd1);
        return $result;
    }

    /**
     * @api              {post} / cms 商票,佣金,积分手动充值
     * @apiDescription   adminRemittance
     * @apiGroup         admin_admin
     * @apiName          adminRemittance
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} passwd 用户密码
     * @apiParam (入参) {String} stype 添加类型 1.商票 2.佣金 3.积分
     * @apiParam (入参) {String} nick_name 前台用户昵称
     * @apiParam (入参) {String} mobile 前台用户昵称
     * @apiParam (入参) {String} credit 收款金额(充值金额)
     * @apiParam (入参) {String} message 详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3001:密码错误 / 3002:请输入转入类型 / 3003:错误的转账类型 / 3004:充值用户不存在  / 3005:credit必须为数字 / 3006:扣款金额不能大于用户余额(商票) / 3007:充值用户昵称不能为空 / 3008:手机号格式错误
     * @apiSampleRequest /admin/admin/adminRemittance
     * @return array
     * @author rzc
     */
    public function adminRemittance(){
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $passwd     = trim($this->request->post('passwd'));
        $stype      = trim($this->request->post('stype'));
        $nick_name  = trim($this->request->post('nick_name'));
        $mobile     = trim($this->request->post('mobile'));
        $credit     = trim($this->request->post('credit'));
        $message    = trim($this->request->post('message'));
        if (empty($passwd)) {
            return ['code' => '3001'];
        }
        if (empty($stype)) {
            return ['code' => '3002'];
        }
        if (!in_array($stype,[1,2,3])) {
            return ['code' => '3003'];
        }
        if (empty($nick_name)) {
            return ['code' => '3007'];
        }
        if (checkMobile($mobile) == false) {
            return ['code' => '3008'];
        }
        if (!is_numeric($credit)) {
            return ['code' => '3005'];
        }
        // $uid = enUid($uid);
        $result = $this->app->admin->adminRemittance($cmsConId,$passwd,intval($stype),$nick_name,$mobile,$credit,$message);
        return $result;
    }

    /**
     * @api              {post} / cms 审核商票,佣金,积分手动充值
     * @apiDescription   auditAdminRemittance
     * @apiGroup         admin_admin
     * @apiName          auditAdminRemittance
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} status 1通过，2不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status必须为数字 / 3002:该用户没有权限 / 3003:不存在的记录  / 3004:已审核的状态无法再次审核 / 3005:空的status / 3006:错误的审核类型 / 3001:id必须为数字
     * @apiSampleRequest /admin/admin/auditAdminRemittance
     * @return array
     * @author rzc
     */
    public function auditAdminRemittance(){
        $cmsConId = trim($this->request->post('cms_con_id'));
        $status   = trim($this->request->post('status'));
        $id       = trim($this->request->post('id'));
        if (!is_numeric($status)) {
            return ['code' => '3001'];
        }
        if (empty($status)) {
            return ['code' => '3005'];
        }
        if (!in_array($status,[1,2])) {
            return ['code' => '3006'];
        }
        if (!is_numeric($id)) {
            return ['code' => '3007'];
        }
        $result = $this->app->admin->auditAdminRemittance($cmsConId,intval($status),intval($id));
        return $result;
    }

    /**
     * @api              {post} / cms 获取充值记录
     * @apiDescription   getAdminRemittance
     * @apiGroup         admin_admin
     * @apiName          getAdminRemittance
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} [initiate_admin_id] 发起操作人
     * @apiParam (入参) {String} [audit_admin_id] 审核人
     * @apiParam (入参) {String} [status] 1.待审核 2.已审核 3.取消
     * @apiParam (入参) {String} [min_credit] 最小收款金额
     * @apiParam (入参) {String} [max_credit] 最大收款金额
     * @apiParam (入参) {String} [uid] 收款账户id 前台用户加密ID
     * @apiParam (入参) {String} [stype] 添加类型 1.商票 2.佣金 3.积分
     * @apiParam (入参) {String} [start_time] 创建起始时间
     * @apiParam (入参) {String} [end_time] 创建中止时间
     * @apiParam (入参) {Number} [page] 当前页 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status必须为数字 / 3002:错误的审核类型 / 3002:该用户没有权限 / 3003:start_time时间格式错误  / 3004:end_time时间格式错误 / 3005:收款金额必须为数字
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/getAdminRemittance
     * @return array
     * @author rzc
     */
    public function getAdminRemittance(){
        $page              = trim(input("post.page"));
        $pageNum           = trim(input("post.page_num"));
        $initiate_admin_id = trim(input("post.initiate_admin_id"));
        $audit_admin_id    = trim(input("post.audit_admin_id"));
        $status            = trim(input("post.status"));
        $min_credit        = trim(input("post.min_credit"));
        $max_credit        = trim(input("post.max_credit"));
        $uid               = trim(input("post.uid"));
        $stype             = trim(input("post.stype"));
        $start_time        = trim(input("post.start_time"));
        $end_time          = trim(input("post.end_time"));
        $page              = empty($page) ? 1 : $page;
        $pageNum           = empty($pageNum) ? 10 : $pageNum;
        if (!is_numeric($page)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($pageNum)) {
            return ["code" => '3002'];
        }
        if (!empty($start_time)) {
            if (preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $start_time, $parts)){
                // print_r($parts);die;
                if (checkdate($parts[2],$parts[3],$parts[1]) == false) {
                    return ['code' => '3003'];
                }
            }else{
                return ['code' => '3003'];
            }
        }
        if (!empty($end_time)) {
            if (preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $end_time, $parts1)){
                if (checkdate($parts1[2],$parts1[3],$parts1[1]) == false) {
                    return ['code' => '3004'];
                }
            }else{
                return ['code' => '3004'];
            }
        }
        if (!empty($min_credit)) {
            if (!is_numeric($min_credit)) {
                return ['code' => '3005'];
            }
        }
        if (!empty($max_credit)) {
            if (!is_numeric($max_credit)) {
                return ['code' => '3005'];
            }
        }
        $result = $this->app->admin->getAdminRemittance(intval($page), intval($pageNum),$initiate_admin_id,$audit_admin_id,$status,$min_credit,$max_credit,$uid,$stype,$start_time,$end_time);
        return $result;
        
    }
}