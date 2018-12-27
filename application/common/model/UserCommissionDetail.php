<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class UserCommissionDetail extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_user_commission_detail';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'settle_time' => 'timestamp:Y-m-d H:i:s',//结算时间
    ];
    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }
}