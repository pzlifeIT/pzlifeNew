<?php

namespace app\common\action\index;

use app\facade\DbRights;
use app\facade\DbShops;
use app\facade\DbUser;
use Config;
use think\Db;

class Rights extends CommonIndex {

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
     * 获取用户钻石会员领取机会记录
     * @param $con_id
     * @return array
     * @author rzc
     */
    public function shopApplyBoss($con_id, $target_nickname, $target_sex, $target_mobile, $target_idcard, $refe_type, $parent_id) {
        $redisKey = Config::get('rediskey.user.redisUserOpenbossLock');
        $refe_type = 2; //暂时只支持购买合伙人
        $uid       = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        if($this->redis->setNx($redisKey . $uid)===false){
            return ['code'=>'3013'];
        }
        $userInfo = DbUser::getUserInfo(['id' => $uid], 'user_identity,nick_name', true);
        if ($userInfo['user_identity'] == 4) {
            return ['code' => '3010'];
        }
        $parent_info = DbUser::getUserInfo(['id' => $parent_id, 'user_identity' => 4], 'user_identity,nick_name', true);
        if (!empty($parent_info)) {
            $parent_shop = DbShops::getShopInfo('id', ['uid' => $parent_id]);
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
        $apply_data['shop_id']         = $parent_shop['id'];
        $apply_data['refe_type']       = $refe_type;
        $apply_data['status']          = 1;

        //招商代理收益日志
        $log_invest               = [];
        $log_invest['uid']        = $parent_id;
        $log_invest['target_uid'] = $uid;
        $log_invest['status']     = 1;
        $log_invest['cost']       = 5000;
        Db::startTrans();
        try {
            DbRights::saveShopApply($apply_data);
            DbUser::saveLogInvest($log_invest);
            Db::commit();
            return ['code' => '200']; //领取成功
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3005']; //领取失败
        }
    }
}
/* {"appid":"wx112088ff7b4ab5f3","attach":"2","bank_type":"CMB_DEBIT","cash_fee":"600","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"lzlqdk6lgavw1a3a8m69pgvh6nwxye89","openid":"o83f0wAGooABN7MsAHjTv4RTOdLM","out_trade_no":"PAYSN201806201611392442","result_code":"SUCCESS","return_code":"SUCCESS","sign":"108FD8CE191F9635F67E91316F624D05","time_end":"20180620161148","total_fee":"600","trade_type":"JSAPI","transaction_id":"4200000112201806200521869502"} */
