<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use think\Db;
use cache\Phpredis;

class Order extends Pzlife {
    private $redis;

//    private $connect;

    private function orderInit() {
        $this->redis = Phpredis::getConn();
//        $this->connect = Db::connect(Config::get('database.db_config'));
    }

    /**
     * 定时取消订单
     * 每分钟执行一次
     */
    public function cancelOrder() {
        $this->orderInit();
        $orderOutTime = Config::get('conf.order_out_time');//订单过期时间
        $subTime      = time() - $orderOutTime;//过期时间节点
        $sql          = sprintf("select id,deduction_money,uid,order_no from pz_orders where delete_time=0 and order_status=1 and create_time<'%s'", $subTime);
        $order        = Db::query($sql);
        if (empty($order)) {
            exit('order_is_null');
        }
        $orderIds = implode(',', array_column($order, 'id'));

        $orderChildSql = sprintf("select id from pz_order_child where delete_time=0 and order_id in (%s)", $orderIds);
        $orderChild    = Db::query($orderChildSql);
        $orderChildIds = implode(',', array_column($orderChild, 'id'));

        $orderGoodsSql = sprintf("select id,sku_id,goods_num from pz_order_goods where delete_time=0 and order_child_id in (%s)", $orderChildIds);
        $orderGoods    = Db::query($orderGoodsSql);

        Db::startTrans();
        try {
            foreach ($order as $o) {
                $orderUpdateSql    = sprintf("update pz_orders set order_status=2 where delete_time=0 and id=%d", $o['id']);
                $userUpdateSql     = sprintf("update pz_users set balance=balance+%.2f where delete_time=0 and id=%d", $o['deduction_money'], $o['uid']);
                $logBonusUpdateSql = sprintf("update pz_log_bonus set status=3 where delete_time=0 and order_no=%s", $o['order_no']);
                Db::execute($orderUpdateSql);
                Db::execute($userUpdateSql);
                Db::execute($logBonusUpdateSql);
            }
            foreach ($orderGoods as $og) {
                $goodsSkuSql = sprintf("update pz_goods_sku set stock=stock+%d where delete_time=0 and id=%d", $og['goods_num'], $og['sku_id']);
                Db::execute($goodsSkuSql);
            }
            Db::commit();
        } catch (\Exception $e) {
            error_log($e . PHP_EOL . PHP_EOL, 3, dirname(dirname(dirname(__DIR__))) . '/cancel_order_error.log');
            Db::rollback();
            exit('rollback');
        }
        exit('ok!!');
    }

    /**
     * 分利正式发放到用户账户
     * 每15天执行一次
     */
    public function bonusSend() {
        $this->orderInit();
        $days      = Config::get('conf.bonus_days');//付款后15天分利正式给到账户
        $times     = bcmul($days, 86400, 0);
        $diffTimes = strtotime(date('Y-m-d', strtotime('+1 day'))) - $times;
        $sql       = sprintf("select id,to_uid,result_price,user_identity from pz_log_bonus where delete_time=0 and status=1 and create_time<=%s", $diffTimes);
        $result    = Db::query($sql);
        if (empty($result)) {
            exit('log_bonus_null');
        }
        $data = [];
        foreach ($result as $rVal) {
            if (!key_exists($rVal['to_uid'], $data)) {
                $data[$rVal['to_uid']]['uid']           = $rVal['to_uid'];
                $data[$rVal['to_uid']]['user_identity'] = $rVal['user_identity'];
            }
            $data[$rVal['to_uid']]['balance'] = isset($data[$rVal['to_uid']]['balance']) ? bcadd($data[$rVal['to_uid']]['balance'], $rVal['result_price'], 2) : $rVal['result_price'];
        }
        $data      = array_values($data);
        $idList    = implode(',', array_column($result, 'id'));
        $updateSql = sprintf("update pz_log_bonus set status=2 where delete_time=0 and id in (%s)", $idList);//更新分利发放日志状态为已结算
        Db::startTrans();
        try {
            foreach ($data as $d) {
                if ($d['user_identity'] == 4) {
                    Db::table('pz_users')->where('id', $d['uid'])->setInc('commission', $d['balance']);
                    continue;
                }
                Db::table('pz_users')->where('id', $d['uid'])->setInc('balance', $d['balance']);
            }
            Db::execute($updateSql);
            Db::commit();
            exit('ok!');
        } catch (\Exception $e) {
            Db::rollback();
            exit('rollback');
        }
    }

