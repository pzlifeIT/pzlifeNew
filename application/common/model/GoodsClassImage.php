<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use Config;

class GoodsClassImage extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_goods_class_image';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;//关闭update_time
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//注册时间
    ];
    private $sourceType = [1 => 'all', 2 => 'pc', 3 => 'app', 4 => 'wechat'];//1.全部 2.pc 3.app 4.微信

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

//    public function getSourceTypeAttr($value) {
//        return $this->sourceType[$value];
//    }

    public function setSourceTypeAttr($value) {
        if (!in_array($value, $this->sourceType)) {
            return $value;
        }
        $sourceType = array_flip($this->sourceType);
        return $sourceType[$value];
    }

    public function getImagePathAttr($value) {
        if (empty($value)) {
            return '';
        }
        if (stripos($value, 'http') === false) {
            return Config::get('qiniu.domain') . '/' . $value;
        }
        return $value;
    }
}