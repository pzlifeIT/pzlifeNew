<?php

namespace app\index\controller;

use app\index\MyController;

class User extends MyController {

    /**
     * @api              {post} / 通过uid获取用户信息
     * @apiDescription   getUser
     * @apiGroup         index_user
     * @apiName          getuser
     * @apiParam (入参) {String} uid 用户加密id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:uid长度只能是32位 / 3002:缺少参数
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSuccess (data) {String} id 用户加密id
     * @apiSuccess (data) {Number} user_type 1.普通账户2.总店账户
     * @apiSuccess (data) {Number} user_identity 1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiSuccess (data) {Number} sex 1.男 2.女 3.未确认
     * @apiSuccess (data) {String} nick_name 微信昵称
     * @apiSuccess (data) {String} true_name 真实姓名
     * @apiSuccess (data) {String} brithday 生日
     * @apiSuccess (data) {String} avatar 微信头像
     * @apiSuccess (data) {String} openid 微信openid
     * @apiSuccess (data) {String} qq qq
     * @apiSuccess (data) {String} idcard 身份证
     * @apiSuccess (data) {String} mobile 手机
     * @apiSuccess (data) {String} email 邮箱
     * @apiSuccess (data) {Date} last_time 最后登录时间
     * @apiSuccess (data) {Date} create_time 注册时间
     * @apiSuccess (data) {Double} balance 商票
     * @apiSuccess (data) {Double} commission 佣金
     * @apiSuccess (data) {Number} integral 剩余积分
     * @apiSampleRequest /index/user/getuser
     * @return array
     * @author zyr
     */
    public function getUser() {
        $paramUid = $this->request->post('uid');
        if (empty($paramUid)) {
            return ['code' => '3002', 'msg' => '缺少参数:uid'];
        }
        $uid = trim($paramUid);
        if (strlen($uid) != 32) {
            return ['code' => 3001];
        }
        $res = $this->app->user->getUser($uid);
        return $res;
    }


    /**
     * @api              {post} / 通过openid获取uid和手机号
     * @apiDescription   loginUserByOpenid
     * @apiGroup         index_user
     * @apiName          loginUserByOpenid
     * @apiParam (入参) {String} openid 微信openid
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数
     * @apiSuccess (data) {String} uid 用户加密id
     * @apiSampleRequest /index/user/loginUserByOpenid
     * @return array
     * @author zyr
     */
    public function loginUserByOpenid() {
        $paramOpenid = $this->request->post('openid');
        if (empty($paramOpenid)) {
            return ['code' => '3002', 'msg' => '缺少参数:openid'];
        }
        $openid = trim($paramOpenid);
        if (strlen($openid) != 28) {
            return ['code' => 3001];
        }
        $res = $this->app->user->loginUserByOpenid($openid);
        return $res;
    }

    /**
     * @api              {post} / 通过uid获取用户添加地址信息
     * @apiDescription   getUserAddress
     * @apiGroup         index_user
     * @apiName          getUserAddress
     * @apiParam (入参) {String} uid 用户加密id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/getUserAddress
     * @return array
     * @author rzc
     */
    public function getUserAddress(){
        $paramUid = $this->request->post('uid');
        if (empty($paramUid)) {
            return ['code' => '3002', 'msg' => '缺少参数:uid'];
        }
        $uid = trim($paramUid);
        if (strlen($uid) != 32) {
            return ['code' => 3001];
        }
        $result = $this->app->user->getUserAddress($uid);
        return $result;
    }

    /**
     * @api              {post} / 用户添加地址信息
     * @apiDescription   addUserAddress
     * @apiGroup         index_user
     * @apiName          addUserAddress
     * @apiParam (入参) {String} uid 用户加密id
     * @apiParam (入参) {Number} province_id 省id
     * @apiParam (入参) {Number} city_id 市id
     * @apiParam (入参) {Number} area_id 区级id
     * @apiParam (入参) {String} address 详细地址
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/addUserAddress
     * @return array
     * @author rzc
     */
    public function addUserAddress(){
        $paramUid = trim($this->request->post('uid'));
        $province_id = trim($this->request->post('province_id'));
        $city_id = trim($this->request->post('city_id'));
        $area_id = trim($this->request->post('area_id'));
        $address = trim($this->request->post('address'));

        if (empty($paramUid)) {
            return ['code' => '3002', 'msg' => '缺少参数:uid'];
        }
        $uid = trim($paramUid);
        if (strlen($uid) != 32) {
            return ['code' => 3001];
        }
        $result = $this->app->user->addUserAddress($uid,intval($province_id),intval($city_id),intval($area_id),$address);
        return $result;
    }

    /**
     * @api              {post} / 用户修改地址
     * @apiDescription   updateUserAddress
     * @apiGroup         index_user
     * @apiName          updateUserAddress
     * @apiParam (入参) {String} uid 用户加密id
     * @apiParam (入参) {Number} province_id 省id
     * @apiParam (入参) {Number} city_id 市id
     * @apiParam (入参) {Number} area_id 区级id
     * @apiParam (入参) {String} address 详细地址
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/updateUserAddress
     * @return array
     * @author rzc
     */
    public function updateUserAddress(){

    }

    /**
     * @api              {post} / 用户修改默认地址
     * @apiDescription   updateUserAddress
     * @apiGroup         index_user
     * @apiName          updateUserAddress
     * @apiParam (入参) {String} uid 用户加密id
     * @apiParam (入参) {Number} province_id 省id
     * @apiParam (入参) {Number} city_id 市id
     * @apiParam (入参) {Number} area_id 区级id
     * @apiParam (入参) {String} address 详细地址
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/updateUserAddress
     * @return array
     * @author rzc
     */
}