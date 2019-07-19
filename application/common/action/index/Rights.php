<?php

namespace app\common\action\index;

use app\facade\DbRights;
use app\facade\DbShops;
use app\facade\DbUser;
use Config;
use think\Db;

class Rights extends CommonIndex {
    public function __construct() {
        parent::__construct();
    }

    /**
     * 领取钻石会员
     * @param $con_id
     * @param $parent_id
     * @return array
     * @author rzc
     */
    public function receiveDiamondvip($con_id, $parent_id) {
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if ($userInfo['user_identity'] > 1) {
            return ['code' => '3004', 'msg' => '当前身份等级大于或等于钻石会员，无法领取'];
        }
        $DiamondvipDominos = DbRights::getDiamondvips(['uid' => $parent_id, 'status' => 1, 'type' => 1], '*', true);
        if (!$DiamondvipDominos) {
            return ['code' => '3005', '分享用户没有分享机会'];
        }
        if ($DiamondvipDominos['stock'] < $DiamondvipDominos['num'] + 1) {
            DbRights::updateDiamondvip(['status' => 3], $DiamondvipDominos['id']);
            return ['code' => '3006', '该机会已领完'];
        }
        $receiveDiamondvip                   = [];
        $receiveDiamondvip['uid']            = $uid;
        $receiveDiamondvip['diamondvips_id'] = $DiamondvipDominos['id'];
        $receiveDiamondvip['share_uid']      = $parent_id;
        Db::startTrans();
        try {
            DbRights::receiveDiamondvip($receiveDiamondvip);
            DbRights::updateDiamondvip(['num' => $DiamondvipDominos['num'] + 1], $DiamondvipDominos['id']);
            DbUser::updateUser(['user_identity' => 2], $uid);
            $this->resetUserInfo($uid);
            Db::commit();
            return ['code' => '200']; //领取成功
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005']; //领取失败
        }

    }

    /**
     * 判断会员是否有分享钻石接龙的的资格
     * @param $parent_id
     * @return array
     * @author rzc
     */
    public function IsGetDominos($parent_id) {
        $userInfo = DbUser::getUserInfo(['id' => $parent_id], 'user_identity', true);
        if (empty($userInfo)) {
            return ['code' => '3000'];
        }
        if ($userInfo['user_identity'] < 4) {
            return ['code' => '3004', 'msg' => '非BOSS无法开启分享钻石接龙资格（200名额）'];
        }
        $DiamondvipDominos = DbRights::getDiamondvips(['uid' => $parent_id, 'status' => 1, 'type' => 1], '*', true);
        if (!$DiamondvipDominos || $DiamondvipDominos['stock'] < $DiamondvipDominos['num'] + 1) {
            return ['code' => '3005', '分享用户没有分享机会'];
        } else {
            return ['code' => 200];
        }
    }

    /**
     * 判断登录会员钻石接龙的的名额是否用完
     * @param $parent_id
     * @return array
     * @author rzc
     */
    public function IsBossDominos($con_id) {
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if ($userInfo['user_identity'] < 4) {
            return ['code' => '3004', 'msg' => '非BOSS无法开启分享钻石接龙资格（200名额）'];
        }
        $DiamondvipDominos = DbRights::getDiamondvips(['uid' => $uid, 'status' => 1, 'type' => 1], '*', true);
        if (!$DiamondvipDominos || $DiamondvipDominos['stock'] < $DiamondvipDominos['num'] + 1) {
            return ['code' => '3005', '分享用户没有分享机会'];
        } else {
            return ['code' => 200];
        }
    }

    /**
     * 获取用户红包提示
     * @param $con_id
     * @return array
     * @author rzc
     */
    public function getDominosBalanceHint($con_id) {
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $redisListKey     = Config::get('redisKey.order.redisMemberShare');
        $uer_balance_hint = $this->redis->hgetall($redisListKey . $uid);

        if ($uer_balance_hint) {
            $this->redis->hdel($redisListKey . $uid);
            return ['code' => 200, 'msg' => '用户有到账红包'];
        } else {
            return ['code' => '3000'];
        }
    }

    /**
     * 获取用户红包提示
     * @param $con_id
     * @return array
     * @author rzc
     */
    public function getDominosChance($con_id) {
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if ($userInfo['user_identity'] > 1) {

            $Diamondvips       = DbRights::getDiamondvips(['uid' => $uid, 'status' => 1, 'type' => 1], '*');
            $DiamondvipDominos = DbRights::getCountDiamondvips(['share_uid' => $uid, 'diamondvips_id' => 0]);
            return ['code' => 200, 'Diamondvips' => $Diamondvips, 'DiamondvipDominos' => $DiamondvipDominos];
        } else {
            return ['code' => '3004'];
        }

    }

    /**
     * 获取用户钻石会员领取机会记录
     * @param $con_id
     * @return array
     * @author rzc
     */
    public function getDominosReceive($con_id, $diamondvips_id = false) {
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if ($userInfo['user_identity'] > 1) {

            if ($diamondvips_id) {
                $where = ['diamondvips_id' => $diamondvips_id];
            } else {
                $where = ['share_uid' => $uid, 'diamondvips_id' => 0];
            }
            $getDiamondvipDominos = DbRights::getDiamondvip($where, '*');
            if (empty($getDiamondvipDominos)) {
                return ['code' => '3000'];
            }
            foreach ($getDiamondvipDominos as $get => $Dominos) {
                $userInfo = DbUser::getUserInfo(['id' => $Dominos['uid']], 'id,nick_name,avatar', true);
                // print_r($userInfo);die;
                $getDiamondvipDominos[$get]['uid']       = enuid($userInfo['id']);
                $getDiamondvipDominos[$get]['nick_name'] = $userInfo['nick_name'];
                $getDiamondvipDominos[$get]['avatar']    = $userInfo['avatar'];
            }
            return ['code' => 200, 'Diamondvips' => $getDiamondvipDominos];
        } else {
            return ['code' => '3004'];
        }
    }

