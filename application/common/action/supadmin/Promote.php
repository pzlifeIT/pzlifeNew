<?php

namespace app\common\action\supadmin;

use app\facade\DbGoods;
use app\facade\DbImage;
use app\facade\DbSup;
use Config;
use think\Db;

class Promote extends CommonIndex {

    /**
     * 报名列表
     * @param $promote_id
     * @param $page
     * @param $pageNum
     * @param $nick_name
     * @param $mobile
     * @param $start_time
     * @param $end_time
     * @return array
     * @author zyr
     */
    public function getSupPromoteSignUp($promote_id, $page, $pageNum, $nick_name = '', $mobile = '', $start_time = '',$end_time = '') {
        $where = [];
        array_push($where, [['id', '=', $promote_id]]);
        if (!empty($nick_name)) {
            array_push($where, [['nick_name', 'LIKE', '%'.$nick_name.'%']]);
        }
        if (!empty($mobile)) {
            array_push($where, [['mobile', '=', $mobile]]);
        }
        if (!empty($start_time)) {
            array_push($where, [['create_time', '>=', $start_time]]);
        }
        if (!empty($end_time)) {
            array_push($where, [['create_time', '<=', $end_time]]);
        }
        $promote = DbSup::getSupPromote($where, 'id', true);
        if (empty($promote)) {
            return ['code' => '3002']; //推广活动不存在
        }
        $offset = ($page - 1) * $pageNum;
        if ($offset < 0) {
            return ['code' => '200', 'suppromotesignup' => []];
        }
        $result = DbSup::getSupPromoteSignUp(['promote_id' => $promote_id], 'id,nick_name,mobile,create_time', false, ['create_time' => 'ASC'],$offset.','.$pageNum);
        $total = DbSup::getSupPromoteSignUpCount($where);
        return ['code' => '200', 'suppromotesignup' => $result];
    }

}