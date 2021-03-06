<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class SupplierFreightDetail extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_supplier_freight_detail';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;//关闭update_time

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function supplierFreight() {
        return $this->belongsTo('supplierFreight', 'freight_id', 'id');
    }

    public function supplierFreightArea() {
        return $this->hasMany('supplierFreightArea', 'freight_detail_id', 'id');
    }
}