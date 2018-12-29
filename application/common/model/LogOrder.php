<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class LogOrder extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_member_order';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//变更时间
    ];
    private $sourceStatus = [
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
    private $arriveStatus = [
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

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function getSourceStatusAttr($value) {
        return $this->sourceStatus[$value];
    }

    public function setSourceStatusAttr($value) {
        if (!in_array($value, $this->sourceStatus)) {
            return $value;
        }
        $sourceStatus = array_flip($this->sourceStatus);
        return $sourceStatus[$value];
    }

    public function getArriveStatusAttr($value) {
        return $this->arriveStatus[$value];
    }

    public function setArriveStatusAttr($value) {
        if (!in_array($value, $this->arriveStatus)) {
            return $value;
        }
        $arriveStatus = array_flip($this->arriveStatus);
        return $arriveStatus[$value];
    }

}