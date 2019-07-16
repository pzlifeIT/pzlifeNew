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
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;//关闭update_time
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//创建时间
        'start_time'  => 'timestamp:Y-m-d H:i:s',//有效期开始时间
        'end_time'    => 'timestamp:Y-m-d H:i:s',//有效期结束时间
    ];
    private $stype = [1 => '满减', 2 => '商品券', 3 => '抵扣'];//类型 1.满减 2.商品券 3.抵扣(全场抵扣)
    private $isSuperposition = [1 => '是', 2 => '否'];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

//    public function getStypeAttr($value) {
//        return $this->stype[$value];
//    }
    public function setStypeAttr($value) {
        if (!in_array($value, $this->stype)) {
            return $value;
        }
        $stype = array_flip($this->stype);
        return $stype[$value];
    }

//    public function getIsSuperpositionAttr($value) {
//        return $this->isSuperposition[$value];
//    }
    public function setIsSuperpositionAttr($value) {
        if (!in_array($value, $this->isSuperposition)) {
            return $value;
        }
        $isSuperposition = array_flip($this->isSuperposition);
        return $isSuperposition[$value];
    }
//    public function goodss() {
//        return $this->belongsToMany('Goods', 'app\\common\\model\\GoodsSubjectRelation','goods_id','id');
//    }
}