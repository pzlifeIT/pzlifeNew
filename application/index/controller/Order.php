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
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 每页展示条数
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:openid长度只能是28位 / 3002:缺少参数 / 3003:order_status、page和pagenum必须是数字 / 3004:订单状态码有误
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/order/getUserOrderList
     * @return array
     * @author rzc
     */
    public function getUserOrderList(){
        $con_id = trim($this->request->post('con_id'));
        $order_status = trim($this->request->post('order_status'));
        $page = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));
        $page = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;
        if (empty($con_id)) {
            return ['code' => '3002'];
        }
        if (strlen($con_id) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3003'];
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
        
        $result = $this->app->order->getUserOrderList($con_id,$order_status,$page,$pagenum);
        return $result;
    }

    /** 
     * @api              {post} / 创建结算页
     * @apiDescription   createSettlement
     * @apiGroup         index_order
     * @apiName          createSettlement
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} sku_id_list skuid列表
     * @apiParam (入参) {Number} [city_id] 选择的地址(不选地址暂不计算邮费)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 /3003:city_id必须为数字 / 3004:商品售罄 / 3005:商品未加入购物车 / 3006:商品不支持配送 3007:商品库存不够
     * @apiSuccess (返回) {Int} goods_count 购买商品总数
     * @apiSuccess (返回) {Float} rebate_all 所有商品钻石返利总和
     * @apiSuccess (返回) {Float} total_goods_price 商品ID
     * @apiSuccess (返回) {Float} total_freight_price 所有商品总价
     * @apiSuccess (返回) {Float} total_price 价格总计
     * @apiSuccess (返回) {Array} supplier_list 供应商分组
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
        $skuIdList = trim($this->request->post('sku_id_list'));
        $conId     = trim($this->request->post('con_id'));
        $cityId    = trim($this->request->post('city_id'));
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
        $cityId = empty($cityId) ? 0 : $cityId;
        if (!is_numeric($cityId)) {
            return ['code' => '3003'];
        }
        $result = $this->app->order->createSettlement($conId, $skuIdList, intval($cityId));
        return $result;
    }

    /**
     * @api              {post} / 创建订单
     * @apiDescription   createOrder
     * @apiGroup         index_order
     * @apiName          createOrder
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} sku_id_list skuid列表
     * @apiParam (入参) {Number} city_id 选择的地址
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 /3003:city_id必须为数字 / 3004:商品售罄 / 3005:商品未加入购物车 / 3006:商品不支持配送 3007:商品库存不够
     * @apiSuccess (返回) {Int} goods_count 购买商品总数
     * @apiSampleRequest /index/order/createorder
     * @author zyr
     */
    public function createOrder() {
        $skuIdList = trim($this->request->post('sku_id_list'));
        $conId     = trim($this->request->post('con_id'));
        $cityId    = trim($this->request->post('city_id'));
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
        if (!is_numeric($cityId)) {
            return ['code' => '3003'];
        }
        $result = $this->app->order->createOrder($conId, $skuIdList, intval($cityId));
        return $result;
    }

    /**
     * @api              {post} / 创建购买权益订单
     * @apiDescription   createOrder
     * @apiGroup         index_order
     * @apiName          createOrder
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} user_type 用户订单类型 1.钻石会员 2.boss
     * @apiParam (入参) {Number} city_id 选择的地址
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 /3003:user_type必须是数字 
     * @apiSuccess (返回) {Int} goods_count 购买商品总数
     * @apiSampleRequest /index/order/createorder
     * @author rzc
     */
    public function createMemberOrder(){
        $conId = trim($this->request->post('con_id'));
        $user_type = trim($this->request->post('user_type'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        if (!is_numeric($user_type)) {
            return ['code' => 3003];
        }
        $result = $this->app->order->createMemberOrder($conId,intval($user_type));
        return $result;
    }
}