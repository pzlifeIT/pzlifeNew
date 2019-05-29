<?php

namespace app\common\action\index;

use app\common\model\UserRelation;
use app\common\model\Users;
use app\facade\DbGoods;
use app\facade\DbOfflineActivities;

class OfflineActivities extends CommonIndex {
    public function getOfflineActivities($id) {
        $offlineactivities = DbOfflineActivities::getOfflineActivities(['id' => $id],'*',true);
        if (empty($offlineactivities)) {
            return ['code' => '200' ,'data' => []];
        }
        if (strtotime($offlineactivities['stop_time']) < time()) {
            return ['code' => '200' ,'data' => []];
        }
        $goods = DbOfflineActivities::getOfflineActivitiesGoods(['active_id' => $id],'goods_id');
        if (!empty($goods)) {
            $goodsid = [];
            foreach ($goods as $key => $value) {
                $goodsid[] = $value['goods_id'];
            }
            $goodslist = DbGoods::getGoods('id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image','', '', [['status', '=', 1],['id', 'IN', $goodsid]]);
            if (!empty($goodslist)) {
                foreach ($goodslist as $l => $list) {
                    /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
                    $result[$key]['spec'] = $goods_spec;
                    $result[$key]['goods_sku'] = $goods_sku; */
                    $where                            = ['goods_id' => $list['id']];
                    $field                            = 'market_price';
                    $goodslist[$l]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                    $field                            = 'retail_price';
                    $goodslist[$l]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                    
                }
            }
            $offlineactivities['goods'] = $goodslist;

        }else {
            $goods = [];
        }
        return ['code' => '200','data' => $offlineactivities];
    }
}