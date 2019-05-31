<?php

namespace app\index\controller;

use app\index\MyController;

class Order extends MyController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //        'isLogin' => ['except' => ''],//除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取用户订单
     * @apiDescription   getUserOrderList
     * @apiGroup         index_order
     * @apiName          getUserOrderList
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {Number} orderStatus 订单状态   1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 每页展示条数
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数 / 3003:order_status、page和pagenum必须是数字 / 3004:订单状态码有误
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSuccess (data) {array} order_list 订单信息
     * @apiSuccess (order_list) {String} order_no 订单号
     * @apiSuccess (order_list) {String} third_order_id 三方订单号
     * @apiSuccess (order_list) {String} order_status 订单状态
     * @apiSuccess (order_list) {String} order_money 订单金额(优惠金额+实际支付的金额)
     * @apiSuccess (order_list) {String} deduction_money 商券抵扣金额
     * @apiSuccess (order_list) {String} pay_money 实际支付(第三方支付金额+商券抵扣金额)
     * @apiSuccess (order_list) {String} goods_money 商品金额
     * @apiSuccess (order_list) {String} discount_money 优惠金额
     * @apiSuccess (order_list) {String} third_money 第三方支付金额
     * @apiSuccess (order_list) {String} express_money 邮费
     * @apiSuccess (order_list) {String} integral 积分
     * @apiSuccess (order_list) {String} commission 佣金
     * @apiSuccess (order_list) {array} order_child 子订单
     * @apiSuccess (order_list[order_child]) {String} id 子订单id
     * @apiSuccess (order_list[order_child]) {String} express_money 子订单邮费
     * @apiSuccess (order_list[order_child]) {String} supplier_id 子订单供应商id
     * @apiSuccess (order_list[order_child]) {String} supplier_name 子订单供应商名称
     * @apiSuccess (order_list[order_child]) {array} order_goods 子订单订单商品
     * @apiSuccess (order_list[order_child][order_goods]) {String} goods_id 子订单订单商品id
     * @apiSuccess (order_list[order_child][order_goods]) {String} goods_name 子订单订单商品名称
     * @apiSuccess (order_list[order_child][order_goods]) {String} order_child_id 子订单id
     * @apiSuccess (order_list[order_child][order_goods]) {String} sku_id 子订单订单商品skuid
     * @apiSuccess (order_list[order_child][order_goods]) {String} sup_id 子订单订单商品供应商id
     * @apiSuccess (order_list[order_child][order_goods]) {String} goods_type 子订单订单商品商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (order_list[order_child][order_goods]) {String} goods_price 子订单订单商品商品成交价
     * @apiSuccess (order_list[order_child][order_goods]) {String} margin_price 子订单订单商品实际成交毛利
     * @apiSuccess (order_list[order_child][order_goods]) {String} integral 子订单订单商品赠送积分
     * @apiSuccess (order_list[order_child][order_goods]) {String} goods_num 子订单订单商品商品成交数量
     * @apiSuccess (order_list[order_child][order_goods]) {String} sku_image 子订单订单商品规格图
     * @apiSuccess (order_list[order_child][order_goods]) {String} sku_json 子订单订单商品商品规格详情列表
     * @apiSampleRequest /index/order/getUserOrderList
     * @return array
     * @author rzc
     */
    public function getUserOrderList() {
        $con_id = trim($this->request->post('con_id'));
        // $con_id = 1;
        $order_status = $this->request->post('orderStatus');
        $page         = trim($this->request->post('page'));
        $pagenum      = trim($this->request->post('pagenum'));
        $page         = $page ? $page : 1;
        $pagenum      = $pagenum ? $pagenum : 10;
        // if (empty($con_id)) {
        //     return ['code' => '3002'];
        // }
        // if (strlen($con_id) != 32) {
        //     return ['code' => '3001'];
        // }
        // var_dump($order_status);die;
        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3003'];
        }
        if ($order_status) {
            if (!is_numeric($order_status)) {
                return ['code' => '3003'];
            }
            $order_statusArr = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
            if (!in_array($order_status, $order_statusArr)) {
                return ['code' => 3004];
            }
        }

        $result = $this->app->order->getUserOrderList($con_id, $order_status, $page, $pagenum);
        return $result;
    }

    /**
     * @api              {post} / 获取用户订单详情
     * @apiDescription   getUserOrderInfo
     * @apiGroup         index_order
     * @apiName          getUserOrderInfo
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {Number} order_no 订单号
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数 / 3003:order_no长度只能是23位 / 3004:订单不存在
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/order/getUserOrderInfo
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSuccess (data) {array} order_info 订单信息
     * @apiSuccess (order_info) {String} order_no 订单号
     * @apiSuccess (order_info) {String} third_order_id 三方订单号
     * @apiSuccess (order_info) {String} order_status 订单状态
     * @apiSuccess (order_info) {String} order_money 订单金额(优惠金额+实际支付的金额)
     * @apiSuccess (order_info) {String} deduction_money 商券抵扣金额
     * @apiSuccess (order_info) {String} pay_money 实际支付(第三方支付金额+商券抵扣金额)
     * @apiSuccess (order_info) {String} goods_money 商品金额
     * @apiSuccess (order_info) {String} discount_money 优惠金额
     * @apiSuccess (order_info) {String} third_money 第三方支付金额
     * @apiSuccess (order_info) {String} third_pay_type 第三方支付类型1.支付宝 2.微信 3.银联
     * @apiSuccess (order_info) {String} linkman 订单联系人
     * @apiSuccess (order_info) {String} linkphone 联系人电话
     * @apiSuccess (order_info) {String} province_name 省份名称
     * @apiSuccess (order_info) {String} city_name 城市名称
     * @apiSuccess (order_info) {String} area_name 地区名称
     * @apiSuccess (order_info) {String} address 收货地址
     * @apiSuccess (order_info) {String} message 买家留言信息
     * @apiSuccess (order_info) {String} express_money 邮费
     * @apiSuccess (order_info) {String} integral 积分
     * @apiSuccess (order_info) {String} commission 佣金
     * @apiSuccess (order_info) {array} order_child 子订单
     * @apiSuccess (order_info[order_child]) {String} id 子订单id
     * @apiSuccess (order_info[order_child]) {String} express_money 子订单邮费
     * @apiSuccess (order_info[order_child]) {String} supplier_id 子订单供应商id
     * @apiSuccess (order_info[order_child]) {String} supplier_name 子订单供应商名称
     * @apiSuccess (order_info[order_child]) {array} order_goods 子订单订单商品
     * @apiSuccess (order_info[order_child][order_goods]) {String} goods_id 子订单订单商品id
     * @apiSuccess (order_info[order_child][order_goods]) {String} goods_name 子订单订单商品名称
     * @apiSuccess (order_info[order_child][order_goods]) {String} order_child_id 子订单id
     * @apiSuccess (order_info[order_child][order_goods]) {String} sku_id 子订单订单商品skuid
     * @apiSuccess (order_info[order_child][order_goods]) {String} sup_id 子订单订单商品供应商id
     * @apiSuccess (order_info[order_child][order_goods]) {String} goods_type 子订单订单商品商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (order_info[order_child][order_goods]) {String} goods_price 子订单订单商品商品成交价
     * @apiSuccess (order_info[order_child][order_goods]) {String} margin_price 子订单订单商品实际成交毛利
     * @apiSuccess (order_info[order_child][order_goods]) {String} integral 子订单订单商品赠送积分
     * @apiSuccess (order_info[order_child][order_goods]) {String} goods_num 子订单订单商品商品成交数量
     * @apiSuccess (order_info[order_child][order_goods]) {String} sku_image 子订单订单商品规格图
     * @apiSuccess (order_info[order_child][order_goods]) {String} sku_json 子订单订单商品商品规格详情列表
     * @return array
     * @author rzc
     */
    public function getUserOrderInfo() {
        $con_id = trim($this->request->post('con_id'));
        // $con_id = 1;
        $order_no = trim($this->request->post('order_no'));
        if (empty($order_no)) {
            return ['code' => 3001];
        }
        if (strlen($order_no) != 23) {
            return ['code' => '3003'];
        }
        $result = $this->app->order->getUserOrderInfo($con_id, $order_no);
        return $result;
    }

    /**
     * @api              {post} / 立即购买结算页
     * @apiDescription   quickSettlement
     * @apiGroup         index_order
     * @apiName          quickSettlement
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {String} buid 推荐人
     * @apiParam (入参) {Number} sku_id 购买的skuid
     * @apiParam (入参) {Number} [user_address_id] 用户选择的地址(user_address的id,不选地址暂不计算邮费)
     * @apiParam (入参) {Number} [num] 购买数量
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 / 3004:商品售罄 / 3006:商品不支持配送 / 3007:商品库存不够
     * @apiSuccess (返回) {Int} goods_count 购买商品总数
     * @apiSuccess (返回) {Float} rebate_all 所有商品钻石返利总和
     * @apiSuccess (返回) {Float} total_goods_price 所有商品价格
     * @apiSuccess (返回) {Float} total_freight_price 运费总价
     * @apiSuccess (返回) {Float} total_price 价格总计
     * @apiSuccess (返回) {Array} supplier_list 供应商分组
     * @apiSuccess (返回) {Array} freight_supplier_price 各个供应商的运费价格(供应商id->价格)
     * @apiSuccess (返回) {Float} balance 账户的商券余额
     * @apiSuccess (supplier_list) {Int} id 供应商id
     * @apiSuccess (supplier_list) {String} name 供应商name
     * @apiSuccess (supplier_list) {String} image 供应商image
     * @apiSuccess (supplier_list) {String} title 供应商title
     * @apiSuccess (supplier_list) {String} desc 供应商详情
     * @apiSuccess (supplier_list) {Array} shop_list 购买店铺分组
     * @apiSuccess (shop_list) {Int} id 店铺id
     * @apiSuccess (shop_list) {Int} uid 店铺boss的uid
     * @apiSuccess (shop_list) {String} shop_name 店铺名称
     * @apiSuccess (shop_list) {String} shop_image 店铺image
     * @apiSuccess (shop_list) {Array} goods_list 店铺购买的商品列表
     * @apiSuccess (goods_list) {Int} id skuid
     * @apiSuccess (goods_list) {Int} goods_id 商品id
     * @apiSuccess (goods_list) {Float} market_price 市场价
     * @apiSuccess (goods_list) {Float} retail_price 零售价
     * @apiSuccess (goods_list) {Int} integral_active 积分赠送
     * @apiSuccess (goods_list) {String} sku_image sku图片
     * @apiSuccess (goods_list) {String} goods_name 商品名称
     * @apiSuccess (goods_list) {Int} goods_type 商品类型
     * @apiSuccess (goods_list) {String} subtitle 商品标题
     * @apiSuccess (goods_list) {Array} attr 属性列表
     * @apiSuccess (goods_list) {Float} rebate 单品返利
     * @apiSuccess (goods_list) {Int} integral 赠送积分
     * @apiSuccess (goods_list) {Int} buySum 购买数量
     * @apiSampleRequest /index/order/quicksettlement
     * @author zyr
     */
    public function quickSettlement() {
        $conId         = trim($this->request->post('con_id'));
        $buid          = trim($this->request->post('buid'));
        $skuId         = trim($this->request->post('sku_id'));
        $num           = trim($this->request->post('num'));
        $userAddressId = trim($this->request->post('user_address_id'));
        if (!is_numeric($skuId)) {
            return ['code' => '3001'];
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        $userAddressId = empty($userAddressId) ? 0 : $userAddressId;
        if (!is_numeric($userAddressId)) {
            return ['code' => '3003'];
        }
        if (!is_numeric($num) || $num <= 0) {
            $num = 1;
        }
        $num    = intval($num);
        $buid   = empty(deUid($buid)) ? 1 : deUid($buid);
        $result = $this->app->order->quickSettlement($conId, $buid, $skuId, $num, $userAddressId);
        return $result;
    }

    /**
     * @api              {post} / 快速购买创建订单
     * @apiDescription   quickCreateOrder
     * @apiGroup         index_order
     * @apiName          quickCreateOrder
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {String} buid 推荐人
     * @apiParam (入参) {Number} sku_id 购买的skuid
     * @apiParam (入参) {Number} user_address_id 用户选择的地址
     * @apiParam (入参) {Number} pay_type 支付方式 1.所有第三方支付 2.商券支付
     * @apiParam (入参) {Number} [num] 购买数量
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 /3003:地址id错误 / 3004:商品售罄 / 3006:商品不支持配送 / 3007:商品库存不够 / 3008:支付方式错误 / 3009:创建失败
     * @apiSuccess (返回) {String} order_no 订单号
     * @apiSuccess (返回) {Int} is_pay 1.已完成支付(商券) 2.需要发起第三方支付
     * @apiSampleRequest /index/order/quickcreateorder
     * @author zyr
     */
    public function quickCreateOrder() {
        $conId         = trim($this->request->post('con_id'));
        $buid          = trim($this->request->post('buid'));
        $skuId         = trim($this->request->post('sku_id'));
        $num           = trim($this->request->post('num'));
        $userAddressId = trim($this->request->post('user_address_id'));
        $payType       = trim($this->request->post('pay_type'));
        $payTypeArr    = [1, 2];
        if (!is_numeric($skuId)) {
            return ['code' => '3001'];
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        $userAddressId = empty($userAddressId) ? 0 : $userAddressId;
        if (!is_numeric($userAddressId)) {
            return ['code' => '3003'];
        }
        if (!is_numeric($num) || $num < 1) {
            $num = 1;
        }
        $num = intval($num);
        if (!in_array($payType, $payTypeArr)) {
            return ['code' => '3008'];
        }
        $num    = intval($num);
        $buid   = empty(deUid($buid)) ? 1 : deUid($buid);
        $result = $this->app->order->quickCreateOrder($conId, $buid, $skuId, $num, $userAddressId, $payType);
        return $result;
    }

    /**
     * @api              {post} / 创建结算页
     * @apiDescription   createSettlement
     * @apiGroup         index_order
     * @apiName          createSettlement
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} sku_id_list skuid列表
     * @apiParam (入参) {Number} [user_address_id] 用户选择的地址(user_address的id,不选地址暂不计算邮费)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 / 3004:商品售罄 / 3005:商品未加入购物车 / 3006:商品不支持配送 / 3007:商品库存不够
     * @apiSuccess (返回) {Int} goods_count 购买商品总数
     * @apiSuccess (返回) {Float} rebate_all 所有商品钻石返利总和
     * @apiSuccess (返回) {Float} total_goods_price 所有商品价格
     * @apiSuccess (返回) {Float} total_freight_price 运费总价
     * @apiSuccess (返回) {Float} total_price 价格总计
     * @apiSuccess (返回) {Array} supplier_list 供应商分组
     * @apiSuccess (返回) {Array} freight_supplier_price 各个供应商的运费价格(供应商id->价格)
     * @apiSuccess (返回) {Float} balance 账户的商券余额
     * @apiSuccess (返回) {Int} default_address_id 默认地址(0表示没有地址)
     * @apiSuccess (supplier_list) {Int} id 供应商id
     * @apiSuccess (supplier_list) {String} name 供应商name
     * @apiSuccess (supplier_list) {String} image 供应商image
     * @apiSuccess (supplier_list) {String} title 供应商title
     * @apiSuccess (supplier_list) {String} desc 供应商详情
     * @apiSuccess (supplier_list) {Array} shop_list 购买店铺分组
     * @apiSuccess (shop_list) {Int} id 店铺id
     * @apiSuccess (shop_list) {Int} uid 店铺boss的uid
     * @apiSuccess (shop_list) {String} shop_name 店铺名称
     * @apiSuccess (shop_list) {String} shop_image 店铺image
     * @apiSuccess (shop_list) {Array} goods_list 店铺购买的商品列表
     * @apiSuccess (goods_list) {Int} id skuid
     * @apiSuccess (goods_list) {Int} goods_id 商品id
     * @apiSuccess (goods_list) {Float} market_price 市场价
     * @apiSuccess (goods_list) {Float} retail_price 零售价
     * @apiSuccess (goods_list) {Int} integral_active 积分赠送
     * @apiSuccess (goods_list) {String} sku_image sku图片
     * @apiSuccess (goods_list) {String} goods_name 商品名称
     * @apiSuccess (goods_list) {Int} goods_type 商品类型
     * @apiSuccess (goods_list) {String} subtitle 商品标题
     * @apiSuccess (goods_list) {Array} attr 属性列表
     * @apiSuccess (goods_list) {Float} rebate 单品返利
     * @apiSuccess (goods_list) {Int} integral 赠送积分
     * @apiSuccess (goods_list) {Int} buySum 购买数量
     * @apiSampleRequest /index/order/createsettlement
     * @author zyr
     */
    public function createSettlement() {
        $skuIdList     = trim($this->request->post('sku_id_list'));
        $conId         = trim($this->request->post('con_id'));
        $userAddressId = trim($this->request->post('user_address_id'));
        if (!is_array($skuIdList)) {
            $skuIdList = explode(',', $skuIdList);
        }
        if (empty($skuIdList)) {
            return ['code' => '3001'];
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        $userAddressId = empty($userAddressId) ? 0 : $userAddressId;
        if (!is_numeric($userAddressId)) {
            return ['code' => '3003'];
        }
        $result = $this->app->order->createSettlement($conId, $skuIdList, intval($userAddressId));
        return $result;
    }

    /**
     * @api              {post} / 创建订单
     * @apiDescription   createOrder
     * @apiGroup         index_order
     * @apiName          createOrder
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} sku_id_list skuid列表
     * @apiParam (入参) {Number} user_address_id 用户选择的地址(user_address的id)
     * @apiParam (入参) {Number} pay_type 支付方式 1.所有第三方支付 2.商券支付
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 /3003:地址id错误 / 3004:商品售罄 / 3005:商品未加入购物车 / 3006:商品不支持配送 / 3007:商品库存不够 / 3008:支付方式错误 / 3009:创建失败
     * @apiSuccess (返回) {String} order_no 订单号
     * @apiSuccess (返回) {Int} is_pay 1.已完成支付(商券) 2.需要发起第三方支付
     * @apiSampleRequest /index/order/createorder
     * @author zyr
     */
    public function createOrder() {
        $skuIdList     = trim($this->request->post('sku_id_list'));
        $conId         = trim($this->request->post('con_id'));
        $userAddressId = trim($this->request->post('user_address_id'));
        $payType       = trim($this->request->post('pay_type'));
        $payTypeArr    = [1, 2];
        if (!is_array($skuIdList)) {
            $skuIdList = explode(',', $skuIdList);
        }
        if (empty($skuIdList)) {
            return ['code' => '3001'];
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        if (!is_numeric($userAddressId)) {
            return ['code' => '3003'];
        }
        if (!in_array($payType, $payTypeArr)) {
            return ['code' => '3008'];
        }
        $result = $this->app->order->createOrder($conId, $skuIdList, intval($userAddressId), intval($payType));
        return $result;
    }

    /**
     * @api              {post} / 取消订单
     * @apiDescription   cancelOrder
     * @apiGroup         index_order
     * @apiName          cancelOrder
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} order_no 订单号
     * @apiSuccess (返回) {String} code 200:成功 / 3001:订单号错误 / 3002.con_id错误 / 3003:没有可取消的订单 / 3005:取消失败
     * @apiSampleRequest /index/order/cancelorder
     * @author zyr
     */
    public function cancelOrder() {
        $conId   = trim($this->request->post('con_id'));
        $orderNo = trim($this->request->post('order_no'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        if (strlen($orderNo) != 23) {
            return ['code' => '3001'];
        }
        $result = $this->app->order->cancelOrder($orderNo, $conId);
        return $result;
    }

    /**
     * @api              {post} / 确认收货
     * @apiDescription   confirmOrder
     * @apiGroup         index_order
     * @apiName          confirmOrder
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} order_no 订单号
     * @apiSuccess (返回) {String} code 200:成功 / 3001:订单号错误 / 3002.con_id错误 / 3003:没有可确认的订单 / 3005:取消失败
     * @apiSampleRequest /index/order/confirmOrder
     * @author rzc
     */
    public function confirmOrder() {
        $conId   = trim($this->request->post('con_id'));
        $orderNo = trim($this->request->post('order_no'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        if (strlen($orderNo) != 23) {
            return ['code' => '3001'];
        }
        $result = $this->app->order->confirmOrder($orderNo, $conId);
        return $result;
    }

    /**
     * @api              {post} / 创建购买权益订单
     * @apiDescription   createMemberOrder
     * @apiGroup         index_order
     * @apiName          createMemberOrder
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} pay_type 支付类型 1.支付宝 2.微信 3.银联 4.线下 [目前只支持微信]
     * @apiParam (入参) {Number} user_type 用户订单类型 1.钻石会员(100) 2.boss 3.钻石会员120
     * @apiParam (入参) {Number} actype 用户订单类型 活动类型：1.无活动 2兼职网推
     * @apiParam (入参) {String} parent_id 分享用户uid
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 /3003:user_type和pay_type必须是数字
     * @apiSuccess (返回) {Int} goods_count 购买商品总数
     * @apiSampleRequest /index/order/createMemberOrder
     * @author rzc
     */
    public function createMemberOrder() {
        $conId     = trim($this->request->post('con_id'));
        $user_type = trim($this->request->post('user_type'));
        $pay_type  = trim($this->request->post('pay_type'));
        $parent_id = trim($this->request->post('parent_id'));
        $actype    = trim($this->request->post('actype'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        if (!is_numeric($user_type) || !is_numeric($pay_type)) {
            return ['code' => 3003];
        }
        $old_parent_id = $parent_id;
        $parent_id     = deUid($parent_id);
        if ($actype != 1) {
            $actype = 2;
        }
        $result        = $this->app->order->createMemberOrder($conId, intval($user_type), intval($pay_type), $parent_id, $old_parent_id, intval($actype));
        return $result;
    }

    /**
     * @api              {post} / 查询订单物流分包信息
     * @apiDescription   getOrderSubpackage
     * @apiGroup         index_order
     * @apiName          getOrderSubpackage
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {Number} order_no 订单号
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.orderNo长度必须为23位 / 3002.con_id长度为32位或者不能为空 /3004:订单不存在 / 3005:uid为空 / 3006:未发货的订单无法查询分包信息
     * @apiSuccess (返回) {Int} goods_count 购买商品总数
     * @apiSampleRequest /index/order/getOrderSubpackage
     * @author rzc
     */
    public function getOrderSubpackage() {
        $conId   = trim($this->request->post('con_id'));
        $orderNo = trim($this->request->post('order_no'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        if (strlen($orderNo) != 23) {
            return ['code' => '3001'];
        }
        $result = $this->app->order->getOrderSubpackage($orderNo, $conId);
        return $result;
    }

    /**
     * @api              {post} / 查询物流详情信息
     * @apiDescription   getExpressLog
     * @apiGroup         index_order
     * @apiName          getExpressLog
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} express_key 订单物流key
     * @apiParam (入参) {Number} express_no 订单物流单号
     * @apiParam (入参) {Number} order_no 订单号
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.orderNo长度必须为23位 / 3002.con_id长度为32位或者不能为空 /3004:订单不存在 / 3005:uid为空 / 3006:未发货的订单无法查询分包信息 / 3007:无效的分包信息
     * @apiSuccess (返回) {Array} address 订单收货地址
     * @apiSuccess (返回) {Array} express_goods 物流打包商品信息
     * @apiSuccess (返回) {Array} expresslog 物流流转信息
     * @apiSuccess (address) {String} province_name 省份名称
     * @apiSuccess (address) {String} city_name 市级名称
     * @apiSuccess (address) {String} area_name 区域名称
     * @apiSuccess (address) {String} address 街道地址信息
     * @apiSuccess (address) {String} message 用户留言信息
     * @apiSuccess (address) {String} is_sign 用户是否已签收：1,签收；2未签收
     * @apiSuccess (express_goods[]) {String} goods_name 商品名称
     * @apiSuccess (express_goods[]) {String} sku_json 规格信息
     * @apiSuccess (expresslog[]) {String} time 流转时间
     * @apiSuccess (expresslog[]) {String} context 流转信息
     * @apiSampleRequest /index/order/getExpressLog
     * @author rzc
     */
    public function getExpressLog() {
        $conId       = trim($this->request->post('con_id'));
        $express_key = trim($this->request->post('express_key'));
        $express_no  = trim($this->request->post('express_no'));
        $orderNo     = trim($this->request->post('order_no'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        $result = $this->app->order->getExpressLog($express_key, $express_no, $orderNo, $conId);
        return $result;
    }
}