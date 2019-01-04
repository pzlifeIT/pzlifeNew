<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class GoodsClass extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_goods_class';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;//关闭update_time
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//创建时间
    ];
    private $tier = [1 => '一级', 2 => '二级', 3 => '三级',];//层级
    private $status = [1 => '启用', 2 => '停用',];//1.启用  2.停用

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

//    public function getTierAttr($value) {
//        return $this->tier[$value];
//    }

    public function setTierAttr($value) {
        if (!in_array($value, $this->tier)) {
            return $value;
        }
        $tier = array_flip($this->tier);
        return $tier[$value];
    }

//    public function getStatusAttr($value) {
//        return $this->status[$value];
//    }

    public function setStatusAttr($value) {
        if (!in_array($value, $this->status)) {
            return $value;
        }
        $status = array_flip($this->status);
        return $status[$value];
    }
}