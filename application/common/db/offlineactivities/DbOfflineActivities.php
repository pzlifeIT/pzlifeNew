<?php

namespace app\common\db\offlineactivities;

use app\common\model\OfflineActivities;
use app\common\model\OfflineActivitiesGoods;
use app\common\model\Users;
use app\common\model\UserWxinfo;
use think\Db;

class DbOfflineActivities {
    /**
     * @param $obj
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    private function getResult($obj, $row = false, $orderBy = '', $limit = '') {
        if (!empty($orderBy)) {
            $obj = $obj->order($orderBy);
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

    public function getOfflineActivities($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = OfflineActivities::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function countOfflineActivities($where){
        return OfflineActivities::where($where)->count();
    }
}