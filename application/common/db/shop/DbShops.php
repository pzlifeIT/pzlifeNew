<?php

namespace app\common\db\shop;

use app\common\model\Shops;

class DbShops {
    /**
     * 获取一个店铺信息
     * @param $where
     * @param $field
     * @return array
     */
    public function getShopInfo($field, $where){
        return Shops::field($field)->where($where)->findOrEmpty()->toArray();
    }
    
}