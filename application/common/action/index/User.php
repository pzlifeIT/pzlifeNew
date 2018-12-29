<?php

namespace app\common\action\index;
class User {

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
}