    /**
     * 用户推广合伙人
     * @param $con_id
     * @return array
     * @author rzc
     */
    public function shopApplyBoss($con_id, $target_nickname, $target_sex, $target_mobile, $target_idcard, $refe_type, $parent_id) {
        $redisKey  = Config::get('rediskey.user.redisUserOpenbossLock');
        $refe_type = 2; //暂时只支持购买合伙人
        $uid       = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }

        $userInfo = DbUser::getUserInfo(['id' => $uid], 'user_identity,nick_name', true);
        if ($userInfo['user_identity'] == 4) {
            return ['code' => '3010'];
        }
        $parent_info = DbUser::getUserInfo(['id' => $parent_id], 'user_identity,nick_name,user_market', true);
        $parent_shop = [];
        if (!empty($parent_info)) {
            $refe_identity = 0;
            if ($parent_info['user_identity'] == 4) {
                $parent_shop   = DbShops::getShopInfo('id', ['uid' => $parent_id]);
                $refe_identity = 3;
            }
            if ($parent_info['user_identity'] == 3) {
                $refe_identity = 1;
            }
            switch ($parent_info['user_market']) {
            case '1':
                $refe_identity = 2;
                break;
            case '2':
                $refe_identity = 2;
                break;
            case '3':
                $refe_identity = 4;
                break;
            case '4':
                $refe_identity = 5;
                break;

            }
        } else {
            return ['code' => '3012'];
        }
        $is_loading = DbRights::getShopApply([['target_uid', '=', $uid], ['status', '<>', '4']], 'id');
        if (!empty($is_loading)) {
            return ['code' => '3009'];
        }
        $has_shop_apply = DbRights::getShopApply(['target_uid' => $uid, 'refe_uid' => $parent_id], '*', true);
        if (!empty($has_shop_apply)) {
            return ['code' => '3011'];
        }
        //开店邀请记录表
        $apply_data                    = [];
        $apply_data['target_uid']      = $uid;
        $apply_data['target_uname']    = $userInfo['nick_name'];
        $apply_data['target_nickname'] = $target_nickname;
        $apply_data['target_sex']      = $target_sex;
        $apply_data['target_mobile']   = $target_mobile;
        $apply_data['target_idcard']   = $target_idcard;
        $apply_data['refe_uid']        = $parent_id;
        $apply_data['refe_uname']      = $parent_info['nick_name'];
        $apply_data['refe_identity']   = $refe_identity;
        if ($parent_shop) {
            $apply_data['shop_id'] = $parent_shop['id'];
        }
        $apply_data['refe_type'] = $refe_type;
        $apply_data['status']    = 1;

/*         //招商代理收益日志
$log_invest               = [];
$log_invest['uid']        = $parent_id;
$log_invest['target_uid'] = $uid;
$log_invest['status']     = 1;
if ($refe_identity == 2) {
$log_invest['cost']       = 3500;
}else if ($refe_identity == 4) {
$log_invest['cost']       = 4000;
}else if ($refe_identity == 5) {
$log_invest['cost']       = 5000;
} */

