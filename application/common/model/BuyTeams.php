<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class BuyTeams extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pz_buy_teams';
    // 设置当前模型的数据库连接
    protected $connection = '';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $updateTime = false; //关闭update_time
    protected $type = [
        'create_time' => 'timestamp:Y-m-d H:i:s', //注册时间
    ];

/*     private $sex = [1 => '男', 2 => '女', 3 => '未确认'];
private $userIdentity = [1 => '普通会员', 2 => '钻石会员', 3 => '创业店主', 4 => 'boss合伙人'];
private $userType = [1 => '普通账户', 2 => '总店账户'];
private $balanceFreeze = [1 => '冻结', 2 => '未冻结'];
private $commissionFreeze = [1 => '冻结', 2 => '未冻结'];

// 模型初始化
protected static function init()
{
//TODO:初始化内容
}

//    public function getSexAttr($value) {
//        return $this->sex[$value];
//    }

public function setSexAttr($value)
{
if (!in_array($value, $this->sex)) {
return $value;
}
$sex = array_flip($this->sex);
return $sex[$value];
}

//    public function getUserIdentityAttr($value) {
//        return $this->userIdentity[$value];
//    }

public function setUserIdentityAttr($value)
{
if (!in_array($value, $this->userIdentity)) {
return $value;
}
$userIdentity = array_flip($this->userIdentity);
return $userIdentity[$value];
}

//    public function getUserTypeAttr($value) {
//        return $this->userType[$value];
//    }

public function setUserTypeAttr($value)
{
if (!in_array($value, $this->userType)) {
return $value;
}
$userType = array_flip($this->userType);
return $userType[$value];
}

//    public function getBalanceFreezeAttr($value) {
//        return $this->balanceFreeze[$value];
//    }

public function setBalanceFreezeAttr($value)
{
if (!in_array($value, $this->balanceFreeze)) {
return $value;
}
$balanceFreeze = array_flip($this->balanceFreeze);
return $balanceFreeze[$value];
}

//    public function getCommissionFreezeAttr($value) {
//        return $this->commissionFreeze[$value];
//    }

public function setCommissionFreezeAttr($value)
{
if (!in_array($value, $this->commissionFreeze)) {
return $value;
}
<div class=""></div>
$commissionFreeze = array_flip($this->commissionFreeze);
return $commissionFreeze[$value];
}
 */
    /**
     * 和用户关系表一对一对应
     * @return \think\model\relation\HasOne
     * @author zyr
     */
    // public function userRelation()
    // {
    //     return $this->hasOne('userRelation', 'uid');
    // }
}
