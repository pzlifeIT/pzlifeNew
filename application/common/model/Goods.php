<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Goods extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_goods';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//注册时间
        'update_time' => 'timestamp:Y-m-d H:i:s',//最后更新时间
    ];

    private $goodsType = [1 => '普通商品', 2 => '虚拟商品',];//1.普通(正常发货)商品 2.虚拟商品
    private $status = [1 => '上架', 2 => '下架',];//1.上架 2.下架

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function getGoodsTypeAttr($value) {
        return $this->goodsType[$value];
    }

    public function setGoodsTypeAttr($value) {
        if (!in_array($value, $this->goodsType)) {
            return $value;
        }
        $goodsType = array_flip($this->goodsType);
        return $goodsType[$value];
    }

    public function getStatusAttr($value) {
        return $this->status[$value];
    }

    public function setStatusAttr($value) {
        if (!in_array($value, $this->status)) {
            return $value;
        }
        $status = array_flip($this->status);
        return $status[$value];
    }
}