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
        $field = 'id,uid,order_no,order_status,order_money,deduction_money,pay_money,goods_money,discount_money,pay_type,third_money,third_pay_type';
        $orderList = DbOrder::getOrder($field, [['1','=','1']], false, $offset.','.$pagenum);
        // dump( Db::getLastSql());die;
        if (empty($orderList)) {
            return ['code' => 3000];
        }
        foreach ($orderList as $key => $value) {
            $orderList[$key]['nick_name'] = DbUser::getUserInfo(['id'=>$value['uid']], 'nick_name', true)['nick_name'];
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
        $order_goods_ids = [];
        foreach ($order_child as $order => $child) {
            $order_goods = DbOrder::getOrderGoods('*', ['order_child_id' => $child['id']]);
            foreach ($order_goods as $og => $goods) {
                $order_goods[$og]['sku_json'] = json_decode($goods['sku_json']);
                $order_goods_ids[] = $goods['id'];
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
                        $order_goods[$og]['sku_image'] = DbGoods::getOneGoodsSku(['id' => $goods['sku_id']], 'sku_image', true)['sku_image'];
                        $order_goods[$og]['sku_json'] = json_decode($order_goods[$og]['sku_json'],true);
                    }
                }
            }
            $order_pack[$order]['order_goods'] = $order_goods;
           
            // dump( Db::getLastSql());die;
        }
        // print_r($order_pack);die;
        $has_deliver_goods = [];
        $has_order_express =  DbOrder::getOrderExpress('order_goods_id,express_no,express_key,express_name', [['order_goods_id' ,'IN', $order_goods_ids]] );
        if ($has_order_express) {
            $has_order_goods_id = [];
            foreach ($has_order_express as $has => $express) {
                $has_order_goods_id[] =$express['order_goods_id'];
                $goods = DbOrder::getOrderGoods('id,goods_name,sku_json,sku_id',[['id','=',$express['order_goods_id']]],false,false,true);
                $goods['sku_image'] = DbGoods::getOneGoodsSku(['id' => $goods['sku_id']], 'sku_image', true)['sku_image'];
                $goods['express'] = $express;
                $has_deliver_goods[$has]['goods'] = $goods;
            }
            $no_order_goods_id = array_diff($order_goods_ids,$has_order_goods_id);
           
            $no_deliver_goods = DbOrder::getOrderGoods('id,goods_name,sku_json',[['id','IN',$no_order_goods_id]]);
            // $has_deliver_goods = DbOrder::getOrderGoods('id,goods_name,sku_json',[['id','IN',$has_order_goods_id]]);
            
        }else{
            $no_deliver_goods = DbOrder::getOrderGoods('id,goods_name,sku_json',[['id','IN',$order_goods_ids]]);
        }
        $no_deliver_goods_num = count($no_deliver_goods);
        // print_r($has_deliver_goods);die;
        return ['code' => 200,'order_info' => $order_info, 'order_pack' => $order_pack,'order_child' => $order_child,'no_deliver_goods' => $no_deliver_goods,'has_deliver_goods' => $has_deliver_goods,'no_deliver_goods_num'=>$no_deliver_goods_num];
    }

    /**
     * 订单发货
     * @param $order_goods_id
     * @param $express_no
     * @param $express_key
     * @param $express_name
     * @return array
     * @author rzc
     */
    public function deliverOrderGoods($order_goods_id,$express_no,$express_key,$express_name){
        $order_express_id = DbOrder::getOrderExpress('id', ['order_goods_id' => $order_goods_id] , false, false,true);
        // dump( Db::getLastSql());die;
        if ($order_express_id) {
            return ['code' => 3005, 'msg' => '已添加的订单商品物流分配关系'];
        }

        $order_child_id = DbOrder::getOrderGoods('order_child_id', ['id' => $order_goods_id],false,false,true)['order_child_id'];
        
        if (!$order_child_id){
            return ['code' => 3003,'msg' => '不存在的order_goods_id'];
        }
        $order_id = DbOrder::getOrderChild('order_id', ['id' => $order_child_id],true)['order_id'];

        $order_status = DbOrder::getOrder('order_status', ['id' => $order_id], true)['order_status'] ;

        if ($order_status!=4) {
            return ['code' => 3004,'msg' => '非待发货订单无法发货'];
        }

        $order_childs =  DbOrder::getOrderChild('id', ['order_id' => $order_id]);
        $new_chileds = [];
        foreach ($order_childs as $order => $value) {
            $new_chileds[] = $value['id'];
        }

        $order_goods_data = DbOrder::getOrderGoods('id,goods_name,sku_json',[['order_child_id','IN',$new_chileds]]);
        $order_goods_ids = [];
        foreach ($order_goods_data as $key => $value) {
            $order_goods_ids[] = $value['id'];
        }
       
        $order_express = [];
        $order_express['order_goods_id'] = $order_goods_id;
        $order_express['express_no'] = $express_no;
        $order_express['express_key'] = $express_key;
        $order_express['express_name'] = $express_name;

        $add_order_express = DbOrder::addOrderExpress($order_express);
        /* 查出已添加的订单商品物流单分配信息 */
        $has_order_express =  DbOrder::getOrderExpress('order_goods_id', [['order_goods_id' ,'IN', $order_goods_ids]] );
        if ($has_order_express) {
            $has_order_goods_id = [];
            foreach ($has_order_express as $has => $express) {
                $has_order_goods_id[] =$express['order_goods_id'];
            }
            $no_order_goods_id = array_diff($order_goods_ids,$has_order_goods_id);
            if (!$no_order_goods_id){
                DbOrder::updataOrder(['order_status'=>5], $order_id);
                $no_deliver_goods = [];
            }else{
                $no_deliver_goods = DbOrder::getOrderGoods('id,goods_name,sku_json',[['id','IN',$no_order_goods_id]]);
            }
            
        }else{
            $no_deliver_goods = $order_goods_data;
        }
        if ($add_order_express) {
            return ['code' => 200 ,'msg' => '添加成功','no_deliver_goods' => $no_deliver_goods];
        }else{
            return ['code' => '3006','msg' => '添加失败','no_deliver_goods' => $no_deliver_goods];
        }

    }

    /**
     * 修改订单发货信息
     * @param $order_goods_id
     * @param $express_no
     * @param $express_key
     * @param $express_name
     * @return array
     * @author rzc
     */
    public function updateDeliverOrderGoods($order_goods_id,$express_no,$express_key,$express_name){
        $order_express = DbOrder::getOrderExpress('id', ['order_goods_id' => $order_goods_id] , false, false,true);
        // dump( Db::getLastSql());die;
        if (!$order_express) {
            return ['code' => 3005, 'msg' => '未添加的订单商品物流分配关系，无法修改'];
        }
        $order_child_id = DbOrder::getOrderGoods('order_child_id', ['id' => $order_goods_id],false,false,true)['order_child_id'];
        
        $order_id = DbOrder::getOrderChild('order_id', ['id' => $order_child_id],true)['order_id'];

        $order_status = DbOrder::getOrder('order_status', ['id' => $order_id], true)['order_status'] ;

        if ($order_status!=4 || $order_status!=5) {
            return ['code' => 3004,'msg' => '非待发货订单无法发货或已发货订单无法变更'];
        }
        $update_order_express = [];

        $update_order_express['express_no'] = $express_no;
        $update_order_express['express_key'] = $express_key;
        $update_order_express['express_name'] = $express_name;
        DbOrder::updateOrderExpress($update_order_express,$order_express['id']);
        return ['code' => 200];
    }
}