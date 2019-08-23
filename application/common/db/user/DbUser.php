<?php

namespace app\common\db\user;

use app\common\model\LogBonus;
use app\common\model\LogIntegral;
use app\common\model\LogInvest;
use app\common\model\LogOpenboss;
use app\common\model\LogTrading;
use app\common\model\LogTransfer;
use app\common\model\LogVercode;
use app\common\model\UserAddress;
use app\common\model\UserBank;
use app\common\model\UserCon;
use app\common\model\UserRead;
use app\common\model\UserRecommend;
use app\common\model\UserRelation;
use app\common\model\Users;
use app\common\model\UserWxinfo;
use app\common\model\AirplanePassenger;
use think\Db;

class DbUser {
    /**
     * 获取一个用户信息
     * @param $where
     * @return array
     */
    public function getUser($where) {
        $field = ['passwd', 'delete_time', 'bindshop', 'balance_freeze', 'commission_freeze', 'bounty_freeze'];
        $user  = Users::where($where)->field($field, true)->findOrEmpty()->toArray();
        return $user;
    }

    public function getUserInfo($where, $field, $row = false, $orderBy = '', $limit = '', $sc = '') {
        $obj = Users::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    public function getUserInfoCount($where) {
        return Users::where($where)->count();
    }

    public function getUserOne($where, $field) {
        $user = Users::where($where)->field($field)->findOrEmpty()->toArray();
        return $user;
    }

    /**
     * 获取多个用户信息
     * @param $field
     * @return array
     */
    public function getUsers($field, $order, $limit) {
        $users = Users::field($field)->order($order, 'desc')->limit($limit)->select()->toArray();
        return $users;
    }

    /**
     * 获取用户表中总记录条数
     * @return num
     */
    public function getUsersCount() {
        return Users::count();
    }

    /**
     * 添加用户
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addUser($data) {
        $user = new Users();
        $user->save($data);
        return $user->id;
    }

    /**
     * 更新用户
     * @param $data
     * @param $uid
     * @return bool
     * @author zyr
     */
    public function updateUser($data, $uid) {
        $user = new Users();
        return $user->save($data, ['id' => $uid]);
    }

    /**
     * 添加验证码日志
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addLogVercode($data) {
        $logVercode = new LogVercode();
        $logVercode->save($data);
        return $logVercode->id;
    }

    /**
     * 获取一条验证码日志
     * @param $where
     * @param $field
     * @return array
     * @author zyr
     */
    public function getOneLogVercode($where, $field) {
        return LogVercode::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 获取con_id记录
     * @param $where
     * @param $field
     * @param bool $row
     * @return array
     * @author zyr
     */
    public function getUserCon($where, $field, $row = false) {
        $obj = UserCon::where($where)->field($field);
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }

    /**
     * 添加一天con_id记录
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addUserCon($data) {
        $userCon = new UserCon();
        $userCon->save($data);
        return $userCon->id;
    }

    /**
     * 更新con_id记录
     * @param $data
     * @param $id
     * @return bool
     * @author zyr
     */
    public function updateUserCon($data, $id) {
        $userCon = new UserCon();
        return $userCon->save($data, ['id' => $id]);
    }

    /**
     * 获取openid是否已保存
     * @param $uid
     * @param $openId
     * @return float|string
     * @author zyr
     */
    public function getUserOpenidCount($uid, $openId) {
        return UserWxinfo::where(['uid' => $uid, 'openid' => $openId])->count();
    }

    /**
     * 保存openid
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function saveUserOpenid($data) {
        $userWxinfo = new UserWxinfo();
        $userWxinfo->save($data);
        return $userWxinfo->id;
    }

    /**
     * 保存地址
     * @param $data
     * @return bool
     * @author rzc
     */
    public function addUserAddress($data) {
        $userAddress = new UserAddress();
        $userAddress->save($data);
        return $userAddress->id;
    }

    /**
     * 更新地址
     * @param $data
     * @param $where
     * @return bool
     * @author rzc
     */
    public function updateUserAddress($data, $where) {
        $userAddress = new UserAddress();
        return $userAddress->save($data, $where);
    }

    /**
     * 获取用户地址
     * @param $field 字段
     * @param $where 条件
     * @param $row 查多条还是一条
     * @return array
     * @author rzc
     */
    public function getUserAddress($field, $where, $row = false, $orderBy = '') {
        $obj = UserAddress::where($where)->field($field);
        if (!empty($orderBy)) {
            $obj = $obj->order($orderBy);
        }
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }

    public function getUserWxinfo($where, $field, $row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = UserWxinfo::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 改商券余额
     * @param $uid
     * @param $balance
     * @param string $modify 增加/减少 inc/dec
     * @author zyr
     */
    public function modifyBalance($uid, $balance, $modify = 'dec') {
        $user          = Users::get($uid);
        $user->balance = [$modify, $balance];
        $user->save();
    }

    /**
     * 改佣金余额
     * @param $uid
     * @param $commission
     * @param string $modify 增加/减少 inc/dec
     * @author zyr
     */
    public function modifyCommission($uid, $commission, $modify = 'dec') {
        $user             = Users::get($uid);
        $user->commission = [$modify, $commission];
        $user->save();
    }

    /**
     * 改积分余额
     * @param $uid
     * @param $integral
     * @param string $modify 增加/减少 inc/dec
     * @author zyr
     */
    public function modifyIntegral($uid, $integral, $modify = 'dec') {
        $user           = Users::get($uid);
        $user->integral = [$modify, $integral];
        $user->save();
    }

    /**
     * 改奖励金余额
     * @param $uid
     * @param $bounty
     * @param string $modify 增加/减少 inc/dec
     * @author zyr
     */
    public function modifyBounty($uid, $bounty, $modify = 'dec') {
        $user         = Users::get($uid);
        $user->bounty = [$modify, $bounty];
        $user->save();
    }

    public function addUserRecommend($data) {
        $userRecommend = new UserRecommend();
        $userRecommend->save($data);
        return $userRecommend->id;
    }

    public function getUserRelationCount($where) {
        return UserRelation::where($where)->count();

    }

    public function getUserChild($pid) {
        $res = UserRelation::field('uid')->where(['pid' => $pid])->select()->toArray();
        return array_column($res, 'uid');
    }

    public function getUserRelation($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = UserRelation::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function addUserRelation($data) {
        $userRelation = new UserRelation();
        $userRelation->save($data);
        return $userRelation->id;
    }

    public function updateUserRelation($data, $id = 0) {
        $userRelation = new UserRelation();
        if ($id == 0) {
            return $userRelation->saveAll($data);
        }
        return $userRelation->save($data, ['id' => $id]);
    }

    public function getLogBonus($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = LogBonus::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getLogBonusDistinct($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = LogBonus::field($field)->distinct(true)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getLogBonusGroup($where, $uid, $limit) {
        array_push($where, ['delete_time', '=', '0']);
        // $obj = LogBonus::field('level_uid,sum(result_price) as price')->where($where);
        // return $obj->group('level_uid')->limit($limit)->order('price desc')->select()->toArray();
        $subSql = Db::table('pz_log_bonus')
            ->field('level_uid,sum(result_price) as price')
            ->where($where)
            ->group('level_uid')
            ->order('price desc')
            ->buildSql();

        $parSql = Db::table('pz_user_relation')
            ->alias('ur')
            ->field('uid,w.price,w.level_uid')
            ->where([['is_boss', '=', '1'], ['pid', '=', $uid], ['delete_time', '=', '0']])
            ->leftJoin([$subSql => 'w'], 'ur.uid = w.level_uid')
            ->buildSql();
        return Db::table('pz_users')
            ->alias('u')
            ->field('uid,p.price,u.nick_name,u.avatar,u.user_identity')
            ->where([['delete_time', '=', '0']])
            ->join([$parSql => 'p'], 'u.id = p.uid')
            ->order('p.price desc,id desc')
            ->limit($limit)
            ->select();
    }

    public function getLogTrading($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = LogTrading::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getLogBonusSum($where, $field) {
        return LogBonus::where($where)->sum($field);
    }

    public function getLogTradingSum($where, $field) {
        return LogTrading::where($where)->sum($field);
    }

    public function saveLogTrading($data) {
        $LogTrading = new LogTrading();
        $LogTrading->save($data);
        return $LogTrading->id;
    }

    public function saveLogInvest($data) {
        $LogInvest = new LogInvest;
        $LogInvest->save($data);
        return $LogInvest->id;
    }

    public function editLogInvest($data, $id) {
        $LogInvest = new LogInvest;
        return $LogInvest->save($data, ['id' => $id]);
    }

    public function editLogTrading($data, $id) {
        $LogTrading = new LogTrading;
        return $LogTrading->save($data, ['id' => $id]);
    }
    public function getLogIntegral($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = LogIntegral::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
        // $where['i.delete_time'] = 0;
        // $where['o.delete_time'] = 0;
        // $obj                    = LogIntegral::alias('i')->join(['pz_orders' => 'o'], 'o.order_no=i.order_no')->field($field)->where($where);
        // return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getLogInvestSum($where, $field) {
        return LogInvest::where($where)->sum($field);
    }

    /**
     * 招商代理收益日志
     * @param $obj
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    public function getLogInvest($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = LogInvest::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * @param $obj
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    private function getResult($obj, $row = false, $orderBy = '', $limit = '') {
        if (!empty($orderBy)) {
            $obj = $obj->order($orderBy);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 获取佣金转出记录
     * @param $where
     * @param bool $field
     * @param string $row
     * @param string $orderBy
     * @param string $limit
     * @return array
     * @author rzc
     */
    public function getLogTransfer($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = LogTransfer::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }
    public function getUserRead($field, $where, $row = false, $orderBy = '', $sc = '', $limit = '') {

        $obj = UserRead::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    public function getUserReadSum($where, $field) {
        return UserRead::where($where)->sum($field);
    }

    /**
     * 佣金转出记录计数
     * @param $data
     * @param $id
     * @return mixed
     * @author rzc
     */
    public function countLogTransfer($where) {
        return LogTransfer::where($where)->count();
    }

    /**
     * 添加佣金转出记录
     * @param $data
     * @return mixed
     * @author rzc
     */
    public function addLogTransfer($data) {
        $LogTransfer = new LogTransfer;
        $LogTransfer->save($data);
        return $LogTransfer->id;
    }

    /**
     * 修改佣金转出记录
     * @param $data
     * @return mixed
     * @author rzc
     */
    public function editLogTransfer($data, $id) {
        $LogTransfer = new LogTransfer;
        return $LogTransfer->save($data, ['id' => $id]);
    }
    public function addUserRead($data) {
        $UserRead = new UserRead;
        return $UserRead->save($data);
    }

    /**
     * 获取用户银行卡信息
     * @param $where
     * @param bool $field
     * @param string $row
     * @param string $orderBy
     * @param string $limit
     * @return array
     * @author rzc
     */
    public function getUserBank($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = UserBank::field($field)->with(
            ['adminBank' => function ($query) {
                // $query->field('abbrev,bank_name')->where([]);
            },
                'users'      => function ($query2) {
                    $query2->field('id,user_identity,nick_name,avatar,mobile');
                }]
        )->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 添加银行卡信息
     * @param $data
     * @return mixed
     * @author rzc
     */

    public function saveUserBank($data) {
        $UserBank = new UserBank;
        $UserBank->save($data);
        return $UserBank->id;
    }

    /**
     * 修改用户银行卡信息
     * @param $data
     * @param $id
     * @return mixed
     * @author rzc
     */
    public function editUserBank($data, $id) {
        $UserBank = new UserBank;
        return $UserBank->save($data, ['id' => $id]);
    }

    /**
     * 删除用户银行卡信息
     * @param $id
     * @return mixed
     * @author rzc
     */
    public function delUserBank($id) {
        return UserBank::destroy($id);
    }

    /**
     * 银行卡表计数
     * @param $data
     * @param $id
     * @return mixed
     * @author rzc
     */
    public function countUserBank($where) {
        return UserBank::where($where)->count();
    }

    public function updateUserRead($data, $id) {
        $UserRead = new UserRead;
        return $UserRead->save($data, ['id' => $id]);
    }
    public function addLogIntegral($data) {
        $LogIntegral = new LogIntegral;
        $LogIntegral->save($data);
        return $LogIntegral->id;
    }

    public function addLogOpenboss($data) {
        $logOpenboss = new LogOpenboss();
        $logOpenboss->save($data);
        return $logOpenboss->id;
    }

    public function getLogOpenboss($limit, $mobile, $nickName) {
        $where = $this->getLogOpenbossWhere($mobile, $nickName);
        return Db::table('pz_log_openboss')
            ->alias('lo')
            ->field('lo.money,u.nick_name,u.mobile,a.admin_name,from_unixtime(lo.create_time) as create_time,lo.message')
            ->join(['pz_users' => 'u'], 'lo.uid=u.id')
            ->join(['pz_admin' => 'a'], 'lo.admin_id=a.id')
            ->whereOr($where)
            ->limit($limit)
            ->select();
    }

    public function getLogOpenbossCount($mobile, $nickName) {
        $where = $this->getLogOpenbossWhere($mobile, $nickName);
        return Db::table('pz_log_openboss')
            ->alias('lo')
            ->join(['pz_users' => 'u'], 'lo.uid=u.id')
            ->join(['pz_admin' => 'a'], 'lo.admin_id=a.id')
            ->whereOr($where)
            ->count();
    }

    private function getLogOpenbossWhere($mobile, $nickName) {
        $where = [];
        $map1  = [];
        $map2  = [];
        if (!empty($mobile)) {
            $map1 = [
                ['lo.status', '=', '1'],
                ['u.mobile', '=', $mobile],
            ];
        }
        if (!empty($nickName)) {
            $map2 = [
                ['lo.status', '=', '1'],
                ['u.nick_name', 'like', '%' . $nickName . '%'],
            ];
        }
        if (!empty($map1)) {
            array_push($where, $map1);
        }
        if (!empty($map2)) {
            array_push($where, $map2);
        }
        if (empty($where)) {
            $where = [
                ['lo.status', '=', '1'],
            ];
        }
        return $where;
    }

    public function getUserBusinessCircle($where, $limit) {
        array_push($where, ['delete_time', '=', '0']);
        $subSql = Db::table('pz_user_relation')
            ->field('uid,pid,relation')
            ->buildSql();
        return Db::table('pz_users')
            ->alias('u')
            ->field('uid,u.nick_name,u.avatar,u.user_identity')
            ->leftJoin([$subSql => 'p'], 'u.id = p.uid')
            ->where($where)
            ->order('id desc')
            ->limit($limit)
            ->select();
    }

    public function countUserBusinessCircle($where) {
        array_push($where, ['delete_time', '=', '0']);
        $subSql = Db::table('pz_user_relation')
            ->field('uid,pid,relation')
            ->buildSql();
        return Db::table('pz_users')
            ->alias('u')
            ->field('uid,u.nick_name,u.avatar,u.user_identity')
            ->leftJoin([$subSql => 'p'], 'u.id = p.uid')
            ->where($where)
            ->count();
    }

    public function getLogBonusGroupOrder($where, $limit) {
        array_push($where, ['delete_time', '=', '0']);
        // $obj = LogBonus::field('level_uid,sum(result_price) as price')->where($where);
        // return $obj->group('level_uid')->limit($limit)->order('price desc')->select()->toArray();
        // $subSql = Db::table('pz_log_bonus')
        //     ->field('level_uid,sum(result_price) as price')
        //     ->where($where)
        //     ->group('level_uid')
        //     ->order('price desc')
        //     ->buildSql();

        $subSql = Db::table('pz_log_bonus')
            ->field('order_no,to_uid,layer,from_uid,level_uid,sum(result_price) as price')
            ->group('order_no,to_uid,layer,from_uid,level_uid')
            ->order('price desc')
            ->buildSql();
        return Db::table('pz_users')
            ->alias('u')
            ->field('order_no,to_uid,layer,from_uid,level_uid,from_uid,p.price,u.nick_name,u.avatar,u.user_identity')
            ->leftJoin([$subSql => 'p'], 'u.id = p.from_uid')
            ->order('p.price desc,id desc')
            ->where($where)
            ->limit($limit)
            ->select();
    }

    public function sumLogBonus($where, $field) {
        array_push($where, ['delete_time', '=', '0']);
        $LogBonus = new LogBonus;
        return $LogBonus::where($where)->sum($field);
    }
    public function sumLogBonusBy($where) {
        $price = Db::query("SELECT Sum(pz_log_bonus.result_price) AS price 
        FROM pz_log_bonus LEFT JOIN 
        pz_users ON pz_log_bonus.level_uid = pz_users.id 
        WHERE pz_log_bonus.to_uid = ".$where['to_uid']." 
        AND pz_log_bonus.layer IN (".$where['layer'].") 
        AND pz_users.user_identity =  ".$where['user_identity']);
        if (empty($price)) {
            return 0;
        }
        if (empty($price[0]['price'])) {
            return 0;
        }
        return $price[0]['price'];
    }
    
    public function getAirplanePassenger($where, $field, $row = false, $orderBy = '', $limit = ''){
        $obj = AirplanePassenger::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function addAirplanePassenger($data){
        $AirplanePassenger = new AirplanePassenger;
        $AirplanePassenger->save($data);
        return $AirplanePassenger->id;
    }

    public function updateAirplanePassenger($data, $id){
        $AirplanePassenger = new AirplanePassenger;
        return $AirplanePassenger->save($data,['id' => $id]);
    }
}