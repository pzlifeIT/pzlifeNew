<?php
namespace app\index\controller;
//
use app\index\MyController;
//
class Shopmanage extends MyController{

    protected $beforeActionList = [
       'isLogin',//所有方法的前置操作
       'isBoss',
        // 'isLogin' => ['except' => 'login,quickLogin,register,resetPassword,sendVercode,loginUserByWx'],//除去getFirstCate其他方法都进行second前置操作
        // 'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
        ];



    /**
     * @api              {post} / 获取店铺商品
     * @apiDescription   getShopGoods
     * @apiGroup         index_shopmanage
     * @apiName          getShopGoods
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} type 查询类型 1:已上架 2:已下架 3:未上架
     * @apiParam (入参) {String} search 查询字段
     * @apiParam (入参) {String} page 查询页数
     * @apiParam (入参) {String} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功  3001:con_id长度只能是32位 / 3002:conId有误 / 3003:type:查询类型必须为数字 / 3004:非法的查询类型 / 3005:店铺不存在
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/shopmanage/getShopGoods
     * @return array
     * @author rzc
     */
    public function getShopGoods() {
        $apiName = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        $type    = trim($this->request->post('type'));
        $search  = trim($this->request->post('search'));
        $page    = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));
        $type    = $type ? $type : 1;
        $pagenum = $pagenum ? $pagenum : 10;
        $page    = $page ? $page : 1;
        $types   = [1, 2, 3];
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($type)) {
            return ['code' => '3003'];
        }
        if (!in_array($type, $types)) {
            return ['code' => '3004'];
        }
        $result = $this->app->shopmanage->getShopGoods($conId, $type, $search, $page, $pagenum);
        $this->apiLog($apiName, [$conId, $type, $search, $page, $pagenum], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 上下架店铺商品
     * @apiDescription   autoShopGoods
     * @apiGroup         index_shopmanage
     * @apiName          autoShopGoods
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} type 1:上架 2:下架
     * @apiParam (入参) {String} goods_id 操作商品id
     * @apiSuccess (返回) {String} code 200:成功  / 3000:用户ID不存在 / 3001:con_id长度只能是32位 / 3002:conId有误 / 3003:type和goods_id必须为数字 / 3004:非法的查询类型 / 3005:goods_id不能为空 / 3006:店铺不存在 / 3007:该商品不存在或者已下架 / 3008:该商品已上架，无需重复上架,或者已下架，无法重复下架 / 3009:该商品不存在，无法下架
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/shopmanage/autoShopGoods
     * @return array
     * @author rzc
     */
    public function autoShopGoods() {
        $apiName  = classBasename($this) . '/' . __function__;
        $conId    = trim($this->request->post('con_id'));
        $type     = trim($this->request->post('type'));
        $goods_id = trim($this->request->post('goods_id'));
        $type     = $type ? $type : 1;
        $types    = [1, 2];
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($type)) {
            return ['code' => '3003'];
        }
        if (!in_array($type, $types)) {
            return ['code' => '3004'];
        }
        if (empty($goods_id)) {
            return ['code' => '3005'];
        }
        if (!is_numeric($goods_id)) {
            return ['code' => '3003'];
        }
        $result = $this->app->shopmanage->autoShopGoods($conId, $type, $goods_id);
        $this->apiLog($apiName, [$conId, $type, $goods_id], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 判断商品是否上下架
     * @apiDescription   getGoodsAway
     * @apiGroup         index_shopmanage
     * @apiName          getSearchGoods
     * @apiParam (入参) {Number} goods_id 对应商品id
     * @apiParam (入参) {String} con_id 用户登录id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户不存在 / 3001.3001必须是数字 / 3003:conId长度小于32位 / 3004:店铺不存在
     * @apiSampleRequest /index/shopmanage/getGoodsAway
     * @author rzc
     */
    public function getGoodsAway() {
        $apiName  = classBasename($this) . '/' . __function__;
        $conId    = trim($this->request->post('con_id'));
        $goods_id = trim($this->request->post('goods_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        $result = $this->app->shopmanage->getGoodsAway($goods_id, $conId);
        $this->apiLog($apiName, [$conId, $goods_id], $result['code'], $conId);
        return $result;
    }
}