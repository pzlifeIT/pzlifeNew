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
     * @apiGroup         index_user
     * @apiName          getShopGoods
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} type 查询类型 1:已上架 2:已下架 3:未上架
     * @apiParam (入参) {String} search 查询字段
     * @apiSuccess (返回) {String} code 200:成功  3001:con_id长度只能是32位 / 3002:conId有误 / 3003:type:查询类型必须为数字 / 3004:非法的查询类型 / 3005:店铺不存在
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getShopGoods
     * @return array
     * @author rzc
     */
    public function getShopGoods(){
        $conId = trim($this->request->post('con_id'));
        $type = trim($this->request->post('type'));
        $search = trim($this->request->post('search'));
        $page = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));
        $type = $type ? $type : 1;
        $types = [1,2,3];
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($type)) {
            return ['code' => '3003'];
        }
        if (!in_array($type,$types)) {
            return ['code' => '3004'];
        }
        $result = $this->app->shopmanage->getShopGoods($conId,$type,$search,$page,$pagenum);
        return $result;
    }

    /**
     * @api              {post} / 上下架店铺商品
     * @apiDescription   autoShopGoods
     * @apiGroup         index_user
     * @apiName          autoShopGoods
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} type 查询类型 1:已上架 2:已下架 
     * @apiSuccess (返回) {String} code 200:成功  3001:con_id长度只能是32位 / 3002:conId有误 / 3003:type:查询类型必须为数字 / 3004:非法的查询类型 / 3005:店铺不存在
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/autoShopGoods
     * @return array
     * @author rzc
     */
    public function autoShopGoods(){
        $conId = trim($this->request->post('con_id'));
        $type = trim($this->request->post('type'));
        $type = $type ? $type : 1;
        $types = [1,2,3];
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!is_numeric($type)) {
            return ['code' => '3003'];
        }
        if (!in_array($type,$types)) {
            return ['code' => '3004'];
        }
        $result = $this->app->shopmanage->autoShopGoods($conId,$type);
        return $result;
    }
}