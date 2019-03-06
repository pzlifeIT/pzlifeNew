<?php

namespace app\common\db\order;

use app\common\model\LogBonus;
use app\common\model\LogIntegral;
use app\common\model\LogTrading;
use app\common\model\Orders;
use app\common\model\OrderChild;
use app\common\model\OrderGoods;
use app\common\model\MemberOrder;
use app\common\model\OrderExpress;
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

    public function updataOrder($data, $id) {
        $order = new Orders();
        return $order->save($data, ['id' => $id]);
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

    public function getOrderCount($where) {
        $obj = Orders::where($where);
        return $obj->count();
    }

    /**
     * 获取订单子订单信息
     * @param $where
     * @param $field
     * @return array
     */
    public function getOrderChild($field, $where, $row = false) {
        $obj = OrderChild::field($field)->where($where);
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }

    public function addOrderGoods($data) {
        $orderGoods = new OrderGoods();
        return $orderGoods->saveAll($data);
    }

    /**
     * 获取订单商品
     * @param $field
     * @param $where
     * @param $group
     * @param $distinct
     * @param $row
     * @return array
     */
    public function getOrderGoods($field, $where, $group = false, $distinct = false, $row = false) {
        $obj = OrderGoods::field($field)->where($where);

        if ($distinct === true) {
            $obj = $obj->distinct(true);
        }
        if ($group) {
            $obj = $obj->group($group);
        }
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }

    /**
     * 获取订单商品物流单分配
     * @param $field
     * @param $where
     * @param $group
     * @param $distinct
     * @param $row
     * @return array
     */
    public function getOrderExpress($field, $where, $group = false, $distinct = false, $row = false) {
        $obj = OrderExpress::field($field)->where($where);

        if ($distinct === true) {
            $obj = $obj->distinct(true);
        }
        if ($group) {
            $obj = $obj->group($group);
        }
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }

    /**
     * 新增订单商品物流单分配
     * @param $data
     * @return array
     */
    public function addOrderExpress($data) {
        $OrderExpress = new OrderExpress;
        $OrderExpress->save($data);
        return $OrderExpress->id;
    }

    public function updateOrderExpress($data, $id) {
        $OrderExpress = new OrderExpress;

        return $OrderExpress->save($data, $id);
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


    /**
     * @param $where
     * @param $field
     * @param bool $row
     * @param string $orderBy
     * @param string $sc 排序方式asc,desc
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    public function getMemberOrder($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = MemberOrder::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $sc, $limit);
    }

    /**
     * @param $where
     * @param $field
     * @param bool $row
     * @param string $orderBy
     * @param string $sc 排序方式asc,desc
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    public function getLogPay($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = LogPay::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $sc, $limit);
    }

    public function addLogPay($data) {
        $logPay = new LogPay();
        $logPay->save($data);
        return $logPay->id;
    }

    public function updateLogPay($data, $id) {
        $logPay = new LogPay();
        return $logPay->save($data, ['id' => $id]);
    }

    public function updateLogBonus($data, $where) {
        $logBonus = new LogBonus();
        return $logBonus->save($data, $where);
    }

    public function updateLogIntegral($data,$where){
        $logIntegral = new LogIntegral();
        return $logIntegral->save($data,$where);
    }

    public function addLogTrading($data) {
        $logTrading = new LogTrading();
        $logTrading->save($data);
        return $logTrading->id;
    }

    /**
     * @param $obj
     * @param bool $row
     * @param string $orderBy
     * @param string $sc
     * @param string $limit
     * @return mixed
     * @author zyr
     */
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


