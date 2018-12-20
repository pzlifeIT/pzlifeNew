<?php

namespace app\common\model;

use think\Model;
use think\Model\concern\SoftDelete;

class GoodsRelation extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_goods_relation';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }
}