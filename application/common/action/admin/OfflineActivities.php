<?php

namespace app\common\action\admin;

use app\facade\DbOfflineActivities;
use app\facade\DbGoods;
use app\facade\DbImage;
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
        $total = DbOfflineActivities::countOfflineActivities([]);
        return ['code' => '200', 'total' => $total, 'result' => $result];
    }

    public function getOfflineActivitiesGoods($page, $pagenum, $active_id) {
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $result = DbOfflineActivities::getOfflineActivitiesGoods(['active_id' => $active_id], '*', false, ['id' => 'desc'], $offset . ',' . $pagenum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        foreach ($result as $key => $value) {
            $result['goods'] = $this->getGoods($value['goods_id']);
        }
        $total = DbOfflineActivities::countOfflineActivitiesGoods(['active_id' => $active_id]);
        return ['code' => '200', 'total' => $total, 'result' => $result];
    }

    function getGoods($goodsid) {
        /* 返回商品基本信息 （从商品库中直接查询）*/
        $where      = [["id", "=", $goodsid], ["status", "=", 1]];
        $field      = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return [];
        }
        list($goods_spec, $goods_sku) = $this->getGoodsSku($goodsid);
        if ($goods_sku) {
            foreach ($goods_sku as $goods => $sku) {

                $retail_price[$sku['id']]    = $sku['retail_price'];
                $brokerage[$sku['id']]       = $sku['brokerage'];
                $integral_active[$sku['id']] = $sku['integral_active'];
            }
            $goods_data['retail_price']        = min($retail_price);
            $goods_data['min_brokerage']       = $brokerage[array_search(min($retail_price), $retail_price)];
            $goods_data['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
        } else {
            $goods_data['min_brokerage']       = 0;
            $goods_data['min_integral_active'] = 0;
            $goods_data['retail_price']        = 0;
        }
        return $goods_data;
    }
}