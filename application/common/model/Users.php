<?php

namespace app\common\model;

use think\Model;
use think\Model\concern\SoftDelete;

class Users extends Model {
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_users';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s',//注册时间
        'last_time'   => 'timestamp:Y-m-d H:i:s',//最后登录时间
        'brithday'    => 'timestamp:Y-m-d',//用户生日
    ];

    // 模型初始化
    protected static function init() {
        //TODO:初始化内容
    }

    public function getSexAttr($value) {
        $sex = [1 => '男', 2 => '女', 3 => '未确认'];
        return $sex[$value];
    }

    public function getUserIdentityAttr($value) {
        $userIdentity = [1 => '普通会员', 2 => '钻石会员', 3 => '创业店主', 4 => 'boss合伙人'];
        return $userIdentity[$value];
    }

    public function getUserTypeAttr($value) {
        $userType = [1 => '普通账户', 2 => '总店账户'];
        return $userType[$value];
    }

    /**
     * 和用户关系表一对一对应
     * @return \think\model\relation\HasOne
     * @author zyr
     */
    public function userRelation() {
        return $this->hasOne('userRelation', 'uid');
    }
}