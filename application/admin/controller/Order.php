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
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单列表空 / 3002:页码和查询条数只能是数字
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
        
        $page = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10 ;
        
        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => 3002];
        }
        $result = $this->app->order->getOrderList(intval($page),intval($pagenum));
        return $result;
    }

    /**
     * @api              {post} / 获取订单详情
     * @apiGroup         admin_Orders
     * @apiName          getOrderInfo
     * @apiParam (入参) {Number} id 订单ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3002:订单ID只能是数字
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
     * @apiSampleRequest /admin/Order/getOrderInfo
     * @apiParamExample (data) {Array} 返回用户列表
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
    public function getOrderInfo(){
        $id = trim($this->request->post('id'));
        if (!is_numeric($id)) {
            return ['code' => 3002];
        }
        $result = $this->app->order->getOrderInfo($id);
        return $result;
    }

    /**
     * @api              {post} / 返回快递公司及其编码(未完成)
     * @apiGroup         admin_Orders
     * @apiName          getExpressList
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 
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
     * @apiSampleRequest /admin/Order/getExpressList
     * @apiParamExample (data) {Array} 返回用户列表
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
    public function getExpressList(){
        $ExpressList = ExpressList;
        return $ExpressList;
    }


}