    /**
     * 分利结算
     * 每一小时结算一次
     */
    public function bonusSettlement() {
        $this->orderInit();
        $constShop    = 0.7;//购物的门店bos分利拿7成
        $redisListKey = Config::get('redisKey.order.redisOrderBonus');
        $orderId      = $this->redis->lPop($redisListKey);
        if (empty($orderId)) {
            exit('order_bonus_null');
        }
        $orderSql = sprintf("select id,uid,order_no from pz_orders where delete_time=0 and order_status in (4,5,6,7) and id = '%d'", $orderId);
        $orderRes = Db::query($orderSql);
        if (empty($orderRes)) {
            exit('order_id_error');//订单id有误
        }
        $uid           = $orderRes[0]['uid'];//购买人的uid
        $orderNo       = $orderRes[0]['order_no'];//购买订单号
        $identity      = $this->getIdentity($uid);//获取自己的身份
        $bossList      = $this->getBossList($uid, $identity);
        $orderChildSql = sprintf("select id from pz_order_child where delete_time=0 and order_id = %d", $orderId);
        $orderChildRes = Db::query($orderChildSql);
        $orderChildRes = array_column($orderChildRes, 'id');

        $orderGoodsSql = sprintf("select id,goods_price,margin_price,boss_uid,goods_id,sku_id,sup_id,integral,goods_num,sku_json from pz_order_goods where delete_time=0 and order_child_id in (%s)", implode(',', $orderChildRes));
        $orderGoodsRes = Db::query($orderGoodsSql);
        $orderGoods    = [];
        foreach ($orderGoodsRes as $ogrVal) {
            if (key_exists($ogrVal['sku_id'], $orderGoods)) {
                $orderGoods[$ogrVal['sku_id']]['goods_num'] += 1;
                continue;
            }
            $orderGoods[$ogrVal['sku_id']] = $ogrVal;
        }
        $data = [];
        foreach ($orderGoods as $ogVal) {
            $o         = [
                'order_no'     => $orderNo,
                'from_uid'     => $uid,
                'sku_id'       => $ogVal['sku_id'],
                'goods_id'     => $ogVal['goods_id'],
                'goods_price'  => $ogVal['goods_price'],
                'margin_price' => $ogVal['margin_price'],
                'sup_id'       => $ogVal['sup_id'],
                'sku_json'     => $ogVal['sku_json'],
                'buy_sum'      => $ogVal['goods_num'],
                'create_time'  => time(),
            ];
            $shopBoss  = $ogVal['boss_uid'];//购买店铺boss的uid
            $calculate = $this->calculate($ogVal['margin_price'], $ogVal['goods_num']);//所有三层分利
            if ($identity == 1) {//普通用户
                $firstShopPrice     = bcmul($calculate['first_price'], $constShop, 2);//购买店铺的分利
                $o['result_price']  = $firstShopPrice;//实际得到分利
                $o['to_uid']        = $shopBoss;
                $o['stype']         = 2;//分利类型 1.推荐关系分利 2.店铺购买分利
                $o['layer']         = 1;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
                $o['user_identity'] = 4;
                array_push($data, $o);
            } else {
                $firstShopPrice     = bcmul($calculate['second_price'], $constShop, 2);//购买店铺的分利
                $o['result_price']  = $firstShopPrice;//实际得到分利
                $o['to_uid']        = $shopBoss;
                $o['stype']         = 2;//分利类型 1.推荐关系分利 2.店铺购买分利
                $o['layer']         = 2;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
                $o['user_identity'] = 4;
                array_push($data, $o);
            }
            $o['result_price']  = bcsub($calculate['first_price'], $firstShopPrice, 2);//实际得到分利
            $o['to_uid']        = $bossList['first_uid'];
            $o['stype']         = 1;//分利类型 1.推荐关系分利 2.店铺购买分利
            $o['layer']         = 1;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
            $o['user_identity'] = $this->getIdentity($bossList['first_uid']);
            array_push($data, $o);
            $o['result_price']  = $calculate['second_price'];//实际得到分利
            $o['to_uid']        = $bossList['second_uid'];
            $o['stype']         = 1;//分利类型 1.推荐关系分利 2.店铺购买分利
            $o['layer']         = 2;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
            $o['user_identity'] = $this->getIdentity($bossList['first_uid']);
            array_push($data, $o);
            $o['result_price']  = $calculate['third_price'];//实际得到分利
            $o['to_uid']        = $bossList['third_uid'];
            $o['stype']         = 1;//分利类型 1.推荐关系分利 2.店铺购买分利
            $o['layer']         = 3;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
            $o['user_identity'] = 4;
            array_push($data, $o);
            Db::name('log_bonus')->insertAll($data);
        }
    }

    /**
     * 购买会员订单结算
     * 每分钟结算
     */
    public function memberOrderSettlement() {
        $this->orderInit();
        $redisListKey = Config::get('redisKey.order.redisMemberOrder');
        $this->redis->rPush($redisListKey, 1);
        $memberOrderId = $this->redis->lPop($redisListKey);//购买会员的订单id
        if (empty($memberOrderId)) {
            exit('member_order_null');
        }
        $memberSql   = sprintf("select id,uid,order_no,user_type,pay_money,from_uid from pz_member_order where delete_time=0 and pay_status=4 and id = '%d'", $memberOrderId);
        $memberOrder = Db::query($memberSql);
        if (empty($memberOrder)) {
            exit('order_id_error');//订单id有误
        }
        $memberOrder = $memberOrder[0];
        $userType    = $memberOrder['user_type'];
        if ($userType == 1) {//钻石会员
            $this->diamondvipSettlement($memberOrder['id'], $memberOrder['uid'], $memberOrder['pay_money'],$memberOrder['from_uid']);
        }
        if ($userType == 2) {//boss
            $this->bossSettlement($memberOrder['id'], $memberOrder['uid']);
        }
    }

