<?php

namespace app\common\action\index;

use app\common\action\notify\Note;
use app\facade\DbOrder;
use app\facade\DbUser;
use app\facade\DbProvinces;
use app\facade\DbImage;
use Env;
use Config;
use think\Db;
use Endroid\QrCode\QrCode;

class User extends CommonIndex {
    private $cipherUserKey = 'userpass';//用户密码加密key
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
        $uid  = $user['id'];
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey);//加密后的password
        if ($cipherPassword != $user['passwd']) {
            return ['code' => '3003'];
        }
        $conId          = $this->createConId();
        $userCon        = DbUser::getUserCon(['uid' => $uid], 'id,con_id', true);
        $userRelationId = 0;
        if ($buid != 1) {//不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $isBoss        = $bUserRelation['is_boss'] ?? 3;//推荐人是否是boss
            if ($isBoss == 1) {
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) {//总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }
        Db::startTrans();
        try {
            if (empty($userCon)) {//第一次登录，未生成过con_id
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
            return ['code' => '3006'];//验证码错误
        }
        $wxInfo = $this->getOpenid($code, $encrypteddata, $iv);
        if ($wxInfo === false) {
            return ['code' => '3002'];
        }
        $updateData = [];
        $addData    = [];
        $uid        = $this->checkAccount($mobile);//通过手机号获取uid
        if (empty($uid)) {//该手机未注册过
            if (empty($wxInfo['unionid'])) {
                return ['code' => '3000'];
            }
            $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id,mobile');//手机号获取不到就通过微信获取
            if (!empty($user)) {//注册了微信的老用户
                if (!empty($user['mobile'])) {//该微信号已绑定
                    return ['code' => '3009'];
                }
                $uid        = $user['id'];
                $updateData = [
                    'mobile' => $mobile,
                ];
            } else {//新用户
                $addData = [
                    'mobile'    => $mobile,
                    'unionid'   => $wxInfo['unionid'],
                    'nick_name' => $wxInfo['nickname'],
                    'avatar'    => $wxInfo['avatarurl'],//$wxInfo['unionid'],
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
        if ($buid != 1) {//不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $relationRes   = $bUserRelation['relation'] ?? '';
            $isBoss        = $bUserRelation['is_boss'] ?? 3;//推荐人是否是boss
            if (!empty($uid) && $isBoss == 1) {//不是个新用户
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) {//总店下的关系,跟着新boss走
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
                $uid = DbUser::addUser($addData);//添加后生成的uid
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
            return ['code' => '3006'];//验证码错误
        }
        if (!empty($this->checkAccount($mobile))) {
            return ['code' => '3008'];
        }
        $wxInfo = $this->getOpenid($code, $encrypteddata, $iv);
        if ($wxInfo === false) {
            return ['code' => '3002'];
        }
        $uid = 0;
        if (empty($wxInfo['unionid'])) {
            return ['code' => '3000'];
        }
        $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id,mobile');
        if (!empty($user)) {
            if (!empty($user['mobile'])) {//该微信号已绑定
                return ['code' => '3009'];
            }
            $uid = $user['id'];
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey);//加密后的password
        $data           = [
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
        if ($buid != 1) {//不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $relationRes   = $bUserRelation['relation'];
            $isBoss        = $bUserRelation['is_boss'] ?? 3;//推荐人是否是boss
            if (!empty($uid) && $isBoss == 1) {//不是哥新用户
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) {//总店下的关系,跟着新boss走
                        $userRelationId = $userRelation['id'];
                    }
                }
            }
        }
        Db::startTrans();
        try {
            if (empty($uid)) {//新用户,直接添加
                $uid = DbUser::addUser($data);//添加后生成的uid
                DbUser::addUserRecommend(['uid' => $uid, 'pid' => $buid]);
                if ($isBoss == 1) {
                    $relation = $buid . ',' . $uid;
                } else if ($isBoss == 3) {
                    $relation = $uid;
                } else {
                    $relation = $relationRes . ',' . $uid;
                }
                DbUser::addUserRelation(['uid' => $uid, 'pid' => $buid, 'relation' => $relation]);
            } else {//老版本用户
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
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype);//成功后删除验证码
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
            return ['code' => '3006'];//验证码错误
        }
        $cipherPassword = $this->getPassword($password, $this->cipherUserKey);//加密后的password
        $result         = DbUser::updateUser(['passwd' => $cipherPassword], $uid);
        if ($result) {
            $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype);//成功后删除验证码
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
        $wxInfo = $this->getOpenid($code);
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
//        $this->saveUser($id, $user);//用户信息保存在缓存
        if (empty($user['mobile'])) {
            return ['code' => '3002'];
        }
        $userRelationId = 0;
        if ($buid != 1) {//不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $isBoss        = $bUserRelation['is_boss'] ?? 3;//推荐人是否是boss
            if ($isBoss == 1) {
                $userRelation = DbUser::getUserRelation(['uid' => $id], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $id) {//总店下的关系,跟着新boss走
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
        if (empty($uid)) {//用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'user_identity,balance,balance_freeze,commission,commission_freeze,integral');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {
            return ['code' => '3004'];//不是boss
        }
//        $redisKey = 1;
        $redisKey = Config::get('rediskey.user.redisUserNextLevelCount') . $uid;
        if ($this->redis->exists($redisKey)) {
            $data = json_decode($this->redis->get($redisKey), true);
        } else {
//            $toMonth    = strtotime(date('Y-m-01'));//当月的开始时间
//            $preMonth   = strtotime(date('Y-m-01', strtotime('-1 month')));//上月的开始时间
            $threeMonth = strtotime(date('Y-m-01', strtotime('-3 month')));//近三个月
            $balance    = $user['balance_freeze'] == '1' ? 0 : $user['balance'];//商票余额
            $commission = $user['commission_freeze'] == '1' ? 0 : $user['commission'];//佣金余额
            $integral   = $user['integral'];//积分余额
//            $balanceNotSettlement = DbUser::getLogBonusSum([
//                ['user_identity', '=', 2],//只查商票
//                ['status', '=', '1'],//待结算(未到账的)
//                ['to_uid', '=', $uid],
//                ['create_time', '>=', $threeMonth],//近三个月
//            ], 'result_price');//未结算商票
            $balanceUse = abs(DbUser::getLogTradingSum([
                ['trading_type', '=', '1'],//商票交易
                ['change_type', 'in', [1, 2]],//消费和取消订单退还商票
                ['uid', '=', $uid],
//                ['create_time', '>=', $threeMonth],//近三个月
            ], 'money'));//已用商票总额
//            $orderSum             = DbOrder::getOrderDetailSum($uid, $threeMonth);//销售总额(近三个月)
//            $toBonus              = DbUser::getLogTradingSum([
//                ['trading_type', '=', '2'],
//                ['change_type', 'in', [4]],
//                ['uid', '=', $uid],
//                ['create_time', '>=', $toMonth],
//            ], 'money');//本月返利
//            $preBonus             = DbUser::getLogTradingSum([
//                ['trading_type', '=', '2'],
//                ['change_type', 'in', [4]],
//                ['uid', '=', $uid],
//                ['create_time', '>=', $preMonth],
//                ['create_time', '<', $toMonth],
//            ], 'money');//上月返利

            $noBbonus = DbUser::getLogBonusSum([
                'to_uid'        => $uid,
                'user_identity' => 4,//boss身份获得的收益
                'status'        => 1,//待结算的
//                'bonus_type'    => 2,//经营性收益
            ], 'result_price');//未到账

            $bonus = DbUser::getLogBonusSum([
                'to_uid'        => $uid,
                'user_identity' => 4,//boss身份获得的收益
                'status'        => 2,//已结算的
//                'bonus_type'    => 2,//经营性收益
            ], 'result_price');//全部返利(已入账)

//            $bonus = DbUser::getLogTradingSum([
//                ['trading_type', '=', '2'],
//                ['change_type', 'in', [4]],
//                ['uid', '=', $uid],
////                ['create_time', '>=', $threeMonth],//近三个月
//            ], 'money');//全部返利(已入账)
//            $userCount            = DbUser::getUserRelationCount([['relation', 'like', $uid . ',%']]);
//            $childId              = DbUser::getUserChild($uid);
//            $uidList              = DbUser::getUserInfo([['id', 'in', $childId], ['user_identity', '=', 2]], 'id');
//            $uidList              = array_column($uidList, 'id');
//            $userDiamondCount     = count($uidList);
            $merchants = DbUser::getLogTradingSum([
                ['trading_type', '=', '2'],
                ['change_type', '=', 5],
                ['uid', '=', $uid],
//                ['create_time', '>=', $threeMonth],//近三个月
            ], 'money');//招商加盟收益
            $data      = [
//                'balance_all'            => bcadd(bcadd($balance, $balanceNotSettlement, 4), $balanceUse, 2),//商票总额
                'balance_all' => bcadd($balance, $balanceUse, 2),//商票总额
                'balance'     => $balance,//商票余额
                'commission'  => $commission,//佣金余额
                'integral'    => $integral,//积分余额
//                'balance_not_settlement' => $balanceNotSettlement,//未结算商票
                'balance_use' => $balanceUse,//已使用商票
//                'order_sum'              => $orderSum,//销售总额
//                'tobonus'     => $toBonus,//本月返利
//                'prebonus'    => $preBonus,//上月返利
                'no_bonus'    => $noBbonus,//未到账
                'bonus'       => $bonus,//已到账返利
                'bonus_all'   => bcadd($noBbonus, $bonus, 2),
//                'user_count_all'         => $userCount,//招商加盟人数
//                'user_diamond_count'     => $userDiamondCount,//钻石会员圈(直属钻石会员)
//                'user_count'             => bcsub($userCount, $userDiamondCount, 2),//(买主圈)
                'merchants'   => $merchants,//招商加盟收益
            ];
            $this->redis->setEx($redisKey, 120, json_encode($data));
        }
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
        if (empty($uid)) {//用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {//只有boss才能查看
            return ['code' => '3000'];//普通用户没有权限查看
        }
        $ct = 0;
        if (!empty($year)) {
            $month = $month ?: '01';
            $ct    = strtotime(date($year . '-' . $month . '-01'));//当月的开始时间
        }
//        $threeMonth = strtotime(date('Y-m-01', strtotime('-3 month')));//近三个月
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
        $where    = [
            ['to_uid', '=', $uid],
            ['status', '=', $status],
            ['user_identity', '=', 4],//只查boss身份时的分利
            ['create_time', '>=', $ct],
            ['from_uid', $en, $uid],
            ['bonus_type', '=', $bn],
        ];
        $offset   = ($page - 1) * $pageNum;
        $field    = 'from_uid,to_uid,result_price,order_no,status,create_time';
        $distinct = DbUser::getLogBonusDistinct($where, 'order_no', false, ['order_no' => 'desc'], $offset . ',' . $pageNum);
        $distinct = array_column($distinct, 'order_no');
        $where2   = $where;
        $where2[] = ['order_no', 'in', $distinct];
        $bonus    = DbUser::getLogBonus($where2, $field);
        $combined = DbUser::getLogBonusSum($where, 'result_price');//合计
        $userList = DbUser::getUserInfo([['id', 'in', array_unique(array_column($bonus, 'from_uid'))]], 'id,nick_name,avatar,user_identity');
        $userList = array_combine(array_column($userList, 'id'), $userList);
//        print_r($bonus);die;
//        $orderNoList = array_unique(array_column($bonus,'order_no'));
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
            return ['code' => '3000', 'data' => []];//没有分利
        }
        $result = array_values($result);
        return ['code' => '200', 'data' => $result, 'combined' => $combined];
    }

    /**
     * 获取店铺商票明细
     * @param $conId
     * @param $stype
     * @return array
     * @author zyr
     */
    public function getShopBalance($conId, $stype) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {//用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] == '1') {//普通用户
            return ['code' => '3000'];//普通用户没有权限查看
        }
        $threeMonth = strtotime(date('Y-m-01', strtotime('-3 month')));//近三个月
        if ($stype == 1) {//已使用
            $where = [
                ['trading_type', '=', '1'],//商票交易
                ['change_type', 'in', [1, 2]],//1.消费 2.取消订单退还 3.充值 4.层级分利 5.购买会员分利 6.提现 7.转商票
                ['uid', '=', $uid],
                ['create_time', '>=', $threeMonth],//近三个月
            ];
            $field = 'change_type,order_no,money,create_time';
            $data  = DbUser::getLogTrading($where, $field, false, 'id desc');
        }
        if ($stype == 3) {//余额明细
            $where = [
                ['trading_type', '=', '1'],//商票交易
                ['change_type', 'in', [1, 2, 4, 5, 7]],//1.消费 2.取消订单退还 3.充值 4.层级分利 5.购买会员分利 6.提现 7.转商票
                ['uid', '=', $uid],
                ['create_time', '>=', $threeMonth],//近三个月
            ];
            $field = 'change_type,order_no,money,create_time';
            $data  = DbUser::getLogTrading($where, $field, false, 'id desc');
        }
        if ($stype == 2) {//未结算商票
            $data = DbUser::getLogBonus([
                ['user_identity', '=', 2],//只查商票
                ['status', '=', '1'],//待结算(未到账的)
                ['to_uid', '=', $uid],
                ['create_time', '>=', $threeMonth],//近三个月
            ], 'order_no,result_price as money,create_time');//未结算商票
        }
        $result = [];
//        print_r($data);die;
        foreach ($data as $d) {
            $trType = $d['change_type'] ?? 4;
            switch ($trType) {
                case 1:
                    $ctype = '已使用商票';
                    break;
                case 2:
                    $ctype = '商票退款';
                    break;
                case 4:
                    $ctype = '钻石返利';
                    break;
                case 5:
                    $ctype = '钻石会员邀请奖励';
                    break;
                case 7:
                    $ctype = '佣金转入';
                    break;
            }
            $d['ctype'] = $ctype;
            unset($d['change_type']);
            array_push($result, $d);
        }
//        print_r($data);die;
        return ['code' => '200', 'data' => $result];
        //商票退款  已使用商票   钻石会员邀请奖励  钻石返利
    }

    public function getUserSocialSum($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {//用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {
            return ['code' => '3000'];//boss才有权限查看
        }
//        $diamonUserList = DbUser::getUserRelation([['pid', '=', $uid], ['is_boss', '=', '2']], 'uid');
//        $diamonUidList  = array_column($diamonUserList, 'uid');//第一层会员圈
//        $diamondRing    = DbUser::getUserInfoCount([['id', 'in', $diamonUidList], ['user_identity', '=', '2']]);//第一层钻石会员
//
////        $userRing  = bcsub(count($diamonUidList), $diamondRing, 0);//第一层普通会员圈人数
////        $nextCount = $this->getUsersNext($diamonUidList);
////        $userCount = bcadd($nextCount, $userRing);
////        $allCount  = bcadd($userCount, $diamondRing);
//        $allCount  = DbUser::getUserRelationCount([['relation', 'like', '%,' . $uid . ',%'], ['is_boss', '=', '2']]);
//        $userCount = bcsub($allCount, $diamondRing, 2);
//        return ['code' => '200', 'diamon_count' => $diamondRing, 'user_count' => $userCount, 'all_user' => $allCount];

        $readCount  = DbUser::getUserReadSum([['view_uid', '=', $uid]], 'read_count');
        $grantCount = DbUser::getUserReadSum([['view_uid', '=', $uid], ['nick_name', '<>', '']], 'read_count');
        return ['code' => '200', 'read_count' => $readCount, 'grant_count' => $grantCount, 'reg_count' => 0];
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
            $uids  = array_column($uList, 'uid');
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
        if (empty($uid)) {//用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {
            return ['code' => '3000'];//boss才有权限查看
        }
        $offset         = ($page - 1) * $pageNum;
        $diamonUserList = DbUser::getUserRelation([['pid', '=', $uid], ['is_boss', '=', '2']], 'uid', false, 'uid desc');//查询直属boss下的所有人
        if (empty($diamonUserList)) {
            return ['code' => '3000', 'data' => []];
        }
        $diamonUidList = array_column($diamonUserList, 'uid');//boss下的所有人(包括钻石和普通)
        if ($stype == 1) {//钻石会员圈
            $diamonUserCount = DbUser::getUserInfoCount([['id', 'in', $diamonUidList], ['user_identity', '=', '2']]);//直接钻石会员
            $uAll            = DbUser::getUserRelation([['pid', '=', $uid], ['is_boss', '=', '2']], 'uid');//查询直属boss下的所有人
            $uDiamon         = DbUser::getUserInfo([['id', 'in', array_column($uAll, 'uid')], ['user_identity', '=', '2']], 'id');
            $uDiamon         = array_column(DbUser::getUserRelation([['pid', 'in', array_column($uDiamon, 'id')], ['is_boss', '=', '2']], 'uid'), 'uid');
            $socialCountAll  = DbUser::getUserInfoCount([['id', 'in', array_column($uAll, 'uid')], ['user_identity', '=', '2']]);

            $diamondRing  = DbUser::getUserInfo([['id', 'in', $diamonUidList], ['user_identity', '=', '2']], 'id,nick_name,avatar', false, 'id desc', $offset . ',' . $pageNum);//查直属的钻石会员
            $diamondUids  = array_column($diamondRing, 'id');
            $diamondUsers = DbUser::getUserRelation([['pid', 'in', $diamondUids], ['is_boss', '=', '2']], 'pid,uid');//boss直属钻石的直属钻石
            $diamondK     = array_column($diamondUsers, 'pid', 'uid');
            $diamondInfo  = DbUser::getUserInfo([['id', 'in', array_column($diamondUsers, 'uid')], ['user_identity', '=', 2]], 'id');//直属钻石圈
            $userCount    = [];
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
                $dr['diamond_count'] = $diamondCount[$dr['id']] ?? 0;//钻石会员圈
                $dr['social_count']  = $userCount[$dr['id']] ?? 0;//社交圈
//                $socialCountAll      += $dr['diamond_count'];
                $dr['id'] = enUid($dr['id']);
                array_push($data, $dr);
            }
            return ['code' => '200', 'data' => $data, 'diamon_user_count' => $diamonUserCount, 'social_count_all' => $socialCountAll];
        }
        if ($stype == 2) {//买主圈
            $userRingCount = DbUser::getUserInfoCount([['id', 'in', $diamonUidList], ['user_identity', '=', '1']]);
            $userRing      = DbUser::getUserInfo([['id', 'in', $diamonUidList], ['user_identity', '=', '1']], 'id,nick_name,avatar', false, 'id desc', $offset . ',' . $pageNum);//查直属的普通会员
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

//            $userCount    = [];
//            foreach ($userUids as $du) {
//                $userList = DbUser::getUserRelation([['relation', 'like', '%' . $du . ',%']], 'relation');
//                $uidList  = [];
//                foreach ($userList as $ul) {
//                    $ul['relation'] = substr($ul['relation'], bcadd(stripos($ul['relation'], $du . ','), strlen($du . ','), 0));
//                    $uidList        = array_merge($uidList, explode(',', $ul['relation']));
//                }
//                $uidList        = array_values(array_unique($uidList));
//                $userCount[$du] = count($uidList);
//            }
//            $data = [];
//            foreach ($userRing as $dr) {
//                $dr['social_count']  = $userCount[$dr['id']] ?? 0;
//                $dr['id'] = enUid($dr['id']);
//                array_push($data, $dr);
//            }
            return ['code' => '200', 'data' => $data, 'user_ring_count' => $userRingCount];
        }
    }

    public function getMerchants($conId, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {//用户不存在
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'mobile,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }
        if ($user['user_identity'] != '4') {
            return ['code' => '3000'];//boss才有权限查看
        }
        $threeMonth = strtotime(date('Y-m-01', strtotime('-3 month')));//近三个月
        $offset     = ($page - 1) * $pageNum;
        $memberUse  = DbUser::getLogTrading([
            ['trading_type', '=', '2'],//商票交易
            ['change_type', '=', 5],//消费和取消订单退还商票
            ['uid', '=', $uid],
            ['create_time', '>=', $threeMonth],//近三个月
        ], 'money,order_no,create_time', false, 'id desc', $offset . ',' . $pageNum);//已用商票总额
        if (empty($memberUse)) {
            return ['code' => '3000', 'data' => []];
        }
        $buyUidList     = DbOrder::getMemberOrder([['order_no', 'in', array_column($memberUse, 'order_no')]], 'uid,order_no');
        $orderNoUid     = array_column($buyUidList, 'uid', 'order_no');
        $buyUidList     = array_column($buyUidList, 'uid');
        $users          = DbUser::getUserInfo([['id', 'in', $buyUidList]], 'id,nick_name,avatar');
        $userNameList   = array_column($users, 'nick_name', 'id');
        $userAvatarList = array_column($users, 'avatar', 'id');
        $data           = [];
        foreach ($memberUse as $mu) {
            $mu['uid']       = enUid($orderNoUid[$mu['order_no']]);
            $mu['nick_name'] = $userNameList[$orderNoUid[$mu['order_no']]];
            $mu['avatar']    = $userAvatarList[$orderNoUid[$mu['order_no']]];
            array_push($data, $mu);
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
        if (empty($uid)) {//用户不存在
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
        if (empty($code)) {//已发送过验证码
            return ['code' => '3003'];//一分钟内不能重复发送
        }
        $content = getVercodeContent($code);//短信内容
        $result  = $this->note->sendSms($mobile, $content);//发送短信
        if ($result['code'] != '200') {
            $this->redis->del($timeoutKey);
            $this->redis->del($redisKey);
            return ['code' => '3004'];//短信发送失败
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
        $redisCode = $this->redis->get($redisKey);//服务器保存的验证码
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
            return '0';//一分钟内不能重复发送
        }
        $this->redis->setTimeout($timeoutKey, 60);//60秒自动过期
        $code = randCaptcha(6);//生成验证码
        if ($this->redis->setEx($redisKey, 600, $code)) {//不重新发送酒10分钟过期
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
        if ($buid != 1) {//不是总店
            $bUserRelation = DbUser::getUserRelation(['uid' => $buid], 'id,is_boss,relation', true);
            $isBoss        = $bUserRelation['is_boss'] ?? 3;//推荐人是否是boss
            if ($isBoss == 1) {
                $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,is_boss,relation', true);
                if ($userRelation['is_boss'] == 2) {
                    $uRelation = $userRelation['relation'];
                    if ($uRelation == $uid) {//总店下的关系,跟着新boss走
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
            $res        = DbUser::getUser(['id' => $uid]);
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
        $saveTime = 300;//保存5分钟
        $this->redis->hMSet($this->redisKey . 'userinfo:' . $id, $user);
        $this->redis->expireAt($this->redisKey . 'userinfo:' . $id, bcadd(time(), $saveTime, 0));//设置过期
    }

    /**
     * 获取用的openid unionid 及详细信息
     * @param $code
     * @param $encrypteddata
     * @param $iv
     * @return array|bool|int
     * @author zyr
     */
    private function getOpenid($code, $encrypteddata = '', $iv = '') {
        $appid         = Config::get('conf.weixin_miniprogram_appid');
        $secret        = Config::get('conf.weixin_miniprogram_appsecret');
        $get_token_url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
        $res           = sendRequest($get_token_url);
        $result        = json_decode($res, true);
        // Array([session_key] => N/G/1C4QKntLTDB9Mk0kPA==,[openid] => oAuSK5VaBgJRWjZTD3MDkTSEGwE8,[unionid] => o4Xj757Ljftj2Z6EUBdBGZD0qHhk)
        if (empty($result['session_key'])) {
            return false;
        }
        $sessionKey = $result['session_key'];
        unset($result['session_key']);
        if (!empty($encrypteddata) && !empty($iv) && empty($result['unionId'])) {
            $result = $this->decryptData($encrypteddata, $iv, $sessionKey);
        }
        if (is_array($result)) {
            $result = array_change_key_case($result, CASE_LOWER);//CASE_UPPER,CASE_LOWER
            return $result;
        }
        return false;
        //[openId] => oAuSK5VaBgJRWjZTD3MDkTSEGwE8,[nickName] => 榮,[gender] => 1,[language] => zh_CN,[city] =>,[province] => Shanghai,[country] => China,
        //[avatarUrl] => https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJiaWQI7tUfDVrvuSrDDcfFiaJriaibibBiaYabWL5h6HlDgMMvkyFul9JRicr0ZMULxs66t5NBdyuhEokhA/132
        //[unionId] => o4Xj757Ljftj2Z6EUBdBGZD0qHhk
    }

    /**
     * 解密微信信息
     * @param $encryptedData
     * @param $iv
     * @param $sessionKey
     * @return int|array
     * @author zyr
     * -40001: 签名验证错误
     * -40002: xml解析失败
     * -40003: sha加密生成签名失败
     * -40004: encodingAesKey 非法
     * -40005: appid 校验错误
     * -40006: aes 加密失败
     * -40007: aes 解密失败
     * -40008: 解密后得到的buffer非法
     * -40009: base64加密失败
     * -40010: base64解密失败
     * -40011: 生成xml失败
     */
    private function decryptData($encryptedData, $iv, $sessionKey) {
        $appid = Config::get('conf.weixin_miniprogram_appid');
        if (strlen($sessionKey) != 24) {
            return -41001;
        }
        $aesKey = base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            return -41002;
        }
        $aesIV     = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result    = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj   = json_decode($result);
        if ($dataObj == null) {
            return -41003;
        }
        if ($dataObj->watermark->appid != $appid) {
            return -41003;
        }
        $data = json_decode($result, true);
        unset($data['watermark']);
        return $data;
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
    public function getQrcode($conId, $page, $scene, $type) {
        $uid    = $this->getUidByConId($conId);
        $Upload = new Upload;
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        // 先查询是否有已存在图片
        $has_QrImage = DbImage::getUserImage('*', ['uid' => $uid, 'stype' => 1], true);
        if (!empty($has_QrImage)) {
            $Qrcode = $has_QrImage['image'];
            return ['code' => '200', 'Qrcode' => $Qrcode];
        }
        $result = $this->createQrcode($scene, $page);

        if (imagecreatefromstring($result)) {
            // $img_file = 'd:/test.png';
            $file = fopen(Config::get('conf.image_path') . $conId . '.png', "w"); //打开文件准备写入
            fwrite($file, $result); //写入
            fclose($file); //关闭  
            // 开始上传,调用上传方法 
            $upload = $Upload->uploadUserImage($conId . '.png');
            if ($upload['code'] == 200) {
                $logImage = DbImage::getLogImage($upload, 2);//判断时候有未完成的图片
                // print_r($logImage);die;
                if (empty($logImage)) {//图片不存在
                    return ['code' => '3010'];//图片没有上传过
                }
                $upUserInfo          = [];
                $upUserInfo['uid']   = $uid;
                $upUserInfo['stype'] = $type;
                $upUserInfo['image'] = $upload['image_path'];
                Db::startTrans();
                try {
                    DbImage::saveUserImage($upUserInfo);
                    DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
                    $new_Qrcode = Config::get('qiniu.domain') . '/' . $upload['image_path'];

                    return ['code' => '200', 'Qrcode' => $new_Qrcode];
                } catch (\Exception $e) {
                    Db::rollback();
                    return ['code' => '3011'];//添加失败
                }
            } else {
                return ['code' => '3009'];
            }
            // echo $result;die;
        } else {
            return ['code' => '3006'];
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
        $appid      = Config::get('conf.weixin_miniprogram_appid');
        $appid      = 'wx1771b2e93c87e22c';
        $secret     = Config::get('conf.weixin_miniprogram_appsecret');
        $secret     = '1566dc764f46b71b33085ba098f58317';
        $requestUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
        if (!$requestUrl) {
            return ['code' => '3004'];
        }
        $requsest_subject = json_decode(sendRequest($requestUrl), true);
        $access_token     = $requsest_subject['access_token'];
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
        $obligation = DbOrder::getOrderCount(['order_status' => 1]);//待付款
        $deliver    = DbOrder::getOrderCount(['order_status' => 4]);//待发货
        $receive    = DbOrder::getOrderCount(['order_status' => 5]);//待收货
        $rating     = DbOrder::getOrderCount(['order_status' => 7]);//待评价

        return ['code' => '200', 'obligation' => $obligation, 'deliver' => $deliver, 'receive' => $receive, 'rating' => $rating];
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
        $wxInfo = $this->getOpenid($code, $encrypteddata, $iv);
        if ($wxInfo === false) {
            return ['code' => '3002'];
        }
        // print_r($wxInfo);die;
        $user = DbUser::getUserOne(['unionid' => $wxInfo['unionid']], 'id');
        if (empty($wxInfo['openid'])) {
            return ['code' => '3003'];
        }

        if (!empty($user)) {//注册了微信的老用户
            return ['code' => '3004'];
        } else {//新用户
            $has_read = DbUser::getUserRead('*', ['openid' => $wxInfo['openid']], true);
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
}