<?php

namespace app\index\controller;

use app\index\MyController;

class Rights extends MyController {
    protected $beforeActionList = [
        // 'isLogin',//所有方法的前置操作
       'isLogin' => ['except' => 'IsGetDominos'],//除去getFirstCate其他方法都进行second前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 领取钻石会员（非二维码绑定）
     * @apiDescription   receiveDiamondvip
     * @apiGroup         index_rights
     * @apiName          receiveDiamondvip
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {String} parent_id 分享者id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:parent_id长度只能是28位 / 3002:缺少参数 / 3003:order_status、page和pagenum必须是数字 / 3004:订单状态码有误
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/rights/receiveDiamondvip
     * @return array
     * @author rzc
     */
    public function receiveDiamondvip() {
        $con_id = $this->request->post('con_id');
        $parent_id = $this->request->post('parent_id');
        $parent_id = deUid($parent_id);
        if (empty($con_id)) {
            return ['code' => '3002'];
        }
        if (strlen($con_id) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->receiveDiamondvip($con_id,$parent_id);
        return $result;
    }

   /**
     * @api              {post} / 判断会员是否有分享钻石接龙的的资格
     * @apiDescription   IsGetDominos
     * @apiGroup         index_rights
     * @apiName          IsGetDominos
     * @apiParam (入参) {String} parent_id 分享者id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:parent_id长度只能是28位 / 3002:缺少参数 / 3003:order_status、page和pagenum必须是数字 / 3004:订单状态码有误
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/rights/IsGetDominos
     * @return array
     * @author rzc
     */
    public function IsGetDominos(){
        $parent_id = $this->request->post('parent_id');
        if (strlen($parent_id) != 32) {
            return ['code' => '3001'];
        }
        $parent_id = deUid($parent_id);
        if (empty($parent_id)) {
            return ['code' => '3002'];
        }
        $result = $this->app->rights->IsGetDominos($parent_id);
        return $result;
        
    }

    /**
     * @api              {post} / 判断登录会员钻石接龙的的名额是否用完
     * @apiDescription   IsBossDominos
     * @apiGroup         index_rights
     * @apiName          IsBossDominos
     * @apiParam (入参) {String} con_id 分享者id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是28位 / 3002:缺少参数 / 3003:order_status、page和pagenum必须是数字 / 3004:订单状态码有误
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/rights/IsBossDominos
     * @return array
     * @author rzc
     */
    public function IsBossDominos(){
        $con_id = $this->request->post('con_id');
        if (empty($con_id)) {
            return ['code' => '3002'];
        }
        if (strlen($con_id) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->IsBossDominos($con_id);
        return $result;
    }

}