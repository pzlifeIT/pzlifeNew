<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use think\Db;
use cache\Phpredis;
use function Qiniu\json_decode;

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
                $userSql        = sprintf("select balance from pz_users where delete_time=0 and id=%d", $o['uid']);
                $user           = Db::query($userSql);
                $user           = $user[0];
                $tradingData    = [
                    'uid'          => $o['uid'],
                    'trading_type' => 1,
                    'change_type'  => 2,
                    'money'        => $o['deduction_money'],
                    'befor_money'  => $user['balance'],
                    'after_money'  => bcadd($user['balance'], $o['deduction_money'], 2),
                    'message'      => '',
                    'create_time'  => time(),
                ];
                $orderUpdateSql = sprintf("update pz_orders set order_status=2 where delete_time=0 and id=%d", $o['id']);
                $userUpdateSql  = sprintf("update pz_users set balance=balance+%.2f where delete_time=0 and id=%d", $o['deduction_money'], $o['uid']);
//                $logBonusUpdateSql    = sprintf("update pz_log_bonus set status=3 where delete_time=0 and order_no='%s'", $o['order_no']);
//                $logIntegralUpdateSql = sprintf("update pz_log_integral set status=3 where delete_time=0 and order_no='%s'", $o['order_no']);
                Db::execute($orderUpdateSql);
                Db::execute($userUpdateSql);
