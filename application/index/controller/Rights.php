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
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度不够32位 / 3002:con_id为空 / 3003:UID为空 / 3004:当前身份等级大于或等于钻石会员，无法领取 / 3005:分享用户没有分享机会 / 3006:该机会已领完
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
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:parent_id长度只能是32位 / 3002:传入用户为空  / 3004:非BOSS无法开启分享钻石接龙资格（200名额）/ 3005:分享用户没有分享机会
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
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是32位 / 3002:缺少参数 / 3003:用户为空 / 3004:非BOSS无法开启分享钻石接龙资格（200名额） / 3005:分享用户没有分享机会
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

    /**
     * @api              {post} / 获取用户红包提示
     * @apiDescription   getDominosBalanceHint
     * @apiGroup         index_rights
     * @apiName          getDominosBalanceHint
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有到账红包 / 3001:con_id长度只能是32位 / 3002:缺少参数 / 3003:用户为空 / 3004:非BOSS无法开启分享钻石接龙资格（200名额） / 3005:分享用户没有分享机会
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/rights/getDominosBalanceHint
     * @return array
     * @author rzc
     */
    public function getDominosBalanceHint(){
        $con_id = $this->request->post('con_id');
        if (empty($con_id)) {
            return ['code' => '3002'];
        }
        if (strlen($con_id) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->getDominosBalanceHint($con_id);
        return $result;
    }

    /**
     * @api              {post} / 获取用户钻石会员领取机会记录
     * @apiDescription   getDominosChance
     * @apiGroup         index_rights
     * @apiName          getDominosChance
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有到账红包 / 3001:con_id长度只能是32位 / 3002:缺少参数 / 3003:用户为空 / 3004:非BOSS无法开启分享钻石接龙资格（200名额） / 3005:分享用户没有分享机会
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/rights/getDominosChance
     * @return array
     * @author rzc
     */
    public function getDominosChance(){
        $con_id = $this->request->post('con_id');
        if (empty($con_id)) {
            return ['code' => '3002'];
        }
        if (strlen($con_id) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->getDominosChance($con_id);
        return $result;
    }

    /**
     * @api              {post} / 获取用户钻石会员领取机会记录
     * @apiDescription   getDominosReceive
     * @apiGroup         index_rights
     * @apiName          getDominosReceive
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有到账红包 / 3001:con_id长度只能是32位 / 3002:缺少参数 / 3003:用户为空 / 3004:非BOSS无法开启分享钻石接龙资格（200名额） / 3005:分享用户没有分享机会
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/rights/getDominosReceive
     * @return array
     * @author rzc
     */
    public function getDominosReceive(){
        $con_id = $this->request->post('con_id');
        if (empty($con_id)) {
            return ['code' => '3002'];
        }
        if (strlen($con_id) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->getDominosChance($con_id);
        return $result;
    }
}