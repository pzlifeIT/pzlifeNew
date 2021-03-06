<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class HdLucky extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_hd_lucky';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//注册时间
        'delete_time' => 'timestamp:Y-m-d H:i:s',//最后更新时间
        'update_time' => 'timestamp:Y-m-d H:i:s', //更新时间
    ];
    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function getImageAttr($value) {
        if (empty($value)) {
            return '';
        }
        if (stripos($value, 'http') === false) {
            return Config::get('qiniu.domain') . '/' . $value;
        }
        return $value;
    }

    public function user(){
        return $this->belongsTo('users', 'uid', 'id');
    }

}