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
     * @api              {post} / 创建结算页
     * @apiDescription   createSettlement
     * @apiGroup         index_order
     * @apiName          createSettlement
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} sku_id_list skuid列表
     * @apiParam (入参) {Number} city_id 选择的地址
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
        if (!is_numeric($cityId)) {
            return ['code' => '3003'];
        }
        $result = $this->app->order->createSettlement($conId, $skuIdList, $cityId);
        return $result;
    }
}