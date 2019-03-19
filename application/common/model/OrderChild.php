<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class OrderChild extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_order_child';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $updateTime = false;//关闭update_time
    protected $defaultSoftDelete = 0;

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function orders() {
        return $this->belongsTo('orders', 'order_id', 'id');
    }
}