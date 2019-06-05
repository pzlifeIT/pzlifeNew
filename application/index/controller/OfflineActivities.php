<?php

namespace app\index\controller;

//
use app\index\MyController;

//
class OfflineActivities extends MyController {
    /**
     * @api              {post} / 分类商品列表
     * @apiDescription   getOfflineActivities
     * @apiGroup         index_OfflineActivities
     * @apiName          getOfflineActivities
     * @apiParam (入参) {Number} active_id 活动id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} cate_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_market_price 最低市场价
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石返利
     * @apiSampleRequest /index/OfflineActivities/getOfflineActivities
     * @author rzc
     */
    public function getOfflineActivities() {
        $apiName = classBasename($this) . '/' . __function__;
        $id      = trim($this->request->post('active_id'));
        if (!is_numeric($id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->offlineactivities->getOfflineActivities(intval($id));
        $this->apiLog($apiName, [$id], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 创建线下活动商品订单
     * @apiDescription   createOfflineActivitiesOrder
     * @apiGroup         index_OfflineActivities
     * @apiName          createOfflineActivitiesOrder
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {String} buid 推荐人
     * @apiParam (入参) {Number} sku_id 商品SKU_ID
     * @apiParam (入参) {Number} buy_num 购买数量
     * @apiParam (入参) {Number} pay_type 支付方式 1.所有第三方支付 2.商券支付
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在
     * @apiSuccess (返回) {String} order_no 订单号
     * @apiSuccess (返回) {Int} is_pay 1.已完成支付(商券) 2.需要发起第三方支付
     * @apiSampleRequest /index/OfflineActivities/createOfflineActivitiesOrder
     * @author rzc
     */
    public function createOfflineActivitiesOrder() {
        $apiName    = classBasename($this) . '/' . __function__;
        $buid       = trim($this->request->post('buid'));
        $skuId      = trim($this->request->post('sku_id'));
        $num        = trim($this->request->post('buy_num'));
        $payType    = trim($this->request->post('pay_type'));
        $payTypeArr = [1, 2];
        if (!is_numeric($skuId)) {
            return ['code' => '3001'];
        }
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
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
        $result = $this->app->offlineactivities->createOfflineActivitiesOrder($conId, $buid, $skuId, $num, $payType);
        $this->apiLog($apiName, [$buid, $skuId, $num, $payType], $result['code'], '');
        return $result;
    }
}