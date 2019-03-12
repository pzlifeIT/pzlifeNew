<?php

namespace app\common\action\index;

use app\facade\DbUser;
use app\facade\DbRights;
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
    public function receiveDiamondvip($con_id,$parent_id){
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id'=>$uid],'user_identity',true);
        if ($userInfo['user_identity']>1) {
            return ['code' => '3004','msg' => '当前身份等级大于或等于钻石会员，无法领取'];
        }
        $DiamondvipDominos = DbRights::getDiamondvips(['uid' => $parent_id,'status' => 1,'type' => 1],'*',true);
        if (!$DiamondvipDominos  ) {
            return ['code' => '3005','分享用户没有分享机会'];
        }
        if ($DiamondvipDominos['stock']<$DiamondvipDominos['num']+1) {
            DbRights::updateDiamondvip(['status' => 3],$DiamondvipDominos['id']);
            return ['code' => '3006','该机会已领完'];
        }
        $receiveDiamondvip = [];
        $receiveDiamondvip['uid'] = $uid;
        $receiveDiamondvip['diamondvips_id'] = $DiamondvipDominos['id'];
        $receiveDiamondvip['share_uid'] = $parent_id;
        Db::startTrans();
        try {
            DbRights::receiveDiamondvip($receiveDiamondvip);
            DbRights::updateDiamondvip(['num' =>$DiamondvipDominos['num']+1],$DiamondvipDominos['id']);
            DbUser::updateUser(['user_identity'=>2],$uid);
            $this->resetUserInfo($uid);
            Db::commit();
            return ['code' => '200'];//领取成功
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//领取失败
        }

    }

    /**
     * 判断会员是否有分享钻石接龙的的资格
     * @param $parent_id
     * @return array
     * @author rzc
     */
    public function IsGetDominos($parent_id){
        $userInfo = DbUser::getUserInfo(['id'=>$parent_id],'user_identity',true);
        if ($userInfo['user_identity']<4) {
            return ['code' => '3004','msg' => '非BOSS无法开启分享钻石接龙资格（200名额）'];
        }
        $DiamondvipDominos = DbRights::getDiamondvips(['uid' => $parent_id,'status' => 1,'type' => 1],'*',true);
        if (!$DiamondvipDominos || $DiamondvipDominos['stock']<$DiamondvipDominos['num']+1) {
            return ['code' => '3005','分享用户没有分享机会'];
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
    public function IsBossDominos($con_id){
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id'=>$uid],'user_identity',true);
        if ($userInfo['user_identity']<4) {
            return ['code' => '3004','msg' => '非BOSS无法开启分享钻石接龙资格（200名额）'];
        }
        $DiamondvipDominos = DbRights::getDiamondvips(['uid' => $uid,'status' => 1,'type' => 1],'*',true);
        if (!$DiamondvipDominos || $DiamondvipDominos['stock']<$DiamondvipDominos['num']+1) {
            return ['code' => '3005','分享用户没有分享机会'];
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
    public function getDominosBalanceHint($con_id){
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $redisListKey = Config::get('redisKey.order.redisMemberShare');
        $uer_balance_hint = $this->redis->hgetall($redisListKey.$uid);
        
        if ($uer_balance_hint) {
            $this->redis->hdel($redisListKey . $uid);
            return ['code' => 200,'msg' => '用户有到账红包'];
        }else{
            return ['code' => '3000'];
        }
    }

    /**
     * 获取用户红包提示
     * @param $con_id
     * @return array
     * @author rzc
     */
    public function getDominosChance($con_id){
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id'=>$uid],'user_identity',true);
        if ($userInfo['user_identity']>1) {
            
            $Diamondvips = DbRights::getDiamondvips(['uid' => $uid,'status' => 1,'type' => 1],'*');
            $DiamondvipDominos = DbRights::getCountDiamondvips(['share_uid' => $uid]);
            return ['code' => 200,'Diamondvips' =>$Diamondvips,'DiamondvipDominos'=>$DiamondvipDominos];
        }else{
            return ['code' => '3004'];
        }
       
    }

    /**
     * 获取用户钻石会员领取机会记录
     * @param $con_id
     * @return array
     * @author rzc
     */
    public function getDominosReceive($con_id,$diamondvips_id = false){
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id'=>$uid],'user_identity',true);
        if ($userInfo['user_identity']>1) {
            
            if ($diamondvips_id){
                $where = ['diamondvips_id' => $diamondvips_id];
            }else{
                $where = ['share_uid' => $con_id];
            }
            $getDiamondvipDominos = DbRights::getDiamondvip($where,'*');
            if (empty($getDiamondvipDominos)){
                return ['code' => '3000'];
            }
            foreach ($getDiamondvipDominos as $get => $Dominos) {
                $userInfo = DbUser::getUserInfo(['id'=>$Dominos['uid']],'id,nick_name,avatar',true);
                $getDiamondvipDominos[$get]['uid'] = enuid($userInfo['id']);
                $getDiamondvipDominos[$get]['nick_name'] = $userInfo['nick_name'];
                $getDiamondvipDominos[$get]['avatar'] = $userInfo['avatar'];
            }
            return ['code' => 200,'Diamondvips' =>$getDiamondvipDominos];
        }else{
            return ['code' => '3004'];
        }
    }
}
/* {"appid":"wx112088ff7b4ab5f3","attach":"2","bank_type":"CMB_DEBIT","cash_fee":"600","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"lzlqdk6lgavw1a3a8m69pgvh6nwxye89","openid":"o83f0wAGooABN7MsAHjTv4RTOdLM","out_trade_no":"PAYSN201806201611392442","result_code":"SUCCESS","return_code":"SUCCESS","sign":"108FD8CE191F9635F67E91316F624D05","time_end":"20180620161148","total_fee":"600","trade_type":"JSAPI","transaction_id":"4200000112201806200521869502"} */
