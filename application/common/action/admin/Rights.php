<?php

namespace app\common\action\admin;

use app\common\action\admin\Admin;
use app\facade\DbRights;
use app\facade\DbShops;
use app\facade\DbOrder;
use app\facade\DbUser;
use think\Db;

class Rights extends CommonIndex {
    /**
     * 创建分享钻石会员机会
     * @param $mobile
     * @param $linkman
     * @param $stock
     * @param $redmoney_status
     * @param $type
     * @param $coupon_money
     * @return array
     * @author rzc
     */
    public function creatBossShareDiamondvip($mobile, $linkman, $stock, $redmoney_status, $type, $coupon_money) {

        $user = DbUser::getUserOne(['mobile' => $mobile, 'user_identity' => 4], 'id');
        if (!$user) {
            return ['code' => '3003', 'msg' => '该用户不存在'];
        }
        $shop = DbShops::getShopInfo('id', ['uid' => $user['id']]);
        if (!$shop) {
            return ['code' => '3004', 'msg' => '非BOSS无法添加机会'];
        }
        if ($type == 2) {
            if ($stock > 500) {
                return ['code' => 3005, 'msg' => '绑定二维码链接设置最大库存为每次500名额'];
            }
        }

        $data = [];
        $data = [
            'uid'             => $user['id'],
            'shopid'          => $shop['id'],
            'linkman'         => $linkman,
            'stock'           => $stock,
            'redmoney_status' => $redmoney_status,
            'type'            => $type,
            'coupon_money'    => $coupon_money,
        ];
        $result = DbRights::creatDiamondvip($data);
        if ($result) {
            return ['code' => '200', 'msg' => '创建成功'];
        } else {
            return ['code' => '3006', 'msg' => '创建失败'];
        }
    }

    /**
     * 列表查询分享钻石会员机会
     * @param $page
     * @param $pagenum
     * @return array
     * @author rzc
     */
    public function getBossShareDiamondvip($page, $pagenum) {
        $offect = ($page - 1) * $pagenum;

        $result = DbRights::getDiamondvips([['1', '=', '1']], '*', false, 'id', 'desc', $offect . ',' . $pagenum);
        $totle  = DbRights::getCountDiamondvip();
        if (empty($result)) {
            return ['code' => 3000];
        }
        return ['code' => 200, 'totle' => $totle, 'data' => $result];
    }

    /**
     * 审核分享钻石会员机会
     * @param $id
     * @return array
     * @author rzc
     */
    public function passBossShareDiamondvip($id, $status) {
        $diamondvips = DbRights::getDiamondvips([['id', '=', $id]], '*', true);
        if (!$diamondvips) {
            return ['code' => 3000];
        }
        if ($diamondvips['status'] != 0) {
            return ['code' => 3002, 'msg' => '已审核过的申请无法再次申请'];
        }
        if ($status == $diamondvips['status']) {
            return ['code' => '3003', 'msg' => '无法再次进行当前状态审核'];
        }
        Db::startTrans();
        try {
            if ($status == 1) {
                if ($diamondvips['type'] == 2) {
                    $binding                   = [];
                    $binding['diamondvips_id'] = $id;
                    $binding['coupon_money']   = $diamondvips['coupon_money'];
                    $binding['status']         = 1;
                    $i                         = 0;
                    do {
                        $code            = hash_hmac('sha1', $i . 'diamondvip' . $id . 'TIME' . time(), 'diamondvipbinding');
                        $binding['code'] = $code;
                        DbRights::creatDiamondvipBinding($binding);
                        $i++;
                    } while ($i < $diamondvips['stock']);
                }
                DbRights::updateDiamondvip(['status' => 1], $id);
            } elseif ($status == 2) {
                DbRights::updateDiamondvip(['status' => 2], $id);
            }
            // 提交事务
            Db::commit();
            return ['code' => '200', 'msg' => '审核通过'];
        } catch (\Exception $e) {
            // 回滚事务

            Db::rollback();
            return ['code' => '3004', 'msg' => '插入数据出错'];
        }
    }

