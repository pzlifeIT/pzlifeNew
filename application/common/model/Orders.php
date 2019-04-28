<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Orders extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_orders';
    // 设置当前模型的数据库连接
    protected $connection         = '';
    protected $deleteTime         = 'delete_time';
    protected $defaultSoftDelete  = 0;
    protected $autoWriteTimestamp = true;
    protected $updateTime         = false; //关闭update_time
    protected $type               = [
        'create_time' => 'timestamp:Y-m-d H:i:s', //注册时间
        'third_time'  => 'timestamp:Y-m-d H:i:s', //最后登录时间
        'pay_time'    => 'timestamp:Y-m-d H:i:s', //支付时间
    ];
    private $orderStatus = [
        1  => '待付款',
        2  => '取消订单',
        3  => '已关闭',
        4  => '已付款',
        5  => '已发货',
        6  => '已收货',
        7  => '待评价',
        8  => '退款申请确认',
        9  => '退款中',
        10 => '退款成功',
    ];
    private $payType = [1 => '支付宝', 2 => '微信', 3 => '银联']; //1.支付宝 2.微信 3.银联

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

//    public function getOrderStatusAttr($value) {
    //        return $this->orderStatus[$value];
    //    }

    public function setOrderStatusAttr($value) {
        if (!in_array($value, $this->orderStatus)) {
            return $value;
        }
        $orderStatus = array_flip($this->orderStatus);
        return $orderStatus[$value];
    }

//    public function getPayTypeAttr($value) {
    //        return $this->payType[$value];
    //    }

    public function setPayTypeAttr($value) {
        if (!in_array($value, $this->payType)) {
            return $value;
        }
        $payType = array_flip($this->payType);
        return $payType[$value];
    }
}