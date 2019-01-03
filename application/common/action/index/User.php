<?php

namespace app\common\action\index;

use app\facade\DbUser;
use cache\Phpredis;
use Env;

class User {
    private $redis;
    private $cryptMethod;
    private $cryptKey;
    private $cryptIv;
    private $iv = '00000000';
    private $redisKey = 'index:user:userinfo:';

    public function __construct() {
        $this->redis       = Phpredis::getConn();
        $this->cryptMethod = Env::get('cipher.userAesMethod', 'AES-256-CBC');
        $this->cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
        $this->cryptIv     = Env::get('cipher.userAesIv', '11111111');
    }

    public function loginUserByOpenid($openid) {
        $user = DbUser::getUser(['openid' => $openid]);
        if (empty($user)) {
            return ['code' => '3000'];
        }
        $uid = $this->enUid($user['id']);
        $id  = $user['id'];
        unset($user['id']);
        $user['uid'] = $uid;
        $this->saveUser($id, $user);
        return ['code' => '200', 'data' => ['uid' => $uid, 'mobile' => $user['mobile']]];
    }

    public function getUser($uid) {
        $id = $this->deUid($uid);
        if ($this->redis->exists($this->redisKey . $id)) {
            $res = $this->redis->hGetAll($this->redisKey . $id);
        } else {
            $res        = DbUser::getUser(['id' => $id]);
            $res['uid'] = $this->enUid($res['id']);
            unset($res['id']);
            $this->saveUser($id, $res);
        }
        if (empty($res)) {
            return ['code' => '3000'];
        }
        unset($res['id']);
        return ['code' => 200, 'data' => $res];
    }

    /**
     * 登录
     */
    public function login() {

    }

    /**
     * 注册
     */
    public function register($openId, $password) {
        $pwd = hash_hmac('sha1', $password, 'userpass');
        return $pwd;
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
     */
    private function saveUser($id, $user) {
        $saveTime = 60;
        $this->redis->hMSet($this->redisKey . $id, $user);
        $this->redis->expireAt($this->redisKey . $id, bcadd(time(), $saveTime, 0));//设置过期
    }

    /**
     * @param $uid
     * @param $ex
     * @return int|string
     */
    private function enUid($uid, $ex = false) {
        if (strlen($uid) > 15) {
            return 0;
        }
        $iv = $this->iv;
        if ($ex !== false) {
            $iv = date('Ymd');
        }
        $uid = intval($uid);
        return $this->encrypt($uid, $iv);
    }

    /**
     * @param $enUid
     * @param bool $ex
     * @return int|string
     */
    private function deUid($enUid, $ex = false) {
        $iv = $this->iv;
        if ($ex !== false) {
            $iv = date('Ymd');
        }
        return $this->decrypt($enUid, $iv);
    }

    /**
     * 加密
     * @param $str
     * @param $iv
     * @return string
     */
    private function encrypt($str, $iv) {
        $encrypt = base64_encode(openssl_encrypt($str, $this->cryptMethod, $this->cryptKey, 0, $this->cryptIv . $iv));
        return $encrypt;
    }

    /**
     * 解密
     * @param $encrypt
     * @param $iv
     * @return int|string
     */
    private function decrypt($encrypt, $iv) {
        $decrypt = openssl_decrypt(base64_decode($encrypt), $this->cryptMethod, $this->cryptKey, 0, $this->cryptIv . $iv);
        if ($decrypt) {
            return $decrypt;
        } else {
            return 0;
        }
    }
}