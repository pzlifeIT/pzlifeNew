<?php

namespace app\index\controller;

use app\index\MyController;
use Config;
use think\Db;
use function Qiniu\json_decode;

class User extends MyController {
    protected $beforeActionList = [
//        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'login,quickLogin,register,resetPassword,sendVercode,loginUserByWx,getUserRead'],//除去getFirstCate其他方法都进行second前置操作
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
        $dd       = [$result, ['mobile' => $mobile, 'vercode' => $vercode, 'buid' => $buid]];
        Db::table('pz_log_error')->insert(['title' => '/index/user/getintegraldetail', 'data' => json_encode($dd)]);
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
     * @apiParam (入参) {Number} stype 验证码类型 1.注册 2.修改密码 3.快捷登录 4.银行卡绑卡验证
     * @apiSuccess (返回) {String} code 200:成功  3001:手机格式有误 / 3002:发送类型有误 / 3003:一分钟内不能重复发送 / 3004:短信发送失败
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/sendvercode
     * @return array
     * @author zyr
     */
    public function sendVercode() {
        $stypeArr = [1, 2, 3,4];
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
     * @api              {post} / boss店铺管理
     * @apiDescription   getBossShop
     * @apiGroup         index_user
     * @apiName          getBossShop
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功 3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:用户不存在 / 3004:用户不是boss
     * @apiSuccess (返回) {Array} data 店铺首页信息
     * @apiSuccess (返回) {Decimal} balance_all 实际得到分利
     * @apiSuccess (返回) {Decimal} balance 商票余额
     * @apiSuccess (返回) {Decimal} commission 佣金余额
     * @apiSuccess (返回) {Decimal} integral 积分余额
     * @apiSuccess (返回) {Decimal} balance_use 已使用商票
     * @apiSuccess (返回) {Decimal} no_bonus 未到账
     * @apiSuccess (返回) {Decimal} bonus 已到账
     * @apiSuccess (返回) {Decimal} bonus_all 总收益
     * @apiSuccess (返回) {Decimal} merchants 招商加盟收益
     * @apiSampleRequest /index/user/getbossshop
     * @return array
     * @author zyr
     */
    public function getBossShop() {
        $conId = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->getBossShop($conId);
        return $result;
    }


    /**
     * @api              {post} / 获取分利列表信息
     * @apiDescription   getUserBonus
     * @apiGroup         index_user
     * @apiName          getUserBonus
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Int} status 1.未入账 2.已入账
     * @apiParam (入参) {Int} stype 1.个人消费 2.会员圈消费 3.渠道收益
     * @apiParam (入参) {Int} [page] 当前页 默认1
     * @apiParam (入参) {Int} [page_num] 每页数量 默认10
     * @apiParam (入参) {Int} [year] 年份
     * @apiParam (入参) {Int} [month] 月份
     * @apiSuccess (返回) {String} code 200:成功 3000:没有分利信息 /3001:con_id长度只能是32位 / 3002:缺少con_id /3003:用户不存在 / 3004:查询周期有误 / 3005:stype错误 / 3006:status错误
     * @apiSuccess (返回) {Decimal} combined 合计
     * @apiSuccess (返回) {Array} data 分利列表
     * @apiSuccess (data) {Decimal} result_price 实际得到分利
     * @apiSuccess (data) {json} order_no 订单号
     * @apiSuccess (data) {int} status 状态 1:待结算 2:已结算
     * @apiSuccess (data) {json} create_time 订单完成时间
     * @apiSuccess (data) {String} nick_name 昵称
     * @apiSuccess (data) {String} avatar 头像
     * @apiSuccess (data) {Int} from_uid 购买人uid
     * @apiSuccess (data) {Int} user_identity 1.普通会员 2.钻石 4.boss
     * @apiSuccess (data) {int} status 状态 1:待结算 2:已结算
     * @apiSampleRequest /index/user/getuserbonus
     * @return array
     * @author zyr
     */
    public function getUserBonus() {
        $conId     = trim($this->request->post('con_id'));
        $status    = trim($this->request->post('status'));
        $stype     = trim($this->request->post('stype'));
        $page      = trim($this->request->post('page'));
        $pageNum   = trim($this->request->post('page_num'));
        $year      = trim($this->request->post('year'));
        $month     = trim($this->request->post('month'));
        $stypeArr  = [1, 2, 3];
        $statusArr = [1, 2];
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!in_array($stype, $stypeArr)) {
            return ['code' => '3005'];
        }
        if (!in_array($status, $statusArr)) {
            return ['code' => '3006'];
        }
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        $result  = $this->app->user->getUserBonus($conId, $status, $stype, $page, $pageNum, $year, $month);
        return $result;
    }

    /**
     * @api              {post} / 获取店铺商票明细
     * @apiDescription   getShopBalance
     * @apiGroup         index_user
     * @apiName          getShopBalance
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Int} stype 1.已使用明细 2.未使用明细 3.余额明细
     * @apiSuccess (返回) {String} code 200:成功 3000:没有分利信息 /3001:con_id长度只能是32位 / 3002:缺少con_id /3003:用户不存在 / 3004:类型错误
     * @apiSuccess (返回) {Array} data 分利列表
     * @apiSuccess (返回) {Decimal} money 商票金额
     * @apiSuccess (返回) {String} order_no 订单号
     * @apiSuccess (返回) {String} ctype 类型
     * @apiSuccess (返回) {json} create_time 明细生成时间
     * @apiSampleRequest /index/user/getshopbalance
     * @return array
     * @author zyr
     */
    public function getShopBalance() {
        $conId    = trim($this->request->post('con_id'));
        $stype    = trim($this->request->post('stype'));
        $stypeArr = [1, 2, 3];
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!in_array($stype, $stypeArr)) {
            return ['code' => '3004'];
        }
        $result = $this->app->user->getShopBalance($conId, $stype);
        return $result;
    }