        if ($this->redis->setNx($redisKey . $uid, 1) === false) {
            return ['code' => '3013'];
        }
        Db::startTrans();
        try {
            DbRights::saveShopApply($apply_data);
            // DbUser::saveLogInvest($log_invest);
            Db::commit();
            return ['code' => '200']; //领取成功
        } catch (\Exception $e) {
            $this->redis->del($redisKey . $uid);
            exception($e);
            Db::rollback();
            return ['code' => '3005']; //领取失败
        }
    }

    /**
     * 用户升级任务
     * @param $con_id
     * @return array
     * @author rzc
     */
    public function userUpgrade($conId, $refe_type, $parent_id) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        //refe_type 被邀请成为店主类型1.创业店主2.兼职市场经理 3 兼职市场总监
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'user_identity,nick_name,user_market', true);
        if ($refe_type == 1) {
            if ($userInfo['user_identity'] != 2) {
                return ['code' => '3002'];
            }
        } else if ($refe_type == 2) {
            if ($userInfo['user_identity'] != 3 || $userInfo['user_market'] > 0) {
                return ['code' => '3003'];
            }
        } else if ($refe_type == 3) {
            if ($userInfo['user_identity'] != 4) {
                return ['code' => '3004'];
            }
        }
        // $parent_id = deUid($parent_id);
        // $parent_id = 26743;
        $parent_id = $this->getRelation($uid)['pid'];
        if (!$parent_id) {
            $parent_id = 1;
        }
        $parent_info = DbUser::getUserInfo(['id' => $parent_id], 'user_identity,nick_name,user_market,commission', true);
        if (empty($parent_info)) {
            $parent_id = 1;
        }
        //refe_type 被邀请成为店主类型1.创业店主2.兼职市场经理 3 兼职市场总监
        if ($refe_type == 1) {

            Db::startTrans();
            try {

                if ($parent_info) {
                    //邀请用户为临时兼职市场经理
                    if ($parent_info['user_market'] == 1) {
                        //创业店主升级兼职市场经理任务
                        $parent_user_task = DbRights::getUserTask(['uid' => $parent_id, 'type' => 1, 'status' => 1], '*', true);

                        if (!empty($parent_user_task)) {
                            //原任务进度
                            $up_parent_task = [];
                            $up_parent_task = [
                                'has_target'  => $parent_user_task['has_target'] + 1,
                                'bonus'       => $parent_user_task['bonus'] + 8,
                                'update_time' => time(),
                            ];
                            if ($parent_user_task['target'] <= $up_parent_task['has_target']) {
                                $up_parent_task['bonus_status'] = 2;
                                $up_parent_task['status']       = 2;
                                $tradingData                    = [];
                                $tradingData                    = [
                                    'uid'          => $parent_id,
                                    'trading_type' => 2,
                                    'change_type'  => 13,
                                    'money'        => $up_parent_task['bonus'],
                                    'befor_money'  => $parent_info['commission'],
                                    'after_money'  => bcadd($parent_info['commission'], $up_parent_task['bonus'], 2),
                                    'message'      => '推广创业店主奖励',
                                ];

                                DbUser::updateUser(['user_market' => 2], $parent_id);
                                DbUser::saveLogTrading($tradingData);
                                DbUser::modifyCommission($parent_id, $up_parent_task['bonus'], 'inc');
                                //创建兼职市场经理升级合伙人任务
                                $upgrade_task = [];
                                $upgrade_task = [
                                    'uid'          => $parent_id,
                                    'title'        => '兼职市场经理升级合伙人任务',
                                    'type'         => 3,
                                    'target'       => 5,
                                    'status'       => 1,
                                    'bonus_status' => 2,
                                    'timekey'      => date('Ym', time()),
                                    'start_time'   => time(),
                                ];
                                DbRights::addUserTask($upgrade_task);
                                $parent_userRelation = $this->getRelation($parent_id);
                                $rela_user           = DbUser::getUserInfo(['id' => $parent_userRelation['pid']], 'commission,user_identity,nick_name,user_market', true);
                                if (!empty($rela_user) && $rela_user['user_market'] > 2) {

                                    $rel_task = DbRights::getUserTask(['uid' => $parent_userRelation['pid'], 'type' => 6, 'timekey' => date('Ym', time())], '*', true);
                                    if (!empty($rel_task)) {
                                        $new_rel_task = [];
                                        $new_rel_task = [
                                            'has_target' => $rel_task['has_target'] + 1,
                                        ];
                                        DbRights::editUserTask($new_rel_task, $rel_task['id']);
                                        $res_task_invited = [];
                                        $res_task_invited = [
                                            'utask_id'      => $rel_task['id'],
                                            'uid'           => $parent_id,
                                            'user_identity' => 5,
                                            'timekey'       => date('Ym', time()),
                                        ];
                                        DbRights::addTaskInvited($res_task_invited);
                                        if ($rela_user['user_market'] == 3) {
                                            $ptype   = 8;
                                            $ptitle  = '兼职市场总监1推广兼职市场经理奖励任务';
                                            $p_bouns = 2;
                                        } else if ($rela_user['user_market'] == 4) {
                                            $ptype   = 9;
                                            $ptitle  = '兼职市场总监2推广兼职市场经理奖励任务';
                                            $p_bouns = 3;
                                        }
                                        $p_task = DbRights::getUserTask(['uid' => $parent_userRelation['pid'], 'type' => $ptype, 'timekey' => date('Ym', time())], '*', true);
                                        if (empty($p_task)) {
                                            $newp_task = [
                                                'uid'          => $parent_userRelation['pid'],
                                                'title'        => $ptitle,
                                                'type'         => $ptype,
                                                'has_target'   => 1,
                                                'status'       => 1,
                                                'bonus'        => $p_bouns,
                                                'bonus_status' => 2,
                                                'timekey'      => date('Ym', time()),
                                                'start_time'   => time(),
                                            ];
                                            $p_task_id = DbRights::addUserTask($newp_task);
                                        } else {
                                            $newp_task = [
                                                'has_target' => $p_task['has_target'] + 1,
                                                'bonus'      => $p_task['bonus'] + $p_bouns,
                                            ];
                                            DbRights::editUserTask($newp_task, $p_task['id']);
                                            $p_task_id = $p_task['id'];

                                        }
                                        $p_task_invited = [
                                            'utask_id'      => $p_task_id,
                                            'uid'           => $parent_id,
                                            'user_identity' => 5,
                                            'bonus'         => $p_bouns,
                                            'timekey'       => date('Ym', time()),
                                        ];
                                        DbRights::addTaskInvited($p_task_invited);
                                        $tradingData = [];
                                        $tradingData = [
                                            'uid'          => $parent_userRelation['pid'],
                                            'trading_type' => 2,
                                            'change_type'  => 13,
                                            'money'        => $p_bouns,
                                            'befor_money'  => $rela_user['commission'],
                                            'after_money'  => bcadd($rela_user['commission'], $p_bouns, 2),
                                            'message'      => '兼职市场经理升级永久奖励',
                                        ];
                                        DbUser::saveLogTrading($tradingData);
                                        DbUser::modifyCommission($parent_userRelation['pid'], $p_bouns, 'inc');

                                    }
                                }
                            }
                            DbRights::editUserTask($up_parent_task, $parent_user_task['id']);
                            $task_invited = [];
                            $task_invited = [
                                'utask_id'      => $parent_user_task['id'],
                                'uid'           => $uid,
                                'user_identity' => 3,
                                'timekey'       => date('Ym', time()),
                                'bonus'         => 8,
                            ];
                            DbRights::addTaskInvited($task_invited);
                        }
                    } elseif ($parent_info['user_market'] > 1) {
                        //查询进行中的邀请创业店主奖励任务
                        $parent_user_task = DbRights::getUserTask(['uid' => $parent_id, 'type' => 2, 'status' => 1], '*', true);
                        if (empty($parent_user_task)) {
                            $add_user_task = [];
                            $add_user_task = [
                                'uid'        => $parent_id,
                                'title'      => '邀请创业店主奖励任务',
                                'type'       => 2,
                                'has_target' => 1,
                                'status'     => 1,
                                'bonus'      => 8,
                                'timekey'    => date('Ym', time()),
                            ];
                            $id = DbRights::addUserTask($add_user_task);

                        } else {
                            $id             = $parent_user_task['id'];
                            $up_parent_task = [];
                            $up_parent_task = [
                                'has_target' => $parent_user_task['has_target'] + 1,
                                'bonus'      => $parent_user_task['bonus'] + 8,
                            ];
                            DbRights::editUserTask($up_parent_task, $id);
                        }
                        //添加邀请创业任务详情记录
                        $task_invited = [];
                        $task_invited = [
                            'utask_id'      => $id,
                            'uid'           => $uid,
                            'user_identity' => 3,
                            'timekey'       => date('Ym', time()),
                            'bonus'         => 8,
                        ];
                        DbRights::addTaskInvited($task_invited);
                        $tradingData = [];
                        $tradingData = [
                            'uid'          => $parent_id,
                            'trading_type' => 2,
                            'change_type'  => 13,
                            'money'        => 8,
                            'befor_money'  => $parent_info['commission'],
                            'after_money'  => bcadd($parent_info['commission'], 8, 2),
                            'message'      => '推广创业店主奖励',
                        ];
                        DbUser::saveLogTrading($tradingData);
                        DbUser::modifyCommission($parent_id, 8, 'inc');

                        //判断是否需要写入兼职市场经理升级合伙人任务记录中
                        $upgrade_task = DbRights::getUserTask(['uid' => $parent_id, 'type' => 3, 'status' => 1], '*', true);
                        if (!empty($upgrade_task)) {
                            $task_invited = [];
                            $task_invited = [
                                'utask_id'      => $upgrade_task['id'],
                                'uid'           => $uid,
                                'user_identity' => 3,
                                'timekey'       => date('Ym', time()),
                                'bonus'         => 0,
                            ];
                            DbRights::addTaskInvited($task_invited);
                            $new_upgrade_task = [];
                            $new_upgrade_task = [
                                'has_target' => $upgrade_task['has_target'] + 1,
                            ];
                            //判断是否达到任务完成条件
                            if ($upgrade_task['target'] <= $new_upgrade_task['has_target']) {
                                $new_upgrade_task['status'] = 2;
                                //完成条件升级成为BOSS
                                $redisKey = Config::get('rediskey.user.redisUserOpenbossLock');
                                //该BOSS已选择其他开店方式
                                if ($this->redis->setNx($redisKey . $parent_id, 1) === true) {
                                     //升级成为BOSS
                                     $bossId = $this->getBoss($parent_id);
                                     if ($bossId == 1) {
                                         $re = $parent_id;
                                     } else {
                                         $re = $bossId . ',' . $parent_id;
                                     }
                                     $userRelationList = DbUser::getUserRelation([['relation', 'like', '%,' . $parent_id . ',%']], 'id,relation');
                                     $userRelationData = [];
                                     if (!empty($userRelationList)) {
                                         foreach ($userRelationList as $url) {
                                             $url['relation'] = substr($url['relation'], stripos($url['relation'], ',' . $parent_id . ',') + 1);
                                             array_push($userRelationData, $url);
                                         }
                                     }
                                     $shopData = [
                                         'uid'         => $parent_id,
                                         'shop_right'  => 'all',
                                         'status'      => 1,
                                         'create_time' => time(),
                                     ];
                                     $pid        = $bossId == 1 ? 0 : $bossId;
                                     $relationId = $this->getRelation($parent_id)['id'];
                                     if (!empty($userRelationData)) {
                                         DbUser::updateUserRelation($userRelationData);
                                     }
                                     DbUser::updateUserRelation(['is_boss' => 1, 'relation' => $re, 'pid' => $pid], $relationId);
                                     DbShops::addShop($shopData); //添加店铺
                                     DbUser::updateUser(['user_identity' => 4, 'user_market' => 0], $parent_id);
                                     $this->redis->del($redisKey . $parent_id);
                                } 
                            }
                            DbRights::editUserTask($new_upgrade_task, $upgrade_task['id']);
                        }

                        //判断是否需要给与额外奖励1200或者每一人12元
                        //测试数量2
                        $thismonth_num = DbRights::getTaskInvitedCount(['utask_id' => $id, 'timekey' => date('Ym', time())]);
                        if ($thismonth_num >= 2) {
                            $month_task_bonus = DbRights::getUserTask(['uid' => $parent_id, 'type' => 4, 'timekey' => date('Ym', time())], 'id', true);
                            if (empty($month_task_bonus)) {
                                $add_month_task = [];
                                $add_month_task = [
                                    'uid'        => $parent_id,
                                    'title'      => '推广创业店主额外奖励1200',
                                    'type'       => 4,
                                    'has_target' => 100,
                                    'target'     => 100,
                                    'status'     => 2,
                                    'bonus'      => 1200,
                                    'timekey'    => date('Ym', time()),
                                ];
                                DbRights::addUserTask($add_month_task);
                                $tradingData = [];
                                $tradingData = [
                                    'uid'          => $parent_id,
                                    'trading_type' => 2,
                                    'change_type'  => 13,
                                    'money'        => $add_month_task['bonus'],
                                    'befor_money'  => $parent_info['commission'],
                                    'after_money'  => bcadd($parent_info['commission'], $add_month_task['bonus'], 2),
                                    'message'      => '推广创业店主额外奖励1200',
                                ];
                                DbUser::saveLogTrading($tradingData);
                                DbUser::modifyCommission($parent_id, $add_month_task['bonus'], 'inc');

                                //上级总监提成
                                // $parent_userRelation = $this->getRelation($parent_id);
                                // $rela_user           = DbUser::getUserInfo(['id' => $parent_userRelation['pid']], 'commission,user_identity,nick_name,user_market', true);
                                // if (!empty($rela_user) && $rela_user['user_market'] > 2) {

                                //     if ($rela_user['user_market'] == 3) {
                                //         $ptype   = 12;
                                //         $ptitle  = '兼职市场总监1获得兼职市场经理本月推广100创业奖励任务';
                                //         $p_bouns = bcmul(1200, 0.1, 2);
                                //     } else if ($rela_user['user_market'] == 4) {
                                //         $ptype   = 13;
                                //         $ptitle  = '兼职市场总监2获得兼职市场经理本月推广100创业奖励任务';
                                //         $p_bouns = bcmul(1200, 0.15, 2);
                                //     }
                                //     $p_task    = DbRights::getUserTask(['uid' => $parent_userRelation['pid'], 'type' => $ptype, 'timekey' => date('Ym', time())], '*', true);
                                //     $newp_task = [];
                                //     if (empty($p_task)) {
                                //         $newp_task = [
                                //             'uid'          => $parent_userRelation['pid'],
                                //             'title'        => $ptitle,
                                //             'type'         => $ptype,
                                //             'has_target'   => 1,
                                //             'status'       => 1,
                                //             'bonus'        => $p_bouns,
                                //             'bonus_status' => 2,
                                //             'timekey'      => date('Ym', time()),
                                //             'start_time'   => time(),
                                //         ];
                                //         $p_task_id = DbRights::addUserTask($newp_task);
                                //     } else {
                                //         $newp_task = [
                                //             'has_target' => $p_task['has_target'] + 1,
                                //             'bonus'      => $p_task['bonus'] + $p_bouns,
                                //         ];
                                //         DbRights::editUserTask($newp_task, $p_task['id']);
                                //         $p_task_id = $p_task['id'];

                                //     }
                                //     $p_task_invited = [
                                //         'utask_id'      => $p_task_id,
                                //         'uid'           => $parent_id,
                                //         'user_identity' => 5,
                                //         'bonus'         => $p_bouns,
                                //         'timekey'       => date('Ym', time()),
                                //     ];
                                //     DbRights::addTaskInvited($p_task_invited);
                                //     $tradingData = [];
                                //     $tradingData = [
                                //         'uid'          => $parent_userRelation['pid'],
                                //         'trading_type' => 2,
                                //         'change_type'  => 13,
                                //         'money'        => $p_bouns,
                                //         'befor_money'  => $rela_user['commission'],
                                //         'after_money'  => bcadd($rela_user['commission'], $p_bouns, 2),
                                //         'message'      => '推广创业店主奖励',
                                //     ];
                                //     DbUser::saveLogTrading($tradingData);
                                //     DbUser::modifyCommission($parent_userRelation['pid'], $p_bouns, 'inc');
                                // }
                                $parent_userRelation = $this->getRelation($parent_id)['relation'];
                                $parent_userRelation = explode(',', $parent_userRelation);
                                $p_bossid            = $this->getPrentBoss($parent_userRelation);
                                if ($p_bossid) {
                                    $rela_user = DbUser::getUserInfo(['id' => $p_bossid], 'commission,user_identity,nick_name,user_market', true);
                                    if ($rela_user['user_market'] > 2) {
                                        if ($rela_user['user_market'] == 3) {
                                            $ptype   = 12;
                                            $ptitle  = '兼职市场总监1获得兼职市场经理本月推广100创业奖励任务';
                                            $p_bouns = bcmul(1200, 0.1, 2);
                                        } else if ($rela_user['user_market'] == 4) {
                                            $ptype   = 13;
                                            $ptitle  = '兼职市场总监2获得兼职市场经理本月推广100创业奖励任务';
                                            $p_bouns = bcmul(1200, 0.15, 2);
                                        }
                                        $p_task    = DbRights::getUserTask(['uid' => $p_bossid, 'type' => $ptype, 'timekey' => date('Ym', time())], '*', true);
                                        $newp_task = [];
                                        if (empty($p_task)) {
                                            $newp_task = [
                                                'uid'          => $p_bossid,
                                                'title'        => $ptitle,
                                                'type'         => $ptype,
                                                'has_target'   => 1,
                                                'status'       => 1,
                                                'bonus'        => $p_bouns,
                                                'bonus_status' => 2,
                                                'timekey'      => date('Ym', time()),
                                                'start_time'   => time(),
                                            ];
                                            $p_task_id = DbRights::addUserTask($newp_task);
                                        } else {
                                            $newp_task = [
                                                'has_target' => $p_task['has_target'] + 1,
                                                'bonus'      => $p_task['bonus'] + $p_bouns,
                                            ];
                                            DbRights::editUserTask($newp_task, $p_task['id']);
                                            $p_task_id = $p_task['id'];

                                        }
                                        $p_task_invited = [
                                            'utask_id'      => $p_task_id,
                                            'uid'           => $parent_id,
                                            'user_identity' => 5,
                                            'bonus'         => $p_bouns,
                                            'timekey'       => date('Ym', time()),
                                        ];
                                        DbRights::addTaskInvited($p_task_invited);
                                        $tradingData = [];
                                        $tradingData = [
                                            'uid'          => $p_bossid,
                                            'trading_type' => 2,
                                            'change_type'  => 13,
                                            'money'        => $p_bouns,
                                            'befor_money'  => $rela_user['commission'],
                                            'after_money'  => bcadd($rela_user['commission'], $p_bouns, 2),
                                            'message'      => '推广创业店主奖励',
                                        ];
                                        DbUser::saveLogTrading($tradingData);
                                        DbUser::modifyCommission($p_bossid, $p_bouns, 'inc');
                                    }

                                } 

                            }
                            else {
                                $the_month_extra_bonus = DbRights::getUserTask(['uid' => $parent_id, 'type' => 5, 'timekey' => date('Ym', time())], 'id,has_target,bonus', true);
                                if (empty($the_month_extra_bonus)) {
                                    $add_month_extra_bonus = [
                                        'uid'          => $parent_id,
                                        'title'        => '推广创业店主额外奖励',
                                        'type'         => 5,
                                        'has_target'   => 1,
                                        'status'       => 1,
                                        'bonus'        => 12,
                                        'bonus_status' => 2,
                                        'timekey'      => date('Ym', time()),
                                    ];
                                    $extra_id = DbRights::addUserTask($add_month_extra_bonus);
                                } else {
                                    $extra_id         = $the_month_extra_bonus['id'];
                                    $new_upgrade_task = [];
                                    $new_upgrade_task = [
                                        'has_target' => $the_month_extra_bonus['has_target'] + 1,
                                        'bonus' => $the_month_extra_bonus['bonus'] + 12,
                                    ];
                                    DbRights::editUserTask($new_upgrade_task, $extra_id);
                                }
                                $task_invited = [];
                                $task_invited = [
                                    'utask_id'      => $extra_id,
                                    'uid'           => $uid,
                                    'user_identity' => 3,
                                    'timekey'       => date('Ym', time()),
                                    'bonus'         => 12,
                                ];
                                DbRights::addTaskInvited($task_invited);

                                $tradingData = [];
                                $tradingData = [
                                    'uid'          => $parent_id,
                                    'trading_type' => 2,
                                    'change_type'  => 13,
                                    'money'        => 12,
                                    'befor_money'  => $parent_info['commission'],
                                    'after_money'  => bcadd($parent_info['commission'], 12, 2),
                                    'message'      => '推广创业店主额外奖励12/人',
                                ];
                                DbUser::saveLogTrading($tradingData);
                                DbUser::modifyCommission($parent_id, 12, 'inc');
                                // $parent_userRelation = $this->getRelation($parent_id);
                                // $rela_user           = DbUser::getUserInfo(['id' => $parent_userRelation['pid']], 'commission,user_identity,nick_name,user_market', true);
                                // if (!empty($rela_user) && $rela_user['user_market'] > 2) {
                                //     if ($rela_user['user_market'] == 3) {
                                //         $ptype   = 10;
                                //         $ptitle  = '兼职市场总监1获得兼职市场经理超额完成任务奖励';
                                //         $p_bouns = bcmul(12, 0.1, 2);
                                //     } else if ($rela_user['user_market'] == 4) {
                                //         $ptype   = 11;
                                //         $ptitle  = '兼职市场总监2获得兼职市场经理超额完成任务奖励';
                                //         $p_bouns = bcmul(12, 0.15, 2);
                                //     }
                                //     $p_task    = DbRights::getUserTask(['uid' => $parent_userRelation['pid'], 'type' => $ptype, 'timekey' => date('Ym', time())], '*', true);
                                //     $newp_task = [];
                                //     if (empty($p_task)) {
                                //         $newp_task = [
                                //             'uid'          => $parent_userRelation['pid'],
                                //             'title'        => $ptitle,
                                //             'type'         => $ptype,
                                //             'has_target'   => 1,
                                //             'status'       => 1,
                                //             'bonus'        => $p_bouns,
                                //             'bonus_status' => 2,
                                //             'timekey'      => date('Ym', time()),
                                //             'start_time'   => time(),
                                //         ];
                                //         $p_task_id = DbRights::addUserTask($newp_task);
                                //     } else {
                                //         $newp_task = [
                                //             'has_target' => $p_task['has_target'] + 1,
                                //             'bonus'      => $p_task['bonus'] + $p_bouns,
                                //         ];
                                //         DbRights::editUserTask($newp_task, $p_task['id']);
                                //         $p_task_id = $p_task['id'];

                                //     }
                                //     $p_task_invited = [
                                //         'utask_id'      => $p_task_id,
                                //         'uid'           => $parent_id,
                                //         'user_identity' => 5,
                                //         'bonus'         => $p_bouns,
                                //         'timekey'       => date('Ym', time()),
                                //     ];
                                //     DbRights::addTaskInvited($p_task_invited);
                                //     $tradingData = [];
                                //     $tradingData = [
                                //         'uid'          => $parent_userRelation['pid'],
                                //         'trading_type' => 2,
                                //         'change_type'  => 13,
                                //         'money'        => $p_bouns,
                                //         'befor_money'  => $rela_user['commission'],
                                //         'after_money'  => bcadd($rela_user['commission'], $p_bouns, 2),
                                //         'message'      => '兼职市场经理超额完成任务奖励',
                                //     ];
                                //     DbUser::saveLogTrading($tradingData);
                                //     DbUser::modifyCommission($parent_userRelation['pid'], $p_bouns, 'inc');
                                $parent_userRelation = $this->getRelation($parent_id)['relation'];
                                $parent_userRelation = explode(',', $parent_userRelation);
                                $p_bossid            = $this->getPrentBoss($parent_userRelation);
                                if ($p_bossid) {
                                    $rela_user = DbUser::getUserInfo(['id' => $p_bossid], 'commission,user_identity,nick_name,user_market', true);
                                    if ($rela_user['user_market'] > 2) {
                                        if ($rela_user['user_market'] == 3) {
                                            $ptype   = 10;
                                            $ptitle  = '兼职市场总监1获得兼职市场经理超额完成任务奖励';
                                            $p_bouns = bcmul(12, 0.1, 2);
                                        } else if ($rela_user['user_market'] == 4) {
                                            $ptype   = 11;
                                            $ptitle  = '兼职市场总监2获得兼职市场经理超额完成任务奖励';
                                            $p_bouns = bcmul(12, 0.15, 2);
                                        }
                                        $p_task    = DbRights::getUserTask(['uid' => $p_bossid, 'type' => $ptype, 'timekey' => date('Ym', time())], '*', true);
                                        $newp_task = [];
                                        if (empty($p_task)) {
                                            $newp_task = [
                                                'uid'          => $p_bossid,
                                                'title'        => $ptitle,
                                                'type'         => $ptype,
                                                'has_target'   => 1,
                                                'status'       => 1,
                                                'bonus'        => $p_bouns,
                                                'bonus_status' => 2,
                                                'timekey'      => date('Ym', time()),
                                                'start_time'   => time(),
                                            ];
                                            $p_task_id = DbRights::addUserTask($newp_task);
                                        } else {
                                            $newp_task = [
                                                'has_target' => $p_task['has_target'] + 1,
                                                'bonus'      => $p_task['bonus'] + $p_bouns,
                                            ];
                                            DbRights::editUserTask($newp_task, $p_task['id']);
                                            $p_task_id = $p_task['id'];

                                        }
                                        $p_task_invited = [
                                            'utask_id'      => $p_task_id,
                                            'uid'           => $parent_id,
                                            'user_identity' => 5,
                                            'bonus'         => $p_bouns,
                                            'timekey'       => date('Ym', time()),
                                        ];
                                        DbRights::addTaskInvited($p_task_invited);
                                        $tradingData = [];
                                        $tradingData = [
                                            'uid'          => $p_bossid,
                                            'trading_type' => 2,
                                            'change_type'  => 13,
                                            'money'        => $p_bouns,
                                            'befor_money'  => $rela_user['commission'],
                                            'after_money'  => bcadd($rela_user['commission'], $p_bouns, 2),
                                            'message'      => '兼职市场经理超额完成任务奖励',
                                        ];
                                        DbUser::saveLogTrading($tradingData);
                                        DbUser::modifyCommission($p_bossid, $p_bouns, 'inc');
                                    }
                                }
                            }
                        }
                    }

                }
                DbUser::updateUser(['user_identity' => 3], $uid);
                $this->resetUserInfo($uid);
                $this->resetUserInfo($parent_id);
                Db::commit();
                return ['code' => '200']; //领取成功
            } catch (\Exception $e) {
                exception($e);
                Db::rollback();
                return ['code' => '3005']; //领取失败
            }
        } elseif ($refe_type == 2) {
            if ($userInfo['user_market'] > 0) {
                return ['code' => '3006'];
            }

            $user_task = DbRights::getUserTask(['uid' => $uid, 'type' => 1, 'status' => 3], '*', true, ['id' => 'desc']);
            $has       = 0;
            $has_up = [];
            if ($user_task) {
                if (strtotime($user_task['end_time'])< time()) {
                   $has_up['status'] = 3;
                }
                $has = DbRights::getUserTaskCount(['uid' => $uid, 'type' => 1]);
            }
            $has_num = $has + 1;

            $add_user_task               = [];
            $add_user_task['uid']        = $uid;
            $add_user_task['title']      = '第' . $has_num . '次升级任务';
            $add_user_task['type']       = 1;
            $add_user_task['target']     = 5;
            $add_user_task['status']     = 1;
            $add_user_task['timekey']    = date('Ym', time());
            $add_user_task['start_time'] = time();
            $add_user_task['end_time']   = strtotime("+1 year",time());
            Db::startTrans();
            try {
                DbUser::updateUser(['user_market' => 1], $uid);
                DbRights::addUserTask($add_user_task);
                if (!empty($has_up)) {
                    DbRights::editUserTask($has_up,$user_task['id']);
                }
                $this->resetUserInfo($uid);
                Db::commit();
                return ['code' => '200']; //升级成功
            } catch (\Exception $e) {
                exception($e);
                Db::rollback();
                return ['code' => '3005']; //升级失败
            }
        } elseif ($refe_type == 3) {
            if ($userInfo['user_market'] > 2) {
                return ['code' => '3008'];
            }
            $add_user_task = [];
            $add_user_task = [
                'uid'          => $uid,
                'title'        => '升级兼职市场总监2任务',
                'type'         => 6,
                'target'       => 10,
                'status'       => 1,
                'bonus_status' => 2,
                'timekey'      => date('Ym', time()),
                'start_time'   => time(),

            ];
            Db::startTrans();
            try {
                DbUser::updateUser(['user_market' => 3], $uid);
                DbRights::addUserTask($add_user_task);
                $this->resetUserInfo($uid);
                Db::commit();
                return ['code' => '200']; //升级成功
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3005']; //升级失败
            }
        }
    }

    private function getBoss($uid) {
        if ($uid == 1) {
            return 1;
        }
        $relation = $this->getRelation($uid);
        $bossUid  = explode(',', $relation['relation'])[0];
        if ($uid == $bossUid) {
            return 1;
        }
        $pBossUidCheck = $this->getIdentity($bossUid);
        if ($pBossUidCheck != 4) { //relation第一个关系人不是boss说明是总店下的用户
            return 1;
        }
        return $bossUid;
    }
    private function getPrentBoss($data) {
        if (empty($data)) {
            return false;
        }
        $rela_user = [];
        foreach ($data as $key => $value) {
            $pr_boss = DbUser::getUserInfo(['id' => $value], 'user_identity,nick_name,user_market', true);
            if ($pr_boss['user_identity'] == 4) {
                $rela_user[] = $value;
            }
        }
        if (!empty($rela_user)) {
            return end($rela_user);
        }
        return false;
    }

    private function getRelation($uid) {
        $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,pid,is_boss,relation', true);
        return $userRelation;
    }

    /**
     * 获取用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @param $uid
     * @return mixed
     * @author zyr
     */
    private function getIdentity($uid) {
        $user = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if (empty($user)) {
            return false;
        }
        return $user['user_identity'];
    }

    public function userTask($conId, int $page, int $pageNum, $taskid = 0) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'id', true);
        if (empty($userInfo)) {
            return ['code' => '3000'];
        }
        if (!empty($taskid)) {
            $usertask = DbRights::getUserTask(['uid' => $uid, 'id' => $taskid], 'id,title,type,target,has_target,status,bonus,bonus_status,start_time,end_time', true);
            return ['code' => 200, 'usertask' => $usertask];
        }
        $offset    = ($page - 1) * $pageNum;
        $usertask  = DbRights::getUserTask(['uid' => $uid], 'id,title,type,target,has_target,status,bonus,bonus_status,start_time,end_time', false, ['status' => 'asc', 'type' => 'desc'], $offset . ',' . $pageNum);
        $had_bonus = DbRights::getUserTaskSum(['uid' => $uid, 'bonus_status' => 2], 'bonus');
        $no_bonus  = DbRights::getUserTaskSum(['uid' => $uid, 'bonus_status' => 1], 'bonus');
        return ['code' => 200, 'had_bonus' => $had_bonus, 'no_bonus' => $no_bonus, 'usertask' => $usertask];
    }

    public function userTaskProgress($conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'id,user_market', true);
        if (empty($userInfo)) {
            return ['code' => '3000'];
        }
        $where = [];
        array_push($where, ['status', '=', 1]);
        array_push($where, ['uid', '=', $uid]);
        if ($userInfo['user_market'] == 0) {
            return ['code' => '200', 'taskprogress' => ''];
        } elseif ($userInfo['user_market'] == 1) { //临时兼职市场经理
            $type = 1;
        } elseif ($userInfo['user_market'] == 2) { //兼职市场经理
            $type = 3;
        } elseif ($userInfo['user_market'] == 3) { //兼职市场总监
            $type = 6;
        } elseif ($userInfo['user_market'] == 4) {
            $type = 6;
        }
        array_push($where, ['type', '=', $type]);
        $userTask = DbRights::getUserTask($where, 'target,has_target', true, ['id' => 'desc']);
        // print_r($userTask);die;
        if (empty($userTask)) {
            return ['code' => '3001'];
        }
        return ['code' => '200', 'taskprogress' => '任务进度' . $userTask['has_target'] . '/' . $userTask['target']];
    }

    public function userTaskInfo($conId, $taskid, $page, $pageNum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'id,user_market', true);
        if (empty($userInfo)) {
            return ['code' => '3000'];
        }
        $usertask = DbRights::getUserTask(['uid' => $uid, 'id' => $taskid], 'id', true);
        if (empty($usertask)) {
            return ['code' => '3002'];
        }
        $offset       = ($page - 1) * $pageNum;
        $task_invited = DbRights::getTaskInvited(['utask_id' => $taskid], '*', false, ['id' => 'asc'], $offset . ',' . $pageNum);
        return ['code' => '200', 'task_invited' => $task_invited];
    }
}
/* {"appid":"wx112088ff7b4ab5f3","attach":"2","bank_type":"CMB_DEBIT","cash_fee":"600","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"lzlqdk6lgavw1a3a8m69pgvh6nwxye89","openid":"o83f0wAGooABN7MsAHjTv4RTOdLM","out_trade_no":"PAYSN201806201611392442","result_code":"SUCCESS","return_code":"SUCCESS","sign":"108FD8CE191F9635F67E91316F624D05","time_end":"20180620161148","total_fee":"600","trade_type":"JSAPI","transaction_id":"4200000112201806200521869502"} */
