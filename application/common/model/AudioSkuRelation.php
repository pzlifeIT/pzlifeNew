<?php

namespace app\common\model;

use think\model\Pivot;

class AudioSkuRelation extends Pivot {
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_audio_sku_relation';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;//关闭update_time
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//注册时间
    ];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }
}