    /**
     * 邀请开通boss列表
     * @param $page
     * @param $pageNum
     * @param $status
     * @param $target_uid
     * @param $target_uname
     * @param $target_nickname
     * @param $target_sex
     * @param $target_mobile
     * @param $target_idcard
     * @param $refe_uid
     * @param $refe_uname
     * @param $shop_id
     * @param $refe_type
     * @return array
     * @author rzc
     */
    public function getShopApplyList($page, $pageNum, $status, $target_uid, $target_uname, $target_nickname, $target_sex, $target_mobile, $target_idcard, $refe_uid, $refe_uname, $shop_id, $refe_type) {
        $offset = $pageNum * ($page - 1);
        //查找所有数据
        $where = [];
        if (!empty($status)) {
            array_push($where, ['status', '=', $status]);
        }
        if (!empty($target_uid)) {
            array_push($where, ['target_uid', '=', $target_uid]);
        }
        if (!empty($target_uname)) {
            array_push($where, ['target_uname', 'LIKE', '%' . $target_uname . '%']);
        }
        if (!empty($target_nickname)) {
            array_push($where, ['target_nickname', 'LIKE', '%' . $target_nickname . '%']);
        }
        if (!empty($target_sex)) {
            array_push($where, ['target_sex', '=', $target_sex]);
        }
        if (!empty($target_mobile)) {
            array_push($where, ['target_mobile', '=', $target_mobile]);
        }
        if (!empty($target_idcard)) {
            array_push($where, ['target_idcard', '=', $target_idcard]);
        }
        if (!empty($refe_uid)) {
            array_push($where, ['refe_uid', '=', $refe_uid]);
        }
        if (!empty($refe_uname)) {
            array_push($where, ['refe_uname', 'LIKE', '%' . $refe_uname . '%']);
        }
        if (!empty($shop_id)) {
            array_push($where, ['shop_id', '=', $shop_id]);
        }
        if (!empty($refe_type)) {
            array_push($where, ['refe_type', '=', $refe_type]);
        }
        $result = DbRights::getShopApply($where, '*', false, 'create_time', 'DESC', $offset . ',' . $pageNum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $total = DbRights::countShopApply($where);
        return ['code' => '200', 'total' => $total, 'data' => $result];
    }

    /**
     * 邀请开通boss列表
     * @param $id
     * @param $status
     * @param $message
     * @return array
     * @author rzc
     */
    public function auditShopApply($id, int $status, $message = '', $cmsConId) {
        $shopapply = DbRights::getShopApply(['id' => $id], '*', true);
        if (empty($shopapply)) {
            return ['code' => '3000'];
        }
        if ($status == $shopapply['status']) {
            return ['code' => '3005'];
        }
        $edit_shopapply = [];
        $edit_invest    = [];
        if ($status == 2) { //财务审核通过
            if ($shopapply['status'] != 1) {
                return ['code' => '3003'];
            }

        } elseif ($status == 3) { //经理审核通过
            if ($shopapply['status'] != 2) {
                return ['code' => '3003'];
            }

        } elseif ($status == 4) { //审核不通过
            if ($shopapply['status'] == 3) {
                return ['code' => '3003'];
            }
        }
        $edit_shopapply['status']  = $status;
        $edit_shopapply['message'] = $message;

        $edit_invest['status']  = $status;
        $edit_invest['message'] = $message;
        $invest                 = DbUser::getLogInvest(['uid' => $shopapply['refe_uid'], 'target_uid' => $shopapply['target_uid']], 'id,cost', true);
        Db::startTrans();
        // print_r($status);die;
        try {
            if ($status == 3) {
                $target_user = DbUser::getUserOne(['id' => $shopapply['target_uid']], 'id,mobile');
                $userRelationList = DbUser::getUserRelation([['relation', 'like', '%,' . $target_user['id'] . ',%']], 'id,relation');
                $userRelationData = [];
                $bossId = $this->getBoss($target_user['id']);
                if ($bossId == 1) {
                    $re = $target_user['id'];
                } else {
                    $re = $bossId . ',' . $target_user['id'];
                }
                if (!empty($userRelationList)) {
                    foreach ($userRelationList as $url) {
                        $url['relation'] = substr($url['relation'], stripos($url['relation'], ',' . $target_user['id'] . ',') + 1);
                        array_push($userRelationData, $url);
                    }
                }
                
                $shopData = [
                    'uid'         => $target_user['id'],
                    'shop_right'  => 'all',
                    'status'      => 1,
                    'create_time' => time(),
                ];
                $refe_user   = DbUser::getUserOne(['id' => $shopapply['refe_uid']], 'commission');
                $tradingData = [
                    'uid'          => $shopapply['refe_uid'],
                    'trading_type' => 2,
                    'change_type'  => 5,
                    'money'        => $invest['cost'],
                    'befor_money'  => $refe_user['commission'],
                    'after_money'  => bcadd($refe_user['commission'], $invest['cost'], 2),
                    'message'      => '',
                ];
                if (!empty($userRelationData)) {
                    DbUser::updateUserRelation($userRelationData);
                }
                $relationId = $this->getRelation($shopapply['target_uid'])['id'];
                // print_r($relationId);
                // print_r($shopData);
                // print_r($userRelationData);
                // print_r($tradingData);die;
                DbUser::updateUserRelation(['is_boss' => 1, 'relation' => $re, 'pid' => $shopapply['refe_uid']], $relationId);
                DbUser::updateUser(['user_identity' => 4], $shopapply['target_uid']);
                DbShops::addShop($shopData); //添加店铺
                DbOrder::addLogTrading($tradingData);//写佣金明细
                DbUser::modifyCommission($shopapply['refe_uid'], $invest['cost'],'inc');
            }
            // 提交事务
            DbUser::editLogInvest($edit_invest, $invest['id']);
            DbRights::editShopApply($edit_shopapply, $id);
            Db::commit();
            return ['code' => '200', 'msg' => '审核通过'];
        } catch (\Exception $e) {
            // 回滚事务
            exception($e);
            Db::rollback();
            return ['code' => '3006', 'msg' => '插入数据出错'];
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
}