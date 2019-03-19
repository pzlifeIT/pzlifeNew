<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class OrderGoods extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_order_goods';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $updateTime = false;//关闭update_time
    protected $defaultSoftDelete = 0;
    private $goodsType = [1 => '普通商品', 2 => '虚拟商品'];// 1.普通(正常发货)商品 2.虚拟商品

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }
//    public function getGoodsTypeAttr($value) {
//        return $this->goodsType[$value];
//    }

    public function setGoodsTypeAttr($value) {
        if (!in_array($value, $this->goodsType)) {
            return $value;
        }
        $goodsType = array_flip($this->goodsType);
        return $goodsType[$value];
    }

    public function orderChild() {
        return $this->belongsTo('orderChild', 'order_child_id', 'id');
    }
}