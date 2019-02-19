<?php
namespace app\common\action\admin;

use app\facade\DbUser;
use app\facade\DbShops;
use Config;
use app\facade\DbGoods;
use app\facade\DbOrder;
use think\Db;

class Order{
    /**
     * 获取订单列表
     * @param $order_status
     * @param $cityId
     * @return array
     * @author rzc
     */
    public function getOrderList($page,$pagenum){
        $offset = ($page-1)*$pagenum;
        if ($offset<0) {
            return ['code' => 3000];
        }
        $field = 'id,order_no,order_status,order_money,deduction_money,pay_money,goods_money,discount_money,pay_type,third_money,third_pay_type';
        $orderList = DbOrder::getOrder($field, [['1','=','1']], false, $offset.','.$pagenum);
        if (empty($orderList)) {
            return ['code' => 3000];
        }
        $totle = DbOrder::getOrderCount([['1','=','1']]);
        return ['code' => 200 , 'totle' => $totle, 'order_list' => $orderList];
    }

    /* 
    foreach ($orderList as $key => $value) {
            $order_child = DbOrder::getOrderChild('*', ['order_id' => $value['id']]);
            $express_money = 0;
            $order_goods = [];
            foreach ($order_child as $order => $child) {
                $order_child[$order]['order_goods'] = DbOrder::getOrderGoods('*', ['order_child_id' => $child['id']]);
                $express_money += $child['express_money'] ;
            }
            $orderList[$key]['express_money'] = $express_money;
            $orderList[$key]['order_goods'] = $order_goods;
        }
    
    */

    /**
     * 获取订单详情
     * @param $order_status
     * @param $cityId
     * @return array
     * @author rzc
     */
    public function getOrderInfo($id){
        $order_info = DbOrder::getOrder('*', [['id','=',$id]], true);
        if (!$order_info) {
            return ['code' => 3001, 'msg' => '该订单不存在'];
        }
        $order_child = DbOrder::getOrderChild('*', ['order_id' => $order_info['id']]);
        $express_money = 0;
        foreach ($order_child as $order => $child) {
            $order_goods = DbOrder::getOrderGoods('*', ['order_child_id' => $child['id']]);
            foreach ($order_goods as $og => $goods) {
                $order_goods[$og]['sku_json'] = json_decode($goods['sku_json']);
            }
            $order_child[$order]['order_goods'] = $order_goods;
            $express_money += $child['express_money'] ;
        }
        return ['code' => 200,'order_info' => $order_info, 'order_child' => $order_child];
    }
}