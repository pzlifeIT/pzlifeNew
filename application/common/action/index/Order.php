<?php

namespace app\common\action\index;

use \cache\Phpredis;

class Order {
    private $redis;

    public function __construct() {
        $this->redis = Phpredis::getConn();
    }

    /**
     * 创建唯一订单号
     * @author zyr
     */
    public function createOrderNo() {
        $orderNo = date('ymdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        return $orderNo;
    }
}