<?php

namespace app\common\action\index;

class Order extends CommonIndex {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 创建唯一订单号
     * @author zyr
     */
    private function createOrderNo() {
        $orderNo = date('ymdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        return $orderNo;
    }
}