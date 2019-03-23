<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\AdminController;
class Order extends AdminController
{
    /**
     * @api              {post} / 获取订单列表
     * @apiDescription   getOrders
     * @apiGroup         admin_Orders
     * @apiName          getOrders
     * @apiParam (入参) {Number} order_status 订单状态 1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功
     * @apiParam (入参) {Number} supplier_id 供应商id
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单列表空 / 3002:页码和查询条数只能是数字 / 3003:无效的状态查询
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} order_list 结果
     * @apiSuccess (data) {String} id 订单ID
     * @apiSuccess (data) {String} order_no 生成唯一订单号
     * @apiSuccess (data) {String} third_order_id 第三方订单id
     * @apiSuccess (data) {String} uid 用户id
     * @apiSuccess (data) {String} order_status 订单状态   1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功
     * @apiSuccess (data) {String} order_money 订单金额(优惠金额+实际支付的金额)
     * @apiSuccess (data) {String} deduction_money 商票抵扣金额
     * @apiSuccess (data) {String} pay_money 实际支付(第三方支付金额+商票抵扣金额)
     * @apiSuccess (data) {String} goods_money 商品金额
     * @apiSuccess (data) {String} discount_money 优惠金额
     * @apiSuccess (data) {String} pay_type 支付类型 1.所有第三方支付 2.商票
     * @apiSuccess (data) {String} third_money 第三方支付金额
     * @apiSuccess (data) {String} third_pay_type 第三方支付类型1.支付宝 2.微信 3.银联
     * @apiSampleRequest /admin/Order/getOrders
     * @apiParamExample (data) {Array} 返回订单列表
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id": 12,
     *   "order_no": "odr19021817062852575049",
     *   "order_status": 1,
     *   "order_money": "100.00",
     *   "deduction_money": "0.00",
     *   "pay_money": "100.00",
     *   "goods_money": "100.00",
     *   "discount_money": "0.00",
     *   "pay_type": 1,
     *   "third_money": "0.01",
     *   "third_pay_type": 2
     *  },
     * ]
     * @author rzc
     */
    public function getOrders(){
        $page = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));
        $order_status = trim($this->request->post('order_status'));
        $supplier_id = trim($this->request->post('supplier_id'));
        
        $page = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10 ;
        
        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => 3002];
        }

        $order_status_data = [1,2,3,4,5,6,7,8,9,10];
        if ($order_status) {
            if (!in_array($order_status,$order_status_data)) {
                return ['code' => '3003'];
            }
        }
        if ($supplier_id) {
            if (!is_numeric($supplier_id)) {
                return ['code' => '3004'];
            }
        }
        
        $result = $this->app->order->getOrderList(intval($page),intval($pagenum),intval($order_status),intval($supplier_id));
        return $result;
    }

    /**
     * @api              {post} / 获取订单详情
     * @apiGroup         admin_Orders
     * @apiName          getOrderInfo
     * @apiParam (入参) {Number} id 订单ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3002:订单ID只能是数字
     * @apiSuccess (order_info) {object_array} order_info 结果
     * @apiSuccess (order_info) {String} id 订单ID
     * @apiSuccess (order_info) {String} order_no 生成唯一订单号
     * @apiSuccess (order_info) {String} third_order_id 第三方订单id
     * @apiSuccess (order_info) {String} uid 用户id
     * @apiSuccess (order_info) {String} order_status 订单状态   1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功
     * @apiSuccess (order_info) {String} order_money 订单金额(优惠金额+实际支付的金额)
     * @apiSuccess (order_info) {String} deduction_money 商票抵扣金额
     * @apiSuccess (order_info) {String} pay_money 实际支付(第三方支付金额+商票抵扣金额)
     * @apiSuccess (order_info) {String} goods_money 商品金额
     * @apiSuccess (order_info) {String} discount_money 优惠金额
     * @apiSuccess (order_info) {String} pay_type 支付类型 1.所有第三方支付 2.商票
     * @apiSuccess (order_info) {String} third_money 第三方支付金额
     * @apiSuccess (order_info) {String} linkman 订单联系人
     * @apiSuccess (order_info) {String} linkphone 联系人电话
     * @apiSuccess (order_info) {String} province_name 省份名称
     * @apiSuccess (order_info) {String} city_name 城市名称
     * @apiSuccess (order_info) {String} area_name 区域名称
     * @apiSuccess (order_info) {String} address 收货地址
     * @apiSuccess (order_info) {String} message 买家留言信息
     * @apiSuccess (order_info) {String} third_time 第三方支付时间
     * @apiSuccess (order_info) {String} pay_time 支付时间
     * @apiSuccess (order_info) {String} create_time 生成订单时间
     * @apiSuccess (order_info) {String} rece_time 收货时间
     * @apiSuccess (order_pack[order_goods]) {String} goods_id 商品ID
     * @apiSuccess (order_pack[order_goods]) {String} goods_name 商品名称
     * @apiSuccess (order_pack[order_goods]) {String} order_child_id 订单字订单ID
     * @apiSuccess (order_pack[order_goods]) {String} sku_id 商品规格ID
     * @apiSuccess (order_pack[order_goods]) {String} sup_id 商品供应商ID
     * @apiSuccess (order_pack[order_goods]) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (order_pack[order_goods]) {String} goods_price 商品成交价
     * @apiSuccess (order_pack[order_goods]) {String} margin_price 实际成交毛利
     * @apiSuccess (order_pack[order_goods]) {String} integral 赠送积分
     * @apiSuccess (order_pack[order_goods]) {String} goods_num 商品成交数量
     * @apiSuccess (order_pack[order_goods]) {String} sku_json 商品规格详情列表
     * @apiSuccess (no_deliver_goods) {object_array} no_deliver_goods 未发货商品及属性及订单商品ID
     * @apiSuccess (no_deliver_goods) {String} id 
     * @apiSuccess (no_deliver_goods) {String} goods_name 商品名称
     * @apiSuccess (no_deliver_goods) {String} sku_json 商品规格详情列表
     * @apiSuccess (has_deliver_goods) {object_array} has_deliver_goods 已发货商品及属性及订单商品ID
     * @apiSuccess (has_deliver_goods) {String} id 
     * @apiSuccess (has_deliver_goods) {String} goods_name 商品名称
     * @apiSuccess (has_deliver_goods) {String} sku_json 商品规格详情列表
     * @apiSampleRequest /admin/Order/getOrderInfo
     * @apiParamExample (order_info) {Array} 返回订单详情
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id": 12,
     *   "order_no": "odr19021817062852575049",
     *   "order_status": 1,
     *   "order_money": "100.00",
     *   "deduction_money": "0.00",
     *   "pay_money": "100.00",
     *   "goods_money": "100.00",
     *   "discount_money": "0.00",
     *   "pay_type": 1,
     *   "third_money": "0.01",
     *   "third_pay_type": 2
     *  },
     * ]
     * @apiParamExample (no_deliver_goods) {Array} 返回订单未发货商品
     * [
     *  {"id": 12,
     *   "goods_name": "天然碱性苏打水5",
     *   "sku_json": "[\"24\\u74f6\",\"\\u767d\\u8272\"]"
     *  },
     * ]
     * @apiParamExample (has_deliver_goods) {Array} 返回订单已发货商品
     * [
     *  {"id": 12,
     *   "goods_name": "天然碱性苏打水5",
     *   "sku_json": "[\"24\\u74f6\",\"\\u767d\\u8272\"]"
     *  },
     * ]
     * @author rzc
     */
    public function getOrderInfo(){
        $id = trim($this->request->post('id'));
        if (!is_numeric($id)) {
            return ['code' => 3002];
        }
        $result = $this->app->order->getOrderInfo($id);
        return $result;
    }

    /**
     * @api              {post} / 返回快递公司及其编码
     * @apiGroup         admin_Orders
     * @apiName          getExpressList
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} ExpressList 结果
     * @apiSampleRequest /admin/Order/getExpressList
     * @author rzc
     */
    public function getExpressList(){
        $ExpressList = getExpressList();
        return ['code' => 200,'ExpressList'=> $ExpressList];
    }

    /**
     * @api              {post} / 订单发货
     * @apiGroup         admin_Orders
     * @apiName          deliverOrderGoods
     * @apiParam (入参) {Number} order_goods_id 订单商品关系表id
     * @apiParam (入参) {Number} express_no 快递单号
     * @apiParam (入参) {Number} express_key 快递key
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3001:空的快递key或者express_no / 3002:请输入正确的快递公司编码
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} ExpressList 结果
     * @apiSampleRequest /admin/Order/deliverOrderGoods
     * @author rzc
     */
    public function deliverOrderGoods(){
        $order_goods_id = trim($this->request->post('order_goods_id'));
        $express_no = trim($this->request->post('express_no'));
        $express_key = trim($this->request->post('express_key'));

        if (empty($express_key) || empty($express_no)) {
            return ['code' => 3001];
        }
        $ExpressList = getExpressList();
       
        if (!array_key_exists($express_key,$ExpressList)) {
            return ['code' => 3002];
        }
        $express_name = $ExpressList[$express_key];
        $result = $this->app->order->deliverOrderGoods($order_goods_id,$express_no,$express_key,$express_name);
        return $result;
    }

    /**
     * @api              {post} / 修改订单发货信息
     * @apiGroup         admin_Orders
     * @apiName          updateDeliverOrderGoods
     * @apiParam (入参) {Number} order_goods_id 订单商品关系表id
     * @apiParam (入参) {Number} express_no 快递单号
     * @apiParam (入参) {Number} express_key 快递key
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3001:空的快递key或者express_no / 3002:请输入正确的快递公司编码
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} ExpressList 结果
     * @apiSampleRequest /admin/Order/updateDeliverOrderGoods
     * @author rzc
     */
    public function updateDeliverOrderGoods(){
        $order_goods_id = trim($this->request->post('order_goods_id'));
        $express_no = trim($this->request->post('express_no'));
        $express_key = trim($this->request->post('express_key'));

        if (empty($express_key) || empty($express_no)) {
            return ['code' => 3001];
        }
        $ExpressList = getExpressList();
       
        if (!array_key_exists($express_key,$ExpressList)) {
            return ['code' => 3002];
        }
        $express_name = $ExpressList[$express_key];
        $result = $this->app->order->updateDeliverOrderGoods($order_goods_id,$express_no,$express_key,$express_name);
        return $result;
    }

}