//                Db::execute($logBonusUpdateSql);
//                Db::execute($logIntegralUpdateSql);//待付款取消的订单还未结算分利和积分不需要取消
                Db::name('log_trading')->insert($tradingData);
            }
            foreach ($orderGoods as $og) {
                $goodsSkuSql = sprintf("update pz_goods_sku set stock=stock+%d where delete_time=0 and id=%d", $og['goods_num'], $og['sku_id']);
                Db::execute($goodsSkuSql);
            }
            Db::commit();
        } catch (\Exception $e) {
//            error_log($e . PHP_EOL . PHP_EOL, 3, dirname(dirname(dirname(__DIR__))) . '/cancel_order_error.log');
            Db::rollback();
            exit('rollback');
        }
        exit('ok!!');
    }

    /**
     * 自动收货(15天)
     * 每天执行一次
     */
    public function orderTheGoods() {
        $days      = Config::get('conf.bonus_days');//付款后15天分利正式给到账户
        $times     = bcmul($days, 86400, 0);
        $diffTimes = strtotime(date('Y-m-d', strtotime('+1 day'))) - $times;
        $sql       = sprintf("select id from pz_orders where delete_time=0 and order_status=5 and create_time<=%s", $diffTimes);
        $result    = Db::query($sql);
        if (empty($result)) {
            exit('order_is_null');
        }
        $orderIdList = implode(',', array_column($result, 'id'));
        $updateSql   = sprintf("update pz_orders set order_status=6 where delete_time=0 and id in (%s)", $orderIdList);
        Db::startTrans();
        try {
            Db::execute($updateSql);
            Db::commit();
            exit('ok!');
        } catch (\Exception $e) {
//            error_log($e . PHP_EOL . PHP_EOL, 3, dirname(dirname(dirname(__DIR__))) . '/error.log');
            Db::rollback();
            exit('rollback');
        }
    }
    
    /**
     * 分利正式发放到用户账户
     * 每天执行一次
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
                $data[$rVal['to_uid']]['uid'] = $rVal['to_uid'];
//                $data[$rVal['to_uid']]['user_identity'] = $rVal['user_identity'];
            }
            if ($rVal['user_identity'] == 4) {
                $data[$rVal['to_uid']]['commission'] = isset($data[$rVal['to_uid']]['commission']) ? bcadd($data[$rVal['to_uid']]['commission'], $rVal['result_price'], 2) : $rVal['result_price'];
            } else {
                $data[$rVal['to_uid']]['balance'] = isset($data[$rVal['to_uid']]['balance']) ? bcadd($data[$rVal['to_uid']]['balance'], $rVal['result_price'], 2) : $rVal['result_price'];
            }
        }
        $data            = array_values($data);
        $idList          = implode(',', array_column($result, 'id'));
        $updateSql       = sprintf("update pz_log_bonus set status=2 where delete_time=0 and id in (%s)", $idList);//更新分利发放日志状态为已结算
        $userSql         = sprintf("select id,balance,commission from pz_users where delete_time=0 and id in (%s)", implode(',', array_unique(array_column($result, 'to_uid'))));
        $userInfo        = Db::query($userSql);
        $userBalance     = array_column($userInfo, 'balance', 'id');
        $userCommission  = array_column($userInfo, 'commission', 'id');
        $fromTradingData = [
            'change_type' => 4,//层级分利
            'message'     => '',
            'create_time' => time(),
        ];
        Db::startTrans();
        try {
            $tradingData = [];
            foreach ($data as $d) {
                $fromTradingData['uid'] = $d['uid'];
                if (isset($d['balance']) && $d['balance'] > 0) {
                    $fromTradingData['money']        = $d['balance'];
                    $fromTradingData['trading_type'] = 1;
                    $fromTradingData['befor_money']  = $userBalance[$d['uid']];
                    $fromTradingData['after_money']  = bcadd($userBalance[$d['uid']], $d['balance'], 2);
                    array_push($tradingData, $fromTradingData);
                    Db::table('pz_users')->where('id', $d['uid'])->setInc('balance', $d['balance']);
                }
                if (isset($d['commission']) && $d['commission'] > 0) {
                    $fromTradingData['money']        = $d['commission'];
                    $fromTradingData['trading_type'] = 2;
                    $fromTradingData['befor_money']  = $userCommission[$d['uid']];
                    $fromTradingData['after_money']  = bcadd($userCommission[$d['uid']], $d['commission'], 2);
                    array_push($tradingData, $fromTradingData);
                    Db::table('pz_users')->where('id', $d['uid'])->setInc('commission', $d['commission']);
                }
            }
            Db::name('log_trading')->insertAll($tradingData);
            Db::execute($updateSql);
            Db::commit();
            exit('ok!');
        } catch (\Exception $e) {
//            error_log($e . PHP_EOL . PHP_EOL, 3, dirname(dirname(dirname(__DIR__))) . '/error.log');
            Db::rollback();
            exit('rollback');
        }
    }

    /**
     * 分利结算
     * 每2分钟结算一条订单
     */
    public function bonusSettlement() {
        $this->orderInit();
        $constShop    = 0.7;//购物的门店bos分利拿7成
        $redisListKey = Config::get('redisKey.order.redisOrderBonus');
        $data         = [];
        $tradingData  = [];
        while (true) {
            $orderId = $this->redis->lPop($redisListKey);
            if (empty($orderId)) {
                break;
            }
            $orderSql = sprintf("select id,uid,order_no,deduction_money from pz_orders where delete_time=0 and order_status in (4,5,6,7) and id = '%d'", $orderId);
            $orderRes = Db::query($orderSql);
            $orderRes = $orderRes[0];
            if (empty($orderRes)) {
                exit('order_id_error');//订单id有误
            }
            $uid      = $orderRes['uid'];//购买人的uid
            $orderNo  = $orderRes['order_no'];//购买订单号
            $identity = $this->getIdentity($uid);//获取自己的身份

            $bossList      = $this->getBossList($uid, $identity);
            $orderChildSql = sprintf("select id from pz_order_child where delete_time=0 and order_id = %d", $orderId);
            $orderChildRes = Db::query($orderChildSql);
            $orderChildRes = array_column($orderChildRes, 'id');

            $orderGoodsSql = sprintf("select id,goods_price,margin_price,boss_uid,goods_id,sku_id,sup_id,integral,goods_num,sku_json from pz_order_goods where delete_time=0 and order_child_id in (%s)", implode(',', $orderChildRes));
            $orderGoodsRes = Db::query($orderGoodsSql);
            $orderGoods    = [];
            foreach ($orderGoodsRes as $ogrVal) {
//                if (key_exists($ogrVal['sku_id'], $orderGoods)) {
//                    $orderGoods[$ogrVal['sku_id']]['goods_num'] += 1;
//                    continue;
//                }
//                $orderGoods[$ogrVal['sku_id']] = $ogrVal;
                array_push($orderGoods, $ogrVal);
            }
            $userSql      = sprintf("select balance from pz_users where delete_time=0 and id=%d", $uid);
            $user         = Db::query($userSql);
            $user         = $user[0];
            $integralData = [];
            foreach ($orderGoods as $ogVal) {
                $o        = [
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
                $shopBoss = $ogVal['boss_uid'];//购买店铺boss的uid
//                $shopBossSql     = sprintf("select balance,commission from pz_users where delete_time=0 and id=%d", $shopBoss);
//                $shopBossBalance = Db::query($shopBossSql);
//                $shopBossBalance = $shopBossBalance[0];
                $calculate      = $this->calculate($ogVal['margin_price'], $ogVal['goods_num']);//所有三层分利
                $f              = 3;//购买用户是否普通用户
                $firstShopPrice = 0;
                if ($identity == 1 && $shopBoss != 1) {//普通用户
                    $f                  = 1;
                    $firstShopPrice     = bcmul($calculate['first_price'], $constShop, 2);//购买店铺的分利
                    $o['result_price']  = $firstShopPrice;//实际得到分利
                    $o['to_uid']        = $shopBoss;
                    $o['stype']         = 2;//分利类型 1.推荐关系分利 2.店铺购买分利
                    $o['layer']         = 1;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
                    $o['user_identity'] = 4;
                    array_push($data, $o);
                } else if ($identity != 1 && $shopBoss != 1) {
                    $f                  = 2;
                    $firstShopPrice     = bcmul($calculate['second_price'], $constShop, 2);//购买店铺的分利
                    $o['result_price']  = $firstShopPrice;//实际得到分利
                    $o['to_uid']        = $shopBoss;
                    $o['stype']         = 2;//分利类型 1.推荐关系分利 2.店铺购买分利
                    $o['layer']         = 2;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
                    $o['user_identity'] = 4;
                    array_push($data, $o);
                }
                $o['result_price']  = $f == 1 ? bcsub($calculate['first_price'], $firstShopPrice, 2) : $calculate['first_price'];//实际得到分利
                $o['to_uid']        = $bossList['first_uid'];
                $o['stype']         = 1;//分利类型 1.推荐关系分利 2.店铺购买分利
                $o['layer']         = 1;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
                $userIden           = $this->getIdentity($bossList['first_uid']);
                $o['user_identity'] = $userIden;
                array_push($data, $o);
                $o['result_price']  = $f == 2 ? bcsub($calculate['second_price'], $firstShopPrice, 2) : $calculate['second_price'];//实际得到分利
                $o['to_uid']        = $bossList['second_uid'];
                $o['stype']         = 1;//分利类型 1.推荐关系分利 2.店铺购买分利
                $o['layer']         = 2;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
                $userIden           = $this->getIdentity($bossList['second_uid']);
                $o['user_identity'] = $userIden;
                array_push($data, $o);
                $o['result_price']  = $calculate['third_price'];//实际得到分利
                $o['to_uid']        = $bossList['third_uid'];
                $o['stype']         = 1;//分利类型 1.推荐关系分利 2.店铺购买分利
                $o['layer']         = 3;//分利层级 1.一层(75) 2.二层(75*15) 三层(75*15*15)'
                $o['user_identity'] = 4;
                array_push($data, $o);
                if (key_exists($orderId, $integralData)) {
                    $integralData[$orderId]['result_integral'] += $ogrVal['integral'];
                } else {
                    $integralData[$orderId] = ['order_no' => $orderNo, 'result_integral' => $ogrVal['integral'], 'uid' => $uid, 'create_time' => time()];
                }
            }
        }
        Db::startTrans();
        try {
            if (!empty($data)) {
                Db::name('log_bonus')->insertAll($data);
                Db::name('log_integral')->insertAll(array_values($integralData));
            }
            Db::commit();
            exit('ok!');
        } catch (\Exception $e) {
            Db::rollback();
            exit('rollback');
        }
    }

    /**
     * 积分正式发放
     * 每天结算
     */
    public function integralSettlement() {
        $this->orderInit();
        $days      = Config::get('conf.bonus_days');//付款后15天分利正式给到账户
        $times     = bcmul($days, 86400, 0);
        $diffTimes = strtotime(date('Y-m-d', strtotime('+1 day'))) - $times;
        $sql       = sprintf("select id,uid,result_integral from pz_log_integral where delete_time=0 and status=1 and create_time<=%s", $diffTimes);
        $result    = Db::query($sql);
        if (empty($result)) {
            exit('log_integral_null');
        }
        Db::startTrans();
        try {
            foreach ($result as $r) {
                Db::table('pz_users')->where('id', $r['uid'])->setInc('integral', $r['result_integral']);
                $logIntegralSql = sprintf("update pz_log_integral set status=3 where delete_time=0 and id=%d", $r['id']);
                Db::execute($logIntegralSql);
            }
            Db::commit();
            exit('ok!');
        } catch (\Exception $e) {
            Db::rollback();
            exit('rollback');
        }
    }

    /**
     * 购买会员订单结算
     * 每分钟结算
     */
    public function memberOrderSettlement() {
        $this->orderInit();
        $redisListKey = Config::get('redisKey.order.redisMemberOrder');
        // $this->redis->rPush($redisListKey, 4);
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
//        print_r($memberOrder);die;
        if ($userType == 1) {//钻石会员
            $this->diamondvipSettlement($memberOrder['uid'], $memberOrder['pay_money'], $memberOrder['from_uid']);
        }
        if ($userType == 2) {//boss
            $this->bossSettlement($memberOrder['uid'], $memberOrder['from_uid']);
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
        if ($identity == 1) {//自己是普通会员
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
            $myPid         = $myRelation['pid'] ?: 1;//直属上级uid
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
     * @param $uid
     * @param $fromUid
     * @author zyr
     */
    private function bossSettlement($uid, $fromUid) {
        $buyBossMoney = 5000;//推荐购买boss可获得奖励佣金额度
        //修改用户关系及身份
        $myBoss = $this->getBoss($uid);
        if ($myBoss == 1) {
            $re = $uid;
        } else {
            $re = $myBoss . ',' . $uid;
        }
        $otherUserSql     = "select id,uid,relation from pz_user_relation where delete_time=0 and relation like '%" . $uid . ',' . "%'";
        $userOther        = Db::query($otherUserSql);
        $userRelationData = [];
        if (!empty($userOther)) {
            foreach ($userOther as $uo) {
//                $uo['relation'] = $uid . ',' . $uo['uid'];
                $uo['relation'] = substr($uo['relation'], stripos($uo['relation'], $uid . ','));
                unset($uo['uid']);
                array_push($userRelationData, $uo);
            }
        }
        //推荐人分利5000
        $fromUserInfoSql = sprintf("select commission from pz_users where delete_time=0 and id = %d", $fromUid);
        $fromUserInfo    = Db::query($fromUserInfoSql);
        $fromUserInfo    = $fromUserInfo[0];
        if ($this->getIdentity($uid) == '4') {
            exit('is_boss');
        }
        $fromTradingDate = [
            'uid'          => $fromUid,
            'trading_type' => 2,
            'change_type'  => 5,
            'money'        => $buyBossMoney,
            'befor_money'  => $fromUserInfo['commission'],
            'after_money'  => bcadd($fromUserInfo['commission'], $buyBossMoney, 2),
            'message'      => '',
            'create_time'  => time(),
        ];
        $shopData        = [
            'uid'         => $uid,
            'shop_right'  => 'all',
            'status'      => 1,
            'create_time' => time(),
        ];
        Db::startTrans();
        try {
            if (!empty($userRelationData)) {
                foreach ($userRelationData as $urd) {
                    Db::name('user_relation')->update($urd);
                }
            }
            if ($fromUid != 1) {
                Db::table('pz_users')->where('id', $fromUid)->setInc('commission', $buyBossMoney);
                Db::name('log_trading')->insert($fromTradingDate);
            }
            $shopId = Db::name('shops')->insertGetId($shopData);
            Db::table('pz_users')->where('id', $uid)->update(['user_identity' => 4, 'bindshop' => $shopId]);
            Db::table('pz_user_relation')->where('uid', $uid)->update(['is_boss' => 1, 'relation' => $re]);
            Db::commit();
        } catch (\Exception $e) {
//            error_log($e . PHP_EOL . PHP_EOL, 3, dirname(dirname(dirname(__DIR__))) . '/error.log');
            Db::rollback();
            exit('rollback');
        }
    }

    /**
     * @param $memberOrderId
     * @param $uid
     * @param $payMoney
     * @param $from_uid
     */
    private function diamondvipSettlement($uid, $payMoney, $from_uid) {
        $redisListKey      = Config::get('redisKey.order.redisMemberShare');
        $fromDiamondvipGet = $this->diamondvipGet($from_uid);


        Db::startTrans();
        try {
            if ($payMoney == 500) {
                Db::name('users')->where('id', $uid)->update(['user_identity' => 2]);
            } elseif ($payMoney == 100) {
                $from_user    = $this->getUserInfo($from_uid);
                $from_balance = 0;

                if (!$fromDiamondvipGet) {

                    if ($from_user['user_identity'] > 1) {
                        $from_diamondvip_get                   = [];
                        $from_diamondvip_get['uid']            = $from_uid;
                        $from_diamondvip_get['share_uid']      = 1;
                        // $from_diamondvip_get['redmoney'] = 50;
                        // $from_diamondvip_get['share_num']      = 1;
                        $from_diamondvip_get['create_time']    = time();
                        Db::name('diamondvip_get')->insert($from_diamondvip_get);
                        $from_balance = $from_user['balance'] + 50;
                        Db::name('users')->where('id', $from_uid)->update(['balance' => $from_balance]);
                        Db::name('log_trading')->insert(
                            [
                                'uid'          => $from_uid,
                                'trading_type' => 1,
                                'change_type'  => 5,
                                'money'        => 50,
                                'befor_money'  => $from_user['balance'],
                                'after_money'  => $from_balance,
                                'create_time'  => time()
                            ]
                        );
                        /* 写入缓存 */
                        $this->redis->hset($redisListKey . $from_uid, $from_uid, time());
                        $this->redis->expire($redisListKey . $from_uid, 2592000);
                        $userRedisKey = Config::get('rediskey.user.redisKey');
                        $this->redis->del( $userRedisKey. 'userinfo:' . $from_uid);
                    }
                } else {
                    /* 给上级 */
                    $from_diamondvip_get              = [];
                    // $from_diamondvip_get['share_num'] = $fromDiamondvipGet['share_num'] + 1;
                    Db::name('diamondvip_get')->where('id', $fromDiamondvipGet['id'])->update($from_diamondvip_get);
                    $from_balance = $from_user['balance'] + 50;
                    Db::name('users')->where('id', $from_uid)->update(['balance' => $from_balance]);
                    Db::name('log_trading')->insert(
                        [
                            'uid'          => $from_uid,
                            'trading_type' => 1,
                            'change_type'  => 5,
                            'money'        => 50,
                            'befor_money'  => $from_user['balance'],
                            'after_money'  => $from_balance,
                            'create_time'  => time()
                        ]
                    );
                    $userRedisKey = Config::get('rediskey.user.redisKey');
                    $this->redis->del( $userRedisKey. 'userinfo:' . $from_uid);
                    $sharefromDiamondvipGet = $this->diamondvipGet($fromDiamondvipGet['share_uid']);
                    $share_from_user        = $this->getUserInfo($fromDiamondvipGet['share_uid']);
                    // Db::getLastSql();die;
                    // print_r($fromDiamondvipGet['share_uid']);die;
                    if ($share_from_user['user_identity'] == 4) {
                        $share_from_balance = 0;
                        $share_from_balance = $share_from_user['balance'] + 50;
                        Db::name('users')->where('id', $share_from_user['id'])->update(['balance' => $share_from_balance]);
                        Db::name('log_trading')->insert(
                            [
                                'uid'          => $share_from_user['id'],
                                'trading_type' => 1,
                                'change_type'  => 5,
                                'money'        => 50,
                                'befor_money'  => $share_from_user['balance'],
                                'after_money'  => $share_from_balance,
                                'create_time'  => time()
                            ]
                        );
                        /* 写入缓存 */
                        $this->redis->hset($redisListKey . $share_from_user['id'], $share_from_user['id'], time());
                        $this->redis->expire($redisListKey . $share_from_user['id'], 2592000);
                        $this->redis->del( $userRedisKey. 'userinfo:' . $share_from_user['id']);
                    }
                }

                $diamondvip_get                = [];
                $diamondvip_get['uid']         = $uid;
                $diamondvip_get['share_uid']   = $from_uid;
                $diamondvip_get['create_time'] = time();
                Db::name('diamondvip_get')->insert($diamondvip_get);
                Db::name('users')->where('id', $uid)->update(['user_identity' => 2]);
            }
            Db::commit();
        } catch (\Exception $e) {
            print_r($e);
            Db::rollback();
            exit('rollback');
        }
    }

    /**
     * @param $uid
     */
    private function diamondvipGet($uid) {
        $diamondvipGetSql = sprintf("select id,diamondvips_id,uid,share_uid,redmoney from pz_diamondvip_get where delete_time=0 and uid = %d", $uid);
        $diamondvipGet    = Db::query($diamondvipGetSql);
        if (!$diamondvipGet) {
            return [];
        }
        return $diamondvipGet[0];
    }

    /**
     * @param $uid
     */
    private function getUserInfo($uid) {
        $getUserSql = sprintf("select id,user_type,user_identity,sex,nick_name,balance,commission from pz_users where delete_time=0 and id = %d", $uid);
        // print_r($getUserSql);die;
        $userInfo   = Db::query($getUserSql);
        if (!$userInfo) {
            return [];
        }
        return $userInfo[0];
    }

    public function orderExpressLog(){
        $this->orderInit();
        $redisDeliverExpressList = Config::get('redisKey.order.redisDeliverExpressList');
        $redisDeliverOrderKey = Config::get('rediskey.order.redisDeliverOrderExpress');
        // print_r($redisDeliverExpressList);die;
        // $this->redis->rPush($redisDeliverExpressList, 4);
        // $this->redis->rPush($redisDeliverExpressList, 'shentong&3701622486414');
        $deliverexpresslist = $this->redis->lPop($redisDeliverExpressList);//购买会员的订单id
        if (empty($deliverexpresslist)) {
            exit('deliver_repress_null');
        }
        $express = explode('&',$deliverexpresslist);
        $HundredExpress = new HundredExpress;
        $express_log = $HundredExpress->getExpressLog($express[0],$express[1]);
        if ($express_log){
            $express_log = json_decode($express_log,true);
            if ($express_log['state'] != 3){//快递签收
                $this->redis->rPush($redisDeliverExpressList, 'shentong&3701622486414');
            }
            $this->redis->set($redisDeliverOrderKey.$deliverexpresslist, json_encode($express_log,true));
            $this->redis->expire($redisDeliverOrderKey. $deliverexpresslist, 2592000);
        }else{
            $this->redis->rPush($redisDeliverExpressList, 'shentong&3701622486414');
        }
        
    }
}

    /**
     * 快递100物流查询类
     * @return array
     * @author rzc
     */
class HundredExpress
{

    /**
     * 修改订单发货信息
     * @param $ShipperCode
     * @param $LogisticCode
     * @return array
     * @author rzc
     */
    public function getExpressLog($ShipperCode, $LogisticCode)
    {
        $post_data = array();
        $post_data["customer"] = '389C6F5CB8C771CC620DCC88932229F3';
        $key = 'jrsaVPbM2682';
        $post_data["param"] = '{"com":"' . $ShipperCode . '","num":"' . $LogisticCode . '"}';

        $url = 'http://poll.kuaidi100.com/poll/query.do';
        $post_data["sign"] = md5($post_data["param"] . $key . $post_data["customer"]);
        $post_data["sign"] = strtoupper($post_data["sign"]);
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&"; //默认UTF-8编码格式
        }
        $post_data = substr($o, 0, -1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);
        $data = str_replace("\&quot;", '"', $result);
        // $data = json_decode($data, true);
        return $data;
    }

}