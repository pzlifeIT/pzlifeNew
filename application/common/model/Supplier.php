<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use Config;

class Supplier extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_supplier';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//创建时间
    ];
    private $status = [1 => '启用', 2 => '停用',];

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

    public function getImageAttr($value) {
        if (stripos($value, 'http') === false) {
            return Config::get('qiniu.domain') . '/' . $value;
        }
        return $value;
    }

}