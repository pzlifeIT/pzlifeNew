<?php

namespace app\index\controller;

use app\index\MyController;

/**
 * 短信通知
 */
class Wap extends MyController
{

    public function test()
    {
        echo 1;
        die;
    }
    protected $beforeActionList = [
        //        'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'getSupPromote,getJsapiTicket,getProvinceCity,getCity,getArea,getBloodSamplingAddress,getsamplingReport,addSamplingAppointment,editSamplingAppointment,getSamplingAppointment,getCheckBloodAddress'], //除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {get} / 获取推广详情
     * @apiDescription   getSupPromote
     * @apiGroup         index_wap
     * @apiName          getSupPromote
     * @apiParam (入参) {Number} promote_id 活动ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 / 3001:promote_id有误 / 3002:
     * @apiSuccess (返回) {Array} promote 基本属性
     * @apiSuccess (返回) {Array} banner 头部轮播（暂无）
     * @apiSuccess (返回) {Array} detail 详情图片
     * @apiSuccess (promote) {String} title 标题
     * @apiSuccess (promote) {String} big_image 大图
     * @apiSuccess (promote) {String} share_title 微信转发分享标题
     * @apiSuccess (promote) {String} share_image 微信转发分享图片
     * @apiSuccess (promote) {Int} share_count 需要分享次数
     * @apiSuccess (promote) {String} bg_image 分享成功页面图片
     * @apiSuccess (banner) {String} image_path 图片路径
     * @apiSuccess (detail) {String} image_path 标题
     * @apiSampleRequest /index/wap/getSupPromote
     * @author rzc
     */
    public function getSupPromote()
    {
        $promote_id = trim($this->request->get('promote_id'));
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3001];
        }
        $result = $this->app->wap->getSupPromote($promote_id);
        return $result;
    }

    /**
     * @api              {post} / 活动报名
     * @apiDescription  SupPromoteSignUp
     * @apiGroup         index_wap
     * @apiName          SupPromoteSignUp
     * @apiParam (入参) {Number} promote_id 活动ID
     * @apiParam (入参) {String} con_id 用户登录ID
     * @apiParam (入参) {String} mobile 联系人手机号
     * @apiParam (入参) {String} sex 性别 1 男 2 女
     * @apiParam (入参) {String} age 年龄
     * @apiParam (入参) {String} signinfo 报名内容
     * @apiParam (入参) {String} nick_name 联系人姓名
     * @apiParam (入参) {String} study_name 学员姓名
     * @apiParam (入参) {String} study_mobile 学员手机号
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该姓名已报名参加 / 3006:请填写姓名 / 3007:验证码格式有误 / 3008:验证码错误 / 3009:性别格式不对  / 3010:年龄格式错误 / 3011:signinfo为空 / 3012:study_name为空 / 3013:study_mobile格式错误
     * @apiSuccess (返回) {Array} data
     * @apiSampleRequest /index/wap/SupPromoteSignUp
     * @author rzc
     */
    public function SupPromoteSignUp()
    {
        $apiName = classBasename($this) . '/' . __function__;
        // $mobile  = trim($this->request->post('mobile'));
        // $vercode = trim($this->request->post('vercode'));
        // $nick_name  = trim($this->request->post('nick_name'));
        $sex          = trim($this->request->post('sex'));
        $age          = trim($this->request->post('age'));
        $signinfo     = trim($this->request->post('signinfo'));
        $promote_id   = trim($this->request->post('promote_id'));
        $conId        = trim($this->request->post('con_id'));
        $study_name   = trim($this->request->post('study_name'));
        $study_mobile = trim($this->request->post('study_mobile'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3003];
        }
        // if (checkMobile($mobile) === false) {
        //     return ['code' => '3004']; //手机号格式错误
        // }
        if (checkMobile($study_mobile) === false) {
            return ['code' => '3013']; //study_mobile手机号格式错误
        }
        // if (checkVercode($vercode) === false) {
        //     return ['code' => '3007'];
        // }
        // if (empty($nick_name)) {
        //     return ['code' => 3006];
        // }
        if (!in_array($sex, [1, 2])) {
            return ['code' => '3009'];
        }

        if (!is_numeric($age)) {
            return ['code' => '3010'];
        }
        $age = intval($age);
        if ($age < 1 || $age > 100) {
            return ['code' => '3010'];
        }
        if (empty($signinfo)) {
            return ['code' => '3011'];
        }
        if (empty($study_name)) {
            return ['code' => '3012'];
        }
        $mobile    = '';
        $nick_name = '';
        $result    = $this->app->wap->SupPromoteSignUp($conId, $mobile, $nick_name, $promote_id, $sex, $age, $signinfo, $study_name, $study_mobile);
        $this->apiLog($apiName, [$conId, $mobile, $nick_name, $promote_id, $sex, $age, $signinfo], $result['code'], '');
        return $result;
    }

    /**
     * @api              {get} / 活动分享次数(调用一次视为分享成功一次)
     * @apiDescription  getPromoteShareNum
     * @apiGroup         index_wap
     * @apiName          getPromoteShareNum
     * @apiParam (入参) {Number} promote_id 活动ID
     * @apiParam (入参) {String} con_id 用户登录ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/getPromoteShareNum
     * @author rzc
     */
    public function getPromoteShareNum()
    {
        $promote_id = trim($this->request->get('promote_id'));
        $conId      = trim($this->request->get('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($promote_id) || $promote_id < 1) {
            return ['code' => 3003];
        }
        $result = $this->app->wap->getPromoteShareNum($promote_id, $conId);
        return $result;
    }

    /**
     * @api              {get} / 获取 JSAPI 分享签名包
     * @apiDescription  getJsapiTicket
     * @apiGroup         index_wap
     * @apiName          getJsapiTicket
     * @apiParam (入参) {Number} url 分享页面的当前网页的URL (不包含#及其后面部分)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/getJsapiTicket
     * @author rzc
     */
    public function getJsapiTicket()
    {
        $url = trim($this->request->get('url'));
        $url = urldecode($url);
        $url = str_replace('&amp;', '&', $url);
        if (empty($url)) {
            return ['code' => 3001];
        }
        $result = $this->app->wap->getJsapiTicket($url);
        return $result;
    }

    /**
     * @api              {post} / 外部公众号发送模板消息
     * @apiDescription  sendModelMessage
     * @apiGroup         index_wap
     * @apiName          sendModelMessage
     * @apiParam (入参) {string} access_token ACCESS_TOKEN
     * @apiParam (入参) {string} template_id 模板消息ID
     * @apiParam (入参) {string} touser 接收者openid
     * @apiParam (入参) {string} [url] 模板跳转链接（海外帐号没有跳转能力）
     * @apiParam (入参) {string} data 模板数据
     * @apiParam (入参) {string} [color] 模板内容字体颜色，不填默认为黑色
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/sendModelMessage
     * @author rzc
     */
    public function sendModelMessage()
    {
        $access_token = trim($this->request->post('access_token'));
        $template_id  = trim($this->request->post('template_id'));
        $touser       = trim($this->request->post('touser'));
        $url          = trim($this->request->post('url'));
        $data         = $this->request->post('data');
        $color        = trim($this->request->post('color'));
        if (empty($access_token)) {
            return ['code' => '3001', 'Error' => 'ACCESS_TOKEN is none'];
        }

        if (empty($template_id)) {
            return ['code' => '3002', 'Error' => 'template_id is none'];
        }
        if (empty($touser)) {
            return ['code' => '3003', 'Error' => 'touser is none'];
        }
        if (empty($data)) {
            return ['code' => '3004', 'Error' => 'data is none'];
        }
        // $data = json_decode($data,true);
        // print_r($touser);die;
        $result = $this->app->wap->sendModelMessage($access_token, $template_id, $touser, $url, $data, $color);
        return $result;
    }
    /**   
     * @api              {POST} / 卡号核验
     * @apiDescription  samplingReport
     * @apiGroup         index_wap
     * @apiName          samplingReport
     * @apiParam (入参) {String} con_id 用户登录ID
     * @apiParam (入参) {Number} card_number 卡号
     * @apiParam (入参) {Number} passwd 密码
     * @apiParam (入参) {Number} mobile 手机号
     * @apiParam (入参) {Number} [from_id] 推荐人12位ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/samplingReport
     * @author rzc
     */
    public function samplingReport()
    {

        $conId      = trim($this->request->post('con_id'));
        $card_number = trim($this->request->post('card_number'));
        $passwd = trim($this->request->post('passwd'));
        $mobile = trim($this->request->post('mobile'));
        $from_id = trim($this->request->post('from_id'));
        if (empty($card_number) || empty($passwd) || empty($mobile)) {
            return ['code' => '3000', 'msg' => '信息不完整，请填写信息'];
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->wap->samplingReport($conId, $card_number, $passwd, $mobile, $from_id);
        return $result;
    }

    /**
     * @api              {post} / 线上推广钻石临时接口
     * @apiDescription  onlineMarketingUser
     * @apiGroup         index_wap
     * @apiName          onlineMarketingUser
     * @apiParam (入参) {String} avatar 头像
     * @apiParam (入参) {String} nick_name 昵称
     * @apiParam (入参) {Number} mobile 手机号
     * @apiParam (入参) {Number} user_identity 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiParam (入参) {String} platform 来源：SJ：宋建
     * @apiSuccess (返回) {String} code 200:成功  /  3001:手机号格式错误 / 3002:用户推广身份错误 / 3003:非法来源 / 3004:手机号已存在 / 3005:数据回滚,添加失败 / 3006:请填写姓名 / 3007:请上传头像
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/onlineMarketingUser
     * @author rzc
     */
    public function onlineMarketingUser()
    {
        $avatar        = trim($this->request->post('avatar'));
        $nick_name     = trim($this->request->post('nick_name'));
        $mobile        = trim($this->request->post('mobile'));
        $user_identity = trim($this->request->post('user_identity'));
        $platform      = trim($this->request->post('platform'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001', 'Errormsg' => 'mobile check false']; //手机号格式错误
        }
        if (!in_array($user_identity, [1, 2, 3, 4])) {
            return ['code' => '3002', 'Errormsg' => 'user_identity false'];
        }
        if (!in_array($platform, ['SJ'])) {
            return ['code' => '3003', 'Errormsg' => 'platform false'];
        }
        if (empty($nick_name)) {
            return ['code' => '3006', 'Errormsg' => 'nick_name is null'];
        }
        if (empty($avatar)) {
            return ['code' => '3007', 'Errormsg' => 'avatar is null'];
        }
        $result = $this->app->wap->onlineMarketingUser($avatar, $nick_name, $mobile, $user_identity, $platform);
        return $result;
    }

    /**
     * @api              {POST} / 根据手机号码获取领取预约卡
     * @apiDescription  getsamplingReport
     * @apiGroup         index_wap
     * @apiName          getsamplingReport
     * @apiParam (入参) {String} con_id 用户登录ID
     * @apiParam (入参) {String} status 状态 1:已核验  2:已预约
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/getsamplingReport
     * @author rzc
     */
    public function getsamplingReport()
    {
        $conId      = trim($this->request->post('con_id'));
        $status      = trim($this->request->post('status'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->wap->getsamplingReport($conId, $status);
        return $result;
    }
    /**
     * @api              {POST} / 根据省市区获取抽血点
     * @apiDescription  getBloodSamplingAddress
     * @apiGroup         index_wap
     * @apiName          getBloodSamplingAddress
     * @apiParam (入参) {Int} province_id 省id
     * @apiParam (入参) {int} city_id 市id
     * @apiParam (入参) {int}  area_id 区级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:发送失败 /  3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:promote_id有误 / 3004:手机号错误 / 3005:本次活动该手机号已报名参加 / 3006:请填写姓名
     * @apiSuccess (返回) {String} is_share 1 未达成分享目标； 2 已达成分享目标
     * @apiSampleRequest /index/wap/getBloodSamplingAddress
     * @author rzc
     */
    public function getBloodSamplingAddress()
    {
        $province_id  = trim($this->request->post('province_id'));
        $city_id  = trim($this->request->post('city_id'));
        $area_id  = trim($this->request->post('area_id'));
        $result = $this->app->wap->getBloodSampling($province_id, $city_id, $area_id);
        return $result;
    }

    /**
     * @api              {post} / 省市列表
     * @apiDescription   getProvinceCity
     * @apiGroup         index_wap
     * @apiName          getProvinceCity
     * @apiSuccess (返回) {String} code 200:成功 / 3000:省市区列表为空
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/wap/getProvinceCity
     * @author zyr
     */
    public function getProvinceCity()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        // $conId = trim($this->request->post('conId'));
        $result = $this->app->wap->getProvinceCity();
        // $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        //        $this->addLog($result['code'],__function__);//接口请求日志
        return $result;
    }

    /**
     * @api              {post} / 提交预约
     * @apiDescription   addSamplingAppointment
     * @apiGroup         index_wap
     * @apiName          addSamplingAppointment
     * @apiParam (入参) {String} con_id 用户登录ID
     * @apiParam (入参) {String} mobile 手机号
     * @apiParam (入参) {String} name 姓名
     * @apiParam (入参) {String} sex 性别 性别 1，男，2，女
     * @apiParam (入参) {String} age 年龄 
     * @apiParam (入参) {String} idenity_type 证件类型，1,身份证 
     * @apiParam (入参) {String} idenity_nmber 证件号码
     * @apiParam (入参) {String} blood_sampling_id 采样点id
     * @apiParam (入参) {String} project_id 预约项目激活卡id，多张卡用,连接
     * @apiParam (入参) {String} is_illness 是否有家族病史, 1:没有,2：有
     * @apiParam (入参) {String} is_had_illness 本人是否患有肿瘤, 1:没有,2：有
     * @apiParam (入参) {String} had_illness_time 本人患肿瘤时间
     * @apiParam (入参) {String} illness 家族肿瘤患者患什么肿瘤
     * @apiParam (入参) {String} my_illness 本人患什么肿瘤
     * @apiParam (入参) {String} relation 本人与肿瘤患者成员关系 1,祖父、2祖母、3外公、4外婆、5父亲、6母亲、7兄弟姐妹、8子女、9伯/叔/姑、10舅/姨
     * @apiParam (入参) {String} health_type 本人健康状态1：查出肿瘤尚未治疗；2已手术未做放化疗，3，已手术正在做放化疗，4，已手术，已结束放化疗，5未做手术已做放化疗
     * @apiSuccess (返回) {String} code 200:成功 / 3000:省市区列表为空
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/wap/addSamplingAppointment
     * @author zyr
     */
    public function addSamplingAppointment()
    {
        $conId      = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $mobile      = trim($this->request->post('mobile'));
        $name      = trim($this->request->post('name'));
        $sex      = trim($this->request->post('sex'));
        $age      = trim($this->request->post('age'));
        $idenity_type      = trim($this->request->post('idenity_type'));
        $idenity_nmber      = trim($this->request->post('idenity_nmber'));
        $blood_sampling_id      = trim($this->request->post('blood_sampling_id'));
        $project_id      = trim($this->request->post('project_id'));
        $is_illness      = trim($this->request->post('is_illness'));
        $is_had_illness      = trim($this->request->post('is_had_illness'));
        $had_illness_time      = trim($this->request->post('had_illness_time'));
        $illness      = trim($this->request->post('illness'));
        $relation      = trim($this->request->post('relation'));
        $my_illness      = trim($this->request->post('my_illness'));
        $health_type      = trim($this->request->post('health_type'));
        $appointment_time      = trim($this->request->post('appointment_time'));
        $project_id = explode(',', $project_id);
        /* if (empty($mobile) || empty($name) || empty($sex) || empty($age) || empty($idenity_type) || empty($idenity_nmber) || empty($blood_sampling_id) || empty($project_id) || empty($is_illness) || empty($is_had_illness)) {
            return ['code' => '3001', 'msg' => '参数为空'];
        } */
        $result = $this->app->wap->addSamplingAppointment($conId, $mobile, $name, $sex, $age, $idenity_type, $blood_sampling_id, $project_id, $is_illness, $idenity_nmber, $is_had_illness, $had_illness_time, $illness, $relation, $my_illness, $health_type, $appointment_time);
        return $result;
    }

    /**
     * @api              {post} / 修改提交预约
     * @apiDescription   editSamplingAppointment
     * @apiGroup         index_wap
     * @apiName          editSamplingAppointment
     * @apiParam (入参) {String} id 修改id 
     * @apiParam (入参) {String} con_id 用户登录ID
     * @apiParam (入参) {String} mobile 手机号
     * @apiParam (入参) {String} name 姓名
     * @apiParam (入参) {String} sex 性别 性别 1，男，2，女
     * @apiParam (入参) {String} age 年龄 
     * @apiParam (入参) {String} idenity_type 证件类型，1,身份证 
     * @apiParam (入参) {String} idenity_nmber 证件号码
     * @apiParam (入参) {String} appointment_time 预约时间
     * @apiParam (入参) {String} blood_sampling_id 采样点id
     * @apiParam (入参) {String} project_id 预约项目激活卡id，多张卡用,连接
     * @apiParam (入参) {String} is_illness 是否有家族病史, 1:没有,2：有
     * @apiParam (入参) {String} is_had_illness 本人是否患有肿瘤, 1:没有,2：有
     * @apiParam (入参) {String} had_illness_time 本人患肿瘤时间
     * @apiParam (入参) {String} illness 家族肿瘤患者患什么肿瘤
     * @apiParam (入参) {String} my_illness 本人患什么肿瘤
     * @apiParam (入参) {String} relation 本人与肿瘤患者成员关系 1,祖父、2祖母、3外公、4外婆、5父亲、6母亲、7兄弟姐妹、8子女、9伯/叔/姑、10舅/姨
     * @apiParam (入参) {String} health_type 本人健康状态1：查出肿瘤尚未治疗；2已手术未做放化疗，3，已手术正在做放化疗，4，已手术，已结束放化疗，5未做手术已做放化疗
     * @apiSuccess (返回) {String} code 200:成功 / 3000:省市区列表为空
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/wap/editSamplingAppointment
     * @author zyr
     */
    public function editSamplingAppointment()
    {
        $conId      = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $id      = trim($this->request->post('id'));
        $mobile      = trim($this->request->post('mobile'));
        $name      = trim($this->request->post('name'));
        $sex      = trim($this->request->post('sex'));
        $age      = trim($this->request->post('age'));
        $idenity_type      = trim($this->request->post('idenity_type'));
        $idenity_nmber      = trim($this->request->post('idenity_nmber'));
        $appointment_time      = trim($this->request->post('appointment_time'));
        $blood_sampling_id      = trim($this->request->post('blood_sampling_id'));
        $project_id      = trim($this->request->post('project_id'));
        $is_illness      = trim($this->request->post('is_illness'));
        $is_had_illness      = trim($this->request->post('is_had_illness'));
        $had_illness_time      = trim($this->request->post('had_illness_time'));
        $illness      = trim($this->request->post('illness'));
        $relation      = trim($this->request->post('relation'));
        $my_illness      = trim($this->request->post('my_illness'));
        $health_type      = trim($this->request->post('health_type'));
        $project_id = explode(',', $project_id);
        if (empty($mobile) || empty($name) || empty($sex) || empty($age) || empty($idenity_type) || empty($idenity_nmber) || empty($blood_sampling_id) || empty($project_id) || empty($is_illness) || empty($is_had_illness) || empty($illness) || empty($had_illness_time) || empty($relation) || empty($my_illness) || empty($health_type)) {
            return ['code' => '3001', 'msg' => '参数为空'];
        }
        $result = $this->app->wap->editSamplingAppointment($id, $conId, $mobile, $name, $sex, $age, $idenity_type, $blood_sampling_id, $project_id, $is_illness, $idenity_nmber, $is_had_illness, $had_illness_time, $illness, $relation, $my_illness, $health_type, $appointment_time);
        return $result;
    }

    /**
     * @api              {post} / 获取预约信息
     * @apiDescription   getSamplingAppointment
     * @apiGroup         index_wap
     * @apiName          getSamplingAppointment
     * @apiSuccess (返回) {String} code 200:成功 / 3000:省市区列表为空
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/wap/getSamplingAppointment
     * @author zyr
     */
    public function getSamplingAppointment()
    {
        $conId      = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $id      = trim($this->request->post('id'));
        $result = $this->app->wap->getSamplingAppointment($id, $conId);
        return $result;
    }

    /**
     * @api              {post} / 获取市级列表
     * @apiDescription   getCity
     * @apiGroup         index_wap
     * @apiName          getCity
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} provinceId 省级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:市列表空 / 3001:省级id不存在 / 3002:省级id只能是数字
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/wap//getCity
     * @author zyr
     */
    public function getCity()
    {
        $apiName    = classBasename($this) . '/' . __function__;
        // $cmsConId   = trim($this->request->post('cms_con_id'));
        $provinceId = trim($this->request->post('provinceId'));
        if (!is_numeric($provinceId)) {
            return ['3002'];
        }
        $provinceId = intval($provinceId);
        $result     = $this->app->wap->getArea($provinceId, 2);
        // $this->apiLog($apiName, [$cmsConId, $provinceId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取区级列表
     * @apiDescription   getArea
     * @apiGroup         index_wap
     * @apiName          getArea
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} cityId 市级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:区列表空 / 3001:市级id不存在 / 3002:市级id只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/wap/getArea
     * @author zyr
     */
    public function getArea()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        // $cmsConId = trim($this->request->post('cms_con_id'));
        $cityId   = trim($this->request->post('cityId'));
        if (!is_numeric($cityId)) {
            return ['3002'];
        }
        $cityId = intval($cityId);
        $result = $this->app->wap->getArea($cityId, 3);
        // $this->apiLog($apiName, [$cmsConId, $cityId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取抽血点可选择地区
     * @apiDescription   getCheckBloodAddress
     * @apiGroup         index_wap
     * @apiName          getCheckBloodAddress
     * @apiSuccess (返回) {String} code 200:成功 / 3000:区列表空 / 3001:市级id不存在 / 3002:市级id只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /index/wap/getCheckBloodAddress
     * @author zyr
     */
    public function getCheckBloodAddress()
    {
        $result = $this->app->wap->getCheckBloodAddress();
        return $result;
    }
}
