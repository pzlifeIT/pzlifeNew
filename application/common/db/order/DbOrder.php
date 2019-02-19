<?php

namespace app\common\db\order;

use app\common\model\Orders;
use app\common\model\OrderChild;
use app\common\model\OrderGoods;
use app\common\model\MemberOrder;
use app\common\model\LogPay;

class DbOrder {

    public function __construct() {
    }

    /**
     * 添加添加一条订单
     * @param $data
     * @return int
     */
    public function addOrder($data) {
        $order = new Orders();
        $order->save($data);
        return $order->id;
    }

    /**
     * 获取用户订单信息
     * @param $where
     * @param $field
     * @param $row
     * @param $limit
     * @return array
     */

    public function getOrder($field, $where, $row = false, $limit = false) {
        $obj = Orders::field($field)->where($where);
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->order('id', 'desc')->limit($limit)->select()->toArray();
    }

    public function addOrderChilds($data) {
        $order = new OrderChild();
        return $order->saveAll($data);
    }

    public function getOrderCount($where){
        $obj = Orders::where($where);
        return $obj->count();
    }

    /**
     * 获取订单子订单信息
     * @param $where
     * @param $field
     * @return array
     */
    public function getOrderChild($field, $where) {
        return OrderChild::field($field)->where($where)->select()->toArray();
    }

    public function addOrderGoods($data) {
        $orderGoods = new OrderGoods();
        return $orderGoods->saveAll($data);
    }

    /**
     * 获取订单商品
     * @param $where
     * @param $field
     * @return array
     */
    public function getOrderGoods($field, $where) {
        return OrderGoods::field($field)->where($where)->select()->toArray();
    }

    /**
     * 新增权益订单
     * @param $data
     * @return array
     */
    public function addMemberOrder($data) {
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
    public function updateMemberOrder($data, $where) {
        $MemberOrder = new MemberOrder;
        return $MemberOrder->save($data, $where);
    }

    /**
     * 查询权益订单
     * @param $data
     * @return array
     */
    /*     public function getMemberOrder($field,$where,$row = false,$limit = false){
            $obj = MemberOrder::field($field)->where($where);
            if ($row === true){
                return $obj->findOrEmpty()->toArray();
            }
            return $obj->order('id', 'desc')->limit($limit)->select()->toArray();
        } */


    public function getMemberOrder($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = MemberOrder::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $sc, $limit);
    }

    public function getLogPay($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = LogPay::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $sc, $limit);
    }

    public function addLogPay($data) {
        $logPay = new LogPay();
        $logPay->save($data);
        return $logPay->id;
    }

    private function getResult($obj, $row = false, $orderBy = '', $sc = '', $limit = '') {
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


