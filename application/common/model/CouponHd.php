<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class CouponHd extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_coupon_hd';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;//关闭update_time
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//创建时间
    ];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function coupons() {
        return $this->belongsToMany('Coupon', 'app\\common\\model\\CouponHdRelation', 'coupon_id', 'coupon_hd_id');
    }
}