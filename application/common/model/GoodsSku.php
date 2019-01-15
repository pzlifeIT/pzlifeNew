<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use Config;

class GoodsSku extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_goods_sku';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'presell_start_time' => 'timestamp:Y-m-d H:i:s',//预售价开始时间
        'presll_end_time'    => 'timestamp:Y-m-d H:i:s',//预售价结束时间
        'active_start_time'  => 'timestamp:Y-m-d H:i:s',//活动价开始时间
        'active_end_time'    => 'timestamp:Y-m-d H:i:s',//活动价过期时间
    ];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function getSkuImageAttr($value) {
        if (empty($value)) {
            return '';
        }
        if (stripos($value, 'http') === false) {
            return Config::get('qiniu.domain') . '/' . $value;
        }
        return $value;
    }
}