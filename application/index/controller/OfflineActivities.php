<?php

namespace app\index\controller;

//
use app\index\MyController;

//
class OfflineActivities extends MyController {
    protected $beforeActionList = [
        // 'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'getOfflineActivities,createOrderQrCode,LuckGoods,getHdLucky'], //除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];
    /**
     * @api              {post} / 线下活动
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
     * @apiSuccess (data) {String} min_brokerage 最低钻石再补贴
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
        $conId      = trim($this->request->post('con_id'));
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
        $this->apiLog($apiName, [$conId, $buid, $skuId, $num, $payType], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {get} / 线下活动商品订单生成取货二维码
     * @apiDescription   createOrderQrCode
     * @apiGroup         index_OfflineActivities
     * @apiName          createOrderQrCode
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {String} data base64加密后的参数
     *  @apiSuccess (返回) {String}  code 错误码 / 3002 参数为空或者加密参数格式有误
     * @apiSuccess (返回) {String}  image 二维码图片
     * @apiSampleRequest /index/OfflineActivities/createOrderQrCode
     * @author rzc
     */
    public function createOrderQrCode() {
        $data = trim($this->request->get('data'));
        $data = base64_decode($data);

        if (strlen($data) < 2) {
            return ['code' => '3002'];
        }
        if (!$data) {
            return ['code' => '3002'];
        }

        $result = $this->app->offlineactivities->createOrderQrCode($data);
        return $result;

    }

    /**
     * @api              {get} /  抽奖奖品
     * @apiDescription   LuckGoods
     * @apiGroup         index_OfflineActivities
     * @apiName          LuckGoods
     * @apiParam (入参) {Number} con_id
     *  @apiSuccess (返回) {String}  code 错误码 / 3001 抽奖奖品为空
     * @apiSuccess (返回) {String}  LuckGoods 奖品
     * @apiSuccess (LuckGoods) {String}  shop_num 奖品编号
     * @apiSuccess (LuckGoods) {String}  goods_name 奖品名称
     * @apiSuccess (LuckGoods) {String}  image_path 图片地址
     * @apiSampleRequest /index/OfflineActivities/LuckGoods
     * @author rzc
     */

    public function LuckGoods() {
        $result = $this->app->offlineactivities->LuckGoods();
        return $result;
    }
    /**
     * @api              {post} / 抽奖操作
     * @apiDescription   luckyDraw
     * @apiGroup         index_OfflineActivities
     * @apiName          luckyDraw
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} hd_id 活动ID
     * @apiParam (入参) {Int} timekey 奖品时间戳
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.用户不存在 / 3002.con_id有误 / 3003:已参与抽奖 / 3004:奖品已全部抽完 / 3005:操作失败 / 3006:活动过期，请刷新页面 / 3007:timekey错误
     * @apiSuccess (返回) {Int} shop_num 中奖编号
     * @apiSampleRequest /index/OfflineActivities/luckydraw
     * @author zyr
     */
    public function luckyDraw() {
        $apiName = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        $hd_id   = trim($this->request->post('hd_id'));
        $timekey = trim($this->request->post('timekey'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3002'];
        }
        if (empty($timekey) || !is_numeric($timekey)) {
            return ['code' => '3007'];
        }
        $result = $this->app->offlineactivities->luckyDraw($conId, $hd_id, $timekey);
//        $this->apiLog($apiName, [$conId], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {get} / 获取抽奖记录
     * @apiDescription   getHdLucky
     * @apiGroup         index_OfflineActivities
     * @apiName          getHdLucky
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} big 1:大奖， 不传为全部
     *  @apiSuccess (返回) {String}  code 错误码 / 3001 奖品类型错误
     * @apiSuccess (返回) {String}  winnings 中奖记录
     * @apiSuccess (winnings) {String}  shop_num 奖品编号
     * @apiSuccess (winnings) {String}  goods_name 奖品名称
     * @apiSuccess (winnings) {String}  image_path 图片地址
     * @apiSuccess (winnings) {String}  user 用户
     * @apiSampleRequest /index/OfflineActivities/getHdLucky
     * @author rzc
     */
    public function getHdLucky() {
        $big = trim($this->request->post('big'));
        if (!empty($big)) {
            if ($big != 1) {
                return ['code' => '3001'];
            }
        }
        $result = $this->app->offlineactivities->getHdLucky($big);
        return $result;
    }

    /**
     * @api              {post} / 获取会员自己抽奖记录
     * @apiDescription   getUserHdLucky
     * @apiGroup         index_OfflineActivities
     * @apiName          getUserHdLucky
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} [page] 页码
     * @apiParam (入参) {Number} [pageNum] 页码
     * @apiParam (入参) {Number} [is_debris] 查询碎片奖品 1查询，空或者不传则不查
     *  @apiSuccess (返回) {String}  code 错误码 / 3001:con_id长度只能是28位 / 3002:缺少参数 / 3003:is_debris错误
     * @apiSuccess (返回) {String}  winnings 中奖记录
     * @apiSuccess (winnings) {String}  shop_num 奖品编号
     * @apiSuccess (winnings) {String}  goods_name 奖品名称
     * @apiSuccess (winnings) {String}  image_path 图片地址
     * @apiSuccess (winnings) {String}  user 用户
     * @apiSampleRequest /index/OfflineActivities/getUserHdLucky
     * @author rzc
     */
    public function getUserHdLucky() {
        $conId     = trim($this->request->post('con_id'));
        $page      = trim($this->request->post('page'));
        $pagenum   = trim($this->request->post('pageNum'));
        $is_debris = trim($this->request->post('is_debris'));
        $page      = is_numeric($page) ? $page : 1;
        $pagenum   = is_numeric($pagenum) ? $pagenum : 10;
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (!empty($is_debris) && $is_debris != 1) {
            return ['code' => '3003'];
        }
        $result = $this->app->offlineactivities->getUserHdLucky($conId, $page, $pagenum, $is_debris);
        return $result;
    }

    /**
     * @api              {post} / 通用碎片兑换其他碎片
     * @apiDescription   userDebrisChange
     * @apiGroup         index_OfflineActivities
     * @apiName          userDebrisChange
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} use_id 使用碎片的id
     * @apiParam (入参) {Number} use_number 使用碎片的数量
     * @apiParam (入参) {Number} chage_id 兑换碎片的id
     *  @apiSuccess (返回) {String}  code 错误码 / 3001:use_debris参数错误 / 3002:chage_debris参数错误 / 3003:您不具有该碎片 / 3004:您暂时无法兑换该碎片 / 3005:通用碎片数量不足，
     * @apiSuccess (返回) {String}  winnings 中奖记录
     * @apiSuccess (winnings) {String}  shop_num 奖品编号
     * @apiSuccess (winnings) {String}  goods_name 奖品名称
     * @apiSuccess (winnings) {String}  image_path 图片地址
     * @apiSuccess (winnings) {String}  user 用户
     * @apiSampleRequest /index/OfflineActivities/userDebrisChange
     * @author rzc
     */
    public function userDebrisChange() {
        $apiName      = classBasename($this) . '/' . __function__;
        $conId        = trim($this->request->post('con_id'));
        $use_id   = trim($this->request->post('use_id'));
        $use_number   = trim($this->request->post('use_number'));
        $chage_id = trim($this->request->post('chage_id'));
        $use_number   = is_numeric($use_number) ? $use_number : 1;
        if (empty($use_id) || !is_numeric($use_id)) {
            return ['code' => '3001'];
        }
        if (empty($chage_id) || !is_numeric($chage_id)) {
            return ['code' => '3002'];
        }
        $result = $this->app->offlineactivities->userDebrisChange($conId, $use_id, $chage_id, $use_number);
        $this->apiLog($apiName, [$conId, $use_id, $chage_id, $use_number], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 奖品碎片合成
     * @apiDescription   userDebrisCompound
     * @apiGroup         index_OfflineActivities
     * @apiName          userDebrisCompound
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {Number} use_debris 合成ID
     *  @apiSuccess (返回) {String}  code 错误码 / 3001:con_id长度只能是28位 / 3002:缺少参数 / 3003:is_debris错误
     * @apiSuccess (返回) {String}  winnings 中奖记录
     * @apiSuccess (winnings) {String}  shop_num 奖品编号
     * @apiSuccess (winnings) {String}  goods_name 奖品名称
     * @apiSuccess (winnings) {String}  image_path 图片地址
     * @apiSuccess (winnings) {String}  user 用户
     * @apiSampleRequest /index/OfflineActivities/userDebrisCompound
     * @author rzc
     */
    public function userDebrisCompound() {

    }
}