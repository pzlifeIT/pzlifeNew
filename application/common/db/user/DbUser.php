<?php

namespace app\common\db\user;

use app\common\model\LogVercode;
use app\common\model\UserCon;
use app\common\model\Users;

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
}