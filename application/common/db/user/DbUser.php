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
}