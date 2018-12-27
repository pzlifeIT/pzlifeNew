<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class UserCommission extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_user_commission';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'action_time' => 'timestamp:Y-m-d H:i:s',//处理时间
        'create_time' => 'timestamp:Y-m-d H:i:s',//创建时间
    ];
    private $status = [1 => '待处理', 2 => '待结算', 3 => '已结算', 4 => '取消结算'];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
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