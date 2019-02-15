<?php

namespace app\common\db\order;

use app\common\model\Orders;
use app\common\model\OrderChild;
use app\common\model\OrderGoods;
use app\common\model\MemberOrder;

class DbOrder {
    /**
     * 获取用户订单信息
     * @param $where
     * @param $field
     * @param $row
     * @param $limit
     * @return array
     */
    public function getUserOrder($field,$where,$row = false,$limit = false){
        $obj = Orders::field($field)->where($where);
        if($row === true){
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->order('id', 'desc')->limit($limit)->select()->toArray();
    }

    /**
     * 获取订单子订单信息
     * @param $where
     * @param $field
     * @return array
     */
    public function getOrderChild($field,$where){
        return OrderChild::field($field)->where($where)->select()->toArray();
    }

    /**
     * 获取订单商品
     * @param $where
     * @param $field
     * @return array
     */
    public function getOrderGoods($field,$where){
        return OrderGoods::field($field)->where($where)->select()->toArray();
    }

    /**
     * 新增权益订单
     * @param $data
     * @return array
     */
    public function addMemberOrder($data){
        $MemberOrder = new MemberOrder;
        $MemberOrder->save($data);
        return $MemberOrder->id;
    }

    /**
     * 更新权益订单
     * @param $data
     * @param $where
     * @return array
     */
    public function updateMemberOrder($data,$where){
        $MemberOrder = new MemberOrder;
        return $MemberOrder->save($data,$where);
    }

    /**
     * 查询权益订单
     * @param $data
     * @return array
     */
    public function getMemberOrder($field,$where,$row = false,$limit = false){
        $obj = MemberOrder::field($field)->where($where);
        if ($row === true){
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->order('id', 'desc')->limit($limit)->select()->toArray();
    }
}