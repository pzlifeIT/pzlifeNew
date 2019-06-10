<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class MessageTask extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_message_task';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//创建时间
        'delete_time' => 'timestamp:Y-m-d H:i:s',//更新时间
    ];

    protected static function init() {
        //TODO:初始化内容
    }

    public function messagetemplate(){
        return $this->belongsTo('message_template', 'mt_id', 'id');
    }

    public function messagetrigger(){
        return $this->belongsTo('trigger', 'trigger_id', 'id');
    }

}