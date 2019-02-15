<?php

namespace app\common\db\order;

use app\common\model\LogPay;
use app\common\model\MemberOrder;

class DbOrder {
    public function __construct() {
    }

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
