<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class OlineMarketingUser extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_online_marketing_user';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;//关闭update_time
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//注册时间
    ];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    /**
     * 和用户关系表一对一对应
     * @return \think\model\relation\HasOne
     * @author zyr
     */
    // public function userRelation() {
    //     return $this->hasOne('userRelation', 'uid');
    // }
}