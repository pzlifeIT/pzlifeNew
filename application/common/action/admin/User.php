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
     * @param $content
     * @return array
     * @author zyr
     */
    public function userDemotion($mobile, $userIdentity, $content) {
        $userInfo = DbUser::getUserInfo(['mobile' => $mobile], 'id,user_identity', true);
        if ($userInfo['user_identity'] != 4) {
            return ['code' => '3003'];//只有boss可以降级
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
            'uid'            => $userInfo['id'],
            'after_identity' => $userIdentity,
            'boss_uid'       => $bossUid,
            'content'        => $content,
            'uid_list'       => json_encode(array_column($relationList, 'uid')),
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

    /**
     * boss降级处理列表
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function userDemotionList($page, $pageNum) {
        $offset = ($page - 1) * $pageNum;
        $list   = DbShops::getLogDemotion([], 'uid,after_identity,boss_uid,content,uid_list,create_time', false, 'id', 'asc', $offset . ',' . $pageNum);
        $bonus  = DbUser::getLogBonus([
            ['to_uid', 'in', array_column($list, 'uid')],
            ['status', '=', 1]
        ], 'to_uid,order_no');
        $result = [];
        foreach ($list as $l) {
            $l['uid_list'] = json_decode($l['uid_list'], true);
            $orderList     = [];
            foreach ($bonus as $b) {
                if ($b['to_uid'] == $l['uid']) {
                    array_push($orderList, $b['order_no']);
                }
            }
            $orderList       = array_values(array_unique($orderList));
            $l['order_list'] = $orderList;
            array_push($result, $l);
        }
        return ['code' => '200', 'data' => $result];
    }

}