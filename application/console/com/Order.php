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

    public function cancelOrder() {
        $this->orderInit();
        $orderOutTime = Config::get('conf.order_out_time');//订单过期时间
        $subTime      = time() - $orderOutTime;//过期时间节点
        $sql          = sprintf("select id,deduction_money,uid from pz_orders where delete_time=0 and order_status=1 and create_time<'%s'", $subTime);
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
                $orderUpdateSql = sprintf("update pz_orders set order_status=2 where delete_time=0 and id=%d", $o['id']);
                $userUpdateSql  = sprintf("update pz_users set balance=balance+%.2f where delete_time=0 and id=%d", $o['deduction_money'], $o['uid']);
                Db::execute($orderUpdateSql);
                Db::execute($userUpdateSql);
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
}