    /**
     * 获取三层分利的用户id列表
     * @param $uid
     * @param $identity
     * @return array
     * @author zyr
     */
    private function getBossList($uid, $identity) {
        $myRelation = $this->getRelation($uid);//获取自己的boss关系
        $pBossUid   = $this->getBoss($uid);
        if ($pBossUid == 1) {
            $firstUid = 1;
            if ($identity != 1) {
                $firstUid = $uid;
            }
            return ['first_uid' => $firstUid, 'second_uid' => 1, 'third_uid' => 1];
        }
        $firstUid  = 1;//默认总店
        $secondUid = 1;//默认总店
        $thirdUid  = 1;//默认总店
        if ($identity = 1) {//自己是普通会员
            $myPid         = $myRelation['pid'] ?: 1;//直属上级uid
            $myPidIdentity = $this->getIdentity($myPid);//上级的身份(判断是不是分享大v)
            $ppUid         = $this->getBoss($pBossUid);
            if ($myPidIdentity == 3) {
                $firstUid  = $myPid;
                $secondUid = $pBossUid;
                $thirdUid  = $ppUid;
            } else {
                $firstUid  = $pBossUid;
                $secondUid = $ppUid;
                $thirdUid  = $this->getBoss($ppUid);
            }
        } else if ($identity == 2) {//自己是钻石会员
            $myPid         = $myRelation['pid'];//直属上级uid
            $myPidIdentity = $this->getIdentity($myPid);//上级的身份(判断是不是分享大v)
            $firstUid      = $uid;
            if ($myPidIdentity == 3) {
                $secondUid = $myPid;
                $thirdUid  = $pBossUid;
            } else {
                $secondUid = $pBossUid;
                $thirdUid  = $this->getBoss($pBossUid);
            }
        } else if ($identity == 3 || $identity == 4) {
            $firstUid  = $uid;
            $secondUid = $pBossUid;
            $thirdUid  = $this->getBoss($pBossUid);
        }
        return ['first_uid' => $firstUid, 'second_uid' => $secondUid, 'third_uid' => $thirdUid];
    }

    /**
     * 计算分利
     * @param $marginPrice
     * @param $num
     * @author zyr
     * @return array
     */
    private function calculate($marginPrice, $num) {
        $firstBonus  = 0.75;
        $secondBonus = 0.15;
        $thirdBonus  = 0.15;
        $firstPrice  = bcmul(bcmul($marginPrice, $firstBonus, 5), $num, 2);
        $secondPrice = bcmul($firstPrice, $secondBonus, 2);
        $thirdPrice  = bcmul($secondPrice, $thirdBonus, 2);
        return ['first_price' => $firstPrice, 'second_price' => $secondPrice, 'third_price' => $thirdPrice];
    }

    /**
     * 获取用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @param $uid
     * @return mixed
     * @author zyr
     */
    private function getIdentity($uid) {
        if ($uid == 1) {
            return 4;
        }
        $userSql = sprintf("select user_identity from pz_users where delete_time=0 and id=%d", $uid);
        $user    = Db::query($userSql);
        return $user[0]['user_identity'];
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
        if ($pBossUidCheck != 4) {//relation第一个关系人不是boss说明是总店下的用户
            return 1;
        }
        return $bossUid;
    }

    private function getRelation($uid) {
        $userRelationSql = sprintf("select id,pid,is_boss,relation from pz_user_relation where delete_time=0 and uid = %d", $uid);
        $userRelation    = Db::query($userRelationSql);
        return $userRelation[0];
    }

    /**
     * @param $memberOrderId
     * @param $uid
     * @author zyr
     */
    private function bossSettlement($memberOrderId, $uid) {

    }

    /**
     * @param $memberOrderId
     * @param $uid
     * @param $payMoney
     */
    private function diamondvipSettlement($memberOrderId, $uid, $payMoney) {
        $diamondvipGet = $this->diamondvipGet($uid);
        if ($payMoney == 500) {

        }elseif ($payMoney == 100) {

        }
    }

    private function diamondvipGet($uid){
        $diamondvipGetSql = sprintf("select id,diamondvips_id,uid,share_uid,redmoney,share_redmoney,share_num from pz_diamondvip_get where delete_time=0 and uid = %d", $uid);
        $diamondvipGet = Db::query($diamondvipGetSql);
        return $diamondvipGet[0];
    }
}