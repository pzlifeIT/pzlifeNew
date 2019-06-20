<?php

namespace app\common\action\wap;

//use third\AliSms;
use app\common\action\notify\Note;
use app\common\action\wap\CommonIndex;
use app\facade\DbUser;
use config;
use Env;
use third\Zthy;

/**
 * H5站接口
 * @package app\common\wap
 */
class Wap extends CommonIndex {

    private $note;

    public function __construct() {
        parent::__construct();
        $this->note = new Note();
    }

    /**
     * 微信授权
     * @param $code
     * @param $redirect_uri
     * @return array
     * @author rzc
     */

    public function wxaccredit($redirect_uri) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';

        $requestUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        // $requsest_subject = json_decode(sendRequest($requestUrl), true);
        // $requestUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
        // $requsest_subject = json_decode(sendRequest($requestUrl), true);
        return $requestUrl;

    }

    /**
     * 微信注册
     * @param $mobile
     * @param $vercode
     * @param $code
     * @param $buid
     * @return array
     * @author rzc
     */
    public function wxregister($mobile, $vercode, $code , $buid) {
        $stype = 1;
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006']; //验证码错误
        }
        if (!empty($this->checkAccount($mobile))) {
            return ['code' => '3008'];
        }
        $wxaccess_token = $this->getaccessToken($code);
        if ($wxaccess_token == false) {
            return ['code' => '3002'];
        }
        $wxInfo = $this->getunionid($wxaccess_token['openid'], $wxaccess_token['access_token']);

        if ($wxInfo == false) {
            return ['code' => '3002'];
        }
        $uid = 0;
        if (empty($wxInfo['unionid'])) {
            return ['code' => '3000'];
        }
        $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id,mobile');
        if (!empty($user)) {
            if (!empty($user['mobile'])) { //该微信号已绑定
                return ['code' => '3009'];
            }
            $uid = $user['id'];
        }
        $data = [
            'mobile'    => $mobile,
            'unionid'   => $wxInfo['unionid'],
            'nick_name' => $wxInfo['nickname'],
            'avatar'    => $wxInfo['headimgurl'],
            'sex'       => $wxInfo['sex'],
            'last_time' => time(),
        ];
        $isBoss         = 3;
        $userRelationId = 0;
        $relationRes    = '';
        if ($buid != 1) { //不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $relationRes   = $bUserRelation['relation'];
            $isBoss        = $bUserRelation['is_boss'] ?? 3; //推荐人是否是boss
            if (!empty($uid) && $isBoss == 1) { //不是哥新用户
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) { //总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }

        Db::startTrans();
        try {
            if (empty($uid)) { //新用户,直接添加
                $uid = DbUser::addUser($data); //添加后生成的uid
                DbUser::addUserRecommend(['uid' => $uid, 'pid' => $buid]);
                if ($isBoss == 1) {
                    $relation = $buid . ',' . $uid;
                } else if ($isBoss == 3) {
                    $relation = $uid;
                } else {
                    $relation = $relationRes . ',' . $uid;
                }
                DbUser::addUserRelation(['uid' => $uid, 'pid' => $buid, 'relation' => $relation]);
            } else { //老版本用户
                DbUser::updateUser($data, $uid);
                if (!empty($userRelationId)) {
                    DbUser::updateUserRelation(['relation' => $buid . ',' . $uid, 'pid' => $buid], $userRelationId);
                }
            }
            $conId = $this->createConId();
            DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
            $this->saveOpenid($uid, $wxInfo['openid'], 2);
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3007'];
        }

    }



      /**
     * 微信登录
     * @param $code
     * @param $platform
     * @param $buid
     * @return array
     * @author zyr
     */
    public function loginUserByWx($code, $platform, $buid) {
        $wxInfo = getOpenid($code);
        if ($wxInfo === false) {
            return ['code' => '3001'];
        }
        if (empty($wxInfo['unionid'])) {
            return ['code' => '3000'];
        }
        $user = DbUser::getUser(['unionid' => $wxInfo['unionid']]);
        if (empty($user) || empty($user['mobile'])) {
            return ['code' => '3000'];
        }
        $uid = enUid($user['id']);
        $id  = $user['id'];
        unset($user['id']);
        $user['uid'] = $uid;
        // $this->saveUser($id, $user); //用户信息保存在缓存
        if (empty($user['mobile'])) {
            return ['code' => '3002'];
        }
        $userRelationId = 0;
        if ($buid != 1) { //不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $isBoss        = $bUserRelation['is_boss'] ?? 3; //推荐人是否是boss
            if ($isBoss == 1) {
                $userRelation = DbUser::getUserRelation(['uid' => $id], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $id) { //总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }
        $conId   = $this->createConId();
        $userCon = DbUser::getUserCon(['uid' => $id], 'id,con_id', true);
        Db::startTrans();
        try {
            if (!empty($userRelationId)) {
                DbUser::updateUserRelation(['relation' => $buid . ',' . $id, 'pid' => $buid], $userRelationId);
            }
            if (empty($userCon)) {
                DbUser::addUserCon(['uid' => $id, 'con_id' => $conId]);
            } else {
                DbUser::updateUserCon(['con_id' => $conId], $userCon['id']);
            }
            DbUser::updateUser(['last_time' => time()], $id);
            $this->saveOpenid($id, $wxInfo['openid'], $platform);
            if (!empty($userCon)) {
                $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
                $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
            }
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $id);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
            }
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3003'];
        }
    }

    private function getaccessToken($code) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
        $res           = sendRequest($get_token_url);
        $result        = json_decode($res, true);
        if (empty($result['openid'])) {
            return false;
        }
        return $result;
    }

    private function getunionid($openid, $access_token) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $get_token_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res           = sendRequest($get_token_url);
        $result        = json_decode($res, true);
        if (empty($result['openid'])) {
            return false;
        }
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
        $redisCode = $this->redis->get($redisKey); //服务器保存的验证码
        if ($redisCode == $vercode) {
            return true;
        }
        return false;
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
}