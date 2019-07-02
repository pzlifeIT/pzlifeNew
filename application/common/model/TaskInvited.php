<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class TaskInvited extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_task_invited';
    // 设置当前模型的数据库连接
    protected $connection         = '';
    protected $deleteTime         = 'delete_time';
    protected $defaultSoftDelete  = 0;
    protected $autoWriteTimestamp = true;
    protected $type               = [
        'create_time' => 'timestamp:Y-m-d H:i:s', //创建时间
        'delete_time' => 'timestamp:Y-m-d H:i:s', //更新时间
    ];
    private $user_identity = [
        1 => '普通会员', 
        2 => '钻石会员',
        3 => '创业店主',
        4 => 'boss合伙人',
        5 => '兼职市场经理',
        6 => '兼职市场总监1升级兼职市场总监2任务',
    ];
    protected static function init() {
        //TODO:初始化内容
    }
    public function getUserIdentityAttr($value) {
        if (!array_key_exists($value, $this->user_identity)) {
            return $value;
        }
        // $sex = array_flip($this->sex);
        return $this->user_identity[$value];
    }

    public function user(){
        return $this->belongsTo('users', 'uid', 'id');
    }
}