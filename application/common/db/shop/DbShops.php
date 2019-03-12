<?php

namespace app\common\db\shop;

use app\common\model\Shops;
use app\common\model\ShopGoods;

class DbShops {
    /**
     * 获取一个店铺信息
     * @param $where
     * @param $field
     * @return array
     */
    public function getShopInfo($field, $where) {
        return Shops::field($field)->where($where)->findOrEmpty()->toArray();
    }

    public function getShops($where, $field) {
        return Shops::field($field)->where($where)->select()->toArray();
    }

    /**
     * 获取店铺商品与商品表关联
     * @param $where1 条件
     * @param $where2 条件
     * @param $field 字段
     * @param $field2 字段
     * @param $row 查多条还是一条
     * @param $orderBy 排序字段
     * @param $sc 排序方式
     * @param $limit 查询分页
     * @return array
     * @author rzc
     */
    public function getShopGoodsWithGoods($where1,$where2,$field, $field2, $row = false, $orderBy = '', $sc = '', $limit = ''){
        // $obj = ShopGoods::field($field)->where($where1);

        $obj = ShopGoods::field($field)->withJoin(
            ['Goods' => function ($query) use ($field2, $where2) {
                $query->withField($field2)->where($where2);
            },
            ])->where($where1)->select()->toArray();

        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
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

    /**
     * 获取店铺商品表信息
     * @param $where 字段
     * @param $field 条件
     * @param $row 查多条还是一条
     * @param $orderBy 排序字段
     * @param $sc 排序方式
     * @param $limit 查询分页
     * @return array
     * @author rzc
     */
    public function getShopGoods($where,$field,$row = false,$orderBy = '',$sc = '',$limit = ''){
        $obj = ShopGoods::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
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
}