<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class UserTask extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_user_task';
    // 设置当前模型的数据库连接
    protected $connection         = '';
    protected $deleteTime         = 'delete_time';
    protected $defaultSoftDelete  = 0;
    protected $autoWriteTimestamp = true;
    protected $type               = [
        'start_time'  => 'timestamp:Y-m-d H:i:s', //开始时间
        'end_time'   => 'timestamp:Y-m-d H:i:s', //停止时间
        'update_time' => 'timestamp:Y-m-d H:i:s', //修改时间
    ];
    private $types = [
        1 => '创业店主升级兼职市场经理任务', 
        2 => '邀请创业店主奖励任务',
        3 => '兼职市场经理升级合伙人任务',
        4 => '推广创业店主每月满100奖励1200佣金任务',
        5 => '推广创业店主超出100位额外奖励任务',
        6 => '兼职市场总监1升级兼职市场总监2任务',
        7 => '推广付费合伙人任务',
        8 => '兼职市场总监1推广兼职市场经理奖励任务',
        9 => '兼职市场总监2推广兼职市场经理奖励任务',
    ];
    private $status = [1 => '进行中', 2 => '已完成', 3 => '任务失败', 4 => '任务中止',];
    private $bonus_status = [1 => '未结算', 2 => '已结算',];

    protected static function init() {
        //TODO:初始化内容
    }
    public function getTypeAttr($value) {
        if (!array_key_exists($value, $this->types)) {
            return $value;
        }
        // $sex = array_flip($this->sex);
        return $this->types[$value];
    }

    public function getStatusAttr($value) {
        if (!array_key_exists($value, $this->status)) {
            return $value;
        }
        // $sex = array_flip($this->sex);
        return $this->status[$value];
    }

    public function getBonusStatusAttr($value) {
        if (!array_key_exists($value, $this->bonus_status)) {
            return $value;
        }
        // $sex = array_flip($this->sex);
        return $this->bonus_status[$value];
    }
}