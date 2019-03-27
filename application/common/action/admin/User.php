<?php

namespace app\common\action\admin;

use app\facade\DbUser;
use think\Db;

class User extends CommonIndex {
    /**
     * 会员列表
     * @return array
     * @author rzc
     */
    public function getUsers($page, $pagenum ,$mobile = '') {
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $where = [];
        if (!empty($mobile)) {
            array_push($where, ['mobile', '=', $mobile]);
        }
        $limit  = $offset . ',' . $pagenum;
        $result = DbUser::getUserInfo($where,'*', false,'id', $limit,'desc');
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $totle = DbUser::getUserInfoCount($where);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }

}