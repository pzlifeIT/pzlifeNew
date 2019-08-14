<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Admin extends AdminController {
    protected $beforeActionList = [
//        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'login'], //除去login其他方法都进行isLogin前置操作
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
        $apiName  = classBasename($this) . '/' . __function__;
        $adminName = trim($this->request->post('admin_name'));
        $passwd    = trim($this->request->post('passwd'));
        if (empty($adminName) || empty($passwd)) {
            return ['code' => '3001'];
        }
        $result = $this->app->admin->login($adminName, $passwd);
        $this->apiLog($apiName, [$adminName, $passwd], $result['code'], '');
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
    public function getAdminUsers() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->getAdminUsers();
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
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
     * @apiSuccess (返回) {Array} group 所属权限组列表
     * @apiSuccess (返回) {Int} stype 用户类型 1.后台管理员 2.超级管理员
     * @apiSampleRequest /admin/admin/getadmininfo
     * @return array
     * @author zyr
     */
    public function getAdminInfo() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->getAdminInfo($cmsConId);
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
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
     * @apiSuccess (返回) {String} code 200:成功 / 3001:账号不能为空 / 3002:密码必须为6-16个任意字符 / 3003:只有root账户可以添加超级管理员 / 3004:该账号已存在 / 3006:添加失败
     * @apiSampleRequest /admin/admin/addadmin
     * @return array
     * @author zyr
     */
    public function addAdmin() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
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
            return ['code' => '3002']; //密码必须为6-16个任意字符
        }
        $result = $this->app->admin->addAdmin($cmsConId, $adminName, $passwd, $stype);
        $this->apiLog($apiName, [$cmsConId, $adminName, $passwd, $stype], $result['code'], $cmsConId);
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
        $apiName    = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $passwd     = trim($this->request->post('passwd'));
        $newPasswd1 = trim($this->request->post('new_passwd1'));
        $newPasswd2 = trim($this->request->post('new_passwd2'));
        if ($newPasswd1 !== $newPasswd2) {
            return ['code' => '3004']; //密码确认有误
        }
        if (checkCmsPassword($newPasswd1) === false) {
            return ['code' => '3002']; //密码必须为6-16个任意字符
        }
        if (empty($passwd)) {
            return ['code' => '3003']; //老密码不能为空
        }
        $result = $this->app->admin->midifyPasswd($cmsConId, $passwd, $newPasswd1);
        $this->apiLog($apiName, [$cmsConId, $passwd, $newPasswd1], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 商券,佣金,积分手动充值
     * @apiDescription   adminRemittance
     * @apiGroup         admin_admin
     * @apiName          adminRemittance
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} passwd 用户密码
     * @apiParam (入参) {String} stype 添加类型 1.商券 2.佣金 3.积分
     * @apiParam (入参) {String} nick_name 前台用户昵称
     * @apiParam (入参) {String} mobile 前台用户昵称
     * @apiParam (入参) {String} credit 收款金额(充值金额)
     * @apiParam (入参) {String} message 详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3001:密码错误 / 3002:请输入转入类型 / 3003:错误的转账类型 / 3004:充值用户不存在  / 3005:credit必须为数字 / 3006:扣款金额不能大于用户余额(商券) / 3007:充值用户昵称不能为空 / 3008:手机号格式错误
     * @apiSampleRequest /admin/admin/adminRemittance
     * @return array
     * @author rzc
     */
    public function adminRemittance() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $passwd        = trim($this->request->post('passwd'));
        $stype         = trim($this->request->post('stype'));
        $nick_name     = trim($this->request->post('nick_name'));
        $mobile        = trim($this->request->post('mobile'));
        $credit        = trim($this->request->post('credit'));
        $message       = trim($this->request->post('message'));
        $admin_message = trim($this->request->post('admin_message'));
        if (empty($passwd)) {
            return ['code' => '3001'];
        }
        if (empty($stype)) {
            return ['code' => '3002'];
        }
        if (!in_array($stype, [1, 2, 3])) {
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
        $result = $this->app->admin->adminRemittance($cmsConId, $passwd, intval($stype), $nick_name, $mobile, $credit, $message, $admin_message);
        $this->apiLog($apiName, [$cmsConId, $passwd, $stype, $nick_name, $mobile, $credit, $message, $admin_message], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 审核商券,佣金,积分手动充值
     * @apiDescription   auditAdminRemittance
     * @apiGroup         admin_admin
     * @apiName          auditAdminRemittance
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id
     * @apiParam (入参) {String} status 1通过，2不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status必须为数字 / 3003:不存在的记录  / 3004:已审核的状态无法再次审核 / 3005:空的status / 3006:错误的审核类型 / 3001:id必须为数字
     * @apiSampleRequest /admin/admin/auditAdminRemittance
     * @return array
     * @author rzc
     */
    public function auditAdminRemittance() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $status = trim($this->request->post('status'));
        $id     = trim($this->request->post('id'));
        if (!is_numeric($status)) {
            return ['code' => '3001'];
        }
        if (empty($status)) {
            return ['code' => '3005'];
        }
        if (!in_array($status, [1, 2])) {
            return ['code' => '3006'];
        }
        if (!is_numeric($id)) {
            return ['code' => '3007'];
        }
        $result = $this->app->admin->auditAdminRemittance($cmsConId, intval($status), intval($id));
        $this->apiLog($apiName, [$cmsConId, $status, $id], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 开通boss
     * @apiDescription   openBoss
     * @apiGroup         admin_admin
     * @apiName          openBoss
     * @apiParam (入参) {String} cms_con_id 操作管理员
     * @apiParam (入参) {String} mobile 开通账号手机号
     * @apiParam (入参) {String} nick_name 开通账号昵称
     * @apiParam (入参) {Decimal} money 开通后扣除金额
     * @apiParam (入参) {String} [message] 开通理由
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机格式有误 / 3002:账号昵称不能未空 / 3003:金额必须为数字 / 3004:扣除金额不能是负数 / 3006:用户不存在 / 3007:该用户已经是boss / 3008:开通失败 / 3009:boss正在申请中
     * @apiSampleRequest /admin/admin/openboss
     * @return array
     * @author zyr
     */
    public function openBoss() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $mobile   = trim($this->request->post('mobile')); //开通账号手机号
        $nickName = trim($this->request->post('nick_name')); //开通账号昵称
        $money    = trim($this->request->post('money')); //开通后扣除金额
        $message  = trim($this->request->post('message')); //开通描述
        if (!is_numeric($money)) {
            return ['code' => '3003']; //金额必须为数字
        }
        $money = doubleval($money);
        if ($money < 0) {
            return ['code' => '3004']; //扣除金额不能是负数
        }
        if (!checkMobile($mobile)) {
            return ['code' => '3001']; //手机格式有误
        }
        if (empty($nickName)) {
            return ['code' => '3002']; //账号昵称不能未空
        }
        $result = $this->app->admin->openBoss($cmsConId, $mobile, $nickName, $money, $message);
        $this->apiLog($apiName, [$cmsConId, $mobile, $nickName, $money, $message], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 开通boss列表
     * @apiDescription   getOpenBossList
     * @apiGroup         admin_admin
     * @apiName          getOpenBossList
     * @apiParam (入参) {String} cms_con_id 操作管理员
     * @apiParam (入参) {String} [mobile] 开通账号手机号
     * @apiParam (入参) {String} [nick_name] 开通账号昵称
     * @apiParam (入参) {Int} [page] 当前页 默认1
     * @apiParam (入参) {Int} [page_num] 每页数量 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机格式有误
     * @apiSuccess (返回) {Int} all_count 总记录数
     * @apiSuccess (返回) {Int} all_page 总页数
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Decimal} money 预扣款金额
     * @apiSuccess (data) {String} nick_name 开通人昵称
     * @apiSuccess (data) {String} mobile 开通人手机号
     * @apiSuccess (data) {String} admin_name 开通管理员
     * @apiSuccess (data) {String} message 描述
     * @apiSampleRequest /admin/admin/getopenbosslist
     * @return array
     * @author zyr
     */
    public function getOpenBossList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $mobile   = trim($this->request->post('mobile')); //开通账号手机号
        $nickName = trim($this->request->post('nick_name')); //开通账号昵称
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('page_num'));
        if (!checkMobile($mobile) && !empty($mobile)) {
            return ['code' => '3001']; //手机格式有误
        }
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        $result  = $this->app->admin->getOpenBossList($cmsConId, $mobile, $nickName, $page, $pageNum);
        $this->apiLog($apiName, [$cmsConId, $mobile, $nickName, $page, $pageNum], $result['code'], $cmsConId);
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
     * @apiParam (入参) {String} [stype] 添加类型 1.商券 2.佣金 3.积分
     * @apiParam (入参) {String} [start_time] 创建起始时间
     * @apiParam (入参) {String} [end_time] 创建中止时间
     * @apiParam (入参) {Number} [page] 当前页 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status必须为数字 / 3002:错误的审核类型 / 3003:start_time时间格式错误  / 3004:end_time时间格式错误 / 3005:收款金额必须为数字
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/getAdminRemittance
     * @return array
     * @author rzc
     */
    public function getAdminRemittance() {
        $apiName           = classBasename($this) . '/' . __function__;
        $cmsConId          = trim($this->request->post('cms_con_id')); //操作管理员
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
            if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $start_time, $parts)) {
                // print_r($parts);die;
                if (checkdate($parts[2], $parts[3], $parts[1]) == false) {
                    return ['code' => '3003'];
                }
            } else {
                return ['code' => '3003'];
            }
        }
        if (!empty($end_time)) {
            if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $end_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3004'];
                }
            } else {
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
        $result = $this->app->admin->getAdminRemittance(intval($page), intval($pageNum), $initiate_admin_id, $audit_admin_id, $status, $min_credit, $max_credit, $uid, $stype, $start_time, $end_time);
        $this->apiLog($apiName, [$cmsConId, $page, $pageNum, $initiate_admin_id, $audit_admin_id, $status, $min_credit, $max_credit, $uid, $stype, $start_time, $end_time], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 获取提现比率
     * @apiDescription   getInvoice
     * @apiGroup         admin_admin
     * @apiName          getInvoice
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:page或者pageNum或者status必须为数字 / 3002:错误的审核类型  / 3003:start_time时间格式错误  / 3004:end_time时间格式错误 / 3005:收款金额必须为数字
     * @apiSuccess (返回) {array} invoice 记录条数
     * @apiSuccess (invoice) {String} has_invoice 有发票比率
     * @apiSuccess (invoice) {String} no_invoice 无发票比率
     * @apiSampleRequest /admin/admin/getInvoice
     * @return array
     * @author rzc
     */
    public function getInvoice() {
        $apiName           = classBasename($this) . '/' . __function__;
        $cmsConId          = trim($this->request->post('cms_con_id')); //操作管理员
        $result = $this->app->admin->getInvoice();
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 编辑提现比率
     * @apiDescription   editInvoice
     * @apiGroup         admin_admin
     * @apiName          editInvoice
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} has_invoice 提供发票
     * @apiParam (入参) {String} no_invoice 不提供发票
     * @apiSuccess (返回) {String} code 200:成功 / 3001:no_invoice或者has_invoice或者status必须为数字 / 3002:比率不能超过100  / 3003:start_time时间格式错误  / 3004:end_time时间格式错误 / 3005:收款金额必须为数字
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/editInvoice
     * @return array
     * @author rzc
     */
    public function editInvoice() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $has_invoice = trim($this->request->post('has_invoice'));
        $no_invoice  = trim($this->request->post('no_invoice'));
        if (!is_numeric($has_invoice) || !is_numeric($no_invoice)) {
            return ['code' => '3001'];
        }
        if ($has_invoice > 100 || $no_invoice > 100) {
            return ['code' => '3002'];
        }
        $result = $this->app->admin->editInvoice($cmsConId, $has_invoice, $no_invoice);
        $this->apiLog($apiName, [$cmsConId, $has_invoice, $no_invoice], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 获取支持银行列表
     * @apiDescription   getAdminBank
     * @apiGroup         admin_admin
     * @apiName          getAdminBank
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [id] 银行ID
     * @apiParam (入参) {String} [abbrev] 银行英文缩写名
     * @apiParam (入参) {String} [bank_name] 银行全称
     * @apiParam (入参) {String} [status] 状态 1.启用 2.停用
     * @apiParam (入参) {Number} [page] 当前页 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:page或者pageNum或者status必须为数字 / 3002:错误的审核类型  / 3003:start_time时间格式错误  / 3004:end_time时间格式错误 / 3005:收款金额必须为数字
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/getAdminBank
     * @return array
     * @author rzc
     */
    public function getAdminBank() {
        $apiName   = classBasename($this) . '/' . __function__;
        $cmsConId  = trim($this->request->post('cms_con_id')); //操作管理员
        $id        = trim(input("post.id"));
        $page      = trim(input("post.page"));
        $pageNum   = trim(input("post.page_num"));
        $abbrev    = trim(input("post.abbrev"));
        $bank_name = trim(input("post.bank_name"));
        $status    = trim(input("post.status"));
        $page      = empty($page) ? 1 : $page;
        $pageNum   = empty($pageNum) ? 10 : $pageNum;
        if (!is_numeric($page)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($pageNum)) {
            return ["code" => '3001'];
        }
        if (!empty($status)) {
            if (!is_numeric($status)) {
                return ['code' => '3001'];
            }
            if (!in_array($status, [1, 2])) {
                return ['code' => '3002'];
            }
        }
        $result = $this->app->admin->getAdminBank($page, $pageNum, $abbrev, $bank_name, $status, $id);
        $this->apiLog($apiName, [$cmsConId, $id, $page, $pageNum, $abbrev, $bank_name, $status, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 添加支持银行
     * @apiDescription   addAdminBank
     * @apiGroup         admin_admin
     * @apiName          addAdminBank
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} abbrev 银行英文缩写名
     * @apiParam (入参) {String} bank_name 银行全称
     * @apiParam (入参) {String} icon_img 图标
     * @apiParam (入参) {String} bg_img 背景图
     * @apiParam (入参) {String} status 状态 1.启用 2.停用(默认停用)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status或者status必须为数字 / 3002:错误的status  / 3003:abbrev和bank_name都不能为空 / 3004:abbrev和bank_name都不能为空重复
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/addAdminBank
     * @return array
     * @author rzc
     */
    public function addAdminBank() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $abbrev    = trim($this->request->post('abbrev'));
        $bank_name = trim($this->request->post('bank_name'));
        $icon_img  = trim($this->request->post('icon_img'));
        $bg_img    = trim($this->request->post('bg_img'));
        $status    = trim($this->request->post('status'));

        $status = $status ? 1 : 2;
        if (!is_numeric($status)) {
            return ['code' => '3001'];
        }
        if (!in_array($status, [1, 2])) {
            return ['code' => '3002'];
        }
        if (empty($abbrev) || empty($bank_name)) {
            return ['code' => '3003'];
        }
        $result = $this->app->admin->addAdminBank($abbrev, $bank_name, $icon_img, $bg_img, $status);
        $this->apiLog($apiName, [$cmsConId, $abbrev, $bank_name, $icon_img, $bg_img, $status], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 修改支持银行
     * @apiDescription   editAdminBank
     * @apiGroup         admin_admin
     * @apiName          editAdminBank
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id
     * @apiParam (入参) {String} abbrev 银行英文缩写名
     * @apiParam (入参) {String} bank_name 银行全称
     * @apiParam (入参) {String} icon_img 图标
     * @apiParam (入参) {String} bg_img 背景图
     * @apiParam (入参) {String} status 状态 1.启用 2.停用(默认停用)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status或者id必须为数字 / 3002:错误的status  / 3003:id不能为空 / 3004:没有更改的资料 / 3005:abbrev和bank_name都不能重复
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/editAdminBank
     * @return array
     * @author rzc
     */

    public function editAdminBank() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id        = trim($this->request->post('id'));
        $abbrev    = trim($this->request->post('abbrev'));
        $bank_name = trim($this->request->post('bank_name'));
        $icon_img  = trim($this->request->post('icon_img'));
        $bg_img    = trim($this->request->post('bg_img'));
        $status    = trim($this->request->post('status'));
        if (empty($id)) {
            return ['code' => '3003'];
        }
        if (!is_numeric($id)) {
            return ['code' => '3001'];
        }
        if (!empty($status)) {
            if (!is_numeric($status)) {
                return ['code' => '3001'];
            }
            if (!in_array($status, [1, 2])) {
                return ['code' => '3002'];
            }
        }
        $result = $this->app->admin->editAdminBank(intval($id), $abbrev, $bank_name, $icon_img, $bg_img, $status);
        $this->apiLog($apiName, [$cmsConId, $id, $abbrev, $bank_name, $icon_img, $bg_img, $status], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 获取提现记录
     * @apiDescription   getLogTransfer
     * @apiGroup         admin_admin
     * @apiName          getLogTransfer
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [id] 提供ID默认查详情
     * @apiParam (入参) {String} [abbrev] 银行英文缩写名
     * @apiParam (入参) {String} [bank_name] 银行全称
     * @apiParam (入参) {Number} [bank_card] 银行卡号
     * @apiParam (入参) {String} [bank_mobile] 银行开户手机号
     * @apiParam (入参) {String} [user_name] 银行开户人
     * @apiParam (入参) {String} [stype] 类型 1.佣金转商券 2.佣金提现 3.奖励金转商券 4. 奖励金提现
     * @apiParam (入参) {String} [wtype] 提现方式 1.银行 2.支付宝 3.微信 4.商券
     * @apiParam (入参) {Number} [status] 状态 1.待处理 2.已完成 3.取消
     * @apiParam (入参) {Number} [invoice] 是否提供发票 1:提供 2:不提供
     * @apiParam (入参) {Number} [min_money] 用户转出最小金额
     * @apiParam (入参) {Number} [max_money] 用户转出最大金额
     * @apiParam (入参) {String} [start_time] 开始时间
     * @apiParam (入参) {String} [end_time] 结束时间
     * @apiParam (入参) {Number} [page] 当前页 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:con_id长度只能是28位 / 3002:con_id不能为空 / 3003:start_time时间格式错误  / 3004:end_time时间格式错误 / 3005:转出金额必须为数字 / 3006:银行卡输入错误 / 3007:查询ID必须为数字 / 3008:page和pageNum必须为数字 / 3009:invoice参数错误 / 3010:wtype参数错误 / 3011:stype参数错误 / 3012:status参数错误
     * apiSuccess (返回) {String} total 记录条数
     * @apiSuccess (返回) {array} log_transfer
     * @apiSuccess (log_transfer) {String} id id
     * @apiSuccess (log_transfer) {String} uid id
     * @apiSuccess (log_transfer) {String} abbrev 银行英文缩写名
     * @apiSuccess (log_transfer) {String} bank_name 银行全称
     * @apiSuccess (log_transfer) {String} bank_card 银行卡号
     * @apiSuccess (log_transfer) {String} bank_add 银行支行
     * @apiSuccess (log_transfer) {String} bank_mobile 银行开户手机号
     * @apiSuccess (log_transfer) {String} user_name 银行开户人
     * @apiSuccess (log_transfer) {String} status 状态 1.待处理 2.已完成 3.取消
     * @apiSuccess (log_transfer) {String} stype 类型 1.佣金转商券 2.佣金提现
     * @apiSuccess (log_transfer) {String} wtype 提现方式 1.银行 2.支付宝 3.微信 4.商券
     * @apiSuccess (log_transfer) {String} money 转出处理金额
     * @apiSuccess (log_transfer) {String} proportion 税率比例
     * @apiSuccess (log_transfer) {String} invoice 是否提供发票 1:提供 2:不提供
     * @apiSuccess (log_transfer) {String} link_mobile 联系人
     * @apiSuccess (log_transfer) {String} message 处理描述
     * @apiSuccess (log_transfer) {String} real_money 实际到账金额
     * @apiSuccess (log_transfer) {String} deduct_money 扣除金额+
     * @apiSampleRequest /admin/admin/getLogTransfer
     * @return array
     * @author rzc
     */
    public function getLogTransfer() {
        $apiName     = classBasename($this) . '/' . __function__;
        $cmsConId    = trim($this->request->post('cms_con_id')); //操作管理员
        $id          = trim(input("post.id"));
        $page        = trim(input("post.page"));
        $pageNum     = trim(input("post.page_num"));
        $abbrev      = trim(input("post.abbrev"));
        $bank_name   = trim(input("post.bank_name"));
        $bank_card   = trim(input("post.bank_card"));
        $bank_mobile = trim(input("post.bank_mobile"));
        $user_name   = trim(input("post.user_name"));
        $stype       = trim(input("post.stype"));
        $wtype       = trim(input("post.wtype"));
        $invoice     = trim(input("post.invoice"));
        $status      = trim(input("post.status"));
        $min_money   = trim(input("post.min_money"));
        $max_money   = trim(input("post.max_money"));
        $start_time  = trim(input("post.start_time"));
        $end_time    = trim(input("post.end_time"));
        $page        = empty($page) ? 1 : $page;
        $pageNum     = empty($pageNum) ? 10 : $pageNum;
        if (!is_numeric($page)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($pageNum)) {
            return ["code" => '3002'];
        }
        if (!empty($bank_card)) {
            if (checkBankCard($bank_card) === false) {
                return ['code' => '3006'];
            }
        }
        if (!empty($start_time)) {
            if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $start_time, $parts)) {
                // print_r($parts);die;
                if (checkdate($parts[2], $parts[3], $parts[1]) == false) {
                    return ['code' => '3003'];
                }
            } else {
                return ['code' => '3003'];
            }
        }
        if (!empty($end_time)) {
            if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $end_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3004'];
                }
            } else {
                return ['code' => '3004'];
            }
        }
        if (!empty($min_money)) {
            if (!is_numeric($min_money)) {
                return ['code' => '3005'];
            }
        }
        if (!empty($max_money)) {
            if (!is_numeric($max_money)) {
                return ['code' => '3005'];
            }
        }
        if (!empty($id)) {
            if (!is_numeric($id)) {
                return ['code' => '3007'];
            }
        }
        if (!is_numeric($page) || !is_numeric($pageNum)) {
            return ['code' => '3008'];
        }
        if (!empty($invoice)) {
            if (!in_array($invoice, [1, 2])) {
                return ['code' => '3009'];
            }
        }
        if (!empty($wtype)) {
            if (!in_array($wtype, [1, 2, 3, 4])) {
                return ['code' => '3010'];
            }
        }
        if (!empty($stype)) {
            if (!in_array($stype, [1, 2, 3, 4])) {
                return ['code' => '3011'];
            }
        }
        if (!empty($status)) {
            if (!in_array($status, [1, 2, 3])) {
                return ['code' => '3012'];
            }
        }
        $result = $this->app->admin->getLogTransfer($bank_card, $abbrev, $bank_mobile, $user_name, $bank_name, $min_money, $max_money, $invoice, $status, $stype, $wtype, $start_time, $end_time, intval($page), intval($pageNum), intval($id));
        $this->apiLog($apiName, [$cmsConId, $id, $page, $pageNum, $pageNum, $abbrev, $bank_name, $bank_card, $bank_mobile, $user_name, $stype, $wtype, $invoice, $status, $min_money, $max_money, $start_time, $end_time], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 审核用户佣金提现
     * @apiDescription   checkUserCommissionTransfer
     * @apiGroup         admin_admin
     * @apiName          checkUserCommissionTransfer
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id
     * @apiParam (入参) {String} message 后台管理员处理回馈留言
     * @apiParam (入参) {String} status 状态 2.已完成 3.取消
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status或者id必须为数字 / 3002:错误的status  / 3003:id不能为空 / 3004:已审核的提现记录无法再次审核 / 3005 错误的请求error_fields / 3006:已审核的银行卡或者用户停用的银行卡无法再次审核 / 3007:审核失败
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/checkUserCommissionTransfer
     * @return array
     * @author rzc
     */
    public function checkUserCommissionTransfer() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id      = trim($this->request->post('id'));
        $status  = trim($this->request->post('status'));
        $message = trim($this->request->post('message'));
        if (empty($id)) {
            return ['code' => '3003'];
        }
        if (empty($status)) {
            return ['code' => '3002'];
        }
        if (!is_numeric($id) || !is_numeric($status)) {
            return ['code' => '3001'];
        }
        $result = $this->app->admin->checkUserTransfer(intval($id), intval($status), $message, 2);
        $this->apiLog($apiName, [$cmsConId, $id, $status, $message, 2], $result['code'], $cmsConId);
        return $result;

    }

    /**
     * @api              {post} / cms 审核用户奖励金提现
     * @apiDescription   checkUserBountyTransfer
     * @apiGroup         admin_admin
     * @apiName          checkUserBountyTransfer
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id
     * @apiParam (入参) {String} message 后台管理员处理回馈留言
     * @apiParam (入参) {String} status 状态 2.已完成 3.取消
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status或者id必须为数字 / 3002:错误的status  / 3003:id不能为空 / 3004:已审核的提现记录无法再次审核 / 3005 错误的请求error_fields / 3006:已审核的银行卡或者用户停用的银行卡无法再次审核 / 3007:审核失败
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/checkUserBountyTransfer
     * @return array
     * @author rzc
     */
    public function checkUserBountyTransfer() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id      = trim($this->request->post('id'));
        $status  = trim($this->request->post('status'));
        $message = trim($this->request->post('message'));
        if (empty($id)) {
            return ['code' => '3003'];
        }
        if (empty($status)) {
            return ['code' => '3002'];
        }
        if (!is_numeric($id) || !is_numeric($status)) {
            return ['code' => '3001'];
        }
        $result = $this->app->admin->checkUserTransfer(intval($id), intval($status), $message, 4);
        $this->apiLog($apiName, [$cmsConId, $id, $status, $message, 4], $result['code'], $cmsConId);
        return $result;

    }

    /**
     * @api              {post} / cms 获取用户提交银行卡信息
     * @apiDescription   getUserBank
     * @apiGroup         admin_admin
     * @apiName          getUserBank
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [id] 传银行卡ID默认查看详情
     * @apiParam (入参) {String} [bank_card] 银行卡号
     * @apiParam (入参) {String} [bank_mobile] 银行开户手机号
     * @apiParam (入参) {String} [user_name] 银行开户人
     * @apiParam (入参) {String} [status]  状态 1.待处理 2.启用(审核通过) 3.停用 4.已处理 5.审核不通过
     * @apiParam (入参) {Number} [page] 当前页 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiSuccess (返回) {String} code 200:成功 / 3001:page或者pageNum或者status必须为数字 / 3002:错误的审核类型  /3003:银行卡号输入错误
     * @apiSuccess (返回) {String} total 记录条数
     * @apiSuccess (返回) {array} userbank
     * @apiSuccess (userbank) {String} id
     * @apiSuccess (userbank) {String} uid 关联uid
     * @apiSuccess (userbank) {String} admin_bank_id 后台银行管理id
     * @apiSuccess (userbank) {String} bank_card 银行卡号
     * @apiSuccess (userbank) {String} bank_add 银行支行
     * @apiSuccess (userbank) {String} bank_mobile  银行开户手机号
     * @apiSuccess (userbank) {String} user_name  银行开户人
     * @apiSuccess (userbank) {String} status  状态 1.待处理 2.启用(审核通过) 3.停用 4.已处理 5.审核不通过
     * @apiSuccess (user_bank[admin_bank]) {string} id
     * @apiSuccess (user_bank[admin_bank]) {string} abbrev  银行英文缩写名
     * @apiSuccess (user_bank[admin_bank]) {string} bank_name 银行全称
     * @apiSuccess (user_bank[admin_bank]) {string} icon_img 图标
     * @apiSuccess (user_bank[admin_bank]) {string} bg_img 背景图
     * @apiSuccess (user_bank[admin_bank]) {string} status 状态 1.启用 2.停用
     * @apiSuccess (user_bank[users]) {string} id 用户id
     * @apiSuccess (user_bank[users]) {string} user_identity 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiSuccess (user_bank[users]) {string} nick_name 用户昵称
     * @apiSuccess (user_bank[users]) {string} avatar 用户头像
     * @apiSuccess (user_bank[users]) {string} mobile 用户注册手机号
     * @apiSampleRequest /admin/admin/getUserBank
     * @return array
     * @author rzc
     */
    public function getUserBank() {
        $apiName     = classBasename($this) . '/' . __function__;
        $cmsConId    = trim($this->request->post('cms_con_id')); //操作管理员
        $id          = trim($this->request->post('id'));
        $bank_card   = trim($this->request->post('bank_card'));
        $bank_mobile = trim($this->request->post('bank_mobile'));
        $user_name   = trim($this->request->post('user_name'));
        $status      = trim($this->request->post('status'));
        $page        = trim($this->request->post('page'));
        $page_num    = trim($this->request->post('page_num'));
        $page        = empty($page) ? 1 : $page;
        $pageNum     = empty($pageNum) ? 10 : $page_num;
        if (!is_numeric($page)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($pageNum)) {
            return ["code" => '3001'];
        }
        if (!empty($status)) {
            if (!is_numeric($status)) {
                return ['code' => '3001'];
            }
            if (!in_array($status, [1, 2, 3, 4, 5])) {
                return ['code' => '3002'];
            }
        }
        if (!empty($bank_card)) {
            if (checkBankCard($bank_card) === false) {
                return ['code' => '3003'];
            }
        }
        if (!empty($id)) {
            if (!is_numeric($id)) {
                return ['code' => '3004'];
            }
        }
        $result = $this->app->admin->getUserBank(intval($id), $bank_card, $bank_mobile, $user_name, $status, intval($page), intval($page_num));
        $this->apiLog($apiName, [$cmsConId, $id, $bank_card, $bank_mobile, $user_name, $status, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms 管理员处理银行卡
     * @apiDescription   checkUserBank
     * @apiGroup         admin_admin
     * @apiName          checkUserBank
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id
     * @apiParam (入参) {String} message 后台管理员处理回馈留言
     * @apiParam (入参) {String} status 状态 2.启用(审核通过)4.已处理 5.审核不通过
     * @apiParam (入参) {String} error_fields 错误字段,用,隔开（例如bank_card,bank_add）  各个字段'bank_card','bank_add','bank_mobile','user_name'
     * @apiSuccess (返回) {String} code 200:成功 / 3001:status或者id必须为数字 / 3002:错误的status  / 3003:id不能为空 / 3004:message不能为空（status传值为5） / 3005 错误的请求error_fields / 3006:已审核的银行卡或者用户停用的银行卡无法再次审核
     * apiSuccess (返回) {String} total 记录条数
     * @apiSampleRequest /admin/admin/checkUserBank
     * @return array
     * @author rzc
     */
    public function checkUserBank() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id           = trim($this->request->post('id'));
        $status       = trim($this->request->post('status'));
        $message      = trim($this->request->post('message'));
        $error_fields = trim($this->request->post('error_fields'));
        if (empty($id)) {
            return ['code' => '3003'];
        }
        if (empty($status)) {
            return ['code' => '3002'];
        }
        if (!in_array($status, [2, 4, 5])) {
            return ['code' => '3002'];
        }
        if ($status == 5) {
            if (empty($message) || empty($error_fields)) {
                return ['code' => '3004'];
            }
        }
        if ($error_fields) {
            $error_fields = preg_replace("/，/", ",", $error_fields);
            $error_fields = strtolower($error_fields);
            $error_fields = explode(',', $error_fields);
            foreach ($error_fields as $error => $fields) {
                if (!in_array($fields, ['bank_card', 'bank_add', 'bank_mobile', 'user_name'])) {
                    return ['code' => '3005'];
                }
            }
            $error_fields = join(',', $error_fields);
        }
        $result = $this->app->admin->checkUserBank($id, $status, $message, $error_fields);
        $this->apiLog($apiName, [$cmsConId, $id, $status, $message, $error_fields], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms左侧菜单
     * @apiDescription   cmsMenu
     * @apiGroup         admin_admin
     * @apiName          cmsMenu
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /admin/admin/cmsmenu
     * @author zyr
     */
    public function cmsMenu() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->cmsMenu($cmsConId);
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / cms菜单详情
     * @apiDescription   cmsMenuOne
     * @apiGroup         admin_admin
     * @apiName          cmsMenuOne
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id 菜单id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.菜单id有误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /admin/admin/cmsmenuone
     * @author zyr
     */
    public function cmsMenuOne() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        if (!is_numeric($id) || $id < 0) {
            return ['code' => '3001'];//菜单id有误
        }
        $result = $this->app->admin->cmsMenuOne($cmsConId, $id);
        $this->apiLog($apiName, [$cmsConId, $id], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改保存cms菜单
     * @apiDescription   editMenu
     * @apiGroup         admin_admin
     * @apiName          editMenu
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id 菜单id
     * @apiParam (入参) {String} name 菜单名称
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.菜单id有误 / 3002:菜单id不存在 / 3003:修改失败
     * @apiSampleRequest /admin/admin/editmenu
     * @author zyr
     */
    public function editMenu() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id   = trim($this->request->post('id'));
        $name = trim($this->request->post('name'));
        if (!is_numeric($id) || $id < 0) {
            return ['code' => '3001'];//菜单id有误
        }
        $result = $this->app->admin->editMenu($cmsConId, $id, $name);
        $this->apiLog($apiName, [$cmsConId, $id, $name], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加权限分组
     * @apiDescription   addPermissionsGroup
     * @apiGroup         admin_admin
     * @apiName          addPermissionsGroup
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} group_name 分组名称
     * @apiParam (入参) {String} content 详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组名称错误 /3005:添加失败
     * @apiSampleRequest /admin/admin/addpermissionsgroup
     * @author zyr
     */
    public function addPermissionsGroup() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupName = trim(($this->request->post('group_name')));
        $content   = trim(($this->request->post('content')));
        if (empty($groupName)) {
            return ['code' => '3001'];
        }
        $result = $this->app->admin->addPermissionsGroup($cmsConId, $groupName, $content);
        $this->apiLog($apiName, [$cmsConId, $groupName, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改权限分组
     * @apiDescription   editPermissionsGroup
     * @apiGroup         admin_admin
     * @apiName          editPermissionsGroup
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 权限分组ID
     * @apiParam (入参) {String} group_name 分组名称
     * @apiParam (入参) {String} content 详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组名称错误 / 3003:修改的用户不存在 / 3004:分组id错误 /3005:修改失败
     * @apiSampleRequest /admin/admin/editpermissionsgroup
     * @author zyr
     */
    public function editPermissionsGroup() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupId   = trim($this->request->post('group_id'));
        $groupName = trim(($this->request->post('group_name')));
        $content   = trim(($this->request->post('content')));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3004'];
        }
        if (empty($groupName)) {
            return ['code' => '3001'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->editPermissionsGroup($cmsConId, $groupId, $groupName, $content);
        $this->apiLog($apiName, [$cmsConId, $groupId, $groupName, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加管理员到权限组
     * @apiDescription   addAdminPermissions
     * @apiGroup         admin_admin
     * @apiName          addAdminPermissions
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 分组id
     * @apiParam (入参) {Int} add_admin_id 添加管理员id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组id错误 / 3003:权限分组不存在 /3004:添加用户不存在 / 3005:管理员id有误 / / 3006:该成员已存在 / 3007:添加失败
     * @apiSampleRequest /admin/admin/addadminpermissions
     * @author zyr
     */
    public function addAdminPermissions() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupId    = trim(($this->request->post('group_id')));
        $addAdminId = trim(($this->request->post('add_admin_id')));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        if (!is_numeric($addAdminId) || $addAdminId < 2) {
            return ['code' => '3005'];
        }
        $groupId    = intval($groupId);
        $addAdminId = intval($addAdminId);
        $result     = $this->app->admin->addAdminPermissions($cmsConId, $groupId, $addAdminId);
        $this->apiLog($apiName, [$cmsConId, $groupId, $addAdminId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加接口权限列表
     * @apiDescription   addPermissionsApi
     * @apiGroup         admin_admin
     * @apiName          addPermissionsApi
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} menu_id 菜单id
     * @apiParam (入参) {String} api_name 接口url
     * @apiParam (入参) {Int} stype 接口curd权限 1.增 2.删 3.改
     * @apiParam (入参) {String} cn_name 权限名称
     * @apiParam (入参) {String} content 权限的详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:菜单id有误 / 3002:接口url不能为空 / 3003:接口权操作类型 /3004:权限名称不能为空 / 3005:接口已存在 / 3006:菜单不存在 / 3007:添加失败
     * @apiSampleRequest /admin/admin/addpermissionsapi
     * @author zyr
     */
    public function addPermissionsApi() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
//        if ($this->checkPermissions($cmsConId, $apiName) === false) { //该接口只有root可以使用,开发特殊接口
//            return ['code' => '3100'];
//        }
        $menuId   = trim($this->request->post('menu_id'));
        $apiUrl   = trim($this->request->post('api_name'));
        $stype    = trim($this->request->post('stype'));
        $cnName   = trim($this->request->post('cn_name'));
        $content  = trim($this->request->post('content'));
        $stypeArr = [1, 2, 3];
        if (!is_numeric($menuId) || $menuId < 1) {
            return ['code' => '3001'];//菜单id有误
        }
        $menuId = intval($menuId);
        if (empty($apiUrl)) {
            return ['code' => '3002'];//接口url不能为空
        }
        if (!in_array($stype, $stypeArr)) {
            return ['code' => '3003'];//接口权操作类型
        }
        if (empty($cnName)) {
            return ['code' => '3004'];//权限名称不能为空
        }
        $content = $content ?? '';
        $result  = $this->app->admin->addPermissionsApi($cmsConId, $menuId, $apiUrl, $stype, $cnName, $content);
        $this->apiLog($apiName, [$cmsConId, $menuId, $apiUrl, $stype, $cnName, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改接口权限名称和详情
     * @apiDescription   editPermissionsApi
     * @apiGroup         admin_admin
     * @apiName          editPermissionsApi
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id
     * @apiParam (入参) {String} cn_name 权限名称
     * @apiParam (入参) {String} content 权限的详细描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:接口id有误 /3004:权限名称不能为空 / 3005:接口不存在 / 3007:修改失败
     * @apiSampleRequest /admin/admin/editpermissionsapi
     * @author zyr
     */
    public function editPermissionsApi() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id      = trim($this->request->post('id'));
        $cnName  = trim($this->request->post('cn_name'));
        $content = trim($this->request->post('content'));
        if (!is_numeric($id) || $id < 1) {
            return ['code' => '3001'];//接口id有误
        }
        $id = intval($id);
        if (empty($cnName)) {
            return ['code' => '3004'];//权限名称不能为空
        }
        $content = $content ?? '';
        $result  = $this->app->admin->editPermissionsApi($cmsConId, $id, $cnName, $content);
        $this->apiLog($apiName, [$cmsConId, $id, $cnName, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 为权限组添加菜单接口
     * @apiDescription   addPermissionsGroupPower
     * @apiGroup         admin_admin
     * @apiName          addPermissionsGroupPower
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 分组id
     * @apiParam (入参) {String} permissions 权限分组:{"1":{"2":1,"3":0},"2":{"4":1,"5":0}}
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组id错误 / 3003:权限分组不存在 / 3004:权限分组不能为空 / 3005:permissions数据有误 / 3006:菜单不存在 / 3007:更改失败
     * @apiSampleRequest /admin/admin/addpermissionsgrouppower
     * @author zyr
     */
    public function addPermissionsGroupPower() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupId     = trim($this->request->post('group_id'));
        $permissions = trim($this->request->post('permissions'));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
//        $permissions = json_encode($arr);
        if (empty($permissions)) {
            return ['code' => '3004'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->addPermissionsGroupPower($cmsConId, $groupId, $permissions);
        $this->apiLog($apiName, [$cmsConId, $groupId, $permissions], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除权限组的成员
     * @apiDescription   delAdminPermissions
     * @apiGroup         admin_admin
     * @apiName          delAdminPermissions
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 分组id
     * @apiParam (入参) {Int} del_admin_id 要删除的admin_id
     * @apiSuccess (返回) {String} code 200:成功  / 3001:分组id错误 / 3003:权限分组不存在 /3004:删除用户不存在 / 3005:管理员id有误 /3006:删除的管理员不存在 / 3007:删除失败
     * @apiSampleRequest /admin/admin/deladminpermissions
     * @author zyr
     */
    public function delAdminPermissions() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $groupId    = trim($this->request->post('group_id'));
        $delAdminId = trim(($this->request->post('del_admin_id')));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        if (!is_numeric($delAdminId) || $delAdminId < 2) {
            return ['code' => '3005'];
        }
        $groupId    = intval($groupId);
        $delAdminId = intval($delAdminId);
        $result     = $this->app->admin->delAdminPermissions($cmsConId, $groupId, $delAdminId);
        $this->apiLog($apiName, [$cmsConId, $groupId, $delAdminId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取权限组下的管理员
     * @apiDescription   getPermissionsGroupAdmin
     * @apiGroup         admin_admin
     * @apiName          getPermissionsGroupAdmin
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 分组id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组id错误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} admin_name 名字
     * @apiSampleRequest /admin/admin/getpermissionsgroupadmin
     * @author zyr
     */
    public function getPermissionsGroupAdmin() {
        $apiName = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $groupId  = trim($this->request->post('group_id'));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->getPermissionsGroupAdmin($cmsConId, $groupId);
        $this->apiLog($apiName, [$cmsConId, $groupId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取用户或所有的权限组列表
     * @apiDescription   getAdminGroup
     * @apiGroup         admin_admin
     * @apiName          getAdminGroup
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} get_admin_id 管理员id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:管理员id有误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} group_name 组名
     * @apiSuccess (返回) {String} content 描述
     * @apiSampleRequest /admin/admin/getadmingroup
     * @author zyr
     */
    public function getAdminGroup() {
        $apiName = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $getAdminId = trim($this->request->post('get_admin_id'));
        if (!is_numeric($getAdminId) || $getAdminId < 2) {
            $getAdminId = 0;
        }
        $getAdminId = intval($getAdminId);
        $result     = $this->app->admin->getAdminGroup($cmsConId, $getAdminId);
        $this->apiLog($apiName, [$cmsConId, $getAdminId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取权限组信息
     * @apiDescription   getGroupInfo
     * @apiGroup         admin_admin
     * @apiName          getGroupInfo
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id 管理员id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:分组id错误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} group_name 组名
     * @apiSuccess (返回) {String} content 描述
     * @apiSampleRequest /admin/admin/getgroupinfo
     * @author zyr
     */
    public function getGroupInfo() {
        $apiName = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $groupId  = trim($this->request->post('group_id'));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->getGroupInfo($cmsConId, $groupId);
        $this->apiLog($apiName, [$cmsConId, $groupId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取权限列表
     * @apiDescription   getPermissionsList
     * @apiGroup         admin_admin
     * @apiName          getPermissionsList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} group_id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:分组id错误
     * @apiSampleRequest /admin/admin/getpermissionslist
     * @author zyr
     */
    public function getPermissionsList() {
        $apiName = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $groupId  = trim($this->request->post('group_id'));
        if (!is_numeric($groupId) || $groupId < 1) {
            return ['code' => '3001'];
        }
        $groupId = intval($groupId);
        $result  = $this->app->admin->getPermissionsList($cmsConId, $groupId);
        $this->apiLog($apiName, [$cmsConId, $groupId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取接口权限列表
     * @apiDescription   getPermissionsApi
     * @apiGroup         admin_admin
     * @apiName          getPermissionsApi
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} group_name 组名
     * @apiSuccess (返回) {Int} menu_id 所属菜单
     * @apiSuccess (返回) {String} stype 权限类型 1.增 2.删 3.改
     * @apiSuccess (返回) {String} cn_name 名称
     * @apiSuccess (返回) {String} content 描述
     * @apiSampleRequest /admin/admin/getpermissionsapi
     * @author zyr
     */
    public function getPermissionsApi() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $result   = $this->app->admin->getPermissionsApi($cmsConId);
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取接口权限详情
     * @apiDescription   getPermissionsApiOne
     * @apiGroup         admin_admin
     * @apiName          getPermissionsApiOne
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:接口id有误
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (返回) {String} group_name 组名
     * @apiSuccess (返回) {Int} menu_id 所属菜单
     * @apiSuccess (返回) {String} stype 权限类型 1.增 2.删 3.改
     * @apiSuccess (返回) {String} cn_name 名称
     * @apiSuccess (返回) {String} content 描述
     * @apiSampleRequest /admin/admin/getpermissionsapione
     * @author zyr
     */
    public function getPermissionsApiOne() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        if (!is_numeric($id) || $id < 0) {
            return ['code' => '3001'];//接口id有误
        }
        $id     = intval($id);
        $result = $this->app->admin->getPermissionsApiOne($cmsConId, $id);
        $this->apiLog($apiName, [$cmsConId, $id], $result['code'], $cmsConId);
        return $result;
    }
}