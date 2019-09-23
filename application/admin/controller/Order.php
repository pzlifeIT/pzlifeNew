<?php

namespace app\admin\controller;

use app\admin\AdminController;
use think\Controller;

class Order extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取订单列表
     * @apiDescription   getOrders
     * @apiGroup         admin_Orders
     * @apiName          getOrders
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} order_status 订单状态 1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiParam (入参) {Number} [order_no] 订单号
     * @apiParam (入参) {Number} [nick_name] 昵称
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单列表空 / 3002:页码和查询条数只能是数字 / 3003:无效的状态查询
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} order_list 结果
     * @apiSuccess (data) {String} id 订单ID
     * @apiSuccess (data) {String} order_no 生成唯一订单号
     * @apiSuccess (data) {String} third_order_id 第三方订单id
     * @apiSuccess (data) {String} uid 用户id
     * @apiSuccess (data) {String} order_status 订单状态   1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功
     * @apiSuccess (data) {String} order_money 订单金额(优惠金额+实际支付的金额)
     * @apiSuccess (data) {String} deduction_money 商券抵扣金额
     * @apiSuccess (data) {String} pay_money 实际支付(第三方支付金额+商券抵扣金额)
     * @apiSuccess (data) {String} goods_money 商品金额
     * @apiSuccess (data) {String} discount_money 优惠金额
     * @apiSuccess (data) {String} pay_type 支付类型 1.所有第三方支付 2.商券
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
    public function getOrders() {
        $apiName      = classBasename($this) . '/' . __function__;
        $cmsConId     = trim($this->request->post('cms_con_id')); //操作管理员
        $page         = trim($this->request->post('page'));
        $pagenum      = trim($this->request->post('pagenum'));
        $order_status = trim($this->request->post('order_status'));
        $order_no     = trim($this->request->post('order_no'));
        $nick_name    = trim($this->request->post('nick_name'));

        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => 3002];
        }

        $order_status_data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        if ($order_status) {
            if (!in_array($order_status, $order_status_data)) {
                return ['code' => '3003'];
            }
        }
        $result = $this->app->order->getOrderList(intval($page), intval($pagenum), intval($order_status), $order_no, $nick_name);
        $this->apiLog($apiName, [$cmsConId, $page, $pagenum, $order_status, $order_no, $nick_name], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取订单详情
     * @apiDescription   getOrderInfo
     * @apiGroup         admin_Orders
     * @apiName          getOrderInfo
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 订单ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3002:订单ID只能是数字
     * @apiSuccess (order_info) {object_array} order_info 结果
     * @apiSuccess (order_info) {String} id 订单ID
     * @apiSuccess (order_info) {String} order_no 生成唯一订单号
     * @apiSuccess (order_info) {String} third_order_id 第三方订单id
     * @apiSuccess (order_info) {String} uid 用户id
     * @apiSuccess (order_info) {String} order_status 订单状态   1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功
     * @apiSuccess (order_info) {String} order_money 订单金额(优惠金额+实际支付的金额)
     * @apiSuccess (order_info) {String} deduction_money 商券抵扣金额
     * @apiSuccess (order_info) {String} pay_money 实际支付(第三方支付金额+商券抵扣金额)
     * @apiSuccess (order_info) {String} goods_money 商品金额
     * @apiSuccess (order_info) {String} discount_money 优惠金额
     * @apiSuccess (order_info) {String} pay_type 支付类型 1.所有第三方支付 2.商券
     * @apiSuccess (order_info) {String} third_money 第三方支付金额
     * @apiSuccess (order_info) {String} linkman 订单联系人
     * @apiSuccess (order_info) {String} linkphone 联系人电话
     * @apiSuccess (order_info) {String} province_name 省份名称
     * @apiSuccess (order_info) {String} city_name 城市名称
     * @apiSuccess (order_info) {String} area_name 区域名称
     * @apiSuccess (order_info) {String} address 收货地址
     * @apiSuccess (order_info) {String} message 买家留言信息
     * @apiSuccess (order_info) {String} express_money 订单总运费
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
    public function getOrderInfo() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $id       = trim($this->request->post('id'));
        if (!is_numeric($id)) {
            return ['code' => 3002];
        }
        $result = $this->app->order->getOrderInfo($id);
        $this->apiLog($apiName, [$cmsConId, $id], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 返回快递公司及其编码
     * @apiDescription   getExpressList
     * @apiGroup         admin_Orders
     * @apiName          getExpressList
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} ExpressList 结果
     * @apiSampleRequest /admin/Order/getExpressList
     * @author rzc
     */
    public function getExpressList() {
        $apiName     = classBasename($this) . '/' . __function__;
        $cmsConId    = trim($this->request->post('cms_con_id')); //操作管理员
        $ExpressList = getExpressList();
        $this->apiLog($apiName, [$cmsConId], 200, $cmsConId);
        return ['code' => 200, 'ExpressList' => $ExpressList];
    }

    /**
     * @api              {post} / 订单发货
     * @apiDescription   deliverOrderGoods
     * @apiGroup         admin_Orders
     * @apiName          deliverOrderGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} order_goods_id 订单商品关系表id
     * @apiParam (入参) {Number} express_no 快递单号
     * @apiParam (入参) {Number} express_key 快递key
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3001:空的快递key或者express_no / 3002:请输入正确的快递公司编码 / 3003:不存在的order_goods_id / 3004:不同用户订单不能使用同一物流公司物流单号发货 / 3005:已添加的订单商品物流分配关系 / 3006:添加失败 / 3007:不同用户订单不能使用同一物流公司物流单号发货
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} ExpressList 结果
     * @apiSampleRequest /admin/Order/deliverOrderGoods
     * @author rzc
     */
    public function deliverOrderGoods() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $order_goods_id = trim($this->request->post('order_goods_id'));
        $express_no     = trim($this->request->post('express_no'));
        $express_key    = trim($this->request->post('express_key'));

        if (empty($express_key) || empty($express_no)) {
            return ['code' => 3001];
        }
        $ExpressList = getExpressList();

        if (!array_key_exists($express_key, $ExpressList)) {
            return ['code' => 3002];
        }
        $express_name = $ExpressList[$express_key];
        $result       = $this->app->order->deliverOrderGoods($order_goods_id, $express_no, $express_key, $express_name);
        $this->apiLog($apiName, [$cmsConId, $order_goods_id, $express_no, $express_key], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改订单发货信息
     * @apiDescription   updateDeliverOrderGoods
     * @apiGroup         admin_Orders
     * @apiName          updateDeliverOrderGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} order_goods_id 订单商品关系表id
     * @apiParam (入参) {Number} express_no 快递单号
     * @apiParam (入参) {Number} express_key 快递key
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3001:空的快递key或者express_no / 3002:请输入正确的快递公司编码 / 3003:不存在的order_goods_id / 3004:非待发货订单无法发货或已发货订单无法变更 / 3005:未添加的订单商品物流分配关系，无法修改 / 3007:不同用户订单不能使用同一物流公司物流单号发货
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} ExpressList 结果
     * @apiSampleRequest /admin/Order/updateDeliverOrderGoods
     * @author rzc
     */
    public function updateDeliverOrderGoods() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $order_goods_id = trim($this->request->post('order_goods_id'));
        $express_no     = trim($this->request->post('express_no'));
        $express_key    = trim($this->request->post('express_key'));

        if (empty($express_key) || empty($express_no)) {
            return ['code' => 3001];
        }
        $ExpressList = getExpressList();

        if (!array_key_exists($express_key, $ExpressList)) {
            return ['code' => 3002];
        }
        $express_name = $ExpressList[$express_key];
        $result       = $this->app->order->updateDeliverOrderGoods($order_goods_id, $express_no, $express_key, $express_name);
        $this->apiLog($apiName, [$cmsConId, $order_goods_id, $express_no, $express_key], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 查询已支付权益订单
     * @apiDescription   getMemberOrders
     * @apiGroup         admin_Orders
     * @apiName          getMemberOrders
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3001:空的快递key或者express_no / 3002:请输入正确的快递公司编码
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} memberOrderList 结果
     * @apiSuccess (memberOrderList) {String} order_no 订单号
     * @apiSuccess (memberOrderList) {String} from_uid 分享用户ID
     * @apiSuccess (memberOrderList) {String} uid 购买用户ID
     * @apiSuccess (memberOrderList) {String} actype 活动类型：1.无活动 2兼职网推
     * @apiSuccess (memberOrderList) {String} user_type 用户订单类型 1.钻石会员 2.boss
     * @apiSuccess (memberOrderList) {String} pay_money 支付金额
     * @apiSuccess (memberOrderList) {String} pay_type 支付类型 1.支付宝 2.微信 3.银联
     * @apiSuccess (memberOrderList) {String} pay_time 支付时间
     * @apiSuccess (memberOrderList) {String} create_time 生成订单时间
     * @apiSuccess (memberOrderList[user]) {String} id 购买用户ID
     * @apiSuccess (memberOrderList[user]) {String} nick_name 购买用户昵称
     * @apiSuccess (memberOrderList[user]) {String} avatar 购买用户头像
     * @apiSuccess (memberOrderList[user]) {String} user_identity 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiSuccess (memberOrderList[fromuser]) {String} id 购买用户ID
     * @apiSuccess (memberOrderList[fromuser]) {String} nick_name 购买用户昵称
     * @apiSuccess (memberOrderList[fromuser]) {String} avatar 购买用户头像
     * @apiSuccess (memberOrderList[fromuser]) {String} user_identity 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiSampleRequest /admin/Order/getMemberOrders
     * @author rzc
     */
    public function getMemberOrders() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $pagenum  = trim($this->request->post('pagenum'));
        $page     = trim($this->request->post('page'));

        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => 3002];
        }
        $result = $this->app->order->getMemberOrders(intval($page), intval($pagenum));
        $this->apiLog($apiName, [$cmsConId, $pagenum, $page], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 订单关键词搜索统计
     * @apiDescription   searchKeywordOrders
     * @apiGroup         admin_Orders
     * @apiName          searchKeywordOrders
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} keyword
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3001:空的keyword /
     * @apiSuccess (返回) {String} order_num 总成交订单
     * @apiSuccess (返回) {String} all_goods_num 总成交数量
     * @apiSuccess (返回) {String} all_goods_price 总成交额
     * @apiSampleRequest /admin/Order/searchKeywordOrders
     * @author rzc
     */
    public function searchKeywordOrders() {
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $keyword  = trim($this->request->post('keyword'));
        if (empty($keyword)) {
            return ['code' => '3001'];
        }
        $result = $this->app->order->searchKeywordOrders($cmsConId, $keyword);
        return $result;
    }

    /**
     * @api              {post} / 发货单导出
     * @apiDescription   exportDeliveryOrder
     * @apiGroup         admin_Orders
     * @apiName          exportDeliveryOrder
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [sup_id] 供应商ID
     * @apiParam (入参) {Number} [order_status] 订单状态 默认4,已付款
     * @apiParam (入参) {Number} [order_type] 订单类型 默认1
     * @apiParam (入参) {Number} page 页码默认1
     * @apiParam (入参) {Number} pagenum 查询条数默认1000
     * @apiSuccess (返回) {String} code 200:成功 / 3000:订单数据空 / 3001:空的keyword /
     * @apiSampleRequest /admin/Order/exportDeliveryOrder
     * @author rzc
     */
    public function exportDeliveryOrder(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $pagenum  = trim($this->request->post('pagenum'));
        $page     = trim($this->request->post('page'));
        $sup_id   = trim($this->request->post('sup_id'));
        $order_status   = trim($this->request->post('order_status'));
        $order_type   = trim($this->request->post('order_type'));

        $order_type    = $order_type ? (int)$order_type : 1;
        $order_status    = $order_status ? (int)$order_status : 4;
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 1000;
        $sup_id = intval($sup_id);

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => 3002];
        }
        $result = $this->app->order->exportDeliveryOrder($sup_id, $order_type, $order_status, intval($page), intval($pagenum));
        $this->apiLog($apiName, [$cmsConId, $pagenum, $page], $result['code'], $cmsConId);
        return $result;
    }
}
