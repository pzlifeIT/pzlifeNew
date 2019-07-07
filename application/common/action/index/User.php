<?php

namespace app\common\action\index;

use app\common\action\notify\Note;
use app\facade\DbAdmin;
use app\facade\DbImage;
use app\facade\DbOrder;
use app\facade\DbProvinces;
use app\facade\DbRights;
use app\facade\DbUser;
use Config;
use Env;
use think\Db;

class User extends CommonIndex {
    private $cipherUserKey = 'userpass'; //用户密码加密key
    // private $userRedisKey = 'index:user:'; //用户密码加密key
    private $note;

    public function __construct() {
        parent::__construct();
        $this->note = new Note();
    }

    /**
     * 账号密码登录
     * @param $mobile
     * @param $password
     * @param $buid
     * @return array
     * @author zyr
     */
    public function login($mobile, $password, $buid) {
        $user = DbUser::getUserOne(['mobile' => $mobile], 'id,passwd');
        if (empty($user)) {
            return ['code' => '3002'];
        }
        $uid = $user['id'];
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey); //加密后的password
        if ($cipherPassword != $user['passwd']) {
            return ['code' => '3003'];
        }
        $conId          = $this->createConId();
        $userCon        = DbUser::getUserCon(['uid' => $uid], 'id,con_id', true);
        $userRelationId = 0;
        if ($buid != 1) { //不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $isBoss        = $bUserRelation['is_boss'] ?? 3; //推荐人是否是boss
            if ($isBoss == 1) {
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) { //总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }
        Db::startTrans();
        try {
            if (empty($userCon)) { //第一次登录，未生成过con_id
                $data = [
                    'uid'    => $uid,
                    'con_id' => $conId,
                ];
                DbUser::addUserCon($data);
            } else {
                DbUser::updateUserCon(['con_id' => $conId], $userCon['id']);
                $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
                $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
            }
            if (!empty($userRelationId)) {
                DbUser::updateUserRelation(['relation' => $buid . ',' . $uid, 'pid' => $buid], $userRelationId);
            }
            DbUser::updateUser(['last_time' => time()], $uid);
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
            }
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3004'];

        }
    }

    /**
     * 快捷登录
     * @param $mobile
     * @param $vercode
     * @param $code
     * @param $encrypteddata
     * @param $iv
     * @param $platform
     * @param $buid
     * @return array
     * @author zyr
     */
    public function quickLogin($mobile, $vercode, $code, $encrypteddata, $iv, $platform, $buid) {
        $stype = 3;
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006']; //验证码错误
        }
        $wxInfo = getOpenid($code, $encrypteddata, $iv);
        if ($wxInfo === false) {
            return ['code' => '3002'];
        }
        $updateData = [];
        $addData    = [];
        $uid        = $this->checkAccount($mobile); //通过手机号获取uid
        if (empty($uid)) { //该手机未注册过
            if (empty($wxInfo['unionid'])) {
                return ['code' => '3000'];
            }
            $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id,mobile'); //手机号获取不到就通过微信获取
            if (!empty($user)) { //注册了微信的老用户
                if (!empty($user['mobile'])) { //该微信号已绑定
                    return ['code' => '3009'];
                }
                $uid        = $user['id'];
                $updateData = [
                    'mobile' => $mobile,
                ];
            } else { //新用户
                if (empty($wxInfo['nickname'])) {
                    return ['code' => 3005];
                }
                $addData = [
                    'mobile'    => $mobile,
                    'unionid'   => $wxInfo['unionid'],
                    'nick_name' => $wxInfo['nickname'],
                    'avatar'    => $wxInfo['avatarurl'], //$wxInfo['unionid'],
                ];
            }
        }
        $userCon = [];
        if (!empty($uid)) {
            $userCon = DbUser::getUserCon(['uid' => $uid], 'id,con_id', true);
        }
        $isBoss         = 3;
        $userRelationId = 0;
        $relationRes    = '';
        if ($buid != 1) { //不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $relationRes   = $bUserRelation['relation'] ?? '';
            $isBoss        = $bUserRelation['is_boss'] ?? 3; //推荐人是否是boss
            if (!empty($uid) && $isBoss == 1) { //不是个新用户
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) { //总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }
        Db::startTrans();
        try {
            $conId = $this->createConId();
            if (!empty($userRelationId)) {
                DbUser::updateUserRelation(['relation' => $buid . ',' . $uid, 'pid' => $buid], $userRelationId);
            }
            if (!empty($updateData)) {
                DbUser::updateUser($updateData, $uid);
            } else if (!empty($addData)) {
                $uid = DbUser::addUser($addData); //添加后生成的uid
                DbUser::addUserRecommend(['uid' => $uid, 'pid' => $buid]);
                if ($isBoss == 1) {
                    $relation = $buid . ',' . $uid;
                } else if ($isBoss == 3) {
                    $relation = $uid;
                } else {
                    $relation = $relationRes . ',' . $uid;
                }
                DbUser::addUserRelation(['uid' => $uid, 'pid' => $buid, 'relation' => $relation]);
            }
            if (!empty($userCon)) {
                DbUser::updateUserCon(['con_id' => $conId], $userCon['id']);
            } else {
                DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            }
            if (!empty($userCon)) {
                $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
                $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
            }
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype);
            DbUser::updateUser(['last_time' => time()], $uid);
            $this->saveOpenid($uid, $wxInfo['openid'], $platform);
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            Db::table('pz_log_error')->insert(['title' => '/user/quickLogin/quickLogin', 'data' => $e]);
            return ['code' => '3007'];
        }
    }

    /**
     * 新用户注册
     * @param $mobile
     * @param $vercode
     * @param $password
     * @param $code
     * @param $encrypteddata
     * @param $iv
     * @param $platform
     * @param $buid
     * @return array
     * @author zyr
     */
    public function register($mobile, $vercode, $password, $code, $encrypteddata, $iv, $platform, $buid) {
        $stype = 1;
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006']; //验证码错误
        }
        if (!empty($this->checkAccount($mobile))) {
            return ['code' => '3008'];
        }
        $wxInfo = getOpenid($code, $encrypteddata, $iv);
        if ($wxInfo === false) {
            return ['code' => '3002'];
        }
        $uid = 0;
        if (empty($wxInfo['unionid'])) {
            return ['code' => '3000'];
        }
        $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id,mobile');
        if (!empty($user)) {
            if (!empty($user['mobile'])) { //该微信号已绑定
                return ['code' => '3009'];
            }
            $uid = $user['id'];
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey); //加密后的password
        if (empty($wxInfo['nickname'])) {
            return ['code' => 3005];
        }
        $data = [
            'mobile'    => $mobile,
            'passwd'    => $cipherPassword,
            'unionid'   => $wxInfo['unionid'],
            'nick_name' => $wxInfo['nickname'],
            'avatar'    => $wxInfo['avatarurl'],
            'last_time' => time(),
        ];
        $isBoss         = 3;
        $userRelationId = 0;
        $relationRes    = '';
        if ($buid != 1) { //不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $relationRes   = $bUserRelation['relation'];
            $isBoss        = $bUserRelation['is_boss'] ?? 3; //推荐人是否是boss
            if (!empty($uid) && $isBoss == 1) { //不是哥新用户
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) { //总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }
        Db::startTrans();
        try {
            if (empty($uid)) { //新用户,直接添加
                $uid = DbUser::addUser($data); //添加后生成的uid
                DbUser::addUserRecommend(['uid' => $uid, 'pid' => $buid]);
                if ($isBoss == 1) {
                    $relation = $buid . ',' . $uid;
                } else if ($isBoss == 3) {
                    $relation = $uid;
                } else {
                    $relation = $relationRes . ',' . $uid;
                }
                DbUser::addUserRelation(['uid' => $uid, 'pid' => $buid, 'relation' => $relation]);
            } else { //老版本用户
                DbUser::updateUser($data, $uid);
                if (!empty($userRelationId)) {
                    DbUser::updateUserRelation(['relation' => $buid . ',' . $uid, 'pid' => $buid], $userRelationId);
                }
            }
            $conId = $this->createConId();
            DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
            $this->saveOpenid($uid, $wxInfo['openid'], $platform);
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007'];
        }
    }

    /**
     * 重置密码
     * @param $mobile
     * @param $vercode
     * @param $password
     * @return array
     * @author zyr
     */
    public function resetPassword($mobile, $vercode, $password) {
        $stype = 2;
        $uid   = $this->checkAccount($mobile);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006']; //验证码错误
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey); //加密后的password
        $result         = DbUser::updateUser(['passwd' => $cipherPassword], $uid);
        if ($result) {
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
            return ['code' => '200'];
        }
        return ['code' => '3003'];
    }

