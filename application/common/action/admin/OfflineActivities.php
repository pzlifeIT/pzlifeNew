<?php

namespace app\common\action\admin;

use app\facade\DbOfflineActivities;
use think\Db;

class OfflineActivities extends CommonIndex {
    /**
     * 线下活动列表
     * @return array
     * @author rzc
     */
    public function getOfflineActivities($page, $pagenum) {
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }

        $result = DbOfflineActivities::getOfflineActivities([], '*', false, ['id' => 'desc'], $offset . ',' . $pagenum);
        if (empty($result)) {
            return ['code' => 3000];
        }
        $totle = DbOfflineActivities::countOfflineActivities([]);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }

    public function getOfflineActivitiesGoods($active_id) {
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $result = DbOfflineActivities::getOfflineActivitiesGoods(['active_id' => $active_id], '*', false, ['id' => 'desc'], $offset . ',' . $pagenum);
    }

}