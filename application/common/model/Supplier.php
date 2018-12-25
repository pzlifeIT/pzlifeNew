<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Supplier extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_supplier';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//创建时间
    ];
    private $types = [1 => '男', 2 => '女', 3 => '未确认'];
    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }
}