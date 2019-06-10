<?php

namespace app\common\db\offlineactivities;

use app\common\model\HdLucky;
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

    public function countOfflineActivities($where) {
        return OfflineActivities::where($where)->count();
    }

    public function addOfflineActivities($data) {
        $OfflineActivities = new OfflineActivities;
        $OfflineActivities->save($data);
        return $OfflineActivities->id;
    }

    public function updateOfflineActivities($data, $id) {
        $OfflineActivities = new OfflineActivities;
        return $OfflineActivities->save($data, ['id' => $id]);
    }

    public function getOfflineActivitiesGoods($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = OfflineActivitiesGoods::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function countOfflineActivitiesGoods($where) {
        return OfflineActivitiesGoods::where($where)->count();
    }

    public function addOfflineActivitiesGoods($data) {
        $OfflineActivitiesGoods = new OfflineActivitiesGoods;
        $OfflineActivitiesGoods->save($data);
        return $OfflineActivitiesGoods->id;
    }

    public function updateOfflineActivitiesGoods($data, $id) {
        $OfflineActivitiesGoods = new OfflineActivitiesGoods;
        return $OfflineActivitiesGoods->save($data, ['id' => $id]);
    }

    public function getWinning($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = HdLucky::field($field)->with([
             'user'       => function ($query) {
                $query->field('id,nick_name,mobile');
            }
        ])->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function updateWinning($data, $id) {
        $HdLucky = new HdLucky;
        return $HdLucky->save($data, ['id' => $id]);
    }

    public function countWinning($where){
        return HdLucky::where($where)->count();
    }

    public function getHdLucky($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = HdLucky::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addHdLucky($data) {
        $hdLucky = new HdLucky();
        $hdLucky->save($data);
        return $hdLucky->id;
    }
}