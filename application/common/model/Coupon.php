<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Coupon extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_coupon';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//创建时间
        'start_time'  => 'timestamp:Y-m-d H:i:s',//有效期开始时间
        'end_time'    => 'timestamp:Y-m-d H:i:s',//有效期结束时间
    ];
    private $stype = [1 => '全场券', 2 => '商品券', 3 => '专题券'];//

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function getStypeAttr($value) {
        return $this->stype[$value];
    }

    public function setStypeAttr($value) {
        if (!in_array($value, $this->stype)) {
            return $value;
        }
        $stype = array_flip($this->stype);
        return $stype[$value];
    }
}