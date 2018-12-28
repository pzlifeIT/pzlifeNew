<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Shops extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_shops';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'end_time'    => 'timestamp:Y-m-d H:i:s',//到期时间
        'create_time' => 'timestamp:Y-m-d H:i:s',//开店时间
    ];
    private $status = [1 => '启用', 2 => '停用', 3 => '关闭'];

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