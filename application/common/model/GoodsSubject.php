<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class GoodsSubject extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_goods_subject';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    private $status = [1 => '开启', 2 => '停用'];//1.开启 2.停用

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
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