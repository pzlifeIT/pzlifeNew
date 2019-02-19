<?php
namespace app\common\action\admin;

use app\facade\DbUser;
use app\facade\DbShops;
use Config;
use app\facade\DbGoods;
use app\facade\DbOrder;
use app\facade\DbProvinces;
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
        // dump( Db::getLastSql());die;
        if (empty($orderList)) {
            return ['code' => 3000];
        }
        $totle = DbOrder::getOrderCount([['1','=','1']]);
        return ['code' => 200 , 'totle' => $totle, 'order_list' => $orderList];
    }


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
        $order_info['province_name']    = DbProvinces::getAreaOne('*', ['id' => $order_info['province_id']])['area_name'];
        $order_info['city_name']    = DbProvinces::getAreaOne('*', ['id' => $order_info['city_id'],'level'=>2])['area_name'];
        $order_info['area_name']    = DbProvinces::getAreaOne('*', ['id' => $order_info['area_id']])['area_name'];
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
        $order_pack = [];
        foreach ($order_child as $order => $child) {
            $order_goods = DbOrder::getOrderGoods('goods_id,goods_name,order_child_id,sku_id,sup_id,goods_type,goods_price,margin_price,integral,goods_num,sku_json', ['order_child_id' => $child['id']],false,true);
            $order_goods_num = DbOrder::getOrderGoods('sku_id,COUNT(goods_num) as goods_num', ['order_child_id' => $child['id']],'sku_id');
            foreach ($order_goods as $og => $goods) {
                foreach ($order_goods_num as $ogn => $goods_num) {
                    if ($goods_num['sku_id'] == $goods['sku_id']) {
                        $order_goods[$og]['goods_num'] = $goods_num['goods_num'];
                        $order_goods[$og]['sku_json'] = json_decode($order_goods[$og]['sku_json'],true);
                    }
                }
            }
            $order_pack[$order]['order_goods'] = $order_goods;
           
            // dump( Db::getLastSql());die;
        }
        // print_r($order_pack);die;
        return ['code' => 200,'order_info' => $order_info, 'order_pack' => $order_pack,'order_child' => $order_child];
    }
}