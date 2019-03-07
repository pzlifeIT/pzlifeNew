<?php

namespace app\common\db\user;

use app\common\model\LogBonus;
use app\common\model\LogVercode;
use app\common\model\UserCon;
use app\common\model\UserRecommend;
use app\common\model\UserRelation;
use app\common\model\Users;
use app\common\model\UserAddress;
use app\common\model\UserWxinfo;

class DbUser {
    /**
     * 获取一个用户信息
     * @param $where
     * @return array
     */
    public function getUser($where) {
        $field = ['passwd', 'delete_time', 'bindshop', 'balance_freeze', 'commission_freeze'];
        $user  = Users::where($where)->field($field, true)->findOrEmpty()->toArray();
        return $user;
    }

    public function getUserInfo($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = Users::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    public function getUserOne($where, $field) {
        $user = Users::where($where)->field($field)->findOrEmpty()->toArray();
        return $user;
    }

    /**
     * 获取多个用户信息
     * @param $field
     * @return array
     */
    public function getUsers($field, $order, $limit) {
        $users = Users::field($field)->order($order, 'desc')->limit($limit)->select()->toArray();
        return $users;
    }

    /**
     * 获取用户表中总记录条数
     * @return num
     */
    public function getUsersCount() {
        return Users::count();
    }

    /**
     * 添加用户
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addUser($data) {
        $user = new Users();
        $user->save($data);
        return $user->id;
    }

    /**
     * 更新用户
     * @param $data
     * @param $uid
     * @return bool
     * @author zyr
     */
    public function updateUser($data, $uid) {
        $user = new Users();
        return $user->save($data, ['id' => $uid]);
    }

    /**
     * 添加验证码日志
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addLogVercode($data) {
        $logVercode = new LogVercode();
        $logVercode->save($data);
        return $logVercode->id;
    }

    /**
     * 获取一条验证码日志
     * @param $where
     * @param $field
     * @return array
     * @author zyr
     */
    public function getOneLogVercode($where, $field) {
        return LogVercode::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 获取con_id记录
     * @param $where
     * @param $field
     * @param bool $row
     * @return array
     * @author zyr
     */
    public function getUserCon($where, $field, $row = false) {
        $obj = UserCon::where($where)->field($field);
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }

    /**
     * 添加一天con_id记录
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addUserCon($data) {
        $userCon = new UserCon();
        $userCon->save($data);
        return $userCon->id;
    }

    /**
     * 更新con_id记录
     * @param $data
     * @param $id
     * @return bool
     * @author zyr
     */
    public function updateUserCon($data, $id) {
        $userCon = new UserCon();
        return $userCon->save($data, ['id' => $id]);
    }

    /**
     * 获取openid是否已保存
     * @param $uid
     * @param $openId
     * @return float|string
     * @author zyr
     */
    public function getUserOpenidCount($uid, $openId) {
        return UserWxinfo::where(['uid' => $uid, 'openid' => $openId])->count();
    }

    /**
     * 保存openid
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function saveUserOpenid($data) {
        $userWxinfo = new UserWxinfo();
        $userWxinfo->save($data);
        return $userWxinfo->id;
    }

    /**
     * 保存地址
     * @param $data
     * @return bool
     * @author rzc
     */
    public function addUserAddress($data) {
        $userAddress = new UserAddress();
        $userAddress->save($data);
        return $userAddress->id;
    }

    /**
     * 更新地址
     * @param $data
     * @param $where
     * @return bool
     * @author rzc
     */
    public function updateUserAddress($data, $where) {
        $userAddress = new UserAddress();
        return $userAddress->save($data, $where);
    }

    /**
     * 获取用户地址
     * @param $field 字段
     * @param $where 条件
     * @param $row 查多条还是一条
     * @return array
     * @author rzc
     */
    public function getUserAddress($field, $where, $row = false) {
        $obj = UserAddress::where($where)->field($field);
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }

    public function getUserWxinfo($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = UserWxinfo::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 改商票余额
     * @param $uid
     * @param $balance
     * @param string $modify 增加/减少 inc/dec
     * @author zyr
     */
    public function modifyBalance($uid, $balance, $modify = 'dec') {
        $user          = Users::get($uid);
        $user->balance = [$modify, $balance];
        $user->save();
    }

    public function addUserRecommend($data) {
        $userRecommend = new UserRecommend();
        $userRecommend->save($data);
        return $userRecommend->id;
    }

    public function getUserRelation($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = UserRelation::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $sc, $limit);
    }

    public function addUserRelation($data) {
        $userRelation = new UserRelation();
        $userRelation->save($data);
        return $userRelation->id;
    }

    public function updateUserRelation($data, $id) {
        $userRelation = new UserRelation();
        return $userRelation->save($data, ['id' => $id]);
    }

    public function getLogBonus($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = LogBonus::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $sc, $limit);
    }

    /**
     * @param $obj
     * @param bool $row
     * @param string $orderBy
     * @param string $sc
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    private function getResult($obj, $row = false, $orderBy = '', $sc = '', $limit = '') {
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }
}