<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class MemberOrder extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_member_order';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;//关闭update_time
    protected $defaultSoftDelete = 0;
    protected $type = [
        'pay_time'    => 'timestamp:Y-m-d H:i:s',//支付时间
        'third_time'  => 'timestamp:Y-m-d H:i:s',//第三方支付时间
        'create_time' => 'timestamp:Y-m-d H:i:s',//生成订单时间
    ];
    private $userType = [1 => '钻石会员', 2 => 'boss'];
    private $payStatus = [1 => '待付款', 2 => '取消', 3 => '已关闭', 4 => '已付款'];
    private $payType = [1 => '支付宝', 2 => '微信', 3 => '银联', 4 => '线下'];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

//    public function getUserTypeAttr($value) {
//        return $this->userType[$value];
//    }

    public function setUserTypeAttr($value) {
        if (!in_array($value, $this->userType)) {
            return $value;
        }
        $userType = array_flip($this->userType);
        return $userType[$value];
    }

//    public function getPayStatusAttr($value) {
//        return $this->payStatus[$value];
//    }

    public function setPayStatusAttr($value) {
        if (!in_array($value, $this->payStatus)) {
            return $value;
        }
        $payStatus = array_flip($this->payStatus);
        return $payStatus[$value];
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