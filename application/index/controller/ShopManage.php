<?php
namespace app\index\controller;
//
use app\index\MyController;
//
class ShopManage extends MyController{

    protected $beforeActionList = [
       'isLogin',//所有方法的前置操作
       'isBoss',
        // 'isLogin' => ['except' => 'login,quickLogin,register,resetPassword,sendVercode,loginUserByWx'],//除去getFirstCate其他方法都进行second前置操作
        // 'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
        ];



    /**
     * @api              {post} / 处理推荐关系
     * @apiDescription   getShopGoods
     * @apiGroup         index_user
     * @apiName          getShopGoods
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} [buid] 推荐人uid
     * @apiSuccess (返回) {String} code 200:成功  3001:con_id长度只能是32位 / 3002:conId有误
     * @apiSuccess (返回) {Array} data 用户信息
     * @apiSampleRequest /index/user/getShopGoods
     * @return array
     * @author zyr
     */
    public function getShopGoods(){
        $conId = trim($this->request->post('con_id'));
        
    }
}