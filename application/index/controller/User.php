<?php

namespace app\index\controller;

use app\index\MyController;

class User extends MyController {
    protected $beforeActionList = [
//        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'login,quickLogin,register,resetPassword,sendVercode,loginUserByOpenid'],//除去getFirstCate其他方法都进行second前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 账号密码登录
     * @apiDescription   login
     * @apiGroup         index_user
     * @apiName          login
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} password 密码
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:账号不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/login
     * @return array
     * @author zyr
     */
    public function login() {
        $mobile   = trim($this->request->post('mobile'));
        $password = trim($this->request->post('password'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];//手机号格式错误
        }
        if (checkPassword($password) === false) {
            return ['code' => '3005'];
        }
        $res = $this->app->user->login($mobile, $password);
        return $res;
    }

    /**
     * @api              {post} / 手机快捷登录
     * @apiDescription   quickLogin
     * @apiGroup         index_user
     * @apiName          quickLogin
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} vercode 验证码
     * @apiParam (入参) {String} openid 微信openid
     * @apiParam (入参) {String} nick_name 微信昵称
     * @apiParam (入参) {String} avatar 微信头像
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:缺少openid / 3003:openid有误 / 3004:验证码格式有误 / 3006:验证码错误
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/quicklogin
     * @return array
     * @author zyr
     */
    public function quickLogin() {
        $mobile   = trim($this->request->post('mobile'));
        $vercode  = trim($this->request->post('vercode'));
        $openid   = trim($this->request->post('openid'));
        $nickName = trim($this->request->post('nick_name'));
        $avatar   = trim($this->request->post('avatar'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];//手机号格式错误
        }
        if (empty($openid)) {
            return ['code' => '3002'];//缺少参数:openid
        }
        $openid = trim($openid);
        if (strlen($openid) != 28) {
            return ['code' => '3003'];//openid有误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }
        $resule = $this->app->user->quickLogin($mobile, $vercode, $openid, $nickName, $avatar);
        return $resule;
    }

    /**
     * @api              {post} / 注册
     * @apiDescription   register
     * @apiGroup         index_user
     * @apiName          register
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} openid 微信openid
     * @apiParam (入参) {String} vercode 验证码
     * @apiParam (入参) {String} password 密码
     * @apiParam (入参) {String} nick_name 微信昵称
     * @apiParam (入参) {String} avatar 微信头像
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:缺少参数:openid / 3003:openid有误 / 3004:验证码格式有误 / 3005:密码强度不够 / 3006:验证码错误 / 3007 注册失败 / 3008:手机号已被注册
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/register
     * @return array
     * @author zyr
     */
    public function register() {
        $mobile   = trim($this->request->post('mobile'));
        $openid   = trim($this->request->post('openid'));
        $vercode  = trim($this->request->post('vercode'));
        $password = trim($this->request->post('password'));
        $nickName = trim($this->request->post('nick_name'));
        $avatar   = trim($this->request->post('avatar'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];//手机号格式错误
        }
        if (empty($openid)) {
            return ['code' => '3002'];//缺少参数:openid
        }
        $openid = trim($openid);
        if (strlen($openid) != 28) {
            return ['code' => '3003'];//openid有误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }
        if (checkPassword($password) === false) {
            return ['code' => '3005'];
        }
        $resule = $this->app->user->register($mobile, $vercode, $password, $openid, $nickName, $avatar);
        return $resule;
    }

    /**
     * @api              {post} / 重置密码
     * @apiDescription   resetPassword
     * @apiGroup         index_user
     * @apiName          resetPassword
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {Number} vercode 验证码
     * @apiParam (入参) {Number} password 新密码
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:该手机未注册 / 3003:更新失败 / 3004:验证码格式有误 / 3005:密码强度不够 / 3006:验证码错误
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/resetpassword
     * @return array
     * @author zyr
     */
    public function resetPassword() {
        $mobile   = trim($this->request->post('mobile'));
        $vercode  = trim($this->request->post('vercode'));
        $password = trim($this->request->post('password'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];//手机号格式错误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }
        if (checkPassword($password) === false) {
            return ['code' => '3005'];
        }
        $result = $this->app->user->resetPassword($mobile, $vercode, $password);
        return $result;
    }

    /**
     * @api              {post} / 发送验证码短信
     * @apiDescription   sendVercode
     * @apiGroup         index_user
     * @apiName          sendVercode
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {Number} stype 验证码类型 1.注册 2修改密码 3.快捷登录
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:发送类型有误 / 3003:一分钟内不能重复发送 / 3004:短信发送失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/sendvercode
     * @return array
     * @author zyr
     */
    public function sendVercode() {
        $stypeArr = [1, 2, 3];
        $mobile   = trim($this->request->post('mobile'));
        $stype    = trim($this->request->post('stype'));
        if (!checkMobile($mobile)) {
            return ['code' => '3001'];//手机格式有误
        }
        if (!in_array($stype, $stypeArr)) {
            return ['code' => '3002'];//手机格式有误
        }
        $result = $this->app->user->sendVercode($mobile, $stype);
        return $result;
    }

    /**
     * @api              {post} / 通过con_id获取用户信息
     * @apiDescription   getUser
     * @apiGroup         index_user
     * @apiName          getuser
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是16位 / 3002:缺少con_id / 3003:conId有误查不到uid
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
        $conId = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 16) {
            return ['code' => '3001'];
        }
        $res = $this->app->user->getUser($conId);
        return $res;
    }


    /**
     * @api              {post} / 通过openid登录
     * @apiDescription   loginUserByOpenid
     * @apiGroup         index_user
     * @apiName          loginUserByOpenid
     * @apiParam (入参) {String} openid 微信openid
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:没有手机号的老用户
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
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:该用户没有地址 / 3001:con_id长度只能是16位 / 3002:缺少con_id / 3003:conId有误查不到uid
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/getUserAddress
     * @return array
     * @author rzc
     */
    public function getUserAddress() {
        $conId = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 16) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->getUserAddress($conId);
        return $result;
    }

    /**
     * @api              {post} / 用户添加地址信息
     * @apiDescription   addUserAddress
     * @apiGroup         index_user
     * @apiName          addUserAddress
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} province_id 省id
     * @apiParam (入参) {Number} city_id 市id
     * @apiParam (入参) {Number} area_id 区级id
     * @apiParam (入参) {String} address 详细地址
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3002:缺少con_id / 3003:conId有误查不到uid
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/addUserAddress
     * @return array
     * @author rzc
     */
    public function addUserAddress() {
        $conId       = trim($this->request->post('con_id'));
        $province_id = trim($this->request->post('province_id'));
        $city_id     = trim($this->request->post('city_id'));
        $area_id     = trim($this->request->post('area_id'));
        $address     = trim($this->request->post('address'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 16) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->addUserAddress($conId, intval($province_id), intval($city_id), intval($area_id), $address);
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
    public function updateUserAddress() {

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