<?php

namespace app\common\action\index;

use app\common\action\notify\Note;
use app\facade\DbUser;
use app\facade\DbProvinces;
use Env;
use Config;
use think\Db;

class User extends CommonIndex {
    private $cipherUserKey = 'userpass';//用户密码加密key
    private $note;

    public function __construct() {
        parent::__construct();
        $this->note = new Note();
    }

    /**
     * 账号密码登录
     * @param $mobile
     * @param $password
     * @return array
     * @author zyr
     */
    public function login($mobile, $password) {
        $user = DbUser::getUserOne(['mobile' => $mobile], 'id,passwd');
        $uid  = $user['id'];
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey);//加密后的password
        if ($cipherPassword != $user['passwd']) {
            return ['code' => '3003'];
        }
        $conId   = $this->createConId();
        $userCon = DbUser::getUserCon(['uid' => $uid], 'id,con_id', true);
        if (DbUser::updateUserCon(['con_id' => $conId], $userCon['id'])) {
            $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
            $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTimem, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
            }
            DbUser::updateUser(['last_time' => time()], $uid);
            return ['code' => '200', 'con_id' => $conId];
        }
        return ['code' => '3004'];
    }

    /**
     * 快捷登录
     * @param $mobile
     * @param $vercode
     * @param $code
     * @param $encrypteddata
     * @param $iv
     * @return array
     * @author zyr
     */
    public function quickLogin($mobile, $vercode, $code, $encrypteddata, $iv) {
        $stype = 3;
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006'];//验证码错误
        }
        $wxInfo = $this->getOpenid($code, $encrypteddata, $iv);
        if ($wxInfo === false) {
            return ['code' => '3002'];
        }
        $updateData = [];
        $addData    = [];
        $uid        = $this->checkAccount($mobile);//通过手机号获取uid
        if (empty($uid)) {
            $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id');//手机号获取不到就通过微信获取
            if (!empty($user)) {//注册了微信的老用户
                $uid        = $user['id'];
                $updateData = [
                    'mobile' => $mobile,
                ];
            } else {//新用户
                $addData = [
                    'mobile'    => $mobile,
                    'unionid'   => $wxInfo['unionid'],
                    'nick_name' => $wxInfo['nickname'],
                    'avatar'    => $wxInfo['avatarurl'],//$wxInfo['unionid'],
                ];
            }
        }
        $userCon = [];
        if (!empty($uid)) {
            $userCon = DbUser::getUserCon(['uid' => $uid], 'id,con_id', true);
        }
        Db::startTrans();
        try {
            $conId = $this->createConId();
            if (!empty($updateData)) {
                DbUser::updateUser($updateData, $uid);
            } else if (!empty($addData)) {
                $uid = DbUser::addUser($addData);//添加后生成的uid
            }
            if (!empty($userCon)) {
                DbUser::updateUserCon(['con_id' => $conId], $userCon['id']);
            } else {
                DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            }
            if (!empty($userCon)) {
                $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
                $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
            }
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTimem, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype);
            Db::commit();
            DbUser::updateUser(['last_time' => time()], $uid);
            $this->saveOpenid($uid, $wxInfo['openid']);
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007'];
        }
    }

    /**
     * 新用户注册
     * @param $mobile
     * @param $vercode
     * @param $password
     * @param $code
     * @param $encrypteddata
     * @param $iv
     * @param $platform
     * @return array
     * @author zyr
     */
    public function register($mobile, $vercode, $password, $code, $encrypteddata, $iv, $platform) {
        $stype = 1;
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006'];//验证码错误
        }
        if (!empty($this->checkAccount($mobile))) {
            return ['code' => '3008'];
        }
        $wxInfo = $this->getOpenid($code, $encrypteddata, $iv);
        if ($wxInfo === false) {
            return ['code' => '3002'];
        }
        $uid  = 0;
        $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id');
        if (!empty($user)) {
            $uid = $user['id'];
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey);//加密后的password
        $data           = [
            'mobile'    => $mobile,
            'passwd'    => $cipherPassword,
            'unionid'   => $wxInfo['unionid'],
            'nick_name' => $wxInfo['nickname'],
            'avatar'    => $wxInfo['avatarurl'],
            'last_time' => time(),
        ];
        Db::startTrans();
        try {
            if (empty($uid)) {//新用户,直接添加
                $uid = DbUser::addUser($data);//添加后生成的uid
            } else {//老版本用户
                DbUser::updateUser($data, $uid);
            }
            $conId = $this->createConId();
            DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTimem, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype);//成功后删除验证码
            Db::commit();
            $this->saveOpenid($uid, $wxInfo['openid'], $platform);
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007'];
        }
    }

    /**
     * 重置密码
     * @param $mobile
     * @param $vercode
     * @param $password
     * @return array
     * @author zyr
     */
    public function resetPassword($mobile, $vercode, $password) {
        $stype = 2;
        $uid   = $this->checkAccount($mobile);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006'];//验证码错误
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey);//加密后的password
        $result         = DbUser::updateUser(['passwd' => $cipherPassword], $uid);
        if ($result) {
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype);//成功后删除验证码
            return ['code' => '200'];
        }
        return ['code' => '3003'];
    }

    /**
     * 微信登录
     * @param $code
     * @param $platform
     * @return array
     * @author zyr
     */
    public function loginUserByWx($code, $platform) {
        $wxInfo = $this->getOpenid($code);
        if ($wxInfo === false) {
            return ['code' => '3001'];
        }
        $user = DbUser::getUser(['unionid' => $wxInfo['unionid']]);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        $uid = enUid($user['id']);
        $id  = $user['id'];
        unset($user['id']);
        $user['uid'] = $uid;
//        $this->saveUser($id, $user);//用户信息保存在缓存
        if (empty($user['mobile'])) {
            return ['code' => '3002'];
        }
        $conId   = $this->createConId();
        $userCon = DbUser::getUserCon(['uid' => $id], 'id,con_id', true);
        if (DbUser::updateUserCon(['con_id' => $conId], $userCon['id'])) {
            $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
            $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTimem, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
            }
            DbUser::updateUser(['last_time' => time()], $uid);
            $this->saveOpenid($id, $wxInfo['openid'], $platform);
            return ['code' => '200', 'con_id' => $conId];
        }
        return ['code' => '3003'];
    }

    /**
     * 保存openid
     * @param $uid
     * @param $openId
     * @param $platform
     * @author zyr
     */
    private function saveOpenid($uid, $openId, $platform) {
        $userCount = DbUser::getUserOpenidCount($uid, $openId);
        if ($userCount == 0) {
            $data = [
                'uid'         => $uid,
                'openid'      => $openId,
                'platform'    => $platform,
                'openid_type' => Config::get('conf.platform_conf')[Config::get('app.deploy')],
            ];
            DbUser::saveUserOpenid($data);
        }
    }

    /**
     * 验证用户是否存在
     * @param $mobile
     * @return bool
     * @author zyr
     */
    private function checkAccount($mobile) {
        $user = DbUser::getUserOne(['mobile' => $mobile], 'id');
        if (!empty($user)) {
            return $user['id'];
        }
        return 0;
    }

    /**
     * 生成并发送验证码
     * @param $mobile
     * @param $stype
     * @return array
     * @author zyr
     */
    public function sendVercode($mobile, $stype) {
        $redisKey   = $this->redisKey . 'vercode:' . $mobile . ':' . $stype;
        $timeoutKey = $this->redisKey . 'vercode:timeout:' . $mobile . ':' . $stype;
        $code       = $this->createVercode($redisKey, $timeoutKey);
        if (empty($code)) {//已发送过验证码
            return ['code' => '3003'];//一分钟内不能重复发送
        }
        $content = getVercodeContent($code);//短信内容
        $result  = $this->note->sendSms($mobile, $content);//发送短信
        if ($result['code'] != '200') {
            $this->redis->del($timeoutKey);
            $this->redis->del($redisKey);
            return ['code' => '3004'];//短信发送失败
        }
        DbUser::addLogVercode(['stype' => $stype, 'code' => $code, 'mobile' => $mobile]);
        return ['code' => '200'];
    }

    /**
     * 验证提交的验证码是否正确
     * @param $stype
     * @param $mobile
     * @param $vercode
     * @return bool
     * @author zyr
     */
    private function checkVercode($stype, $mobile, $vercode) {
        $redisKey  = $this->redisKey . 'vercode:' . $mobile . ':' . $stype;
        $redisCode = $this->redis->get($redisKey);//服务器保存的验证码
        if ($redisCode == $vercode) {
            return true;
        }
        return false;
    }

    /**
     * 生成并保存验证码
     * @param $redisKey
     * @param $timeoutKey
     * @return string
     * @author zyr
     */
    private function createVercode($redisKey, $timeoutKey) {
        if (!$this->redis->setNx($timeoutKey, 1)) {
            return '0';//一分钟内不能重复发送
        }
        $this->redis->setTimeout($timeoutKey, 60);//60秒自动过期
        $code = randCaptcha(6);//生成验证码
        if ($this->redis->setEx($redisKey, 600, $code)) {//不重新发送酒10分钟过期
            return $code;
        }
        return '0';
    }

    /**
     * 获取用户信息
     * @param $conId
     * @return array
     * @author zyr
     */
    public function getUser($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        if ($this->redis->exists($this->redisKey . 'userinfo:' . $uid)) {
            $res = $this->redis->hGetAll($this->redisKey . 'userinfo:' . $uid);
        } else {
            $res        = DbUser::getUser(['id' => $uid]);
            $res['uid'] = enUid($res['id']);
            unset($res['id']);
            $this->saveUser($uid, $res);
        }
        if (empty($res)) {
            return ['code' => '3000'];
        }
        unset($res['id']);
        return ['code' => 200, 'data' => $res];
    }

    /**
     * @return array
     */
    public function getBoss() {
        $userRelation = UserRelation::where('uid', '=', $this->uid)->field('pid,is_boss,relation')->findOrEmpty()->toArray();
        $relation     = explode(',', $userRelation['relation']);

        $this->getIdentity($userRelation['pid']);
//        print_r($relation);die;
        $boss = $relation[0];
        return ['pid' => $userRelation['pid'], 'is_boss' => $userRelation['is_boss'], 'boss' => $boss];
    }

    /**
     * 获取用户身份
     * @param $uid
     * @return bool
     */
    public function getIdentity($uid) {
        $user = Users::where('id', '=', $uid)->field('user_identity')->findOrEmpty()->toArray();
        if (empty($user)) {
            return false;
        }
        return $user['user_identity'];
    }

    /**
     * 保存用户信息(记录到缓存)
     * @param $id
     * @param $user
     * @author zyr
     */
    private function saveUser($id, $user) {
        $saveTime = 600;//保存10分钟
        $this->redis->hMSet($this->redisKey . 'userinfo:' . $id, $user);
        $this->redis->expireAt($this->redisKey . 'userinfo:' . $id, bcadd(time(), $saveTime, 0));//设置过期
    }

    /**
     * 获取用的openid unionid 及详细信息
     * @param $code
     * @param $encrypteddata
     * @param $iv
     * @return array|bool|int
     * @author zyr
     */
    private function getOpenid($code, $encrypteddata = '', $iv = '') {
        $appid         = Config::get('conf.weixin_miniprogram_appid');
        $secret        = Config::get('conf.weixin_miniprogram_appsecret');
        $get_token_url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
        $res           = sendRequest($get_token_url);
        $result        = json_decode($res, true);
        // Array([session_key] => N/G/1C4QKntLTDB9Mk0kPA==,[openid] => oAuSK5VaBgJRWjZTD3MDkTSEGwE8,[unionid] => o4Xj757Ljftj2Z6EUBdBGZD0qHhk)
        if (empty($result['unionid']) || empty($result['session_key'])) {
            return false;
        }
        $sessionKey = $result['session_key'];
        unset($result['session_key']);
        if (!empty($encrypteddata) && !empty($iv)) {
            $result = $this->decryptData($encrypteddata, $iv, $sessionKey);
        }
        if (is_array($result)) {
            $result = array_change_key_case($result, CASE_LOWER);//CASE_UPPER,CASE_LOWER
            return $result;
        }
        return false;
        //[openId] => oAuSK5VaBgJRWjZTD3MDkTSEGwE8,[nickName] => 榮,[gender] => 1,[language] => zh_CN,[city] =>,[province] => Shanghai,[country] => China,
        //[avatarUrl] => https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJiaWQI7tUfDVrvuSrDDcfFiaJriaibibBiaYabWL5h6HlDgMMvkyFul9JRicr0ZMULxs66t5NBdyuhEokhA/132
        //[unionId] => o4Xj757Ljftj2Z6EUBdBGZD0qHhk
    }

    /**
     * 解密微信信息
     * @param $encryptedData
     * @param $iv
     * @param $sessionKey
     * @return int|array
     * @author zyr
     * -40001: 签名验证错误
     * -40002: xml解析失败
     * -40003: sha加密生成签名失败
     * -40004: encodingAesKey 非法
     * -40005: appid 校验错误
     * -40006: aes 加密失败
     * -40007: aes 解密失败
     * -40008: 解密后得到的buffer非法
     * -40009: base64加密失败
     * -40010: base64解密失败
     * -40011: 生成xml失败
     */
    private function decryptData($encryptedData, $iv, $sessionKey) {
        $appid = Config::get('conf.weixin_miniprogram_appid');
        if (strlen($sessionKey) != 24) {
            return -41001;
        }
        $aesKey = base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            return -41002;
        }
        $aesIV     = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result    = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj   = json_decode($result);
        if ($dataObj == null) {
            return -41003;
        }
        if ($dataObj->watermark->appid != $appid) {
            return -41003;
        }
        $data = json_decode($result, true);
        unset($data['watermark']);
        return $data;
    }

    /**
     * 添加新地址
     * @param $conId
     * @param $province_name
     * @param $city_name
     * @param $area_name
     * @param $address
     * @param $mobile
     * @param $name
     * @return array
     * @author rzc
     */
    public function addUserAddress($conId, $province_name, $city_name, $area_name, $address, $mobile, $name) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3009', 'msg' => 'uid为空'];
        }
        if (empty($address)) {
            return ['code' => '3007', 'msg' => '请填写详细街道地址'];
        }
        /* 判断省市区ID是否合法 */
        $field    = 'id,area_name,pid,level';
        $where    = ['area_name' => $province_name];
        $province = DbProvinces::getAreaOne($field, $where);

        if (empty($province) || $province['level'] != '1') {
            return ['code' => '3006', 'msg' => '错误的省份ID'];
        }
        $field = 'id,area_name,pid,level';
        $where = ['area_name' => $city_name];
        $city  = DbProvinces::getAreaOne($field, $where);
        if (empty($city) || $city['level'] != '2') {
            return ['code' => '3004', 'msg' => '错误的市级ID'];
        }
        $field = 'id,area_name,pid,level';
        $where = ['area_name' => $area_name];
        $area  = DbProvinces::getAreaOne($field, $where);
        if (empty($area) || $area['level'] != '3') {
            return ['code' => '3005', 'msg' => '错误的区级ID'];
        }
        $data                = [];
        $data['uid']         = $uid;
        $data['province_id'] = $province['id'];
        $data['city_id']     = $city['id'];
        $data['area_id']     = $area['id'];
        $data['address']     = $address;
        $data['mobile']      = $mobile;
        $data['name']        = $name;
        $data                = ['default' => 2];
        $add                 = DbUser::addUserAddress($data);
        if ($add) {
            return ['code' => '200', 'msg' => '添加成功'];
        } else {
            return ['code' => '3006', 'msg' => '添加失败'];
        }
    }

    /**
     * 修改地址
     * @param $conId
     * @param $province_name
     * @param $city_name
     * @param $area_name
     * @param $address
     * @param $mobile
     * @param $name
     * @param $address_id
     * @return array
     * @author rzc
     */
    public function updateUserAddress($conId, $province_name, $city_name, $area_name, $address, $name, $mobile, $address_id) {
        $uid = $this->getUidByConId($conId);
        $field      = 'id,uid';
        $where      = ['id' => $address_id, 'uid' => $uid];
        $is_address = DbUser::getUserAddress($field, $where, true);
        if (!$is_address) {
            return ['code' => '3010', 'msg' => '无效的address_id'];
        }
        if (empty($uid)) {
            return ['code' => '3009', 'msg' => 'uid为空'];
        }
        if (empty($address)) {
            return ['code' => '3007', 'msg' => '请填写详细街道地址'];
        }
        /* 判断省市区ID是否合法 */
        $field    = 'id,area_name,pid,level';
        $where    = ['area_name' => $province_name];
        $province = DbProvinces::getAreaOne($field, $where);

        if (empty($province) || $province['level'] != '1') {
            return ['code' => '3006', 'msg' => '错误的省份名称'];
        }
        $field = 'id,area_name,pid,level';
        $where = ['area_name' => $city_name];
        $city  = DbProvinces::getAreaOne($field, $where);
        if (empty($city) || $city['level'] != '2') {
            return ['code' => '3004', 'msg' => '错误的市级名称'];
        }
        $field = 'id,area_name,pid,level';
        $where = ['area_name' => $area_name];
        $area  = DbProvinces::getAreaOne($field, $where);
        if (empty($area) || $area['level'] != '3') {
            return ['code' => '3005', 'msg' => '错误的区级名称'];
        }
        
        $data                = [];
        $data['province_id'] = $province['id'];
        $data['city_id'] = $city['id'];
        $data['area_id'] = $area['id'];
        $data['address'] = $address;
        $data['mobile'] = $mobile;
        $data['name'] = $name;
        DbUser::updateUserAddress($data,$where);
        return ['code' => 200,'msg' => '修改成功'];
    }

    /**
     * 查询用户地址
     * @param $conId
     * @param $address_id
     * @author rzc
     */
    public function getUserAddress($conId, $address_id = false) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $field = 'id,uid,province_id,city_id,area_id,address,default,name,mobile';
        $where = ['uid' => $uid];

        /* 查询一条用户地址详细信息 */
        if ($address_id) {
            $where  = ['uid' => $uid, 'address_id' => $address_id];
            $result = DbUser::getUserAddress($field, $where, true);
            if (empty($result)) {
                return ['code' => 3000];
            }
           
            // $field = 'id,area_name,pid,level';
            // $where = ['id' => $city_id];
            $result['province_name']    = DbProvinces::getAreaOne('*', ['id' => $city_id])['area_name'];
            $result['city_name']    = DbProvinces::getAreaOne('*', ['id' => $city_id])['area_name'];
            $result['area_name']    = DbProvinces::getAreaOne('*', ['id' => $city_id])['area_name'];

            return ['code' => 200, 'data' => $result];
        }

        $result = DbUser::getUserAddress($field, $where);
        if (empty($result)) {
            return ['code' => 3000];
        }
        foreach ($result as $key => $value) {
            $result[$key]['province_name']    = DbProvinces::getAreaOne('*', ['id' => $value['city_id']])['area_name'];
            $result[$key]['city_name']    = DbProvinces::getAreaOne('*', ['id' => $value['city_id']])['area_name'];
            $result[$key]['area_name']    = DbProvinces::getAreaOne('*', ['id' => $value['city_id']])['area_name'];
        }
        return ['code' => 200, 'data' => $result];
    }

    /**
     * 设置用户默认地址
     * @param $conId
     * @param $address_id
     * @author rzc
     */
    public function updateUserAddressDefault($conId, $address_id) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3004'];
        }
        $field  = 'id,uid,province_id,city_id,area_id,address,default,name,mobile';
        $where  = ['uid' => $uid, 'address_id' => $address_id];
        $result = DbUser::getUserAddress($field, $where, true);
        if (empty($result)) {
            return ['code' => 3005, 'msg' => '该地址不存在，无法设为默认']; /*  */
        }

        DbUser::updateUserAddress(['default' => 2], ['uid' => $uid]);
        DbUser::updateUserAddress(['default' => 1], $where);
        return ['code' => 200, 'msg' => '修改默认成功'];

    }

    /**
     * 密码加密
     * @param $str
     * @param $key
     * @return string
     * @author zyr
     */
    private function getPassword($str, $key) {
        $algo   = Config::get('conf.cipher_algo');
        $md5    = hash_hmac('md5', $str, $key);
        $key2   = strrev($key);
        $result = hash_hmac($algo, $md5, $key2);
        return $result;
    }

    /**
     * 创建唯一conId
     * @author zyr
     */
    private function createConId() {
        $conId = uniqid(date('ymdHis'));
        $conId = hash_hmac('ripemd128', $conId, '');
        return $conId;
    }
}