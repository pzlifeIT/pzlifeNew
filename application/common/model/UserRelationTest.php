<?php

namespace app\common\model;

use think\Model;

class UserRelationTest extends Model {
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_user_relation_test';

    // 设置当前模型的数据库连接
    protected $connection = '';

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }
}