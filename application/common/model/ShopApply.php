<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class ShopApply extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_shop_apply';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//注册时间
        'finish_time' => 'timestamp:Y-m-d H:i:s',//完成时间
        'delete_time' => 'timestamp:Y-m-d H:i:s',//删除时间
    ];
    private $status = [1 => '申请中' , 2 => '财务审核通过', 3 => '经理审核通过', 4 => '审核不通过'];// 申请进度 1.提交申请  2:财务审核通过 3:经理审核通过 4 审核不通过

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

//    public function getStatusAttr($value) {
//        return $this->status[$value];
//    }

    public function setStatusAttr($value) {
        if (!in_array($value, $this->status)) {
            return $value;
        }
        $status = array_flip($this->status);
        return $status[$value];
    }
}