<?php

namespace app\common\db\user;

use app\common\model\Users;

class DbUser {
    /**
     * 获取一个用户信息
     * @param $where
     * @return array
     */
    public function getUser($where) {
        $field = ['passwd', 'delete_time', 'bindshop', 'balance_freeze', 'commission_freeze'];
        $user = Users::where($where)->field($field, true)->findOrEmpty()->toArray();
        return $user;
    }

    /**
     * 获取多个用户信息
     * @param $field
     * @return array
     */
    public function getUsers($field, $order, $limit){
        $users = Users::field($field)->order($order,'desc')->limit($limit)->select()->toArray();
        return $users;
    }

    /**
     * 获取用户表中总记录条数
     * @return num
     */
    public function getUsersCount(){
        return Users::count();
    }
}