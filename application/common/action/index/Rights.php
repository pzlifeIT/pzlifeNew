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
     * @author zyr
     */
    public function receiveDiamondvip($con_id,$parent_id){
        $uid = $this->getUidByConId($con_id);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $userInfo = DbUser::getUserInfo(['id'=>$uid],'user_identity');
        if ($userInfo['user_identity']>1) {
            return ['code' => '3004','msg' => '当前身份等级大于或等于钻石会员，无法领取'];
        }
        $DiamondvipDominos = DbRights::getDiamondvips(['uid' => $parent_id,'status' => 1,'type' => 1],'*',true);
        if (!$DiamondvipDominos || $DiamondvipDominos['stock']<$DiamondvipDominos['num']+1) {
            return ['code' => '3005','分享用户没有分享机会'];
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
     * @author zyr
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
     * @author zyr
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

}
/* {"appid":"wx112088ff7b4ab5f3","attach":"2","bank_type":"CMB_DEBIT","cash_fee":"600","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"lzlqdk6lgavw1a3a8m69pgvh6nwxye89","openid":"o83f0wAGooABN7MsAHjTv4RTOdLM","out_trade_no":"PAYSN201806201611392442","result_code":"SUCCESS","return_code":"SUCCESS","sign":"108FD8CE191F9635F67E91316F624D05","time_end":"20180620161148","total_fee":"600","trade_type":"JSAPI","transaction_id":"4200000112201806200521869502"} */
