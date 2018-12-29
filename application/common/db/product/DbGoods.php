<?php

namespace app\common\db\product;

use app\common\model\GoodsClass;

class DbGoods {

    /**
     * 根据status条件查询商品分类
     * @param $field 要获取的字段
     * @param $status where status条件
     * @return array
     */
    public function getGoodsClassByStatus($field, $status) {
        return GoodsClass::where("status", $status)->field($field)->select()->toArray();
    }

    /**
     * 获取所有商品分类
     * @param $field
     * @return array
     */
    public function getGoodsClassAll($field) {
        return GoodsClass::field($field)->select()->toArray();
    }

    /**
     * 根据where条件查询商品分类
     * @param $field
     * @param $where
     * @return array
     */
    public function getGoodsClass($field, $where) {
        return GoodsClass::where($where)->field($field)->select()->toArray();
    }
}