<?php

namespace app\common\action\admin;

use app\facade\DbShops;
use app\facade\DbUser;
use think\Db;

class User extends CommonIndex {
    /**
     * 会员列表
     * @return array
     * @author rzc
     */
    public function getUsers($page, $pagenum ,$mobile = '') {
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $where = [];
        if (!empty($mobile)) {
            array_push($where, ['mobile', '=', $mobile]);
        }
        $limit  = $offset . ',' . $pagenum;
        $result = DbUser::getUserInfo($where,'*', false,'id', $limit,'desc');
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $totle = DbUser::getUserInfoCount($where);
        return ['code' => '200', 'totle' => $totle, 'result' => $result];
    }

    /**
     * boss降级处理
     * @param $mobile
     * @param $userIdentity
     * @return array
     * @author zyr
     */
    public function userDemotion($mobile, $userIdentity) {
        $userInfo = DbUser::getUserInfo(['mobile' => $mobile], 'id,user_identity', true);
        if ($userInfo['user_identity'] != 4) {
            return ['code' => '3003'];//只有boss可以降级
        }
        $bonus     = DbUser::getLogBonus(['to_uid' => $userInfo['id'], 'status' => 1], 'order_no');
        $orderList = array_values(array_unique(array_column($bonus, 'order_no')));
        if (count($orderList) > 0) {
            return ['code' => '3004', 'order_list' => $orderList];//有未完成订单
        }
        $relation = DbUser::getUserRelation(['uid' => $userInfo['id']], 'relation', true);
        $bossUid  = explode(',', $relation['relation'])[0];
        $bossInfo = DbUser::getUserInfo(['id' => $bossUid,], 'user_identity', true);
        if ($bossInfo['user_identity'] != 4 || $bossUid == $userInfo['id']) {
            $bossUid = 1;
        }
        $relationList = DbUser::getUserRelation([['relation', 'like', $userInfo['id'] . ',%']], 'id,uid,relation');
        foreach ($relationList as &$rl) {
            $rl['relation'] = $bossUid . ',' . $rl['relation'];
        }
        unset($rl);
        $shopId          = DbShops::getShopInfo('id', ['uid' => $userInfo['id']])['id'];
        $shopGoodsList   = DbShops::getShopGoods(['shop_id' => $shopId], 'id');
        $shopGoodsListId = array_column($shopGoodsList, 'id');
        $logDemotionData = [
            'uid'      => $userInfo['id'],
            'boss_uid' => $bossUid,
            'uid_list' => json_encode(array_column($relationList, 'uid')),
        ];
        Db::startTrans();
        try {
            DbShops::deleteShop($shopId);
            DbUser::updateUser(['user_identity' => $userIdentity], $userInfo['id']);
            DbUser::updateUserRelation($relationList);
            DbShops::deleteShopGoods($shopGoodsListId);
            DbShops::addLogDemotion($logDemotionData);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3006']; //修改失败
        }
    }

}