<?php
namespace app\index\controller;
//
use app\index\MyController;
//
class OfflineActivities extends MyController{
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
    public function getOfflineActivities(){
        $id = trim($this->request->post('active_id'));
        if (!is_numeric($id)) {
            return ['code' => '3001'];
        } 
        $result = $this->app->offlineactivities->getOfflineActivities(intval($id));
        return $result;
    }

    
}