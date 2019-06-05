<?php

namespace app\common\db\shop;

use app\common\model\LogDemotion;
use app\common\model\Shops;
use app\common\model\ShopGoods;
use app\common\model\Goods;

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
    public function getShopWithGoods($where1,$where2,$field, $field2, $row = false, $orderBy = '', $sc = '', $limit = ''){
        // $obj = ShopGoods::field($field)->where($where1);
        // print_r($this->goods);die;
        $obj = ShopGoods::field($field)->with(
            ['goods' => function ($query) use ($field2, $where2) {
                $query->field($field2)->where($where2);
            }])->where($where1);
            // echo Db::getlastSQL();die;
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
     * 获取一条商品数据
     * @param $where
     * @param $field
     * @return array
     * @author wujunjie
     * 2019/1/2-16:14
     */
    public function getOneGoods($field, $where) {
        return Goods::where($where)->field($field)->findOrEmpty()->toArray();
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

    /**
     * 降级处理记录
     * @param $where
     * @param $field
     * @param bool $row
     * @param string $orderBy
     * @param string $sc
     * @param string $limit
     * @return array
     * @author zyr
     */
    public function getLogDemotion($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = LogDemotion::field($field)->where($where);
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

    public function getLogDemotionCount($where){
        return LogDemotion::where($where)->count();
    }

    /**
     * 添加店铺商品
     * @param $data 
     * @return array
     * @author rzc
     */
    public function addShopGoods($data){
        $ShopGoods = new ShopGoods;
        $ShopGoods->save($data);
        return $ShopGoods->id;
    }

    /**
     * 添加店铺
     * @param $data
     * @return array
     * @author zyr
     */
    public function addShop($data){
        $shops = new Shops;
        $shops->save($data);
        return $shops->id;
    }

    /**
     * 修改店铺商品
     * @param $data 
     * @param $id 
     * @return array
     * @author rzc
     */
    public function updateShopGoods($data,$id){
        $ShopGoods = new ShopGoods;
        $ShopGoods->save($data,['id'=>$id]);
        return $id;
    }

    /**
     * 删除店铺商品
     * @param $id 
     * @return array
     * @author rzc
     */
    public function deleteShopGoods($id){
        $ShopGoods = new ShopGoods;
        // return $ShopGoods->where('id',$id)->delete();
        return $ShopGoods->destroy($id);
    }

    /**
     * 删除店铺
     * @param $id
     * @return bool
     * @author zyr
     */
    public function deleteShop($id){
        $shop = new Shops();
        return $shop->destroy($id);
    }

    /**
     * 添加降级处理记录
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addLogDemotion($data){
        $logDemotion = new LogDemotion;
        $logDemotion->save($data);
        return $logDemotion->id;
    }
}