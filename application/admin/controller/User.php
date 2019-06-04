<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\AdminController;

class User extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
//        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取会员列表
     * @apiDescription   getUsers
     * @apiGroup         admin_Users
     * @apiName          getUsers
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [mobile] 手机号
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:手机号格式错误 / 3002:页码和查询条数只能是数字
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 用户ID
     * @apiSuccess (data) {String} user_type 用户类型1.普通账户2.总店账户
     * @apiSuccess (data) {String} user_identity 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiSuccess (data) {String} sex 用户性别 1.男 2.女 3.未确认
     * @apiSuccess (data) {String} nick_name 微信昵称
     * @apiSuccess (data) {String} true_name 真实姓名
     * @apiSuccess (data) {String} brithday 生日
     * @apiSuccess (data) {String} avatar 微信头像
     * @apiSuccess (data) {String} mobile 手机号
     * @apiSuccess (data) {String} email email
     * @apiSampleRequest /admin/User/getUsers
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  },
     * ]
     * @author rzc
     */
    public function getUsers() {
        $page    = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));
        $mobile = trim($this->request->post('mobile'));
        if (!empty($mobile)){
            if (checkMobile($mobile) == false) {
                return ['code' =>'3001'];
            }
        }
        $result = $this->app->user->getUsers($page, $pagenum , $mobile);
        return $result;
    }


    /**
     * @api              {post} / boss降级处理
     * @apiDescription   userDemotion
     * @apiGroup         admin_Users
     * @apiName          userDemotion
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} mobile 降级boss的手机号
     * @apiParam (入参) {Int} user_identity 降级后用户身份 1.普通,2.钻石会员
     * @apiParam (入参) {String} content 降级原因描述
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:手机格式有误 / 3002:只能降级为钻石或普通会员 / 3003:只有boss可以降级 / 3004:有未完成订单 / 3006:修改失败
     * @apiSuccess (返回) {Array} order_list 未完成订单列表
     * @apiSuccess (order_list) {String} order_no 订单号
     * @apiSampleRequest /admin/user/userdemotion
     * @author zyr
     */
    public function userDemotion() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $mobile          = trim($this->request->post('mobile'));
        $userIdentity    = trim($this->request->post('user_identity'));
        $content         = trim($this->request->post('content'));
        $userIdentityArr = [1, 2];
        if (!checkMobile($mobile)) {
            return ['code' => '3001'];
        }
        if (!in_array($userIdentity, $userIdentityArr)) {
            return ['code' => '3002'];
        }
        $result = $this->app->user->userDemotion($mobile, $userIdentity, $content);
        $this->apiLog($apiName, [$cmsConId, $mobile, $userIdentity, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / boss降级处理列表
     * @apiDescription   userDemotionList
     * @apiGroup         admin_Users
     * @apiName          userDemotionList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} page
     * @apiParam (入参) {Int} page_num
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSuccess (返回) {Int} uid 降级的uid
     * @apiSuccess (返回) {Int} after_identity 降级后的身份
     * @apiSuccess (返回) {Int} boss_uid 降级后的上级boss
     * @apiSuccess (返回) {Int} content 降级原因描述
     * @apiSuccess (返回) {Array} uid_list 降级前可获取收益的会员列表
     * @apiSuccess (返回) {Array} order_list 降级后未处理订单列表
     * @apiSampleRequest /admin/user/userdemotionlist
     * @author zyr
     */
    public function userDemotionList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('page_num'));
        $page     = is_numeric($page) ? $page : 1;
        $pageNum  = is_numeric($pageNum) ? $pageNum : 10;
        $result   = $this->app->user->userDemotionList($page, $pageNum);
        $this->apiLog($apiName, [$cmsConId, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }
}