    /**
     * 微信登录
     * @param $code
     * @param $platform
     * @param $buid
     * @return array
     * @author zyr
     */
    public function loginUserByWx($code, $platform, $buid) {
        if ($platform == 2) {
            $wxaccess_token = $this->getaccessToken($code);
            if ($wxaccess_token == false) {
                return ['code' => '3004'];
            }
            $wxInfo = $this->getunionid($wxaccess_token['openid'], $wxaccess_token['access_token']);
        } else {
            $wxInfo = getOpenid($code);
        }
        if ($wxInfo === false) {
            return ['code' => '3001'];
        }
        if (empty($wxInfo['unionid'])) {
            return ['code' => '3000'];
        }
        $user = DbUser::getUser(['unionid' => $wxInfo['unionid']]);
        if (empty($user) || empty($user['mobile'])) {
            return ['code' => '3000'];
        }
        $uid = enUid($user['id']);
        $id  = $user['id'];
        unset($user['id']);
        $user['uid'] = $uid;
        // $this->saveUser($id, $user); //用户信息保存在缓存
        if (empty($user['mobile'])) {
            return ['code' => '3002'];
        }
        $userRelationId = 0;
        if ($buid != 1) { //不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $isBoss        = $bUserRelation['is_boss'] ?? 3; //推荐人是否是boss
            if ($isBoss == 1) {
                $userRelation = DbUser::getUserRelation(['uid' => $id], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $id) { //总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }
        $conId   = $this->createConId();
        $userCon = DbUser::getUserCon(['uid' => $id], 'id,con_id', true);
        Db::startTrans();
        try {
            if (!empty($userRelationId)) {
                DbUser::updateUserRelation(['relation' => $buid . ',' . $id, 'pid' => $buid], $userRelationId);
            }
            if (empty($userCon)) {
                DbUser::addUserCon(['uid' => $id, 'con_id' => $conId]);
            } else {
                DbUser::updateUserCon(['con_id' => $conId], $userCon['id']);
            }
            DbUser::updateUser(['last_time' => time()], $id);
            $this->saveOpenid($id, $wxInfo['openid'], $platform);
            if (!empty($userCon)) {
                $this->redis->hDel($this->redisConIdUid, $userCon['con_id']);
                $this->redis->zDelete($this->redisConIdTime, $userCon['con_id']);
            }
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $id);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
            }
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3003'];
        }
    }

    /**
     * boss店铺管理
     * @param $conId
     * @return array
     * @author zyr
     */
    public function getBossShop($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'user_identity,balance,balance_freeze,commission,commission_freeze,integral');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] < 3) {
            return ['code' => '3004']; //不是boss
        }
        // $redisKey = Config::get('rediskey.user.redisUserNextLevelCount') . $uid;
        // if ($this->redis->exists($redisKey)) {
        //     $data = json_decode($this->redis->get($redisKey), true);
        // } else {
        // $toMonth    = strtotime(date('Y-m-01')); //当月的开始时间
        // $preMonth   = strtotime(date('Y-m-01', strtotime('-1 month'))); //上月的开始时间
        // $threeMonth = strtotime(date('Y-m-01', strtotime('-3 month'))); //近三个月
        $balance    = $user['balance_freeze'] == '1' ? 0 : $user['balance']; //商券余额
        $commission = $user['commission_freeze'] == '1' ? 0 : $user['commission']; //佣金余额
        $integral   = $user['integral']; //积分余额
        $balanceUse = abs(DbUser::getLogTradingSum([
            ['trading_type', '=', '1'], //商券交易
            ['change_type', 'in', [1, 2]], //消费和取消订单退还商券
            ['uid', '=', $uid],
            // ['create_time', '>=', $threeMonth], //近三个月
        ], 'money')); //已用商券总额
        $balanceAll = DbUser::getLogTradingSum([
            ['trading_type', '=', '1'],
            ['change_type', 'in', [3, 4, 5, 7, 8, 11]],
            ['money', '>', 0],
            ['uid', '=', $uid],
        ], 'money'); //商券总额
        $noBbonus = DbUser::getLogBonusSum([
            'to_uid'        => $uid,
            'user_identity' => 4, //boss身份获得的收益
            'status'        => 1, //待结算的
            // 'bonus_type'    => 2, //经营性收益
        ], 'result_price'); //未到账

        $bonus = DbUser::getLogBonusSum([
            'to_uid'        => $uid,
            'user_identity' => 4, //boss身份获得的收益
            'status'        => 2, //已结算的
            // 'bonus_type'    => 2, //经营性收益
        ], 'result_price'); //全部返利(已入账)
        // $merchants = DbUser::getLogTradingSum([
        //     ['trading_type', '=', '2'],
        //     ['change_type', '=', 5],
        //     ['uid', '=', $uid],
        //     // ['create_time', '>=', $threeMonth], //近三个月
        // ], 'money'); //招商加盟收益
        $merchants = DbUser::getLogInvestSum([
            ['uid', '=', $uid],
            ['status', '=', 3],
        ], 'cost'); //招商加盟收益
        $commissionAll = DbUser::getLogTradingSum([
            ['trading_type', '=', '2'],
            ['change_type', 'in', [3, 4, 5, 8, 11, 12 , 13]],
            ['money', '>', 0],
            ['uid', '=', $uid],
        ], 'money'); //商券总额
        $data = [
            'balance_all'    => $balanceAll, //商券总额
            'balance'        => $balance, //商券余额
            'commission'     => $commission, //佣金余额
            'commission_all' => $commissionAll, //佣金总额
            'integral'       => $integral, //积分余额
            'balance_use'    => $balanceUse, //已使用商券
            'no_bonus'       => $noBbonus, //未到账
            'bonus'          => $bonus, //已到账返利
            'bonus_all'      => bcadd($noBbonus, $bonus, 2),
            'merchants'      => $merchants, //招商加盟收益
        ];
        //     $this->redis->setEx($redisKey, 120, json_encode($data));
        // }
        return ['code' => '200', 'data' => $data];
    }

    /**
     * 获取分利列表信息
     * @param $conId
     * @param $status
     * @param $stype
     * @param $page
     * @param $pageNum
     * @param $year
     * @param $month
     * @return array
     * @author zyr
     */
    public function getUserBonus($conId, $status, $stype, $page, $pageNum, $year, $month) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') { //只有boss才能查看
            return ['code' => '3000']; //普通用户没有权限查看
        }
        $ct = 0;
        if (!empty($year)) {
            $month = $month ?: '01';
            $ct    = strtotime(date($year . '-' . $month . '-01')); //当月的开始时间
        }
        // $threeMonth = strtotime(date('Y-m-01', strtotime('-3 month'))); //近三个月
        $en = '=';
        $bn = '2';
        switch ($stype) {
        case 1:
            $en = '=';
            break;
        case 2:
            $en = '<>';
            break;
        case 3:
            $en = '<>';
            $bn = '3';
            break;
        }
        $where = [
            ['to_uid', '=', $uid],
            ['status', '=', $status],
            ['user_identity', '=', 4], //只查boss身份时的分利
            ['create_time', '>=', $ct],
            ['from_uid', $en, $uid],
            ['bonus_type', '=', $bn],
        ];
        $offset = ($page - 1) * $pageNum;
        if ($stype == 3) {
            $bonusGroup = DbUser::getLogBonusGroup($where, $uid, $offset . ',' . $pageNum);
            $result     = [];
            foreach ($bonusGroup as $ku => $u) {
                $arr = [
                    'from_uid'      => enUid($u['uid']),
                    'result_price'  => $u['price'] ?: 0,
                    'status'        => $status == 2 ? '已结算' : '待结算',
                    'order_no'      => '',
                    'create_time'   => 0,
                    'user_identity' => $u['user_identity'],
                    'nick_name'     => $u['nick_name'],
                    'avatar'        => $u['avatar'],
                ];
                array_push($result, $arr);
            }
            // $last = array_column($result, 'result_price');
            // array_multisort($last, SORT_DESC, $result);
            $combined = DbUser::getLogBonusSum($where, 'result_price'); //合计
            return ['code' => '200', 'data' => $result, 'combined' => $combined];
        }

        $field    = 'from_uid,to_uid,result_price,order_no,status,create_time';
        $distinct = DbUser::getLogBonusDistinct($where, 'order_no', false, 'order_no desc', $offset . ',' . $pageNum);
        $distinct = array_column($distinct, 'order_no');
        $where2   = $where;
        $where2[] = ['order_no', 'in', $distinct];
        $bonus    = DbUser::getLogBonus($where2, $field, false, 'create_time desc');
        $combined = DbUser::getLogBonusSum($where, 'result_price'); //合计
        $userList = DbUser::getUserInfo([['id', 'in', array_unique(array_column($bonus, 'from_uid'))]], 'id,nick_name,avatar,user_identity');
        $userList = array_combine(array_column($userList, 'id'), $userList);
        // print_r($bonus);die;
        // $orderNoList = array_unique(array_column($bonus, 'order_no'));
        $result = [];
        foreach ($bonus as $b) {
            $keyy = $b['order_no'] . $b['from_uid'];
            if (!key_exists($keyy, $result)) {
                $b['nick_name']     = $userList[$b['from_uid']]['nick_name'];
                $b['avatar']        = $userList[$b['from_uid']]['avatar'];
                $b['user_identity'] = $userList[$b['from_uid']]['user_identity'];
                $b['from_uid']      = enUid($b['from_uid']);
                unset($b['to_uid']);
                $b['status']   = $b['status'] == 2 ? '已结算' : '待结算';
                $result[$keyy] = $b;
                continue;
            }
            $result[$keyy]['result_price'] = bcadd($b['result_price'], $result[$keyy]['result_price'], 2);
        }
        if (empty($result)) {
            return ['code' => '3000', 'data' => []]; //没有分利
        }
        $result = array_values($result);
        return ['code' => '200', 'data' => $result, 'combined' => $combined];
    }

    /**
     * 个人中心佣金明细
     * @param $conId
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getShopCommission($conId, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        $offset = ($page - 1) * $pageNum;
        $where  = [
            ['trading_type', '=', '2'], //佣金交易
            ['change_type', 'in', [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]], //1.消费 2.取消订单退还 3.充值 4.层级分利 5.购买会员分利 6.提现 7.转商券 8.后台充值操作 9.后台开通boss预扣款 10.审核不通过退回 11.老商城转入
            ['uid', '=', $uid],
        ];
        $field  = 'change_type,money,create_time,message';
        $data   = DbUser::getLogTrading($where, $field, false, 'id desc', $offset . ',' . $pageNum);
        $result = [];
        foreach ($data as $d) {
            $trType = $d['change_type'];
            switch ($trType) {
            case 3:
                $ctype = '充值';
                break;
            case 4:
                $ctype = '订单收益';
                break;
            case 5:
                $ctype = '招商代理收益';
                break;
            case 6:
                $ctype = '提现';
                break;
            case 7:
                $ctype = '转商券';
                break;
            case 8:
                $ctype = '后台充值操作';
                break;
            case 9:
                $ctype = '开通boss预扣款';
                break;
            case 10:
                $ctype = '提现审核不通过退回';
                break;
            case 11:
                $ctype = '老商城转入';
                break;
            case 12:
                $ctype = '奖励金转入';
                break;
            case 13:
                $ctype = '市场任务奖励';
                break;
            }
            $d['ctype'] = empty($d['message']) ? $ctype : $d['message'];
            unset($d['message']);
            array_push($result, $d);
        }
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 个人中心佣金统计
     * @param $conId
     * @return array
     * @author zyr
     */
    public function getShopCommissionSum($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity,commission,commission_freeze');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        $commission = $user['commission']; //佣金余额
        if ($user['commission_freeze'] == 1) { //佣金冻结
            $commission = 0;
        }
        $commissionAll = DbUser::getLogTradingSum([
            ['trading_type', '=', '2'],
            ['change_type', 'in', [3, 4, 5, 8, 11, 12 , 13]],
            ['money', '>', 0],
            ['uid', '=', $uid],
        ], 'money'); //佣金总额
        $commissionExtract = DbUser::getLogTradingSum([
            ['trading_type', '=', '2'],
            ['change_type', 'in', [6, 10]],
            ['uid', '=', $uid],
        ], 'money'); //佣金提现
        $commissionToBalance = DbUser::getLogTradingSum([
            ['trading_type', '=', '2'],
            ['change_type', '=', 7],
            ['uid', '=', $uid],
        ], 'money'); //佣金转商券
        return ['code' => '200', 'commission' => $commission, 'commission_all' => $commissionAll, 'commission_extract' => abs($commissionExtract), 'commission_to_balance' => abs($commissionToBalance)];
    }

    /**
     * 获取店铺商券明细
     * @param $conId
     * @param $stype
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getShopBalance($conId, $stype, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        // if ($user['user_identity'] == '1') { //普通用户
        //     return ['code' => '3000']; //普通用户没有权限查看
        // }
        $offset = ($page - 1) * $pageNum;
        // $threeMonth = strtotime(date('Y-m-01', strtotime('-3 month'))); //近三个月
        $data = [];
        if ($stype == 1) { //已使用
            $where = [
                ['trading_type', '=', '1'], //商券交易
                ['change_type', 'in', [1, 2]], //1.消费 2.取消订单退还 3.充值 4.层级分利 5.购买会员分利 6.提现 7.转商券
                ['uid', '=', $uid],
                // ['create_time', '>=', $threeMonth], //近三个月
            ];
            $field = 'change_type,order_no,money,create_time,message';
            $data  = DbUser::getLogTrading($where, $field, false, 'id desc', $offset . ',' . $pageNum);
        }
        if ($stype == 3) { //余额明细
            $where = [
                ['trading_type', '=', '1'], //商券交易
                ['change_type', 'in', [1, 2, 3, 4, 5, 7, 8, 11]], //1.消费 2.取消订单退还 3.充值 4.层级分利 5.购买会员分利 6.提现 7.转商券 8.后台充值操作
                ['uid', '=', $uid],
                // ['create_time', '>=', $threeMonth], //近三个月
            ];
            $field = 'change_type,order_no,money,create_time,message';
            $data  = DbUser::getLogTrading($where, $field, false, 'id desc', $offset . ',' . $pageNum);
        }
        if ($stype == 2) { //未结算商券
            $data = DbUser::getLogBonus([
                ['user_identity', '=', 2], //只查商券
                ['status', '=', '1'], //待结算(未到账的)
                ['to_uid', '=', $uid],
                // ['create_time', '>=', $threeMonth], //近三个月
            ], 'order_no,result_price as money,create_time', false, 'id desc', $offset . ',' . $pageNum); //未结算商券
        }
        if ($stype == 4) { //总额明细
            $where = [
                ['trading_type', '=', '1'], //商券交易
                ['change_type', 'in', [3, 4, 5, 8, 7, 11]], //1.消费 2.取消订单退还 3.充值 4.层级分利 5.购买会员分利 6.提现 7.转商券 8.后台充值操作
                ['money', '>', 0],
                ['uid', '=', $uid],
                // ['create_time', '>=', $threeMonth], //近三个月
            ];
            $field = 'change_type,order_no,money,create_time,message';
            $data  = DbUser::getLogTrading($where, $field, false, 'id desc', $offset . ',' . $pageNum);
        }
        $result = [];
        // print_r($data);die;
        foreach ($data as $d) {
            $trType = $d['change_type'] ?? 4;
            switch ($trType) {
            case 1:
                $ctype = '已使用商券';
                break;
            case 2:
                $ctype = '订单取消商券退回';
                break;
            case 4:
                $ctype = '钻石再让利';
                break;
            case 5:
                $ctype = '钻石会员邀请奖励';
                break;
            case 7:
                $ctype = '佣金转入';
                break;
            case 8:
                $ctype = '后台充值操作';
                break;
            }
            $d['ctype'] = empty($d['message']) ? $ctype : $d['message'];
            unset($d['change_type']);
            array_push($result, $d);
        }
        // print_r($data);die;
        return ['code' => '200', 'data' => $result];
        //商券退款  已使用商券   钻石会员邀请奖励  钻石返利
    }

    /**
     * 个人中心我的商券
     * @param {type}
     * @return array
     * @author zyr
     */
    public function getShopBalanceSum($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity,balance,balance_freeze');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        $balance = $user['balance']; //商券余额
        if ($user['balance_freeze'] == 1) { //商券冻结
            $balance = 0;
        }
        $balanceUse = abs(DbUser::getLogTradingSum([
            ['trading_type', '=', '1'], //商券交易
            ['change_type', 'in', [1, 2]], //消费和取消订单退还商券
            ['uid', '=', $uid],
        ], 'money')); //已用商券
        $noBbonus = DbUser::getLogBonusSum([
            'to_uid'     => $uid,
            'bonus_type' => 1, //返利
            'status'     => 1, //待结算的
            // 'bonus_type'    => 2, //经营性收益
        ], 'result_price'); //待到账商券
        $balanceAll = DbUser::getLogTradingSum([
            ['trading_type', '=', '1'],
            ['change_type', 'in', [3, 4, 5, 7, 8, 11]],
            ['money', '>', 0],
            ['uid', '=', $uid],
        ], 'money'); //商券总额
        return ['code' => '200', 'balance' => $balance, 'balanceUse' => $balanceUse, 'balanceAll' => $balanceAll, 'noBbonus' => $noBbonus];
    }

    /**
     * 用户社交圈统计
     * @param $conId
     * @return array
     * @author zyr
     */
    public function getUserSocialSum($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {
            return ['code' => '3000']; //boss才有权限查看
        }
        // $diamonUserList = DbUser::getUserRelation([['pid', '=', $uid], ['is_boss', '=', '2']], 'uid');
        // $diamonUidList  = array_column($diamonUserList, 'uid'); //第一层会员圈
        // $diamondRing    = DbUser::getUserInfoCount([['id', 'in', $diamonUidList], ['user_identity', '=', '2']]); //第一层钻石会员

        // // $userRing  = bcsub(count($diamonUidList), $diamondRing, 0); //第一层普通会员圈人数
        // // $nextCount = $this->getUsersNext($diamonUidList);
        // // $userCount = bcadd($nextCount, $userRing);
        // // $allCount  = bcadd($userCount, $diamondRing);
        // // $allCount  = DbUser::getUserRelationCount([['relation', 'like', '%,' . $uid . ',%'], ['is_boss', '=', '2']]);
        // $userCount = bcsub($allCount, $diamondRing, 2);
        // return ['code' => '200', 'diamon_count' => $diamondRing, 'user_count' => $userCount, 'all_user' => $allCount];

        $readCount    = DbUser::getUserReadSum([['view_uid', '=', $uid]], 'read_count');
        $grantCount   = DbUser::getUserReadSum([['view_uid', '=', $uid], ['nick_name', '<>', '']], 'read_count');
        $userRelation = DbUser::getUserRelation([['relation', 'like', $uid . ',%']], 'relation');
        $reg          = [];
        foreach ($userRelation as $ur) {
            $rel    = substr($ur['relation'], strlen($uid . ','));
            $uidArr = explode(',', $rel);
            $reg    = array_merge($reg, $uidArr);
        }
        $regCount = count(array_unique($reg));
        return ['code' => '200', 'read_count' => $readCount, 'grant_count' => $grantCount, 'reg_count' => $regCount];
    }

    public function getRead($conId, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {
            return ['code' => '3000']; //boss才有权限查看
        }
        $offset = ($page - 1) * $pageNum;
        $grant  = DbUser::getUserRead('nick_name,avatar', [['view_uid', '=', $uid], ['nick_name', '<>', '']], false, 'id', 'desc', $offset . ',' . $pageNum);
        return ['code' => '200', 'data' => $grant];
    }

    /**
     * 批量获取用户下的人数
     * @param $uids
     * @return int
     * @author zyr
     */
    private function getUsersNext($uids) {
        $count = 0;
        while (true) {
            $uList = DbUser::getUserRelation([['pid', 'in', $uids], ['is_boss', '=', '2']], 'uid');
            if (empty($uList)) {
                break;
            }
            $count += count($uList);
            $uids = array_column($uList, 'uid');
        }
        return $count;
    }

    /**
     * 社交圈
     * @param $conId
     * @param $stype
     * @param $page
     * @param $pageNum
     * @return array
     */
    public function getUserSocial($conId, $stype, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {
            return ['code' => '3000']; //boss才有权限查看
        }
        $offset         = ($page - 1) * $pageNum;
        $diamonUserList = DbUser::getUserRelation([['pid', '=', $uid], ['is_boss', '=', '2']], 'uid', false, 'uid desc'); //查询直属boss下的所有人
        if (empty($diamonUserList)) {
            return ['code' => '3000', 'data' => []];
        }
        $diamonUidList = array_column($diamonUserList, 'uid'); //boss下的所有人(包括钻石和普通)
        if ($stype == 1) { //钻石会员圈
            $diamonUserCount = DbUser::getUserInfoCount([['id', 'in', $diamonUidList], ['user_identity', '=', '2']]); //直接钻石会员数量
            $uDiamon         = DbUser::getUserInfo([['id', 'in', array_column($diamonUserList, 'uid')], ['user_identity', '=', '2']], 'id'); //直接钻石会员
            $uDiamonDiff     = array_column(DbUser::getUserRelation([['pid', 'in', array_column($uDiamon, 'id')], ['is_boss', '=', '2']], 'uid'), 'uid'); //第二级钻石会员
            $socialCountAll  = DbUser::getUserInfoCount([['id', 'in', $uDiamonDiff], ['user_identity', '=', '2']]);
            $diamondRing     = DbUser::getUserInfo([['id', 'in', $diamonUidList], ['user_identity', '=', '2']], 'id,nick_name,avatar', false, 'id desc', $offset . ',' . $pageNum); //查直属的钻石会员
            $diamondUids     = array_column($diamondRing, 'id');
            $diamondUsers    = DbUser::getUserRelation([['pid', 'in', $diamondUids], ['is_boss', '=', '2']], 'pid,uid'); //boss直属钻石的直属钻石
            $diamondK        = array_column($diamondUsers, 'pid', 'uid');
            $diamondInfo     = DbUser::getUserInfo([['id', 'in', array_column($diamondUsers, 'uid')], ['user_identity', '=', 2]], 'id'); //直属钻石圈
            $userCount       = [];
            foreach ($diamondUids as $du) {
                $userList = DbUser::getUserRelation([['relation', 'like', '%,' . $du . ',%']], 'relation');
                $uidList  = [];
                foreach ($userList as $ul) {
                    $ul['relation'] = substr($ul['relation'], bcadd(stripos($ul['relation'], ',' . $du . ','), strlen(',' . $du . ','), 0));
                    $uidList        = array_merge($uidList, explode(',', $ul['relation']));
                }
                $uidList        = array_values(array_unique($uidList));
                $userCount[$du] = count($uidList);
            }
            $diamondCount = [];
            foreach ($diamondInfo as $di) {
                $pid = $diamondK[$di['id']];
                if (!key_exists($pid, $diamondCount)) {
                    $diamondCount[$pid] = 0;
                }
                $diamondCount[$pid] += 1;
            }
            $data = [];
            foreach ($diamondRing as $dr) {
                $dr['diamond_count'] = $diamondCount[$dr['id']] ?? 0; //钻石会员圈
                $dr['social_count']  = $userCount[$dr['id']] ?? 0; //社交圈
                // $socialCountAll += $dr['diamond_count'];
                $dr['id'] = enUid($dr['id']);
                array_push($data, $dr);
            }
            return ['code' => '200', 'data' => $data, 'diamon_user_count' => $diamonUserCount, 'social_count_all' => $socialCountAll];
        }
        if ($stype == 2) { //买主圈
            $userRingCount = DbUser::getUserInfoCount([['id', 'in', $diamonUidList], ['user_identity', '=', '1']]);
            $userRing      = DbUser::getUserInfo([['id', 'in', $diamonUidList], ['user_identity', '=', '1']], 'id,nick_name,avatar', false, 'id desc', $offset . ',' . $pageNum); //查直属的普通会员
            $userUids      = array_column($userRing, 'id');

            $userNextList = DbUser::getUserRelation([['pid', 'in', $userUids]], 'pid,uid');
            $userK        = array_column($userNextList, 'pid', 'uid');
            $uCount       = [];
            $data         = [];
            foreach ($userNextList as $dr) {
                $pid = $userK[$dr['uid']];
                if (!key_exists($pid, $uCount)) {
                    $uCount[$pid] = 0;
                }
                $uCount[$pid] += 1;
            }
            foreach ($userRing as $ur) {
                $ur['social_count'] = $uCount[$ur['id']] ?? 0;
                $ur['id']           = enUid($ur['id']);
                array_push($data, $ur);
            }

            // $userCount = [];
            // foreach ($userUids as $du) {
            //     $userList = DbUser::getUserRelation([['relation', 'like', '%' . $du . ',%']], 'relation');
            //     $uidList  = [];
            //     foreach ($userList as $ul) {
            //         $ul['relation'] = substr($ul['relation'], bcadd(stripos($ul['relation'], $du . ','), strlen($du . ','), 0));
            //         $uidList        = array_merge($uidList, explode(',', $ul['relation']));
            //     }
            //     $uidList        = array_values(array_unique($uidList));
            //     $userCount[$du] = count($uidList);
            // }
            // $data = [];
            // foreach ($userRing as $dr) {
            //     $dr['social_count'] = $userCount[$dr['id']] ?? 0;
            //     $dr['id']           = enUid($dr['id']);
            //     array_push($data, $dr);
            // }
            return ['code' => '200', 'data' => $data, 'user_ring_count' => $userRingCount];
        }
    }

    /**
     * 招商代理收益
     * @param $conId
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getMerchants($conId, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {
            return ['code' => '3000']; //boss才有权限查看
        }
        // $threeMonth = strtotime(date('Y-m-01', strtotime('-3 month'))); //近三个月
        $offset = ($page - 1) * $pageNum;
        // $memberUse = DbUser::getLogTrading([
        //     ['trading_type', '=', '2'], //佣金交易
        //     ['change_type', '=', 5], //消费和取消订单退还商券
        //     ['uid', '=', $uid],
        //     // ['create_time', '>=', $threeMonth], //近三个月
        // ], 'money,order_no,create_time', false, 'id desc', $offset . ',' . $pageNum); //已用商券总额
        $memberUse = DbUser::getLogInvest([
            ['uid', '=', $uid],
            ['status', '=', '3'],
        ], 'cost as money,order_no,create_time,target_uid', false, 'id desc', $offset . ',' . $pageNum);
        if (empty($memberUse)) {
            return ['code' => '3000', 'data' => []];
        }
        // $buyUidList     = DbOrder::getMemberOrder([['order_no', 'in', array_column($memberUse, 'order_no')]], 'uid,order_no');
        // $orderNoUid     = array_column($buyUidList, 'uid', 'order_no');
        // $buyUidList     = array_column($buyUidList, 'uid');
        $buyUidList     = array_column($memberUse, 'target_uid');
        $users          = DbUser::getUserInfo([['id', 'in', $buyUidList]], 'id,nick_name,avatar');
        $userNameList   = array_column($users, 'nick_name', 'id');
        $userAvatarList = array_column($users, 'avatar', 'id');
        $data           = [];
        foreach ($memberUse as $mu) {
            $mu['nick_name'] = $userNameList[$mu['target_uid']];
            $mu['avatar']    = $userAvatarList[$mu['target_uid']];
            $mu['uid']       = enUid($mu['target_uid']);
            unset($mu['target_uid']);
            array_push($data, $mu);
        }
        return ['code' => '200', 'data' => $data];
    }

    /**
     * 其他收益
     * @param $conId
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getOtherEarn($conId, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] <= '2') {
            return ['code' => '3000']; //boss才有权限查看
        }
        $where = [
            ['trading_type', '=', '2'], //佣金交易
            ['change_type', 'in', [3, 6, 7, 8, 9, 13]], //1.消费 2.取消订单退还 3.充值 4.层级分利 5.购买会员分利 6.提现 7.转商券 8.后台充值操作 9.后台开通boss预扣款
            ['uid', '=', $uid],
        ];
        $field  = 'change_type,money,create_time,message';
        $offset = ($page - 1) * $pageNum;
        $data   = DbUser::getLogTrading($where, $field, false, 'id desc', $offset . ',' . $pageNum);
        $result = [];
        foreach ($data as $d) {
            $trType = $d['change_type'];
            switch ($trType) {
            case 3:
                $ctype = '充值';
                break;
            case 6:
                $ctype = '提现';
                break;
            case 7:
                $ctype = '转商券';
                break;
            case 8:
                $ctype = '后台充值操作';
                break;
            }
            $d['message'] = empty($d['message']) ? $ctype : $d['message'];
            unset($d['change_type']);
            array_push($result, $d);
        }
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 积分明细
     * @param $conId
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getIntegralDetail($conId, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        // if ($user['user_identity'] != '4') {
        //     return ['code' => '3000']; //boss才有权限查看
        // }
        $offset = ($page - 1) * $pageNum;
        $data   = [];
        $result = DbUser::getLogIntegral(['uid' => $uid, 'status' => 2], 'stype,result_integral,create_time', false, 'id desc', $offset . ',' . $pageNum);
        foreach ($result as $d) {
            $trType = $d['stype'] ?? 1;
            switch ($trType) {
            case 1:
                $ctype = '购物积分';
                break;
            case 2:
                $ctype = '后台充值';
                break;
            case 3:
                $ctype = '老商城转入积分';
                break;
            }
            $d['ctype'] = $ctype;
            unset($d['stype']);
            array_push($data, $d);
        }
        return ['code' => '200', 'data' => $data];
    }

    /**
     * 获取所有下级关系网
     * @param $conId
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getUserNextLevel($conId, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $offset   = $pageNum * ($page - 1) . ',' . $pageNum;
        $redisKey = Config::get('rediskey.user.redisUserNextLevel') . $uid;
        if ($this->redis->exists($redisKey)) {
            $uidList = json_decode($this->redis->get($redisKey), true);
        } else {
            $userList = DbUser::getUserRelation([['relation', 'like', '%' . $uid . ',%']], 'relation');
            $uidList  = [];
            foreach ($userList as $ul) {
                $ul['relation'] = substr($ul['relation'], bcadd(stripos($ul['relation'], $uid . ','), strlen($uid . ','), 0));
                $uidList        = array_merge($uidList, explode(',', $ul['relation']));
            }
            $uidList = array_values(array_unique($uidList));
            $this->redis->setEx($redisKey, 600, json_encode($uidList));
        }
        $result = DbUser::getUserInfo([['id', 'in', $uidList], ['user_identity', '<>', '4']], 'id,user_identity,nick_name,avatar', false, 'id asc', $offset);
        foreach ($result as &$r) {
            $r['uid'] = enUid($r['id']);
            unset($r['id']);
        }
        unset($r);
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 保存openid
     * @param $uid
     * @param $openId
     * @param $platform
     * @author zyr
     */
    private function saveOpenid($uid, $openId, $platform) {
        $userCount = DbUser::getUserOpenidCount($uid, $openId);
        if ($userCount == 0) {
            $data = [
                'uid'         => $uid,
                'openid'      => $openId,
                'platform'    => $platform,
                'openid_type' => Config::get('conf.platform_conf')[Config::get('app.deploy')],
            ];
            DbUser::saveUserOpenid($data);
        }
    }

    /**
     * 验证用户是否存在
     * @param $mobile
     * @return bool
     * @author zyr
     */
    private function checkAccount($mobile) {
        $user = DbUser::getUserOne(['mobile' => $mobile], 'id');
        if (!empty($user)) {
            return $user['id'];
        }
        return 0;
    }

    /**
     * 生成并发送验证码
     * @param $mobile
     * @param $stype
     * @return array
     * @author zyr
     */
    public function sendVercode($mobile, $stype) {
        $redisKey   = $this->redisKey . 'vercode:' . $mobile . ':' . $stype;
        $timeoutKey = $this->redisKey . 'vercode:timeout:' . $mobile . ':' . $stype;
        $code       = $this->createVercode($redisKey, $timeoutKey);
        if (empty($code)) { //已发送过验证码
            return ['code' => '3003']; //一分钟内不能重复发送
        }
        if ($stype == 5) {
            $content = getVercodeContent($code, 5); //短信内容
        } else {
            $content = getVercodeContent($code); //短信内容
        }
        $result = $this->note->sendSms($mobile, $content); //发送短信
        if ($result['code'] != '200') {
            $this->redis->del($timeoutKey);
            $this->redis->del($redisKey);
            return ['code' => '3004']; //短信发送失败
        }
        DbUser::addLogVercode(['stype' => $stype, 'code' => $code, 'mobile' => $mobile]);
        return ['code' => '200'];
    }

    /**
     * 验证提交的验证码是否正确
     * @param $stype
     * @param $mobile
     * @param $vercode
     * @return bool
     * @author zyr
     */
    private function checkVercode($stype, $mobile, $vercode) {
        $redisKey  = $this->redisKey . 'vercode:' . $mobile . ':' . $stype;
        $redisCode = $this->redis->get($redisKey); //服务器保存的验证码
        if ($redisCode == $vercode) {
            return true;
        }
        return false;
    }

    /**
     * 生成并保存验证码
     * @param $redisKey
     * @param $timeoutKey
     * @return string
     * @author zyr
     */
    private function createVercode($redisKey, $timeoutKey) {
        if (!$this->redis->setNx($timeoutKey, 1)) {
            return '0'; //一分钟内不能重复发送
        }
        $this->redis->setTimeout($timeoutKey, 60); //60秒自动过期
        $code = randCaptcha(6); //生成验证码
        if ($this->redis->setEx($redisKey, 600, $code)) { //不重新发送酒10分钟过期
            return $code;
        }
        return '0';
    }

    public function indexMain($conId, $buid) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userRelationId = 0;
        if ($buid != 1) { //不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $isBoss        = $bUserRelation['is_boss'] ?? 3; //推荐人是否是boss
            if ($isBoss == 1) {
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) { //总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }
        if (!empty($userRelationId)) {
            DbUser::updateUserRelation(['relation' => $buid . ',' . $uid, 'pid' => $buid], $userRelationId);
        }
        return ['code' => 200];
    }

    /**
     * 获取用户信息
     * @param $conId
     * @return array
     * @author zyr
     */
    public function getUser($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        if ($this->redis->exists($this->redisKey . 'userinfo:' . $uid)) {
            $res = $this->redis->hGetAll($this->redisKey . 'userinfo:' . $uid);
        } else {
            $res = DbUser::getUser(['id' => $uid]);
            if (empty($res)) {
                return ['code' => '3000'];
            }
            $res['uid'] = enUid($res['id']);
            unset($res['id']);
            $this->saveUser($uid, $res);
        }
        if (empty($res)) {
            return ['code' => '3000'];
        }
        unset($res['id']);
        return ['code' => 200, 'data' => $res];
    }

    /**
     * @return array
     */
    public function getBoss() {
        $userRelation = UserRelation::where('uid', '=', $this->uid)->field('pid,is_boss,relation')->findOrEmpty()->toArray();
        $relation     = explode(',', $userRelation['relation']);

        $this->getIdentity($userRelation['pid']);
//        print_r($relation);die;
        $boss = $relation[0];
        return ['pid' => $userRelation['pid'], 'is_boss' => $userRelation['is_boss'], 'boss' => $boss];
    }

    /**
     * 获取用户身份
     * @param $uid
     * @return bool
     */
    public function getIdentity($uid) {
        $user = Users::where('id', '=', $uid)->field('user_identity')->findOrEmpty()->toArray();
        if (empty($user)) {
            return false;
        }
        return $user['user_identity'];
    }

    /**
     * 保存用户信息(记录到缓存)
     * @param $id
     * @param $user
     * @author zyr
     */
    private function saveUser($id, $user) {
        $saveTime = 300; //保存5分钟
        $this->redis->hMSet($this->redisKey . 'userinfo:' . $id, $user);
        $this->redis->expireAt($this->redisKey . 'userinfo:' . $id, bcadd(time(), $saveTime, 0)); //设置过期
    }

    /**
     * 添加新地址
     * @param $conId
     * @param $province_name
     * @param $city_name
     * @param $area_name
     * @param $address
     * @param $mobile
     * @param $name
     * @return array
     * @author rzc
     */
    public function addUserAddress($conId, $province_name, $city_name, $area_name, $address, $mobile, $name) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3009', 'msg' => 'uid为空'];
        }
        if (empty($address)) {
            return ['code' => '3007', 'msg' => '请填写详细街道地址'];
        }
        if (empty($name)) {
            return ['code' => '3010', 'msg' => '请填写收货人姓名'];
        }
        /* 判断省市区ID是否合法 */
        $field    = 'id,area_name,pid,level';
        $where    = ['area_name' => $province_name];
        $province = DbProvinces::getAreaOne($field, $where);

        if (empty($province) || $province['level'] != '1') {
            return ['code' => '3006', 'msg' => '错误的省份名称'];
        }
        // $field = 'id,area_name,pid,level';
        $where = ['area_name' => $city_name, 'level' => 2];
        $city  = DbProvinces::getAreaOne($field, $where);
        if (empty($city)) {
            return ['code' => '3004', 'msg' => '错误的市级名称'];
        }
        // $field = 'id,area_name,pid,level';
        $where = ['area_name' => $area_name];
        $area  = DbProvinces::getAreaOne($field, $where);
        if (empty($area) || $area['level'] != '3') {
            return ['code' => '3005', 'msg' => '错误的区级名称'];
        }

        $data                = [];
        $data['uid']         = $uid;
        $data['province_id'] = $province['id'];
        $data['city_id']     = $city['id'];
        $data['area_id']     = $area['id'];
        $data['address']     = $address;
        $data['mobile']      = $mobile;
        $data['name']        = $name;
        $data['default']     = 2;
        $add                 = DbUser::addUserAddress($data);
        if ($add) {
            return ['code' => '200', 'msg' => '添加成功', 'id' => $add];
        } else {
            return ['code' => '3008', 'msg' => '添加失败'];
        }
    }

    /**
     * 修改地址
     * @param $conId
     * @param $province_name
     * @param $city_name
     * @param $area_name
     * @param $address
     * @param $mobile
     * @param $name
     * @param $address_id
     * @return array
     * @author rzc
     */
    public function updateUserAddress($conId, $province_name, $city_name, $area_name, $address, $name, $mobile, $address_id) {
        $uid        = $this->getUidByConId($conId);
        $field      = 'id,uid';
        $where      = ['id' => $address_id, 'uid' => $uid];
        $is_address = DbUser::getUserAddress($field, $where, true);
        if (!$is_address) {
            return ['code' => '3010', 'msg' => '无效的address_id'];
        }
        if (empty($uid)) {
            return ['code' => '3009', 'msg' => 'uid为空'];
        }
        if (empty($address)) {
            return ['code' => '3007', 'msg' => '请填写详细街道地址'];
        }
        /* 判断省市区ID是否合法 */
        $field    = 'id,area_name,pid,level';
        $where    = ['area_name' => $province_name];
        $province = DbProvinces::getAreaOne($field, $where);

        if (empty($province) || $province['level'] != '1') {
            return ['code' => '3006', 'msg' => '错误的省份名称'];
        }
        $field = 'id,area_name,pid,level';
        $where = ['area_name' => $city_name, 'level' => 2];
        $city  = DbProvinces::getAreaOne($field, $where);
        if (empty($city)) {
            return ['code' => '3004', 'msg' => '错误的市级名称'];
        }
        $field = 'id,area_name,pid,level';
        $where = ['area_name' => $area_name];
        $area  = DbProvinces::getAreaOne($field, $where);
        if (empty($area) || $area['level'] != '3') {
            return ['code' => '3005', 'msg' => '错误的区级名称'];
        }
        $data                = [];
        $data['province_id'] = $province['id'];
        $data['city_id']     = $city['id'];
        $data['area_id']     = $area['id'];
        $data['address']     = $address;
        $data['mobile']      = $mobile;
        $data['name']        = $name;
        DbUser::updateUserAddress($data, ['id' => $address_id]);
        return ['code' => 200, 'msg' => '修改成功'];
    }

    /**
     * 查询用户地址
     * @param $conId
     * @param $address_id
     * @author rzc
     */
    public function getUserAddress($conId, $address_id = false) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $field = 'id,uid,province_id,city_id,area_id,address,default,name,mobile';
        $where = ['uid' => $uid];

        /* 查询一条用户地址详细信息 */
        if ($address_id) {
            $where  = ['uid' => $uid, 'id' => $address_id];
            $result = DbUser::getUserAddress($field, $where, true);
            if (empty($result)) {
                return ['code' => 3000];
            }

            // $field = 'id,area_name,pid,level';
            // $where = ['id' => $city_id];
            $result['province_name'] = DbProvinces::getAreaOne('*', ['id' => $result['province_id']])['area_name'];
            $result['city_name']     = DbProvinces::getAreaOne('*', ['id' => $result['city_id'], 'level' => 2])['area_name'];
            $result['area_name']     = DbProvinces::getAreaOne('*', ['id' => $result['area_id']])['area_name'];

            return ['code' => 200, 'data' => $result];
        }

        $result = DbUser::getUserAddress($field, $where);
        if (empty($result)) {
            return ['code' => 3000];
        }
        foreach ($result as $key => $value) {
            $result[$key]['province_name'] = DbProvinces::getAreaOne('*', ['id' => $value['province_id']])['area_name'];
            $result[$key]['city_name']     = DbProvinces::getAreaOne('*', ['id' => $value['city_id'], 'level' => 2])['area_name'];
            $result[$key]['area_name']     = DbProvinces::getAreaOne('*', ['id' => $value['area_id']])['area_name'];
        }
        return ['code' => 200, 'data' => $result];
    }

    /**
     * 设置用户默认地址
     * @param $conId
     * @param $address_id
     * @author rzc
     */
    public function updateUserAddressDefault($conId, $address_id) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3004'];
        }
        $field  = 'id,uid,province_id,city_id,area_id,address,default,name,mobile';
        $where  = ['uid' => $uid, 'id' => $address_id];
        $result = DbUser::getUserAddress($field, $where, true);
        if (empty($result)) {
            return ['code' => 3005, 'msg' => '该地址不存在，无法设为默认']; /*  */
        }

        DbUser::updateUserAddress(['default' => 2], ['uid' => $uid]);
        DbUser::updateUserAddress(['default' => 1], $where);
        return ['code' => 200, 'msg' => '修改默认成功'];

    }

    /**
     * 密码加密
     * @param $str
     * @param $key
     * @return string
     * @author zyr
     */
    private function getPassword($str, $key) {
        $algo   = Config::get('conf.cipher_algo');
        $md5    = hash_hmac('md5', $str, $key);
        $key2   = strrev($key);
        $result = hash_hmac($algo, $md5, $key2);
        return $result;
    }

    /**
     * 创建唯一conId
     * @author zyr
     */
    private function createConId() {
        $conId = uniqid(date('ymdHis'));
        $conId = hash_hmac('ripemd128', $conId, '');
        return $conId;
    }

    /**
     * 生成二维码
     * @param $link
     * @return string
     * @author rzc
     */
    public function getQrcode($conId, $page, $scene, $stype) {
        $uid    = $this->getUidByConId($conId);
        $Upload = new Upload;
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        // 先查询是否有已存在图片
        $has_QrImage = DbImage::getUserImage('*', ['uid' => $uid, 'stype' => $stype], true);
        if (!empty($has_QrImage)) {
            $Qrcode = $has_QrImage['image'];
            return ['code' => '200', 'Qrcode' => $Qrcode];
        }
        $result = $this->createQrcode($scene, $page);
        // print_r(strlen($result));die;
        // print_r (imagecreatefromstring($result));die;
        if (strlen($result) > 100) {
            // $img_file = 'd:/test.png';
            $file = fopen(Config::get('conf.image_path') . $conId . '.png', "w"); //打开文件准备写入
            fwrite($file, $result); //写入
            fclose($file); //关闭
            // 开始上传,调用上传方法
            $upload = $Upload->uploadUserImage($conId . '.png');
            if ($upload['code'] == 200) {
                $logImage = DbImage::getLogImage($upload, 2); //判断时候有未完成的图片
                // print_r($logImage);die;
                if (empty($logImage)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                $upUserInfo          = [];
                $upUserInfo['uid']   = $uid;
                $upUserInfo['stype'] = $stype;
                $upUserInfo['image'] = $upload['image_path'];
                Db::startTrans();
                try {
                    $save = DbImage::saveUserImage($upUserInfo);
                    if (!$save) {
                        return ['code' => '3011'];
                    }
                    DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
                    $new_Qrcode = Config::get('qiniu.domain') . '/' . $upload['image_path'];
                    Db::commit();
                    return ['code' => '200', 'Qrcode' => $new_Qrcode];
                } catch (\Exception $e) {
                    print_r($e);
                    Db::rollback();
                    return ['code' => '3011']; //添加失败
                }
            } else {
                return ['code' => '3009'];
            }
            // echo $result;die;
        } else {
            $result = json_decode($result,true);
            return ['code' => $result['errcode'],'errmsg' => $result['errmsg']];

        }
    }

    function sendRequest2($requestUrl, $data = []) {
        $curl = curl_init();
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Content-Length:' . strlen($data)]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    public function createQrcode($scene, $page) {
        $access_token = $this->getWeiXinAccessToken();
        if (!$access_token) {
            return ['code' => '3005'];
        }
        $requestUrl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;
        // print_r($link);die;
        $result = $this->sendRequest2($requestUrl, ['scene' => $scene, 'page' => $page]);
        return $result;
    }

    /**
     * 生成二维码
     * @param $conId
     * @return string
     * @author rzc
     */
    public function getUserOrderCount($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $obligation = DbOrder::getOrderCount(['order_status' => 1, 'uid' => $uid]); //待付款
        $deliver    = DbOrder::getOrderCount(['order_status' => 4, 'uid' => $uid]); //待发货
        $receive    = DbOrder::getOrderCount(['order_status' => 5, 'uid' => $uid]); //待收货
        $rating     = DbOrder::getOrderCount(['order_status' => 6, 'uid' => $uid]); //已收货
        return ['code' => '200', 'obligation' => $obligation, 'deliver' => $deliver, 'receive' => $receive, 'rating' => $rating];
    }

    /**
     * 用户转商券
     * @param $conId
     * @param $money
     * @param $type 1.佣金转商券,2.奖励金转商券
     * @return string
     * @author rzc
     */
    public function commissionTransferBalance($conId, $money, $type = '') {
        $userRedisKey = Config::get('rediskey.user.redisKey');
        $uid          = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'id,user_identity,nick_name,balance,commission,bounty', true);
        if (empty($userInfo)) {
            return ['code' => '3000'];
        }
        if ($type == 1) { //1.佣金转商券
            if ($userInfo['commission'] <= 0) {
                return ['code' => '3005'];
            }
            if ($userInfo['commission'] - $money < 0) {
                return ['code' => '3005'];
            }
        } elseif ($type == 2) { //2.奖励金转商券
            if ($userInfo['bounty'] <= 0) {
                return ['code' => '3005'];
            }
            if ($userInfo['bounty'] - $money < 0) {
                return ['code' => '3005'];
            }
        }

        $transfer           = [];
        $transfer['uid']    = $uid;
        $transfer['status'] = 2;
        if ($type == 1) { //1.佣金转商券
            $transfer['stype'] = 1;
        } elseif ($type == 2) { //2.奖励金转商券
            $transfer['stype'] = 3;
        }
        $transfer['wtype']      = 4;
        $transfer['money']      = $money;
        $transfer['proportion'] = 0;
        $transfer['invoice']    = 2;
        //扣除佣金
        if ($type == 1) { //1.佣金转商券
            $tradingData = [
                'uid'          => $uid,
                'trading_type' => 2,
                'change_type'  => 7,
                'money'        => -$money,
                'befor_money'  => $userInfo['commission'],
                'after_money'  => bcsub($userInfo['commission'], $money, 2),
                // 'message'      => $remittance['message'],
            ];
        } elseif ($type == 2) { //2.奖励金转商券
            $tradingData = [
                'uid'          => $uid,
                'trading_type' => 3,
                'change_type'  => 7,
                'money'        => -$money,
                'befor_money'  => $userInfo['bounty'],
                'after_money'  => bcsub($userInfo['bounty'], $money, 2),
                // 'message'      => $remittance['message'],
            ];
        }

        //增加商券日志
        // if ($type == 2) {
        //     // $money = bcmul($money, 1.25, 2);
        //     $addtrading = [
        //         'uid'          => $uid,
        //         'trading_type' => 1,
        //         'change_type'  => 7,
        //         'money'        => bcmul($money, 1.25, 2),
        //         'befor_money'  => $userInfo['balance'],
        //         'after_money'  => bcadd($userInfo['balance'], bcmul($money, 1.25, 2), 2),
        //         // 'message'      => $remittance['message'],
        //     ];
        // } else {

        // }
        $addtrading = [
            'uid'          => $uid,
            'trading_type' => 1,
            'change_type'  => 7,
            'money'        => $money,
            'befor_money'  => $userInfo['balance'],
            'after_money'  => bcadd($userInfo['balance'], $money, 2),
            // 'message'      => $remittance['message'],
        ];

        if ($type == 2) {
            $addtrading['change_type'] = 12;
        }
        // print_r($addtrading);die;
        Db::startTrans();
        try {
            DbUser::addLogTransfer($transfer);
            if ($type == 1) { //1.佣金转商券
                DbUser::modifyCommission($uid, $money);
            } elseif ($type == 2) { //2.奖励金转商券
                // $money = bcdiv($money, 1.25, 2);
                DbUser::modifyBounty($uid, $money);
            }
            DbUser::saveLogTrading($tradingData);
            DbUser::saveLogTrading($addtrading);
            // if ($type == 2) {
            //     DbUser::modifyBalance($uid, bcmul($money, 1.25, 2), 'inc');
            // } else {
            DbUser::modifyBalance($uid, $money, 'inc');
            // }

            Db::commit();
            $this->redis->del($userRedisKey . 'userinfo:' . $uid);
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3006']; //添加失败
        }

    }

    /**
     * 用户绑卡
     * @param $conId
     * @param $user_name
     * @param $bank_mobile
     * @param $bank_card
     * @param $bank_key_id
     * @param $bank_add
     * @param $vercode
     * @return string
     * @author rzc
     */
    public function addUserBankcard($conId, $user_name, $bank_mobile, $bank_card, $bank_key_id, $bank_add, $vercode, $bankcard_message) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $stype = 4;
        if ($this->checkVercode($stype, $bank_mobile, $vercode) === false) {
            return ['code' => '3003']; //验证码错误
        }
        $admin_bank   = DbAdmin::getAdminBank(['id' => $bank_key_id, 'status' => 1], 'abbrev', true);
        $is_bank_card = DbUser::getUserBank(['uid' => $uid, 'bank_card' => $bank_card], '*', true);
        if ($is_bank_card) {
            return ['code' => '3012', 'msg' => '该银行卡号已添加'];
        }
        $user_bank_num = DbUser::countUserBank(['uid' => $uid]);
        if ($user_bank_num + 1 > 10) {
            return ['code' => '3011', 'msg' => '超出添加范围(最多10张)'];
        }
        if (empty($admin_bank)) {
            return ['code' => '3009'];
        }
        // $this_card = $this->getBancardKey($bank_card);
        if ($bankcard_message['bank'] != $admin_bank['abbrev']) {
            return ['code' => '3010'];
        }
        $userBank                  = [];
        $userBank['uid']           = $uid;
        $userBank['user_name']     = $user_name;
        $userBank['admin_bank_id'] = $bank_key_id;
        $userBank['bank_card']     = $bank_card;
        $userBank['bank_add']      = $bank_add;
        $userBank['bank_mobile']   = $bank_mobile;
        $userBank['status']        = 1;
        Db::startTrans();
        try {
            $addid = DbUser::saveUserBank($userBank);
            Db::commit();
            return ['code' => '200', 'id' => $addid];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3006']; //添加失败
        }

    }

    /**
     * 获取用户绑卡记录或者详情
     * @param $conId
     * @param $id
     * @return string
     * @author rzc
     */
    public function getUserBankcards($conId, $id = '', $is_transfer = '') {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        if (!empty($id)) {
            $user_bank = DbUser::getUserBank(['uid' => $uid, 'id' => $id], '*', true);
            if (empty($user_bank)) {
                return ['code' => '3000'];
            }
            // print_r($user_bank);die;
            if ($user_bank['error_fields']) {
                $error_fields = explode(',', $user_bank['error_fields']);
                $new_fields   = [];
                foreach ($error_fields as $error => $fields) {
                    $new_fields[$fields] = 1;
                }
                $user_bank['error_fields'] = $new_fields;
            }
            return ['code' => '200', 'user_bank' => $user_bank];
        }
        $where = [];
        array_push($where, ['uid', '=', $uid]);
        if ($is_transfer) {
            array_push($where, ['status', 'in', '2,4']);
        }
        $user_bank = DbUser::getUserBank($where, '*');
        if (empty($user_bank)) {
            return ['code' => '200', 'user_bank' => []];
        }
        return ['code' => '200', 'user_bank' => $user_bank];
    }

    /**
     * 用户修改银行卡信息
     * @param $id
     * @param $conId
     * @param $user_name
     * @param $bank_mobile
     * @param $bank_card
     * @param $bank_key_id
     * @param $bank_add
     * @param $vercode
     * @param $bankcard_message
     * @return string
     * @author rzc
     */
    public function editUserBankcards($id, $conId, $user_name, $bank_mobile, $bank_card, $bank_key_id, $bank_add, $vercode, $bankcard_message) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $stype = 4;
        if ($this->checkVercode($stype, $bank_mobile, $vercode) === false) {
            return ['code' => '3003']; //验证码错误
        }
        $admin_bank = DbAdmin::getAdminBank(['id' => $bank_key_id, 'status' => 1], 'abbrev', true);
        if (empty($admin_bank)) {
            return ['code' => '3009'];
        }
        $user_bank = DbUser::getUserBank(['id' => $uid, 'id' => $id], '*', true);
        if (empty($user_bank)) {
            return ['code' => '3000'];
        }
        $is_bank_card = DbUser::getUserBank([['uid', '=', $uid], ['bank_card', '=', $bank_card], ['id', '<>', $id]], '*', true);
        if ($is_bank_card) {
            return ['code' => '3013', 'msg' => '该银行卡号已添加'];
        }
        // print_r($user_bank);die;
        if ($user_bank['status'] == 2 || $user_bank['status'] == 3 || $user_bank['status'] == 4) {
            return ['code' => '3012'];
        }
        // $this_card = $this->getBancardKey($bank_card);
        if ($bankcard_message['bank'] != $admin_bank['abbrev']) {
            return ['code' => '3010'];
        }

        $userBank                  = [];
        $userBank['uid']           = $uid;
        $userBank['user_name']     = $user_name;
        $userBank['admin_bank_id'] = $bank_key_id;
        $userBank['bank_card']     = $bank_card;
        $userBank['bank_add']      = $bank_add;
        $userBank['bank_mobile']   = $bank_mobile;
        $userBank['status']        = 1;
        $userBank['error_fields']  = '';
        $userBank['message']       = '';
        Db::startTrans();
        try {
            DbUser::editUserBank($userBank, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            // print_r($e);die;
            Db::rollback();
            return ['code' => '3006']; //添加失败
        }

    }

    /**
     * 修改银行卡状态（启用、停用、撤回）
     * @param $conId
     * @param $id
     * @param $status 变更状态 1启用 2停用 3撤销
     * @return string
     * @author rzc
     */
    public function changeUserBankcardStatus($conId, int $id, int $status) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $bank_card = DbUser::getUserBank(['uid' => $uid, 'id' => $id], '*', true);
        // print_r($user_bank_card);die;
        if (empty($bank_card)) {
            return ['code' => '3006'];
        }
        if (!in_array($bank_card['status'], [1, 2, 3])) {
            return ['code' => '3004'];
        }
        if ($status == 3) { //撤销
            if ($bank_card['status'] != 1) {
                return ['code' => '3004'];
            }
            Db::startTrans();
            try {
                DbUser::delUserBank($id);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                exception($e);
                Db::rollback();
                return ['code' => '3007']; //添加失败
            }
        } elseif ($status == 1) { //启用
            if ($bank_card['status'] != 3) {
                return ['code' => '3004'];
            }
            Db::startTrans();
            try {
                DbUser::editUserBank(['status' => 2], $id);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                exception($e);
                Db::rollback();
                return ['code' => '3007']; //添加失败
            }
        } elseif ($status == 2) { //启用
            if ($bank_card['status'] != 2) {
                return ['code' => '3004'];
            }
            Db::startTrans();
            try {
                DbUser::editUserBank(['status' => 3], $id);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                exception($e);
                Db::rollback();
                return ['code' => '3007']; //添加失败
            }
        }
    }

    /**
     * 用户提现
     * @param $conId
     * @param $bankcard_id
     * @param $money
     * @return string
     * @author rzc
     */
    public function commissionTransferCash($conId, int $bankcard_id, $money, int $invoice = 2, $stype = 2) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }

        $user_bank_card = DbUser::getUserBank(['uid' => $uid, 'id' => $bankcard_id], '*', true);
        // print_r($user_bank_card);die;
        if (empty($user_bank_card)) {
            return ['code' => '3006'];
        }
        if (!in_array($user_bank_card['status'], [2, 4])) {
            return ['code' => '3008', 'msg' => '该银行卡暂不可用'];
        }

        $userInfo = DbUser::getUserInfo(['id' => $uid], 'id,user_identity,nick_name,commission,bounty', true);
        if (empty($userInfo)) {
            return ['code' => '3000'];
        }
        if ($stype == 2) { //1.佣金提现
            if ($money < 2000 || $money > 200000) {
                return ['code' => '3007'];
            }
            if ($userInfo['commission'] <= 0) {
                return ['code' => '3005'];
            }
            if ($userInfo['commission'] - $money < 0) {
                return ['code' => '3005'];
            }
            $redisManageInvoice = Config::get('rediskey.manage.redisManageInvoice');
            $invoice_data       = $this->redis->get($redisManageInvoice);
            if (empty($invoice_data)) {
                $invoice_data = @file_get_contents(Env::get('root_path') . "invoice.json");
                if ($invoice_data == false) {
                    return ['code' => '3009'];
                }
            }
            $invoice_data = json_decode($invoice_data, true);
            if ($invoice == 1) {
                $proportion = $invoice_data['has_invoice'];
            } elseif ($invoice == 2) {
                $proportion = $invoice_data['no_invoice'];
            }

        } elseif ($stype == 4) { //2.奖励金提现
            if ($userInfo['bounty'] <= 0) {
                return ['code' => '3005'];
            }
            if ($userInfo['bounty'] - $money < 0) {
                return ['code' => '3005'];
            }
            $proportion = 0;
        }
        $userRedisKey            = Config::get('rediskey.user.redisKey');
        $transfer                = [];
        $transfer['uid']         = $uid;
        $transfer['abbrev']      = $user_bank_card['admin_bank']['abbrev'];
        $transfer['bank_name']   = $user_bank_card['admin_bank']['bank_name'];
        $transfer['bank_card']   = $user_bank_card['bank_card'];
        $transfer['bank_add']    = $user_bank_card['bank_add'];
        $transfer['bank_mobile'] = $user_bank_card['bank_mobile'];
        $transfer['user_name']   = $user_bank_card['user_name'];
        $transfer['status']      = 1;
        $transfer['stype']       = $stype;
        $transfer['wtype']       = 1;
        $transfer['money']       = $money;
        $transfer['proportion']  = $proportion;
        $transfer['invoice']     = $invoice;

        // if ($stype == 2) { //1.佣金提现
        //     $transfer['stype'] = 2;
        // } elseif ($stype == 4) { //2.奖励金提现

        // }
        //扣除佣金
        if ($stype == 2) { //1.佣金提现
            $tradingData = [
                'uid'          => $uid,
                'trading_type' => 2,
                'change_type'  => 6,
                'money'        => -$money,
                'befor_money'  => $userInfo['commission'],
                'after_money'  => bcsub($userInfo['commission'], $money, 2),
                // 'message'      => $remittance['message'],
            ];
        } elseif ($stype == 4) { //2.奖励金提现
            $tradingData = [
                'uid'          => $uid,
                'trading_type' => 3,
                'change_type'  => 7,
                'money'        => -$money,
                'befor_money'  => $userInfo['bounty'],
                'after_money'  => bcsub($userInfo['bounty'], $money, 2),
                // 'message'      => $remittance['message'],
            ];
        }

        // print_r($transfer);die;
        Db::startTrans();
        try {
            DbUser::addLogTransfer($transfer);
            if ($stype == 2) { //1.佣金提现
                DbUser::modifyCommission($uid, $money);
            } elseif ($stype == 4) { //2.奖励金提现
                DbUser::modifyBounty($uid, $money);
            }
            DbUser::saveLogTrading($tradingData);
            Db::commit();
            $this->redis->del($userRedisKey . 'userinfo:' . $uid);
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3006']; //添加失败
        }
    }

    /**
     * 用户提现记录
     * @param $conId
     * @param $bank_card
     * @param $bank_name
     * @param $min_money
     * @param $max_money
     * @param $invoice
     * @param $status
     * @param $wtype
     * @param $stype
     * @param $start_time
     * @param $end_time
     * @param $page
     * @param $pageNum
     * @param $id
     * @return string
     * @author rzc
     */
    public function getLogTransfer($conId, $bank_card = '', $bank_name = '', $min_money = '', $max_money = '', $invoice = '', $status = '', $wtype = '', $stype = '', $start_time = '', $end_time = '', int $page, int $pageNum, $id = '') {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $offset = ($page - 1) * $pageNum;
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $where = [];
        array_push($where, ['uid', '=', $uid]);
        if (!empty($id)) {
            array_push($where, ['id', '=', $id]);
            $result = DbUser::getLogTransfer($where, '*', true);
            if (empty($result)) {
                return ['code' => '3000'];
            }
            return ['code' => '200', 'log_transfer' => $result];
        }
        if (!empty($bank_card)) {
            array_push($where, ['bank_card', '=', $bank_card]);
        }
        if (!empty($bank_name)) {
            array_push($where, ['bank_name', 'LIKE', '%' . $bank_name . '%']);
        }
        if (!empty($min_money)) {
            array_push($where, ['money', '>=', $min_money]);
        }
        if (!empty($max_money)) {
            array_push($where, ['money', '<=', $max_money]);
        }
        if (!empty($invoice)) {
            array_push($where, ['invoice', '=', $invoice]);
        }
        if (!empty($status)) {
            if ($status == 4) {
                array_push($where, ['status', '<>', 3]);
            } else {
                array_push($where, ['status', '=', $status]);
            }
        }
        if (!empty($wtype)) {
            array_push($where, ['wtype', '=', $wtype]);
        }
        if (!empty($stype)) {
            array_push($where, ['stype', '=', $stype]);
        }
        if (!empty($start_time)) {
            $start_time = strtotime($start_time);
            array_push($where, ['create_time', '>=', $start_time]);
        }
        if (!empty($end_time)) {
            $end_time = strtotime($end_time);
            array_push($where, ['create_time', '<=', $end_time]);
        }
        $result = DbUser::getLogTransfer($where, '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        if (empty($result)) {
            return ['code' => '200', 'log_transfer' => []];
        }
        foreach ($result as $key => $value) {
            if ($value['stype'] == 1) {
                $result[$key]['real_money'] = bcmul(bcdiv(bcsub(100, $value['proportion'], 2), 100, 2), $value['money'], 2);
            } elseif ($value['stype'] == 3) {
                $result[$key]['real_money'] = bcmul($value['money'], 1.25, 2);
            }

            $result[$key]['deduct_money'] = bcmul(bcdiv($value['proportion'], 100, 2), $value['money'], 2);
        }
        return ['code' => '200', 'log_transfer' => $result];
    }

    /**
     * 生成二维码
     * @param $code
     * @param $encrypteddata
     * @param $iv
     * @return string
     * @author rzc
     */
    public function userRead($code, $encrypteddata = '', $iv = '', $view_uid = '') {
        $wxInfo = getOpenid($code, $encrypteddata, $iv);
        if ($wxInfo === false) {
            return ['code' => '3002'];
        }
        // print_r($wxInfo);die;
        if (empty($wxInfo['openid'])) {
            return ['code' => '3003'];
        }
        if (empty($wxInfo['unionid'])) {
            if (empty($view_uid)) {
                $view_uid = deUid($view_uid);
                if ($view_uid) {
                    $view_user = DbUser::getUserOne(['id' => $view_uid], 'id');
                    if ($view_user) {
                        $view_uid = $view_user['id'];
                    } else {
                        $view_uid = 1;
                    }
                } else {
                    $view_uid = 1;
                }
            }
            $has_read = DbUser::getUserRead('*', ['openid' => $wxInfo['openid'], 'view_uid' => $view_uid], true);
            if (empty($has_read)) {
                $unionid   = $wxInfo['unionid'] ?? '';
                $nickname  = $wxInfo['nickname'] ?? '';
                $avatarurl = $wxInfo['avatarurl'] ?? '';
                $view_user = DbUser::getUserOne(['id' => $view_uid], 'id,user_identity');
                $addData   = [
                    'openid'        => $wxInfo['openid'],
                    'unionid'       => $unionid,
                    'nick_name'     => $nickname,
                    'avatar'        => $avatarurl,
                    'view_uid'      => $view_uid,
                    'view_identity' => $view_user['user_identity'],
                ];
                DbUser::addUserRead($addData);
                return ['code' => '200'];
            } else {
                $red_time  = date('Y-m-d', strtotime($has_read['update_time']));
                $this_time = date('Y-m-d', time());
                if ($red_time != $this_time) {
                    DbUser::updateUserRead(['read_count' => $has_read['read_count'] + 1], $has_read['id']);
                    return ['code' => '200'];
                } else {
                    return ['code' => '3005'];
                }

            }
        }
        $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id');
        if (!empty($user)) { //注册了微信的老用户
            return ['code' => '3004'];
        } else {
            if (empty($view_uid)) {
                $view_uid = deUid($view_uid);
                if ($view_uid) {
                    $view_user = DbUser::getUserOne(['id' => $view_uid], 'id');
                    if ($view_user) {
                        $view_uid = $view_user['id'];
                    } else {
                        $view_uid = 1;
                    }
                } else {
                    $view_uid = 1;
                }
            }
            $has_read = DbUser::getUserRead('*', ['openid' => $wxInfo['openid'], 'view_uid' => $view_uid], true);
            if (empty($has_read)) {
                $unionid   = $wxInfo['unionid'] ?? '';
                $nickname  = $wxInfo['nickname'] ?? '';
                $avatarurl = $wxInfo['avatarurl'] ?? '';
                $view_user = DbUser::getUserOne(['id' => $view_uid], 'id,user_identity');
                $addData   = [
                    'openid'        => $wxInfo['openid'],
                    'unionid'       => $unionid,
                    'nick_name'     => $nickname,
                    'avatar'        => $avatarurl,
                    'view_uid'      => $view_uid,
                    'view_identity' => $view_user['user_identity'],
                ];
                DbUser::addUserRead($addData);
                return ['code' => '200'];
            } else {
                $red_time  = date('Y-m-d', strtotime($has_read['update_time']));
                $this_time = date('Y-m-d', time());
                if ($red_time != $this_time) {
                    DbUser::updateUserRead(['read_count' => $has_read['read_count'] + 1], $has_read['id']);
                    return ['code' => '200'];
                } else {
                    return ['code' => '3005'];
                }

            }
        }
    }

    /**
     * 获取支持银行
     * @return string
     * @author rzc
     */
    public function getAdminBank() {
        $result = DbAdmin::getAdminBank(['status' => 1], '*', false);
        if (empty($result)) {
            return ['code' => '200', 'adminBank' => []];
        }
        return ['code' => '200', 'adminBank' => $result];
    }

    /**
     * 获取提现比率
     * @return string
     * @author rzc
     */
    public function getInvoice() {
        // echo ;die;
        $invoice = @file_get_contents(Env::get('root_path') . "invoice.json");
        if ($invoice == false) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'invoice' => json_decode($invoice, true)];

    }

    public function bountyDetail($conId, int $page, int $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) { //用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity,bounty,bounty_freeze');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        $offset = ($page - 1) * $pageNum;
        $bounty = $user['bounty']; //奖励金余额
        if ($user['bounty_freeze'] == 1) { //奖励金冻结
            $bounty = 0;
        }
        $bountyAll = DbUser::getLogTradingSum([
            ['trading_type', '=', '3'],
            ['change_type', 'in', [5]],
            ['money', '>', 0],
            ['uid', '=', $uid],
        ], 'money'); //奖励金总额
        $diamondvip = DbRights::getDiamondvip(['uid' => $uid], 'uid,id,share_num', true);
        if (!$diamondvip) {
            $share_num = 0;
        } else {
            $share_num = $diamondvip['share_num'];
        }
        $bountyDetail = DbRights::getDiamondvip(['share_uid' => $uid, 'source' => 2], 'id,uid,create_time,bounty_status', false, 'id', 'desc', $offset . ',' . $pageNum);
        return ['code' => '200', 'share_num' => $share_num, 'bounty' => $bounty, 'bountyAll' => $bountyAll, 'bountyDetail' => $bountyDetail];
    }

    /**
     * 微信授权
     * @param $code
     * @param $redirect_uri
     * @return array
     * @author rzc
     */

    public function wxaccredit($redirect_uri) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $requestUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        return ['code' => 200, 'requestUrl' => $requestUrl];

    }

    /**
     * 微信公众号注册
     * @param $mobile
     * @param $vercode
     * @param $code
     * @param $buid
     * @return array
     * @author rzc
     */
    public function wxregister($mobile, $vercode, $code, $buid) {
        $stype = 1;
        if ($this->checkVercode($stype, $mobile, $vercode) === false) {
            return ['code' => '3006']; //验证码错误
        }
       
        if (!empty($this->checkAccount($mobile))) {
            $uid = $this->checkAccount($mobile); //通过手机号获取uid
            Db::startTrans();
            try {
                $conId = $this->createConId();
                $userconID = DbUser::getUserCon(['uid' => $uid], 'id', true);
                DbUser::updateUserCon(['con_id' => $conId], ['id' => $userconID['id']]);
                $this->redis->zAdd($this->redisConIdTime, time(), $conId);
                $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
               if ($conUid === false) {
                   $this->redis->zDelete($this->redisConIdTime, $conId);
                   $this->redis->hDel($this->redisConIdUid, $conId);
                   Db::rollback();
                }
                $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
                // $this->saveOpenid($uid, $wxInfo['openid'], 2);
                Db::commit();
                return ['code' => '200', 'con_id' => $conId];
            } catch (\Exception $e) {
                Db::rollback();
                exception($e);
                return ['code' => '3007'];
            }
        }
        $wxaccess_token = $this->getaccessToken($code);
        if ($wxaccess_token == false) {
            return ['code' => '3002'];
        }
        $wxInfo = $this->getunionid($wxaccess_token['openid'], $wxaccess_token['access_token']);
        if ($wxInfo == false) {
            return ['code' => '3002'];
        }
        $uid = 0;
        if (empty($wxInfo['unionid'])) {
            return ['code' => '3000'];
        }
        
        $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id,mobile');
        if (!empty($user)) {
            $uid = $user['id'];
            if (!empty($user['mobile'])) { //该微信号已绑定
                Db::startTrans();
                try {
                    $conId = $this->createConId();
                    $userconID = DbUser::getUserCon(['uid' => $uid], 'id', true);
                    DbUser::updateUserCon(['con_id' => $conId], ['id' => $userconID['id']]);
                    $this->redis->zAdd($this->redisConIdTime, time(), $conId);
                    $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
                   if ($conUid === false) {
                       $this->redis->zDelete($this->redisConIdTime, $conId);
                       $this->redis->hDel($this->redisConIdUid, $conId);
                       Db::rollback();
                    }
                    $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
                    // $this->saveOpenid($uid, $wxInfo['openid'], 2);
                    Db::commit();
                    return ['code' => '200', 'con_id' => $conId];
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                    return ['code' => '3007'];
                }
                // return ['code' => '3009'];
            }
           
        }
        if ($wxInfo['sex'] == 0) {
            $wxInfo['sex'] = 3;
        }
        $data = [
            'mobile'    => $mobile,
            'unionid'   => $wxInfo['unionid'],
            'nick_name' => $wxInfo['nickname'],
            'avatar'    => $wxInfo['headimgurl'],
            'sex'       => $wxInfo['sex'],
            'last_time' => time(),
        ];
        $isBoss         = 3;
        $userRelationId = 0;
        $relationRes    = '';
        if ($buid != 1) { //不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $relationRes   = $bUserRelation['relation'];
            $isBoss        = $bUserRelation['is_boss'] ?? 3; //推荐人是否是boss
            if (!empty($uid) && $isBoss == 1) { //不是哥新用户
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) { //总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }

        Db::startTrans();
        try {
            $conId = $this->createConId();
            if (empty($uid)) { //新用户,直接添加
                $uid = DbUser::addUser($data); //添加后生成的uid
                DbUser::addUserRecommend(['uid' => $uid, 'pid' => $buid]);
                if ($isBoss == 1) {
                    $relation = $buid . ',' . $uid;
                } else if ($isBoss == 3) {
                    $relation = $uid;
                } else {
                    $relation = $relationRes . ',' . $uid;
                }
                DbUser::addUserRelation(['uid' => $uid, 'pid' => $buid, 'relation' => $relation]);
                DbUser::addUserCon(['uid' => $uid, 'con_id' => $conId]);
            } else { //老版本用户
                DbUser::updateUser($data, $uid);
                if (!empty($userRelationId)) {
                    DbUser::updateUserRelation(['relation' => $buid . ',' . $uid, 'pid' => $buid], $userRelationId);
                }
                $userconID = DbUser::getUserCon(['uid' => $uid], 'id', true);
                DbUser::updateUserCon(['con_id' => $conId], ['id' => $userconID['id']]);
            }

            $this->redis->zAdd($this->redisConIdTime, time(), $conId);
            $conUid = $this->redis->hSet($this->redisConIdUid, $conId, $uid);
            if ($conUid === false) {
                $this->redis->zDelete($this->redisConIdTime, $conId);
                $this->redis->hDel($this->redisConIdUid, $conId);
                Db::rollback();
            }
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
            $this->saveOpenid($uid, $wxInfo['openid'], 2);
            Db::commit();
            return ['code' => '200', 'con_id' => $conId];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3007'];
        }

    }

    private function getaccessToken($code) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
        $res           = sendRequest($get_token_url);
        $result        = json_decode($res, true);
        if (empty($result['openid'])) {
            return false;
        }
        return $result;
    }

    private function getunionid($openid, $access_token) {
        $appid = Env::get('weixin.weixin_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Env::get('weixin.weixin_secret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $get_token_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res           = sendRequest($get_token_url);
        $result        = json_decode($res, true);
        if (empty($result['openid'])) {
            return false;
        }
        return $result;
    }

}