<?php

namespace app\index\controller;

use app\index\MyController;

class Order extends MyController {
    protected $beforeActionList = [
        'isLogin',//所有方法的前置操作
//        'isLogin' => ['except' => ''],//除去getFirstCate其他方法都进行second前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取用户订单
     * @apiDescription   getUserOrderList
     * @apiGroup         index_order
     * @apiName          getUserOrderList
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {Number} order_status 订单状态   1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数 / 3003:order_status必须是数字 / 3004:订单状态码有误
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/order/getUserOrderList
     * @return array
     * @author rzc
     */
    public function getUserOrderList(){
        $con_id = trim($this->request->post('con_id'));
        $order_status = trim($this->request->post('order_status'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if ($order_status) {
            if (!is_numeric($order_status)) {
                return ['code' => '3003'];
            }
            $order_statusArr = [1,2,3,4,5,6,7,8,9,10];
            if (!in_array($order_status,$order_statusArr)) {
                return ['code' => 3004];
            }
        }
        
        $result = $this->app->order->getUserOrderList($con_id,$order_status);
        return $result;
    }

}