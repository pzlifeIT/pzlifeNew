<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class UserBank extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_user_bank';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//注册时间
        'update_time' => 'timestamp:Y-m-d H:i:s',//最后更新时间
    ];
    private $status = [1 => '待审核', 2 => '启用', 3 => '停用', 4 => '审核不通过'];
    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }
    public function setStatusAttr($value) {
        if (!in_array($value, $this->status)) {
            return $value;
        }
        $status = array_flip($this->status);
        return $status[$value];
    }

    public function adminBank() {
        return $this->belongsTo('admin_bank', 'admin_bank_id', 'id');
    }

    public function users() {
        return $this->belongsTo('users', 'uid', 'id');
    }

    
}