    /**
     * @api              {post} / 用户社交圈统计
     * @apiDescription   getUserSocialSum
     * @apiGroup         index_user
     * @apiName          getUserSocialSum
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有分利信息 /3001:con_id长度只能是32位 / 3002:缺少con_id /3003:用户不存在
     * @apiSuccess (返回) {Int} read_count 浏览人次
     * @apiSuccess (返回) {Int} grant_count 授权未注册
     * @apiSuccess (返回) {Int} reg_count 已注册
     * @apiSampleRequest /index/user/getusersocialsum
     * @return array
     * @author zyr
     */
    public function getUserSocialSum() {
        $conId = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->getUserSocialSum($conId);
        return $result;
    }

    /**
     * @api              {post} / 浏览未注册用户
     * @apiDescription   getRead
     * @apiGroup         index_user
     * @apiName          getRead
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Int} page 当前页
     * @apiParam (入参) {Int} page_num 每页数量
     * @apiSuccess (返回) {String} code 200:成功 3000:没有分利信息 /3001:con_id长度只能是32位 / 3002:缺少con_id /3003:用户不存在
     * @apiSuccess (返回) {Array} data
     * @apiSampleRequest /index/user/getread
     * @return array
     * @author zyr
     */
    public function getRead() {
        $conId   = trim($this->request->post('con_id'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        $result  = $this->app->user->getRead($conId, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 用户社交圈
     * @apiDescription   getUserSocial
     * @apiGroup         index_user
     * @apiName          getUserSocial
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Int} stype 1.钻石会员圈 2.买主圈
     * @apiParam (入参) {Int} page 当前页
     * @apiParam (入参) {Int} page_num 每页数量
     * @apiSuccess (返回) {String} code 200:成功 3000:没有分利信息 /3001:con_id长度只能是32位 / 3002:缺少con_id /3003:用户不存在 / 3004:类型错误
     * @apiSuccess (返回) {Int} diamon_user_count 直接钻石会员
     * @apiSuccess (返回) {Int} social_count_all 好友钻石会员
     * @apiSuccess (返回) {Array} data 列表
     * @apiSuccess (data) {Int} id 用户id
     * @apiSuccess (data) {String} nick_name 昵称
     * @apiSuccess (data) {String} avatar 头像
     * @apiSuccess (data) {Int} diamond_count 钻石会员人数
     * @apiSuccess (data) {Int} social_count 社交圈人数
     * @apiSuccess (data) {Int} user_ring_count 已注册普通会员
     * @apiSampleRequest /index/user/getusersocial
     * @return array
     * @author zyr
     */
    public function getUserSocial() {
        $conId    = trim($this->request->post('con_id'));
        $stype    = trim($this->request->post('stype'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('page_num'));
        $stypeArr = [1, 2];
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!in_array($stype, $stypeArr)) {
            return ['code' => '3005'];
        }
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        $result  = $this->app->user->getUserSocial($conId, $stype, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 招商收益
     * @apiDescription   getMerchants
     * @apiGroup         index_user
     * @apiName          getMerchants
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Int} page 当前页
     * @apiParam (入参) {Int} page_num 每页数量
     * @apiSuccess (返回) {String} code 200:成功 3000:没有分利信息 /3001:con_id长度只能是32位 / 3002:缺少con_id /3003:用户不存在
     * @apiSuccess (返回) {Array} data 列表
     * @apiSuccess (返回) {Decimal} money 获利金额
     * @apiSuccess (返回) {String} order_no 购买订单号
     * @apiSuccess (返回) {Decimal} create_time 到账时间
     * @apiSuccess (返回) {Int} uid 购买用户id
     * @apiSuccess (返回) {String} nick_name 购买用户名
     * @apiSuccess (返回) {String} avatar 购买用户头像
     * @apiSampleRequest /index/user/getmerchants
     * @return array
     * @author zyr
     */
    public function getMerchants() {
        $conId   = trim($this->request->post('con_id'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        $result  = $this->app->user->getMerchants($conId, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 积分明细
     * @apiDescription   getIntegralDetail
     * @apiGroup         index_user
     * @apiName          getIntegralDetail
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Int} page 当前页
     * @apiParam (入参) {Int} page_num 每页数量
     * @apiSuccess (返回) {String} code 200:成功 3000:没有分利信息 /3001:con_id长度只能是32位 / 3002:缺少con_id /3003:用户不存在
     * @apiSuccess (返回) {Array} data 列表
     * @apiSuccess (返回) {Decimal} result_integral 积分
     * @apiSuccess (返回) {String} ctype 描述
     * @apiSuccess (返回) {Decimal} create_time 到账时间
     * @apiSampleRequest /index/user/getintegraldetail
     * @return array
     * @author zyr
     */
    public function getIntegralDetail() {
        $conId   = trim($this->request->post('con_id'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $page    = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        $result  = $this->app->user->getIntegralDetail($conId, $page, $pageNum);
        return $result;
    }


    /**
     * @api              {post} / 获取所有下级关系网
     * @apiDescription   getUserNextLevel
     * @apiGroup         index_user
     * @apiName          getUserNextLevel
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} page 当前页(默认:1)
     * @apiParam (入参) {Number} [page_num] 每页数量(默认:10)
     * @apiSuccess (返回) {String} code 200:成功 3000:没有分利信息 /3001:con_id长度只能是32位 / 3002:缺少con_id /3003:用户不存在
     * @apiSuccess (返回) {Array} data 用户列表
     * @apiSuccess (返回) {Decimal} result_price 实际得到分利
     * @apiSuccess (返回) {json} order_no 订单号
     * @apiSuccess (返回) {int} status 状态 1:待结算 2:已结算
     * @apiSuccess (返回) {json} create_time 订单完成时间
     * @apiSampleRequest /index/user/getusernextlevel
     * @return array
     * @author zyr
     */
    public function getUserNextLevel() {
        $conId   = trim($this->request->post('con_id'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        $page    = empty($page) ? 1 : $page;
        $pageNum = empty($pageNum) ? 10 : $pageNum;
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->getUserNextLevel($conId, intval($page), intval($pageNum));
        return $result;
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
     * @apiParam (入参) {String} page 跳转页面
     * @apiParam (入参) {String} scene 跳转页面
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是28位 / 3002:缺少参数 / 3003:scene不能为空 / 3004:获取access_token失败 / 3005:未获取到access_token / 3006:生成二维码识别 / 3007:scene最大长度32 / 3008:page不能为空 / 3009:图片上传失败
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/user/getUserQrcode
     * @return array
     * @author rzc
     */
    public function getUserQrcode() {
        $page  = trim($this->request->get('page'));
        $scene = trim($this->request->get('scene'));
        $conId = trim($this->request->get('con_id'));
        // print_r(Config::get('conf.image_path'));die;
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (empty($page)) {
            return ['code' => '3008'];
        }
        if (empty($scene)) {
            return ['code' => '3003'];
        }
        if (strlen($scene) > 32) {
            return ['code' => '3007'];
        }

        $result = $this->app->user->getQrcode($conId, $page, $scene, 1);
        return $result;
    }

    /**
     * @api              {get} / 用户订单计数
     * @apiDescription   getUserOrderCount
     * @apiGroup         index_user
     * @apiName          getUserOrderCount
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是28位 / 3002:缺少参数 / 3003:scene不能为空 / 3004:获取access_token失败 / 3005:未获取到access_token / 3006:生成二维码识别 / 3007:scene最大长度32 / 3008:page不能为空 / 3009:图片上传失败
     * @apiSuccess (返回) {String} obligation 待付款
     * @apiSuccess (返回) {String} deliver 待发货
     * @apiSuccess (返回) {String} receive 待收货
     * @apiSuccess (返回) {String} rating 待评价
     * @apiSampleRequest /index/user/getUserOrderCount
     * @return array
     * @author rzc
     */
    public function getUserOrderCount() {
        $conId = trim($this->request->get('con_id'));

        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->user->getUserOrderCount($conId);
        return $result;

    }

    /**
     * @api              {post} / 用户绑定银行卡
     * @apiDescription   addUserBankcard
     * @apiGroup         index_user
     * @apiName          addUserBankcard
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {String} vercode 验证码
     * @apiParam (入参) {String} user_name 银行开户人(真实姓名)
     * @apiParam (入参) {String} bank_mobile 银行开户手机号(手机号码)
     * @apiParam (入参) {String} bank_card 银行卡号
     * @apiParam (入参) {String} bank_key_id 开户银行
     * @apiParam (入参) {String} bank_add 开户支行
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是28位 / 3002:缺少参数con_id / 3003:验证码错误 / 3004:银行卡号错误 / 3005:缺少参数bank_key_id,或者bank_key_id必须为数字 / 3006:缺少参数user_name / 3007:手机格式错误 / 3008:未提供支行信息 / 3009:该银行不存在或者已停用 / 3010:银行卡号不属于所选银行
     * @apiSampleRequest /index/user/addUserBankcard
     * @return array
     * @author rzc
     */
    public function addUserBankcard(){
        $conId       = trim($this->request->post('con_id'));
        $user_name   = trim($this->request->post('user_name'));
        $bank_mobile = trim($this->request->post('bank_mobile'));
        $bank_card   = trim($this->request->post('bank_card'));
        $bank_key_id = trim($this->request->post('bank_key_id'));
        $bank_add    = trim($this->request->post('bank_add'));
        $vercode     = trim($this->request->post('vercode'));
        if (checkVercode($vercode) === false) {
            return ['code' => '3003'];
        }
        if (checkBankCard($bank_card) === false) {
            return ['code' => '3004'];
        }
        if (checkMobile($bank_mobile) === false) {
            return ['code' => '3007'];
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (empty($bank_key_id)) {
            return ['code' => '3005'];
        }
        if (!is_numeric($bank_key_id)) {
            return ['code' => '3005'];
        }
        if (empty($user_name)) {
            return ['code' => '3006'];
        }
        if (empty($bank_add)) {
            return ['code' => '3008'];
        }
        $result = $this->app->user->addUserBankcard($conId,$user_name,$bank_mobile,$bank_card,$bank_key_id,$bank_add,$vercode);
        return $result;
    }

    /**
     * @api              {post} / 获取用户信息银行卡
     * @apiDescription   getUserBankcards
     * @apiGroup         index_user
     * @apiName          getUserBankcards
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 
     * @apiSampleRequest /index/user/getUserBankcards
     * @return array
     * @author rzc
     */
    public function getUserBankcards(){

    }

    /**
     * @api              {post} / 修改银行卡信息
     * @apiDescription   editUserBankcards
     * @apiGroup         index_user
     * @apiName          editUserBankcards
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {String} vercode 验证码
     * @apiParam (入参) {String} user_name 银行开户人(真实姓名)
     * @apiParam (入参) {String} bank_mobile 银行开户手机号(手机号码)
     * @apiParam (入参) {String} bank_card 银行卡号
     * @apiParam (入参) {String} bank_key_id 开户银行
     * @apiParam (入参) {String} bank_add 开户支行
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是28位 / 3002:缺少参数con_id / 3003:验证码错误 / 3004:银行卡号错误 / 3005:缺少参数bank_key_id,或者bank_key_id必须为数字 / 3006:缺少参数user_name / 3007:手机格式错误 / 3008:未提供支行信息 / 3009:该银行不存在或者已停用 / 3010:银行卡号不属于所选银行
     * @apiSampleRequest /index/user/editUserBankcards
     * @return array
     * @author rzc
     */
    public function editUserBankcards(){
        $id          = trim($this->request->post('id'));
        $conId       = trim($this->request->post('con_id'));
        $user_name   = trim($this->request->post('user_name'));
        $bank_mobile = trim($this->request->post('bank_mobile'));
        $bank_card   = trim($this->request->post('bank_card'));
        $bank_key_id = trim($this->request->post('bank_key_id'));
        $bank_add    = trim($this->request->post('bank_add'));
        $vercode     = trim($this->request->post('vercode'));
        if (checkVercode($vercode) === false) {
            return ['code' => '3003'];
        }
        if (checkBankCard($bank_card) === false) {
            return ['code' => '3004'];
        }
        if (checkMobile($bank_mobile) === false) {
            return ['code' => '3007'];
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (empty($bank_key_id)) {
            return ['code' => '3005'];
        }
        if (!is_numeric($bank_key_id)) {
            return ['code' => '3005'];
        }
        if (empty($user_name)) {
            return ['code' => '3006'];
        }
        if (empty($bank_add)) {
            return ['code' => '3008'];
        }
        $result = $this->app->user->editUserBankcards($conId,$user_name,$bank_mobile,$bank_card,$bank_key_id,$bank_add,$vercode);
        return $result;
    }

    /**
     * @api              {post} / 佣金提现
     * @apiDescription   commissionTransferCash
     * @apiGroup         index_user
     * @apiName          commissionTransferCash
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {Number} con_id 用户登录bankcard_id
     * @apiParam (入参) {Number} money 用户转出金额
     * @apiParam (入参) {Number} invoice 是否提供发票 1:提供 2:不提供
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是28位 / 3003:money必须为数字 / 3004:提现金额不能小于0 / 3005:没有足够的余额用于提现 / 3006:未查询到该银行卡 / 3007:单笔提现金额不能低于2000，不能高于200000
     * @apiSampleRequest /index/user/commissionTransferCash
     * @return array
     * @author rzc
     */
    public function commissionTransferCash(){
        $conId       = trim($this->request->post('con_id'));
        $bankcard_id = trim($this->request->post('bankcard_id'));
        $money       = trim($this->request->post('money'));
        $invoice     = trim($this->request->post('invoice'));
        if ($invoice != 1) {
            $invoice = 2;
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($money)) {
            return ['code' => '3003'];
        }
        if ($money <= 0) {
            return ['code' => '3004'];
        }
        $result = $this->app->request->commissionTransferCash($conId,intval($bankcard_id),$money,$invoice);
        return $result;
    }

    /**
     * @api              {post} / 佣金转商票
     * @apiDescription   commissionTransferBalance
     * @apiGroup         index_user
     * @apiName          commissionTransferBalance
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {Number} money 用户转出金额
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是28位 / 3003:money必须为数字 / 3004:提现金额不能小于0 / 3005:没有足够的余额用于提现 / 3006:转商票失败
     * @apiSampleRequest /index/user/commissionTransferBalance
     * @return array
     * @author rzc
     */
    public function commissionTransferBalance(){
        $conId       = trim($this->request->post('con_id'));
        $money       = trim($this->request->post('money'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($money)) {
            return ['code' => '3003'];
        }
        if ($money <= 0) {
            return ['code' => '3004'];
        }
        $result = $this->app->user->commissionTransferBalance($conId,intval($money));
        return $result;
    }
     /**
     ** @api              {post} / 分享浏览人次
     * @apiDescription   getUserRead
     * @apiGroup         index_user
     * @apiName          getUserRead
     * @apiParam (入参) {String} code 微信code
     * @apiParam (入参) {String} [encrypteddata] 微信加密信息
     * @apiParam (入参) {String} [iv]
     * @apiParam (入参) {String} [view_uid] 被浏览的用户
     * @apiSuccess (返回) {String} code 200:成功 3000:没有code / 3002:code长度只能是32位 / 3002:缺少参数 / 3003:未获取到openid / 3004:注册了微信的老用户 / 3005:今天已记录过该次数
     * @apiSuccess (返回) {String} obligation 待付款
     * @apiSuccess (返回) {String} deliver 待发货
     * @apiSuccess (返回) {String} receive 待收货
     * @apiSuccess (返回) {String} rating 待评价
     * @apiSampleRequest /index/user/getUserRead
     * @return array
     * @author rzc
     */
    public function getUserRead() {
        $code          = trim($this->request->post('code'));
        $encrypteddata = trim($this->request->post('encrypteddata'));
        $iv            = trim($this->request->post('iv'));
        $view_uid      = trim($this->request->post('view_uid'));
        if (empty($code)) {
            return ['code' => '3000'];
        }
        if (strlen($code) != 32) {
            return ['code' => '3002'];//code有误
        }
        $view_uid = deUid($view_uid);
        $view_uid = $view_uid ? $view_uid : 1;
        $result   = $this->app->user->userRead($code, $encrypteddata, $iv, $view_uid);
        return $result;
    }
}