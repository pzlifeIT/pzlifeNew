<?php

namespace app\index\controller;

use app\index\MyController;
use Config;
use function Qiniu\json_decode;

class User extends MyController {
    protected $beforeActionList = [
//        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'login,quickLogin,register,resetPassword,sendVercode,loginUserByWx'],//除去getFirstCate其他方法都进行second前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 处理推荐关系
     * @apiDescription   indexMain
     * @apiGroup         index_user
     * @apiName          indexMain
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} [buid] 推荐人uid
     * @apiSuccess (返回) {String} code 200:成功  3001:con_id长度只能是32位 / 3002:conId有误
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/indexmain
     * @return array
     * @author zyr
     */
    public function indexMain() {
        $conId = trim($this->request->post('con_id'));
        $buid  = trim($this->request->post('buid'));
        $buid  = empty(deUid($buid)) ? 1 : deUid($buid);
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $res = $this->app->user->indexMain($conId, $buid);
        return $res;
    }

    /**
     * @api              {post} / 账号密码登录
     * @apiDescription   login
     * @apiGroup         index_user
     * @apiName          login
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} password 密码
     * @apiParam (入参) {String} [buid] 推荐人uid
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:账号不存在 / 3003:密码错误 / 3004:登录失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/login
     * @return array
     * @author zyr
     */
    public function login() {
        $mobile   = trim($this->request->post('mobile'));
        $password = trim($this->request->post('password'));
        $buid     = trim($this->request->post('buid'));
        $buid     = empty(deUid($buid)) ? 1 : deUid($buid);
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];//手机号格式错误
        }
        if (checkPassword($password) === false) {
            return ['code' => '3005'];
        }
        $res = $this->app->user->login($mobile, $password, $buid);
        return $res;
    }

    /**
     * @api              {post} / 手机快捷登录
     * @apiDescription   quickLogin
     * @apiGroup         index_user
     * @apiName          quickLogin
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} vercode 验证码
     * @apiParam (入参) {String} code 微信code
     * @apiParam (入参) {String} encrypteddata 微信加密信息
     * @apiParam (入参) {String} iv
     * @apiParam (入参) {String} [platform] 1.小程序 2.公众号(默认1)
     * @apiParam (入参) {String} [buid] 推荐人uid
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误  / 3002:code码错误 / 3004:验证码格式有误 / 3006:验证码错误 / 3009:该微信号已绑定手机号
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/quicklogin
     * @return array
     * @author zyr
     */
    public function quickLogin() {
        $mobile        = trim($this->request->post('mobile'));
        $vercode       = trim($this->request->post('vercode'));
        $code          = trim($this->request->post('code'));
        $encrypteddata = trim($this->request->post('encrypteddata'));
        $iv            = trim($this->request->post('iv'));
        $platform      = trim($this->request->post('platform'));
        $buid          = trim($this->request->post('buid'));
        $buid          = empty(deUid($buid)) ? 1 : deUid($buid);
        $platformArr   = [1, 2];
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];//手机号格式错误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }
        if (strlen($code) != 32) {
            return ['code' => '3002'];//code有误
        }
        $platform = in_array($platform, $platformArr) ? intval($platform) : 1;
        $result   = $this->app->user->quickLogin($mobile, $vercode, $code, $encrypteddata, $iv, $platform, $buid);
        return $result;
    }

    /**
     * @api              {post} / 注册
     * @apiDescription   register
     * @apiGroup         index_user
     * @apiName          register
     * @apiParam (入参) {String} mobile 接收的手机号
     * @apiParam (入参) {String} vercode 验证码
     * @apiParam (入参) {String} password 密码
     * @apiParam (入参) {String} code 微信code
     * @apiParam (入参) {String} encrypteddata 微信加密信息
     * @apiParam (入参) {String} iv
     * @apiParam (入参) {String} [platform] 1.小程序 2.公众号(默认1)
     * @apiParam (入参) {String} [buid] 推荐人uid
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:code码错误 / 3004:验证码格式有误 / 3005:密码强度不够 / 3006:验证码错误 / 3007 注册失败 / 3008:手机号已被注册
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/register
     * @return array
     * @author zyr
     */
    public function register() {
        $mobile        = trim($this->request->post('mobile'));
        $code          = trim($this->request->post('code'));
        $vercode       = trim($this->request->post('vercode'));
        $password      = trim($this->request->post('password'));
        $encrypteddata = trim($this->request->post('encrypteddata'));
        $iv            = trim($this->request->post('iv'));
        $platform      = trim($this->request->post('platform'));
        $buid          = trim($this->request->post('buid'));
        $buid          = empty(deUid($buid)) ? 1 : deUid($buid);
        $platformArr   = [1, 2];
        if (checkMobile($mobile) === false) {
            return ['code' => '3001'];//手机号格式错误
        }
        if (strlen($code) != 32) {
            return ['code' => '3002'];//code有误
        }
        if (checkVercode($vercode) === false) {
            return ['code' => '3004'];
        }
        if (checkPassword($password) === false) {
            return ['code' => '3005'];
        }
        $platform = in_array($platform, $platformArr) ? intval($platform) : 1;
        $result   = $this->app->user->register($mobile, $vercode, $password, $code, $encrypteddata, $iv, $platform, $buid);
        return $result;
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
     * @apiParam (入参) {Number} stype 验证码类型 1.注册 2.修改密码 3.快捷登录
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
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:conId有误查不到uid
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
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $res = $this->app->user->getUser($conId);
        return $res;
    }


    /**
     * @api              {post} / 通过微信code登录
     * @apiDescription   loginUserByWx
     * @apiGroup         index_user
     * @apiName          loginUserByWx
     * @apiParam (入参) {String} code 微信code
     * @apiParam (入参) {String} [platform] 1.小程序 2.公众号(默认1)
     * @apiParam (入参) {String} [buid] 推荐人uid
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户或未绑定手机号 / 3001:code码错误 / 3002:没有手机号的老用户 / 3003:登录失败
     * @apiSuccess (data) {String} con_id
     * @apiSampleRequest /index/user/loginuserbywx
     * @return array
     * @author zyr
     */
    public function loginUserByWx() {
        $code        = trim($this->request->post('code'));
        $platform    = trim($this->request->post('platform'));
        $buid        = trim($this->request->post('buid'));
        $buid        = empty(deUid($buid)) ? 1 : deUid($buid);
        $platformArr = [1, 2];
        if (strlen($code) != 32) {
            return ['code' => 3001];
        }
        $platform = in_array($platform, $platformArr) ? intval($platform) : 1;
        $res      = $this->app->user->loginUserByWx($code, $platform, $buid);
        return $res;
    }

    /**
     * @api              {post} / 通过con_id获取用户添加地址信息
     * @apiDescription   getUserAddress
     * @apiGroup         index_user
     * @apiName          getUserAddress
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} address_id 查询一条传值，查所有不传
     * @apiSuccess (返回) {String} code 200:成功 3000:该用户没有地址 / 3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:conId有误查不到uid
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/getUserAddress
     * @return array
     * @author rzc
     */
    public function getUserAddress() {
        $conId      = trim($this->request->post('con_id'));
        $address_id = trim($this->request->post('address_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if ($address_id) {
            if (!is_numeric($address_id)) {
                return ['code' => '3003'];
            }
        }
        $result = $this->app->user->getUserAddress($conId, $address_id);
        return $result;
    }

    /**
     * @api              {post} / 用户添加地址信息
     * @apiDescription   addUserAddress
     * @apiGroup         index_user
     * @apiName          addUserAddress
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} province_name 省id
     * @apiParam (入参) {Number} city_name 市id
     * @apiParam (入参) {Number} area_name 区级id
     * @apiParam (入参) {String} address 详细地址
     * @apiParam (入参) {String} mobile 电话号码
     * @apiParam (入参) {String} name 姓名
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3002:缺少con_id / 3003:手机格式有误 / 3004:错误的市级名称 / 3005:错误的区级名称 / 3006:错误的省份名称 / 3007:请填写详细街道地址 / 3008:添加失败 / 3009:uid为空 / 3010:请填写收货人姓名
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/addUserAddress
     * @return array
     * @author rzc
     */
    public function addUserAddress() {
        $conId         = trim($this->request->post('con_id'));
        $province_name = trim($this->request->post('province_name'));
        $city_name     = trim($this->request->post('city_name'));
        $area_name     = trim($this->request->post('area_name'));
        $address       = trim($this->request->post('address'));
        $mobile        = trim($this->request->post('mobile'));
        $name          = trim($this->request->post('name'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!checkMobile($mobile)) {
            return ['code' => '3003'];//手机格式有误
        }
        $result = $this->app->user->addUserAddress($conId, $province_name, $city_name, $area_name, $address, $mobile, $name);
        return $result;
    }

    /**
     * @api              {post} / 用户修改地址
     * @apiDescription   updateUserAddress
     * @apiGroup         index_user
     * @apiName          updateUserAddress
     * @apiParam (入参) {String} con_id 用户加密id
     * @apiParam (入参) {String} address_id 修改地址ID
     * @apiParam (入参) {Number} province_name 省id
     * @apiParam (入参) {Number} city_name 市id
     * @apiParam (入参) {Number} area_name 区级id
     * @apiParam (入参) {String} address 详细地址
     * @apiParam (入参) {String} mobile 电话号码
     * @apiParam (入参) {String} name 姓名
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/updateUserAddress
     * @return array
     * @author rzc
     */
    public function updateUserAddress() {
        $conId         = trim($this->request->post('con_id'));
        $province_name = trim($this->request->post('province_name'));
        $city_name     = trim($this->request->post('city_name'));
        $area_name     = trim($this->request->post('area_name'));
        $address       = trim($this->request->post('address'));
        $mobile        = trim($this->request->post('mobile'));
        $name          = trim($this->request->post('name'));
        $address_id    = trim($this->request->post('address_id'));
        if (!checkMobile($mobile)) {
            return ['code' => '3003'];//手机格式有误
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->updateUserAddress($conId, $province_name, $city_name, $area_name, $address, $name, $mobile, $address_id);
        return $result;
    }

    /**
     * @api              {post} / 用户修改默认地址
     * @apiDescription   updateUserAddressDefault
     * @apiGroup         index_user
     * @apiName          updateUserAddressDefault
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {Number} address_id 地址ID
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数 / 3003:address_id必须是数字
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/updateUserAddressDefault
     * @return array
     * @author rzc
     */

    public function updateUserAddressDefault() {
        $conId      = trim($this->request->post('con_id'));
        $address_id = trim($this->request->post('address_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($address_id)) {
            return ['code' => '3003'];
        }
        $result = $this->app->user->updateUserAddressDefault($conId, $address_id);
        return $result;
    }

    /**
     * @api              {get} / 获取用户二维码
     * @apiDescription   getUserQrcode
     * @apiGroup         index_user
     * @apiName          getUserQrcode
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {String} link 跳转页面
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数 / 3003:link不能为空 / 3004:获取access_token失败 / 3005:未获取到access_token / 3006:生成二维码识别 / 3007:link最大长度32
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/getUserQrcode
     * @return array
     * @author rzc
     */
    public function getUserQrcode(){
        $link = trim($this->request->get('link'));
        $conId = trim($this->request->get('con_id'));
        // print_r($conId);die;
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (empty($link)) {
            return ['code' => '3003'];
        }
        if (strlen($link) > 32) {
            return ['code' => '3007'];
        }
        $appid         = Config::get('conf.weixin_miniprogram_appid');
        $secret        = Config::get('conf.weixin_miniprogram_appsecret');
        $requestUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
        if (!$requestUrl) {
            return ['code' => '3004'];
        }
        $requsest_subject = json_decode(sendRequest($requestUrl),true);
        $access_token     = $requsest_subject['access_token'];
        if (!$access_token){
            return ['code' => '3005'];
        }
        $requestUrl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
        // print_r($link);die;
        $result = $this->sendRequest2($requestUrl,['scene'=>$link]);
        if (imagecreatefromstring($result)) {
            echo $result;die;
        }else{
            return ['code' => '3006'];
        }
        
        
    }

    function sendRequest2($requestUrl, $data = []) {
        $curl = curl_init();
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,['Content-Type: application/json; charset=utf-8','Content-Length:' . strlen($data)]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return  $res;
    }
}