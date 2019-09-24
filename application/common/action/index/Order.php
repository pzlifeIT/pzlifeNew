<?php

namespace app\common\action\index;

use app\facade\DbCoupon;
use app\facade\DbGoods;
use app\facade\DbOrder;
use app\facade\DbProvinces;
use app\facade\DbShops;
use app\facade\DbUser;
use app\facade\DbAudios;
use Config;
use function Qiniu\json_decode;
use think\Db;

class Order extends CommonIndex {
    private $redisCartUserKey;
    private $redisDeliverOrderKey;
    private $prefix = 'skuid:';

    public function __construct() {
        parent::__construct();
        $this->redisCartUserKey     = Config::get('rediskey.cart.redisCartUserKey');
        $this->redisDeliverOrderKey = Config::get('rediskey.order.redisDeliverOrderExpress');
    }

    public function cancelOrder($orderNo, $conId = '', $uid = 0) {
        if (empty($uid)) {
            $uid = $this->getUidByConId($conId);
        }
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $order = DbOrder::getOrder('id,deduction_money', ['order_no' => $orderNo, 'uid' => $uid, 'order_status' => 1], true);
        if (empty($order)) {
            return ['code' => '3003']; //没有可取消的订单
        }
        $orderChild    = DbOrder::getOrderChild('id', [['order_id', '=', $order['id']]]);
        $orderChildIds = array_column($orderChild, 'id');
        $orderGoods    = DbOrder::getOrderGoods('id,sku_id,goods_num', [['order_child_id', 'in', $orderChildIds]]);
        $data          = [
            'order_status' => 2,
        ];
        $tradingData   = [];
        if ($order['deduction_money'] != 0) {
            $userInfo    = DbUser::getUserInfo(['id' => $uid], 'balance', true);
            $tradingData = [
                'uid'          => $uid,
                'trading_type' => 1,
                'change_type'  => 2,
                'money'        => $order['deduction_money'],
                'befor_money'  => $userInfo['balance'],
                'after_money'  => bcadd($userInfo['balance'], $order['deduction_money'], 2),
                'message'      => '',
                'create_time'  => time(),
            ];
        }
        Db::startTrans();
        try {
            foreach ($orderGoods as $og) {
                DbGoods::modifyStock($og['sku_id'], $og['goods_num'], 'inc'); //退回库存
            }
            DbOrder::updataOrder($data, $order['id']); //改订单状态
            if ($order['deduction_money'] != 0) {
                DbUser::modifyBalance($uid, $order['deduction_money'], 'inc'); //退还用户商票
            }
//            DbOrder::updateLogBonus(['status' => 3], ['order_no' => $orderNo]);//待结算分利取消结算
            //            DbOrder::updateLogIntegral(['status' => 3], ['order_no' => $orderNo]);//待结算积分取消结算(待付款取消的订单还未结算分利和积分不需要取消)
            if (!empty($tradingData)) {
                DbOrder::addLogTrading($tradingData);
            }
            $this->resetUserInfo($uid);
            Db::commit();
            return ['code' => '200']; //取消成功
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005']; //取消失败
        }
    }

    public function quickSettlement($conId, $buid, $skuId, $num, $userAddressId, $userCouponId = 0) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $cityId           = 0;
        $defaultAddressId = 0;
        if (!empty($userAddressId)) {
            $userAddress      = DbUser::getUserAddress('id,city_id', ['id' => $userAddressId], true);
            $cityId           = $userAddress['city_id'] ?? 0;
            $defaultAddressId = $userAddress['id'] ?? 0;
        }
        if (empty($defaultAddressId)) { //没有地址返回默认地址id
            $defaultAddress = DbUser::getUserAddress('id,city_id', ['uid' => $uid, 'default' => 1], true);
            if (empty($defaultAddress)) {
                $defaultAddress = DbUser::getUserAddress('id,city_id', ['uid' => $uid, 'default' => 2], true, 'id desc');
            }
            $defaultAddressId = $defaultAddress['id'] ?? 0;
            $cityId           = $defaultAddress['city_id'] ?? 0;
        }
        $balance = DbUser::getUserInfo(['id' => $uid, 'balance_freeze' => 2], 'user_identity,balance', true);
        $user_identity = $balance['user_identity'];
        $balance = $balance['balance'] ?? 0;
        $summary = $this->quickSummary($uid, $buid, $skuId, $num, $cityId, $userCouponId);
        if ($summary['code'] != '200') {
            return $summary;
        }
        $target_users = $summary['goods_list'][0]['target_users']; //适用人群
        if ($user_identity < $target_users) {
            if ($target_users == 2){
                return ['code' => 3010, 'msg' => '该商品钻石会员及以上身份专享'];
            }elseif ($target_users == 3){
                return ['code' => 3011, 'msg' => '该商品创业店主及以上身份专享'];
            }elseif ($target_users == 4){
                return ['code' => 3012, 'msg' => '该商品合伙人及以上身份专享'];
            }
        }
        $shopList = DbShops::getShops([['uid', '=', $buid]], 'id,uid,shop_name,shop_image'); //购买的所有店铺信息列表
        if (empty($shopList)) {
            $shopList = DbShops::getShops([['id', '=', '1']], 'id,uid,shop_name,shop_image'); //不是boss就查总店
        }
        $shopList     = array_combine(array_column($shopList, 'id'), $shopList);
        $supplierId   = $summary['goods_list'][0]['supplier_id']; //供应商id
        $supplierList = DbGoods::getSupplier('id,name,image,title,desc', [['id', '=', $supplierId], ['status', '=', 1]]);
        $supplier = [];
        foreach ($supplierList as $sl) {
            $glList = [];
            $sList  = []; //门店
            foreach ($summary['goods_list'] as $gl) {
                if ($gl['supplier_id'] == $sl['id']) {
                    unset($gl['freight_id']);
                    unset($gl['cost_price']);
                    unset($gl['margin_price']);
                    unset($gl['weight']);
                    unset($gl['volume']);
                    unset($gl['spec']);
                    unset($gl['status']);
                    unset($gl['stock']);
                    unset($gl['supplier_id']);
                    unset($gl['buySum']);
                    unset($gl['shopBuySum']);
                    $glList[$gl['id']] = $gl;
//                    $shopKey           = array_keys($cart[$gl['id']]['track']);
                    $shopKey = array_keys($shopList);
                    foreach ($shopKey as $s) {
                        if (!isset($sList[$s])) {
                            $sList[$s] = $shopList[$s];
                        }
                    }
                }
            }
            foreach ($sList as $sk => $s) {
                $ggList = [];
                foreach ($glList as $kg => $g) {
                    if (in_array($s['id'], array_keys($shopList))) {
                        $bSum        = $num; //店铺购买的数量
                        $g['buySum'] = $bSum;
                        $ggList[$kg] = $g;
                    }
                }
                $sList[$sk]['goods_list'] = $ggList;
            }
            $sl['shop_list'] = $sList;
            array_push($supplier, $sl);
        }
        unset($summary['goods_list']);
        $summary['supplier_list']      = $supplier;
        $summary['balance']            = $balance;
        $summary['default_address_id'] = $defaultAddressId;
        return $summary;
    }

    public function quickCreateOrder($conId, $buid, $skuId, $num, $userAddressId, $payType, $userCouponId = 0) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $userAddress = DbUser::getUserAddress('uid,mobile,name,province_id,city_id,area_id,address', ['id' => $userAddressId], true);
        if (empty($userAddress)) {
            return ['code' => '3003'];
        }
        $cityId  = $userAddress['city_id'];
        $summary = $this->quickSummary($uid, $buid, $skuId, $num, $cityId, $userCouponId);
//        print_r($summary);die;
        if ($summary['code'] != '200') {
            return $summary;
        }
        $shopInfo = DbShops::getShopInfo('id', ['uid' => $buid]);
        if (empty($shopInfo)) {
            $buid = 1;
        }
        $goods = $summary['goods_list'][0];
        $target_users = $goods['target_users']; //适用人群
        $user = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if ($user['user_identity'] < $target_users) {
            if ($target_users == 2){
                return ['code' => 3010, 'msg' => '该商品钻石会员及以上身份专享'];
            }elseif ($target_users == 3){
                return ['code' => 3011, 'msg' => '该商品创业店主及以上身份专享'];
            }elseif ($target_users == 4){
                return ['code' => 3012, 'msg' => '该商品合伙人及以上身份专享'];
            }
        }

        $from_uid = $buid;
        $giving_rights = $summary['giving_rights'];
//        print_r($goods);die;
        $orderGoodsData = [];
        foreach ($goods['shopBuySum'] as $kgl => $gl) {
            for ($i = 0; $i < $gl; $i++) {
                $goodsData = [
                    'goods_id'     => $goods['goods_id'],
                    'goods_name'   => $goods['goods_name'],
                    'sku_id'       => $goods['id'],
                    'sup_id'       => $goods['supplier_id'],
                    'boss_uid'     => $buid,
                    'goods_price'  => $goods['retail_price'],
                    'margin_price' => getDistrProfits($goods['retail_price'], $goods['cost_price'], $goods['margin_price']),
                    'integral'     => $goods['integral'],
                    'goods_num'    => 1,
                    'sku_json'     => json_encode($goods['attr']),
                ];
                array_push($orderGoodsData, $goodsData);
            }
        }
//        print_r($orderGoodsData);die;
        /*
         * 子订单内容
         */
        $freightSupplierPrice = $summary['freight_supplier_price'];
        $supplier             = DbGoods::getSupplier('id,name', [['id', 'in', array_keys($freightSupplierPrice)], ['status', '=', '1']]);
        $supplierData         = [];
        foreach ($supplier as $sval) {
            $sval['express_money'] = $freightSupplierPrice[$sval['id']];
            $sval['supplier_id']   = $sval['id'];
            $sval['supplier_name'] = $sval['name'];
            unset($sval['id']);
            unset($sval['name']);
            array_push($supplierData, $sval);
        }
//        print_r($supplierData);die;
        /*
         * 子订单内容
         */
        $orderNo        = createOrderNo(); //创建订单号
        $deductionMoney = 0; //商票抵扣金额
        $thirdMoney     = 0; //第三方支付金额
        $isPay          = false;
        $tradingData    = []; //交易日志
        if ($payType == 2) { //商票支付
            $userInfo = DbUser::getUserInfo(['id' => $uid], 'balance,balance_freeze', true);
            if ($userInfo['balance_freeze'] == '2') { //商票未冻结
                if ($summary['total_price'] > $userInfo['balance']) {
                    $deductionMoney = $userInfo['balance'] > 0 ? $userInfo['balance'] : 0; //可支付的商票
                    $thirdMoney     = bcsub($summary['total_price'], $deductionMoney, 2);
                } else {
                    $isPay          = true; //可以直接商票支付完成
                    $deductionMoney = $summary['total_price'];
                }
            } else {
                $thirdMoney = $summary['total_price'];
            }
            $tradingData = [
                'uid'          => $uid,
                'trading_type' => 1,
                'change_type'  => 1,
                'money'        => -$deductionMoney,
                'befor_money'  => $userInfo['balance'],
                'after_money'  => bcsub($userInfo['balance'], $deductionMoney, 2),
                'message'      => '',
                'create_time'  => time(),
            ];
        } else if ($payType == 1) { //第三方支付
            $thirdMoney = $summary['total_price'];
        }
        $orderData = [
            'order_no'        => $orderNo,
            'third_order_id'  => 0,
            'uid'             => $uid,
            'order_status'    => $isPay ? 4 : 1,
            'order_money'     => bcadd($summary['total_price'], $summary['discount_money'], 2), //订单金额(优惠金额+实际支付的金额)
            'deduction_money' => $deductionMoney, //商票抵扣金额
            'pay_money'       => $summary['total_price'], //实际支付(第三方支付金额+商票抵扣金额)
            'goods_money'     => $summary['total_goods_price'], //商品金额
            'third_money'     => $thirdMoney, //第三方支付金额
            'discount_money'  => $summary['discount_money'], //优惠金额
            'pay_type'        => $payType,
            'third_pay_type'  => 2, //第三方支付类型1.支付宝 2.微信 3.银联 (暂时只能微信)
            'linkman'         => $userAddress['name'],
            'linkphone'       => $userAddress['mobile'],
            'province_id'     => $userAddress['province_id'],
            'city_id'         => $userAddress['city_id'],
            'area_id'         => $userAddress['area_id'],
            'address'         => $userAddress['address'],
            'message'         => '',
            'giving_rights'   => $giving_rights,
            'from_uid'        => $from_uid,
            'pay_time'        => $isPay ? time() : 0,
        ];
//        print_r($orderData);die;
        $stockSku = [$skuId => $goods['buySum']];
        Db::startTrans();
        try {
            $orderId = DbOrder::addOrder($orderData);
            if (empty($orderId)) {
                Db::rollback();
                return ['code' => '3009'];
            }
            foreach ($supplierData as $sdkey => $sdval) {
                $supplierData[$sdkey]['order_id'] = $orderId;
            }
            $childOrder    = DbOrder::addOrderChilds($supplierData);
            $childSupplier = $childOrder->toArray();
            $childSupplier = array_column($childSupplier, 'id', 'supplier_id');
            foreach ($orderGoodsData as $ogdK => $ogdV) {
                $orderGoodsData[$ogdK]['order_child_id'] = $childSupplier[$ogdV['sup_id']];
            }
            DbOrder::addOrderGoods($orderGoodsData);
            DbGoods::decStock($stockSku);
            DbUser::modifyBalance($uid, $deductionMoney, $modify = 'dec');
            if (!empty($tradingData)) {
                DbOrder::addLogTrading($tradingData);
            }
            if (!empty($userCouponId)) {
                DbCoupon::updateUserCoupon([
                    'is_use'   => 1,
                    'order_id' => $orderId,
                ], $userCouponId);
            }
            if ($isPay) {
                $redisListKey = Config::get('rediskey.order.redisOrderBonus');
                $this->redis->rPush($redisListKey, $orderId);
            }
            $this->resetUserInfo($uid);
            Db::commit();
            return ['code' => '200', 'order_no' => $orderNo, 'is_pay' => $isPay ? 1 : 2];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    private function quickSummary($uid, $buid, $skuId, $num, $cityId, $userCouponId) {
        $goodsSku = DbGoods::getSkuGoods([['goods_sku.id', '=', $skuId], ['stock', '>', '0'], ['goods_sku.status', '=', '1']], 'id,goods_id,stock,freight_id,market_price,retail_price,cost_price,margin_price,weight,volume,sku_image,spec', 'id,supplier_id,goods_name,target_users,goods_type,subtitle,status,giving_rights');
        if (empty($goodsSku)) {
            return ['code' => '3004']; //商品下架
        }
        $goodsSku = $goodsSku[0];
        if ($goodsSku['stock'] < $num) {
            return ['code' => '3007']; //库存不足商品
        }
        $shopInfo = DbShops::getShopInfo('id', ['uid' => $buid]);
        if (empty($shopInfo)) {
            $shopId = 1;
        } else {
            $shopId = $shopInfo['id'];
        }
        $goodsSku['supplier_id'] = $goodsSku['goods']['supplier_id'];
        $goodsSku['goods_name']  = $goodsSku['goods']['goods_name'];
        $goodsSku['goods_type']  = $goodsSku['goods']['goods_type'];
        $goodsSku['target_users']  = $goodsSku['goods']['target_users'];
        $goodsSku['subtitle']    = $goodsSku['goods']['subtitle'];
        $goodsSku['status']      = $goodsSku['goods']['status'];
        $giving_rights           = $goodsSku['goods']['giving_rights'];
        $attr                    = DbGoods::getAttrList([['id', 'in', explode(',', $goodsSku['spec'])]], 'attr_name');
        $goodsSku['attr']        = array_column($attr, 'attr_name');
        unset($goodsSku['goods']);
        $goodsSku['buySum']     = $num;
        $goodsSku['shopBuySum'] = [$shopId => $num];
        $totalGoodsPrice        = bcmul($goodsSku['retail_price'], $num, 2); //商品总价
        $couponPrice = 0;
        if (!empty($userCouponId)) {//有优惠券
            $userCoupon = DbCoupon::getUserCoupon([
                ['id', '=', $userCouponId],
                ['uid', '=', $uid],
                ['is_use', '=', 2],//未使用的
                ['end_time', '>', time()],//未过期的
            ], 'id,price,gs_id,level', true);
            if (empty($userCoupon)) {
                return ['code' => '3013'];
            }
            if ($userCoupon['level'] == 1) {//单品券
                if ($userCoupon['gs_id'] != $goodsSku['goods_id']) {
                    return ['code' => '3013'];
                }
            }
            if ($userCoupon['level'] == 2) {//专题券
                $couponGoodsIdList = DbGoods::getSubjectRelation([['subject_id', '=', $userCoupon['gs_id']]], 'goods_id');
                $couponGoodsIdList = array_column($couponGoodsIdList, 'goods_id');
                if (!in_array($goodsSku['goods_id'], $couponGoodsIdList)) {
                    return ['code' => '3013'];
                }
            }
            $couponPrice = $userCoupon['price'];
        }
        $distrProfits         = getDistrProfits($goodsSku['retail_price'], $goodsSku['cost_price'], $goodsSku['margin_price']);//可分配利润
        $goodsSku['rebate']   = $this->getRebate($distrProfits, $num);
        $goodsSku['integral'] = $this->getIntegral($goodsSku['retail_price'], $goodsSku['cost_price'], $goodsSku['margin_price']);
        $freightPrice         = bcmul($goodsSku['retail_price'], $num, 2); //同一个供应商模版id的商品价格累加
        $freightCount         = bcmul(1, $num, 2); //同一个供应商模版id的商品数量累加
        $freightWeight        = bcmul($goodsSku['weight'], $num, 2); //同一个供应商模版id的商品重量累加
        $freightVolume        = bcmul($goodsSku['volume'], $num, 2); //同一个供应商模版id的商品体积累加
        $totalFreightPrice    = 0;
        $freightSupplierPrice = []; //各个供应商的运费
        if (!empty($cityId)) {
            /* 运费模版 运费计算 start */
            $freightId   = $goodsSku['freight_id'];
            $freightList = DbGoods::getFreightAndDetail([['freight_id', 'in', [$freightId]]], $cityId, 'id,freight_id,price,after_price,total_price,unit_price', 'id,supid,stype', 'freight_detail_id,city_id', [$freightId]);
            if (empty($freightList)) {
                return ['code' => '3006']; //商品不支持配送
            }
            $freightList = array_values($freightList)[0];
            if ($freightList['total_price'] > $freightPrice) { //该供应商的当前运费模版下购买的总价超过包邮价可以包邮
                if ($freightList['stype'] == 1) { //件数
                    if ($freightList['unit_price'] > $freightCount) { //购买件数超过当前模版的满件包邮条件可以包邮
                        $totalFreightPrice = bcadd(bcmul(bcsub($freightCount, 1, 2), $freightList['after_price'], 2), $freightList['price'], 2);
                    }
                } else if ($freightList['stype'] == 2) { //重量
                    if ($freightList['unit_price'] > $freightWeight) { //购买重量超过当前模版的满件包邮条件可以包邮
                        $totalFreightPrice = bcadd(bcmul(bcsub(ceil($freightWeight), 1, 2), $freightList['after_price'], 2), $freightList['price'], 2);
                    }
                } else if ($freightList['stype'] == 3) { //体积
                    if ($freightList['unit_price'] > $freightVolume) { //购买件数超过当前模版的满件包邮条件可以包邮
                        $totalFreightPrice = bcadd(bcmul(bcsub(ceil($freightVolume), 1, 2), $freightList['after_price'], 2), $freightList['price'], 2);
                    }
                }
            }
            $freightSupplierPrice[$freightList['supid']] = $totalFreightPrice;
        }
        if ($totalGoodsPrice <= 0) {
            return ['code' => '3009'];
        }
        $discountMoney = $couponPrice;
        $totalPrice    = bcsub(bcadd($totalGoodsPrice, $totalFreightPrice, 2), $discountMoney, 2);
        return ['code' => '200', 'goods_count' => $num, 'rebate_all' => $goodsSku['rebate'], 'total_goods_price' => $totalGoodsPrice, 'total_freight_price' => $totalFreightPrice, 'total_price' => $totalPrice, 'discount_money' => $discountMoney, 'goods_list' => [$goodsSku], 'freight_supplier_price' => $freightSupplierPrice, 'giving_rights' => $giving_rights];
    }

    /**
     * 结算页面
     * @param $conId
     * @param $skuIdList
     * @param $userAddressId
     * @param $userCouponId
     * @return array
     * @author zyr
     */
    public function createSettlement($conId, $skuIdList, int $userAddressId, $userCouponId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if ($this->checkCart($skuIdList, $uid) === false) {
            return ['code' => '3005']; //商品未加入购物车
        }
        $cityId           = 0;
        $defaultAddressId = 0;
        if (!empty($userAddressId)) {
            $userAddress      = DbUser::getUserAddress('id,city_id', ['id' => $userAddressId], true);
            $cityId           = $userAddress['city_id'] ?? 0;
            $defaultAddressId = $userAddress['id'] ?? 0;
        }
        if (empty($defaultAddressId)) { //没有地址返回默认地址id
            $defaultAddress = DbUser::getUserAddress('id,city_id', ['uid' => $uid, 'default' => 1], true);
            if (empty($defaultAddress)) {
                $defaultAddress = DbUser::getUserAddress('id,city_id', ['uid' => $uid, 'default' => 2], true, 'id desc');
            }
            $defaultAddressId = $defaultAddress['id'] ?? 0;
            $cityId           = $defaultAddress['city_id'] ?? 0;
        }
        $balance = DbUser::getUserInfo(['id' => $uid, 'balance_freeze' => 2], 'balance', true);
        $balance = $balance['balance'] ?? 0;
        $summary = $this->summary($uid, $skuIdList, $cityId, $userCouponId);
        if ($summary['code'] != '200') {
            return $summary;
        }
        $cart = $this->getCartGoods($skuIdList, $uid);
        if ($cart == false) {
            return ['code' => '3005'];
        }
        $cartShops = array_column($cart, 'shops'); //所有购买涉及的门店
        $shops     = [];
        array_map(function ($value) use (&$shops) {
            $shops = array_merge($shops, array_values($value));
        }, $cartShops);
        $shops          = array_values(array_unique($shops)); //去重后的门店
        $shopList       = DbShops::getShops([['id', 'in', $shops]], 'id,uid,shop_name,shop_image'); //购买的所有店铺信息列表
        $shopList       = array_combine(array_column($shopList, 'id'), $shopList);
        $goodsList      = $summary['goods_list'];
        $supplierIdList = array_unique(array_column($goodsList, 'supplier_id')); //要结算商品的供应商id列表
        $supplierList   = DbGoods::getSupplier('id,name,image,title,desc', [['id', 'in', $supplierIdList], ['status', '=', 1]]);
        $supplier       = [];
        // print_r($supplierList);die;
        foreach ($supplierList as $sl => $sll) {
            //各供应商运费
            $supplierList[$sl]['freight_status'] = 1;
            if (isset($summary['freight_supplier_price'][$sll['id']])) {
                $supplierList[$sl]['freight_supplier_price'] = $summary['freight_supplier_price'][$sll['id']];
                if ($summary['freight_supplier_price'][$sll['id']]>0){
                    $supplierList[$sl]['freight_status'] = 2;
                }
            }
            $supplierList[$sl]['freight_supplier_price_text'] = '';
            
            if (isset($summary['freight_supplier_price_text'][$sll['id']])){
                $supplierList[$sl]['freight_supplier_price_text'] = $summary['freight_supplier_price_text'][$sll['id']];
            }
            foreach ($goodsList as $gl) {
                if ($gl['supplier_id'] == $sll['id']) {
                    unset($gl['freight_id']);
                    unset($gl['cost_price']);
                    unset($gl['margin_price']);
                    unset($gl['weight']);
                    unset($gl['volume']);
                    unset($gl['spec']);
                    unset($gl['status']);
                    unset($gl['stock']);
                    unset($gl['supplier_id']);
                    unset($gl['shopBuySum']);
                    $supplierList[$sl]['goods_list'][] = $gl;
                    
                }
            }
        }
        $supplier = $supplierList;
        // print_r($supplierList);die;
   /*      foreach ($supplierList as $sl) {
            $glList = [];
            $sList  = []; //门店
            foreach ($goodsList as $gl) {
                if ($gl['supplier_id'] == $sl['id']) {
                    unset($gl['freight_id']);
                    unset($gl['cost_price']);
                    unset($gl['margin_price']);
                    unset($gl['weight']);
                    unset($gl['volume']);
                    unset($gl['spec']);
                    unset($gl['status']);
                    unset($gl['stock']);
                    unset($gl['supplier_id']);
                    unset($gl['buySum']);
                    unset($gl['shopBuySum']);
                    $glList[$gl['id']] = $gl;
                    $shopKey           = array_keys($cart[$gl['id']]['track']);
                    foreach ($shopKey as $s) {
                        if (!isset($sList[$s])) {
                            $sList[$s] = $shopList[$s];
                        }
                    }
                }
            }
            foreach ($sList as $sk => $s) {
                $ggList = [];
                foreach ($glList as $kg => $g) {
                    if (in_array($s['id'], array_keys($cart[$g['id']]['track']))) {
                        $bSum        = $cart[$g['id']]['track'][$s['id']]; //店铺购买的数量
                        $g['buySum'] = $bSum;
                        $ggList[$kg] = $g;
                    }
                }
                $sList[$sk]['goods_list'] = $ggList;
            }
            $sl['shop_list'] = $sList;
            array_push($supplier, $sl);
        } */
        unset($summary['goods_list']);
        unset($summary['freight_supplier_price_text']);
        $summary['supplier_list']      = $supplier;
        $summary['balance']            = $balance;
        $summary['default_address_id'] = $defaultAddressId;
        return $summary;
    }

    /**
     * 创建订单
     * @param $conId
     * @param $skuIdList 1,2,3
     * @param $userAddressId 1 收货地址id
     * @param $payType 2 支付方式1:所有第三方支付2:商券支付
     * @param $userCouponId
     * @return array
     * @author zyr
     */
    public function createOrder($conId, $skuIdList, int $userAddressId, int $payType, $userCouponId = 0) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $userAddress = DbUser::getUserAddress('uid,mobile,name,province_id,city_id,area_id,address', ['id' => $userAddressId], true);
        if (empty($userAddress)) {
            return ['code' => '3003'];
        }
        $cityId = $userAddress['city_id'];
        if ($this->checkCart($skuIdList, $uid) === false) {
            return ['code' => '3005']; //参数有误，商品未加入购物车
        }
        $summary = $this->summary($uid, $skuIdList, $cityId, $userCouponId);
        if ($summary['code'] != '200') {
            return $summary;
        }
//        print_r($summary);die;
        /*
         * 商品订单内容
         */
        $cart = $this->getCartGoods($skuIdList, $uid);
        if ($cart === false) {
            return ['code' => '3005'];
        }
        $from_uid = 0;
        foreach ($cart as $c => $t) {
            if (isset($t['from_uid'])) {
                $from_uid = $t['from_uid'];
            }
        }
        $giving_rights = $summary['giving_rights'];
        $cartShops = array_column($cart, 'shops'); //所有购买涉及的门店
        $shops     = [];
        array_map(function ($value) use (&$shops) {
            $shops = array_merge($shops, array_values($value));
        }, $cartShops);
        $shops    = array_values(array_unique($shops)); //去重后的门店
        $shopList = DbShops::getShops([['id', 'in', $shops]], 'id,uid'); //购买的所有店铺信息列表
        $shopList = array_column($shopList, 'uid', 'id');
//        print_r($shopList);die;
        $orderGoodsData = [];
        foreach ($summary['goods_list'] as $gList) {
            foreach ($gList['shopBuySum'] as $kgl => $gl) {
                for ($i = 0; $i < $gl; $i++) {
                    $goodsData = [
                        'goods_id'     => $gList['goods_id'],
                        'goods_name'   => $gList['goods_name'],
                        'sku_id'       => $gList['id'],
                        'sup_id'       => $gList['supplier_id'],
                        'boss_uid'     => $shopList[$kgl] ?: 1,
                        'goods_price'  => $gList['retail_price'],
                        'margin_price' => getDistrProfits($gList['retail_price'], $gList['cost_price'], $gList['margin_price']),
                        'integral'     => $gList['integral'],
                        'goods_num'    => 1,
                        'sku_json'     => json_encode($gList['attr']),
                    ];
                    array_push($orderGoodsData, $goodsData);
                }
            }
        }
//        print_r($orderGoodsData);die;
        /*
         * 商品订单内容
         */
        /*
         * 子订单内容
         */
        $freightSupplierPrice = $summary['freight_supplier_price'];
        $supplier             = DbGoods::getSupplier('id,name', [['id', 'in', array_keys($freightSupplierPrice)], ['status', '=', '1']]);
        $supplierData         = [];
        foreach ($supplier as $sval) {
            $sval['express_money'] = $freightSupplierPrice[$sval['id']];
            $sval['supplier_id']   = $sval['id'];
            $sval['supplier_name'] = $sval['name'];
            unset($sval['id']);
            unset($sval['name']);
            array_push($supplierData, $sval);
        }
//        print_r($supplierData);die;
        /*
         * 子订单内容
         */
        $orderNo        = createOrderNo(); //创建订单号
        $deductionMoney = 0; //商票抵扣金额
        $thirdMoney     = 0; //第三方支付金额
        $isPay          = false;
        $tradingData    = []; //交易日志
        if ($payType == 2) { //商票支付
            $userInfo = DbUser::getUserInfo(['id' => $uid], 'balance,balance_freeze', true);
            if ($userInfo['balance_freeze'] == '2') { //商票未冻结
                if ($summary['total_price'] > $userInfo['balance']) {
                    $deductionMoney = $userInfo['balance'] > 0 ? $userInfo['balance'] : 0; //可支付的商票
                    $thirdMoney     = bcsub($summary['total_price'], $deductionMoney, 2);
                } else {
                    $isPay          = true; //可以直接商票支付完成
                    $deductionMoney = $summary['total_price'];
                }
            } else {
                $thirdMoney = $summary['total_price'];
            }
            $tradingData = [
                'uid'          => $uid,
                'trading_type' => 1,
                'change_type'  => 1,
                'money'        => -$deductionMoney,
                'befor_money'  => $userInfo['balance'],
                'after_money'  => bcsub($userInfo['balance'], $deductionMoney, 2),
                'message'      => '',
                'create_time'  => time(),
            ];
        } else if ($payType == 1) { //第三方支付
            $thirdMoney = $summary['total_price'];
        }
        $orderData = [
            'order_no'        => $orderNo,
            'third_order_id'  => 0,
            'uid'             => $uid,
            'order_status'    => $isPay ? 4 : 1,
            'order_money'     => bcadd($summary['total_price'], $summary['discount_money'], 2), //订单金额(优惠金额+实际支付的金额)
            'deduction_money' => $deductionMoney, //商票抵扣金额
            'pay_money'       => $summary['total_price'], //实际支付(第三方支付金额+商票抵扣金额)
            'goods_money'     => $summary['total_goods_price'], //商品金额
            'third_money'     => $thirdMoney, //第三方支付金额
            'discount_money'  => $summary['discount_money'], //优惠金额
            'pay_type'        => $payType,
            'third_pay_type'  => 2, //第三方支付类型1.支付宝 2.微信 3.银联 (暂时只能微信)
            'linkman'         => $userAddress['name'],
            'linkphone'       => $userAddress['mobile'],
            'province_id'     => $userAddress['province_id'],
            'city_id'         => $userAddress['city_id'],
            'area_id'         => $userAddress['area_id'],
            'address'         => $userAddress['address'],
            'message'         => '',
            'giving_rights'   => $giving_rights,
            'from_uid'        => $from_uid,
            'pay_time'        => $isPay ? time() : 0,
        ];
//        print_r($orderData);die;
        $stockSku = array_column($summary['goods_list'], 'buySum', 'id');
        Db::startTrans();
        try {
            $orderId = DbOrder::addOrder($orderData);
            if (empty($orderId)) {
                Db::rollback();
                return ['code' => '3009'];
            }
            foreach ($supplierData as $sdkey => $sdval) {
                $supplierData[$sdkey]['order_id'] = $orderId;
            }
            $childOrder    = DbOrder::addOrderChilds($supplierData);
            $childSupplier = $childOrder->toArray();
            $childSupplier = array_column($childSupplier, 'id', 'supplier_id');
            foreach ($orderGoodsData as $ogdK => $ogdV) {
                $orderGoodsData[$ogdK]['order_child_id'] = $childSupplier[$ogdV['sup_id']];
            }
            DbOrder::addOrderGoods($orderGoodsData);
            DbGoods::decStock($stockSku);
            DbUser::modifyBalance($uid, $deductionMoney, $modify = 'dec');
            if (!empty($tradingData)) {
                DbOrder::addLogTrading($tradingData);
            }
            if (!empty($userCouponId)) {
                DbCoupon::updateUserCoupon([
                    'is_use'   => 1,
                    'order_id' => $orderId,
                ], $userCouponId);
            }
            if ($isPay) {
                $redisListKey = Config::get('rediskey.order.redisOrderBonus');
                $this->redis->rPush($redisListKey, $orderId);
            }
            $this->summaryCart($skuIdList, $uid);
            $this->resetUserInfo($uid);
            Db::commit();
            return ['code' => '200', 'order_no' => $orderNo, 'is_pay' => $isPay ? 1 : 2];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3009'];
        }
    }

    /**
     * 购买的结算汇总
     * @param $uid
     * @param $skuIdList
     * @param $cityId
     * @param $userCouponId
     * @return array
     * @author zyr
     */
    private function summary($uid, $skuIdList, $cityId, $userCouponId) {
        $cart = $this->getCartGoods($skuIdList, $uid);
        if ($cart === false) {
            return ['code' => '3005'];
        }
        $goodsSku = DbGoods::getSkuGoods([['goods_sku.id', 'in', $skuIdList], ['stock', '>', '0'], ['goods_sku.status', '=', '1']], 'id,goods_id,stock,freight_id,market_price,retail_price,cost_price,margin_price,weight,volume,sku_image,spec', 'id,supplier_id,goods_name,goods_type,target_users,subtitle,status,giving_rights');
        $diff     = array_diff($skuIdList, array_column($goodsSku, 'id'));
        if (!empty($diff)) {
            $eGoodsList = [];
            foreach ($diff as $di) {
                $oneGoods = DbGoods::getOneGoods(['id' => $cart[$di]['goods_id']], 'id,goods_name,subtitle,image,giving_rights');
                $attrList = DbGoods::getAttrList([['id', 'in', explode(',', $cart[$di]['spec'])]], 'attr_name');
                $attrList = array_column($attrList, 'attr_name');
                array_push($eGoodsList, $oneGoods['goods_name'] . '(' . implode('、', $attrList) . ')');
            }
            return ['code' => '3004', 'goodsError' => $eGoodsList]; //商品售罄列表
        }
        $goodsOversold        = []; //库存不够商品列表
        $goodsList            = [];
        $freightPrice         = []; //各个运费模版的商品购买价格
        $freightCount         = []; //各个运费模版的购买数量
        $freightWeight        = []; //各个运费模版的购买重量
        $freightVolume        = []; //各个运费模版的购买体积
        $rebateAll            = 0; //所有商品钻石再补贴总和
        $totalGoodsPrice      = 0; //所有商品总价
        $goodsCount           = 0; //购买商品总数
        $totalFreightPrice    = 0; //总运费
        $freightSupplierPrice = []; //各个供应商的运费
        $user = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        $giving_rights        = 1;
        foreach ($goodsSku as $value) {
            if ($user['user_identity'] < $value['goods']['target_users']) {
                if ($value['goods']['target_users'] == 2){
                    return ['code' => 3010, 'msg' => '该商品钻石会员及以上身份专享'];
                }elseif ($value['goods']['target_users'] == 3){
                    return ['code' => 3011, 'msg' => '该商品创业店主及以上身份专享'];
                }elseif ($value['goods']['target_users'] == 4){
                    return ['code' => 3012, 'msg' => '该商品合伙人及以上身份专享'];
                }
            }
            $value['supplier_id'] = $value['goods']['supplier_id'];
            $value['goods_name']  = $value['goods']['goods_name'];
            $value['goods_type']  = $value['goods']['goods_type'];
            $value['subtitle']    = $value['goods']['subtitle'];
            $value['status']      = $value['goods']['status'];
            $attr                 = DbGoods::getAttrList([['id', 'in', explode(',', $value['spec'])]], 'attr_name');
            $value['attr']        = array_column($attr, 'attr_name');

            /* 商品赠送权益 */
            if ($value['goods']['giving_rights'] > $giving_rights) {
                $giving_rights = $value['goods']['giving_rights'];
            }

            unset($value['goods']);
            $cartSum = intval($cart[$value['id']]['sum']);
            if ($cartSum > $value['stock']) { //购买数量超过库存
                array_push($goodsOversold, $value['goods_name'] . '(' . implode('、', $value['attr']) . ')');
            }
            $value['buySum']                     = $cartSum;
            $value['shopBuySum']                 = $cart[$value['id']]['track'];
            $fPrice                              = bcmul($value['retail_price'], $cartSum, 2);
            $freightPrice[$value['freight_id']]  = isset($freightPrice[$value['freight_id']]) ? bcadd($freightPrice[$value['freight_id']], $fPrice, 2) : $fPrice; //同一个供应商模版id的商品价格累加
            $fCount                              = bcmul(1, $cartSum, 2);
            $freightCount[$value['freight_id']]  = isset($freightCount[$value['freight_id']]) ? bcadd($freightCount[$value['freight_id']], $fCount, 0) : $fCount; //同一个供应商模版id的商品数量累加
            $fWeight                             = bcmul($value['weight'], $cartSum, 2);
            $freightWeight[$value['freight_id']] = isset($freightWeight[$value['freight_id']]) ? bcadd($freightWeight[$value['freight_id']], $fWeight, 2) : $fWeight; //同一个供应商模版id的商品重量累加
            $fVolume                             = bcmul($value['volume'], $cartSum, 2);
            $freightVolume[$value['freight_id']] = isset($freightVolume[$value['freight_id']]) ? bcadd($freightVolume[$value['freight_id']], $fVolume, 2) : $fVolume;//同一个供应商模版id的商品体积累加
            $distrProfits                        = getDistrProfits($value['retail_price'], $value['cost_price'], $value['margin_price']);//可分配利润
            $value['rebate']                     = $this->getRebate($distrProfits, $cartSum);
            $value['integral']                   = $this->getIntegral($value['retail_price'], $value['cost_price'], $value['margin_price']);
            $rebateAll                           = bcadd($this->getRebate($distrProfits, $cartSum), $rebateAll, 2); //钻石再补贴
            //            $integralAll                         = bcadd($this->getIntegral($value['retail_price'], $value['cost_price'], $value['margin_price'], $cartSum));
            $totalGoodsPrice = bcadd(bcmul($value['retail_price'], $cartSum, 2), $totalGoodsPrice, 2); //商品总价
            $goodsCount      += $cartSum;
            array_push($goodsList, $value);
        }
        $couponPrice = 0;
        $goodsIdList = array_unique(array_column($goodsList, 'goods_id'));
        if (!empty($userCouponId)) {//有优惠券
            $userCoupon = DbCoupon::getUserCoupon([
                ['id', '=', $userCouponId],
                ['uid', '=', $uid],
                ['is_use', '=', 2],//未使用的
                ['end_time', '>', time()],//未过期的
            ], 'id,price,gs_id,level', true);
            if (empty($userCoupon)) {
                return ['code' => '3013'];
            }
            if ($userCoupon['level'] == 1) {//单品券
                if (!in_array($userCoupon['gs_id'], $goodsIdList)) {
                    return ['code' => '3013'];
                }
            }
            if ($userCoupon['level'] == 2) {//专题券
                $couponGoodsIdList = DbGoods::getSubjectRelation([['subject_id', '=', $userCoupon['gs_id']]], 'goods_id');
                $couponGoodsIdList = array_column($couponGoodsIdList, 'goods_id');
                $intersect         = array_intersect($couponGoodsIdList, $goodsIdList);
                if (empty($intersect)) {//没有交集
                    return ['code' => '3013'];
                }
            }
            $couponPrice = $userCoupon['price'];
        }
        if (!empty($goodsOversold)) {
            return ['code' => '3007', 'goods_oversold' => $goodsOversold]; //库存不足商品
        }
        $freightSupplierPriceText = [];
        if (!empty($cityId)) {
            /* 运费模版 运费计算 start */
            $freightIdList = array_values(array_unique(array_column($goodsList, 'freight_id')));
            $freightList   = DbGoods::getFreightAndDetail([['freight_id', 'in', $freightIdList]], $cityId, 'id,freight_id,price,after_price,total_price,unit_price', 'id,supid,stype', 'freight_detail_id,city_id', $freightIdList);
            $freightDiff   = array_values(array_diff($freightIdList, array_keys($freightList)));
            if (!empty($freightDiff)) {
                $eGoodsList = [];
                foreach ($freightDiff as $fd) {
                    foreach ($goodsList as $gl) {
                        if ($gl['freight_id'] == $fd) {
                            array_push($eGoodsList, $gl['goods_name'] . '(' . implode('、', $gl['attr']) . ')');
                        }
                    }
                }
                return ['code' => '3006', 'freightError' => $eGoodsList]; //商品不支持配送
            }
            
            foreach ($freightList as $flk => $fl) {
                // echo $fl['total_price'].'</br>';
                // echo $freightPrice[$flk];
                
                if ($fl['total_price'] <= $freightPrice[$flk]) { //该供应商的当前运费模版下购买的总价超过包邮价可以包邮
                    $addOnItems = $fl['total_price']-$freightPrice[$flk];
                    $freightSupplierPriceText[$fl['supid']] = '再凑'.$addOnItems.'元可享受包邮';
                    $freightSupplierPrice[$fl['supid']] = isset($freightSupplierPrice[$fl['supid']]) ? bcadd($freightSupplierPrice[$fl['supid']], 0, 0) : 0;
                    continue;
                }
                $price = 0;
                if ($fl['stype'] == 1) { //件数
                    if ($fl['unit_price'] > $freightCount[$flk]) { //购买件数超过当前模版的满件包邮条件可以包邮
                        $price = bcadd(bcmul(bcsub($freightCount[$flk], 1, 2), $fl['after_price'], 2), $fl['price'], 2);
                        $addOnItems = $freightCount[$flk]-$fl['unit_price'];
                        if ($addOnItems){
                            $freightSupplierPriceText[$fl['supid']] = '再凑'.$addOnItems.'件可享受包邮';
                        }
                    }
                } else if ($fl['stype'] == 2) { //重量
                    if ($fl['unit_price'] > $freightWeight[$flk]) { //购买重量超过当前模版的满件包邮条件可以包邮
                        $price = bcadd(bcmul(bcsub(ceil($freightWeight[$flk]), 1, 2), $fl['after_price'], 2), $fl['price'], 2);
                        $addOnItems = $freightWeight[$flk]-$fl['unit_price'];
                        if ($addOnItems){
                            $freightSupplierPriceText[$fl['supid']] = '再凑'.$addOnItems.'千克可享受包邮';
                        }
                    }
                } else if ($fl['stype'] == 3) { //体积
                    if ($fl['unit_price'] > $freightVolume[$flk]) { //购买件数超过当前模版的满件包邮条件可以包邮
                        $price = bcadd(bcmul(bcsub($freightVolume[$flk], 1, 2), $fl['after_price'], 2), $fl['price'], 2);
                        $addOnItems = $freightVolume[$flk]-$fl['unit_price'];
                        if ($addOnItems){
                            $freightSupplierPriceText[$fl['supid']] = '再凑'.$addOnItems.'立方米可享受包邮';
                        }
                    }
                }
                $freightSupplierPrice[$fl['supid']] = isset($freightSupplierPrice[$fl['supid']]) ? bcadd($freightSupplierPrice[$fl['supid']], $price, 0) : $price;
                $totalFreightPrice                  = bcadd($totalFreightPrice, $price, 2);
                // print_r($addOnItems);
            }
            /* 运费模版 end */
        }
        // print_r($freightSupplierPriceText);die;
        if ($totalGoodsPrice <= 0) {
            return ['code' => '3009'];
        }
        $discountMoney = $couponPrice;
        $totalPrice    = bcsub(bcadd($totalGoodsPrice, $totalFreightPrice, 2), $discountMoney, 2);
        return ['code' => '200', 'goods_count' => $goodsCount, 'rebate_all' => $rebateAll, 'total_goods_price' => $totalGoodsPrice, 'total_freight_price' => $totalFreightPrice, 'total_price' => $totalPrice, 'discount_money' => $discountMoney, 'goods_list' => $goodsList, 'freight_supplier_price' => $freightSupplierPrice, 'giving_rights' => $giving_rights, 'freight_supplier_price_text' => $freightSupplierPriceText];
    }

    /**
     * 创建订单后清空购物车中下单的商品
     * @param $skuIdList
     * @param $uid
     * @author zyr
     */
    private function summaryCart($skuIdList, $uid) {
        $params = [$this->redisCartUserKey . $uid];
        foreach ($skuIdList as $silV) {
            array_push($params, $this->prefix . $silV);
        }
        call_user_func_array([$this->redis, 'hDel'], $params);
    }

    /**
     * 获取钻石再补贴
     * @param $distrProfits 可分配利润
     * @param int $num 商品数量
     * @return string
     * @author zyr
     */
    private function getRebate($distrProfits, $num = 1) {
        $rebate = bcmul($distrProfits, 0.75, 5);
        $result = bcmul($rebate, $num, 2);
        return $result;
    }

    /**
     * 可获得积分计算
     * @param $retailPrice
     * @param $costPrice
     * @param $marginPrice
     * @param $num
     * @return string
     * @author zyr
     */
    private function getIntegral($retailPrice, $costPrice, $marginPrice, $num = 1) {
        $profits  = bcsub(bcsub($retailPrice, $costPrice, 2), $marginPrice, 2); //利润(售价-进价-其他成本)
        $integral = bcmul($profits, 2, 5);
        $integral = bcmul($integral, $num, 0);
        return $integral;
    }

    /**
     * 获取购物车里要购买的商品
     * @param $skuIdList
     * @param $uid
     * @return array
     * @author zyr
     */
    private function getCartGoods($skuIdList, $uid) {
        $prefix       = $this->prefix;
        $skuIdListNew = array_map(function ($v) use ($prefix) {
            return $prefix . $v;
        }, $skuIdList);
        $keys = $this->redis->hKeys($this->redisCartUserKey . $uid);
        $diff = array_diff($skuIdListNew, $keys);
        if (!empty($diff)) {
            return false;
        }
        $buyList = $this->redis->hMGet($this->redisCartUserKey . $uid, $skuIdListNew);
        $result  = [];
        foreach ($buyList as $key => $val) {
            $cartRow          = json_decode($val, true);
            $cartRow['sum']   = array_sum($cartRow['track']);
            $cartRow['shops'] = array_keys($cartRow['track']);
            $resKey           = str_replace($prefix, '', $key);
            $result[$resKey]  = $cartRow;
        }
        return $result;
    }

    /**
     * 判断结算的商品是否已加入购物车
     * @param $skuIdList
     * @param $uid
     * @return bool
     * @author zyr
     */
    private function checkCart($skuIdList, $uid) {
        if (!$this->redis->exists($this->redisCartUserKey . $uid)) {
            return false;
        }
        $prefix   = $this->prefix;
        $carts    = $this->redis->hKeys($this->redisCartUserKey . $uid);
        $cartList = array_map(function ($v) use ($prefix) {
            return str_replace($prefix, '', $v);
        }, $carts);
        $diff     = array_diff($skuIdList, $cartList);
        if (empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 获取用户订单列表
     * @param $conId
     * @param $order_status
     * @param $cityId
     * @return array
     * @author rzc
     */
    public function getUserOrderList($conId, $order_status = false, $page, $pagenum) {
        $uid = $this->getUidByConId($conId);
        // $uid = 23697;
        if (empty($uid)) {
            return ['code' => '3005'];
        }
        $offset = ($page - 1) * $pagenum;
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $field = 'id,order_no,third_order_id,order_status,order_money,order_type,deduction_money,pay_money,goods_money,discount_money,deduction_money,third_money';
        $where = ['uid' => $uid];
        if ($order_status) {
            $where = ['uid' => $uid, 'order_status' => $order_status];
        }
        // print_r($order_status);die;
        $limit  = $offset . ',' . $pagenum;
        $result = DbOrder::getOrder($field, $where, false, $limit);
        if (empty($result)) {
            return ['code' => '200', 'order_list' => []];
        }
        foreach ($result as $key => $value) {
            $order_child   = DbOrder::getOrderChild('id,express_money,supplier_id,supplier_name', ['order_id' => $value['id']]);
            $integral      = 0;
            $commission    = 0;
            $express_money = 0;
            foreach ($order_child as $order => $child) {
                $order_goods     = DbOrder::getOrderGoods('goods_id,goods_name,order_child_id,sku_id,sup_id,goods_type,goods_price,margin_price,integral,goods_num,sku_json', ['order_child_id' => $child['id']], false, true);
                $order_goods_num = DbOrder::getOrderGoods('sku_id,COUNT(goods_num) as goods_num', ['order_child_id' => $child['id']], 'sku_id');
                foreach ($order_goods as $og => $goods) {

                    foreach ($order_goods_num as $ogn => $goods_num) {
                        if ($goods_num['sku_id'] == $goods['sku_id']) {
                            $order_goods[$og]['goods_num'] = $goods_num['goods_num'];
                            if ($goods['goods_type'] == 1) {
                                $order_goods[$og]['sku_image'] = DbGoods::getOneGoodsSku(['id' => $goods['sku_id']], 'sku_image', true)['sku_image'];
                                $order_goods[$og]['sku_json']  = json_decode($order_goods[$og]['sku_json'], true);
                            } else if ($goods['goods_type'] == 2) {
                                $order_goods[$og]['sku_image'] = '';
                                $order_goods[$og]['sku_json'] = DbAudios::getAudio([['id','in',join(',',json_decode($order_goods[$og]['sku_json'], true))]],'id,name,audio,audition_time,audio_length_text');
                               
                                // print_r(json_encode($sku_json));die;
                            }
                            
                            $integral                      += $goods['integral'] * $goods_num['goods_num'];
                            $commission                    = bcadd($commission, bcmul(bcmul($goods['margin_price'], 0.75, 2), $goods_num['goods_num'], 2), 2);
                        }
                    }
                }
                // dump( Db::getLastSql());die;
                $order_child[$order]['order_goods'] = $order_goods;
                $express_money += $child['express_money'];
            }
            $result[$key]['express_money'] = $express_money;
            $result[$key]['integral']      = $integral;
            if ($commission) {
                $user_identity = DbUser::getLogBonus(['order_no' => $value['order_no'], 'to_uid' => $uid], 'user_identity', true);
                $commission    = empty($user_identity) ? 0 : $commission;
            }
            $result[$key]['commission']  = $commission;
            $result[$key]['order_child'] = $order_child;
            unset($result[$key]['id']);
        }
        return ['code' => '200', 'order_list' => $result];
    }

    /**
     * 获取用户订单详情
     * @param $conId
     * @param $order_id
     * @return array
     * @author rzc
     */
    public function getUserOrderInfo($conId, $order_no) {
        $uid = $this->getUidByConId($conId);
        // $uid = 23697;
        if (empty($uid)) {
            return ['code' => '3005'];
        }
        /* 取货码短信 */
        $field  = 'id,order_no,third_order_id,order_type,order_status,order_money,deduction_money,pay_money,goods_money,discount_money,deduction_money,third_money,third_pay_type,linkman,linkphone,province_id,city_id,area_id,address,message,create_time';
        $where  = ['uid' => $uid, 'order_no' => $order_no];
        $result = DbOrder::getOrder($field, $where, true);
        if (empty($result)) {
            return ['code' => '3004', 'msg' => '订单不存在'];
        }
        $order_child   = DbOrder::getOrderChild('*', ['order_id' => $result['id']]);
        $integral      = 0;
        $express_money = 0;
        $commission    = 0;
        foreach ($order_child as $order => $child) {
            $order_goods     = DbOrder::getOrderGoods('goods_id,goods_name,order_child_id,sku_id,sup_id,goods_type,goods_price,margin_price,integral,goods_num,sku_json,sku_image', ['order_child_id' => $child['id']], false, true);
            $order_goods_num = DbOrder::getOrderGoods('sku_id,COUNT(goods_num) as goods_num', ['order_child_id' => $child['id']], 'sku_id');
            foreach ($order_goods as $og => $goods) {

                foreach ($order_goods_num as $ogn => $goods_num) {
                    if ($goods_num['sku_id'] == $goods['sku_id']) {
                        $order_goods[$og]['goods_num'] = $goods_num['goods_num'];
                        if ($goods['goods_type'] == 1) {
                            $order_goods[$og]['sku_image'] = DbGoods::getOneGoodsSku(['id' => $goods['sku_id']], 'sku_image', true)['sku_image'];
                            $order_goods[$og]['sku_json']  = json_decode($order_goods[$og]['sku_json'], true);
                        } else if ($goods['goods_type'] == 2) {
                            $order_goods[$og]['sku_image'] = '';
                            $order_goods[$og]['sku_json'] = DbAudios::getAudio([['id','in',join(',',json_decode($order_goods[$og]['sku_json'], true))]],'id,name,audio,audition_time,audio_length_text');
                           
                            // print_r(json_encode($sku_json));die;
                        }
                        
                        $integral                     += $goods['integral'] * $goods_num['goods_num'];
                        $commission                   = bcadd($commission, bcmul(bcmul($goods['margin_price'], 0.75, 2), $goods_num['goods_num'], 2), 2);
                    }
                }
            }
            // dump( Db::getLastSql());die;
            $order_child[$order]['order_goods'] = $order_goods;
            $express_money += $child['express_money'];
        }
        $result['express_money'] = $express_money;
        $result['integral']      = $integral;
        if ($commission) {
            $user_identity = DbUser::getLogBonus(['order_no' => $result['order_no'], 'to_uid' => $uid], 'user_identity', true);
            $commission    = empty($user_identity) ? 0 : $commission;
        }
        $result['commission']  = $commission;
        $result['order_child'] = $order_child;
        if ($result['province_id']) {
            $result['province_name'] = DbProvinces::getAreaOne('*', ['id' => $result['province_id']])['area_name'];
        }
        if ($result['city_id']) {
            $result['city_name'] = DbProvinces::getAreaOne('*', ['id' => $result['city_id'], 'level' => 2])['area_name'];
        }
        if ($result['area_id']) {
            $result['area_name'] = DbProvinces::getAreaOne('*', ['id' => $result['area_id']])['area_name'];
        }
        return ['code' => '200', 'order_info' => $result];
    }

    /**
     * 创建用户权益订单
     * @param $conId
     * @param $user_type
     * @return array
     * @author rzc
     */
    public function createMemberOrder($conId, $user_type, $pay_type, $parent_id = false, $old_parent_id = '', int $actype) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if (!$parent_id) {
            $parent_id = 1;
        } else {
            $parent_info = DbUser::getUserInfo(['id' => $parent_id], 'user_identity', true);
            if (empty($parent_info)) {
                $parent_id = 1;
            } else {
                if ($actype != 2) {
                    if ($parent_info['user_identity'] < 2) {
                        $parent_id = 1;
                    }
                    if ($user_type == 2 && $parent_info['user_identity'] < 3) {
                        $parent_id = 1;
                    }
                }
            }
        }
        if ($uid == $parent_id) {
            $parent_id = 1;
        }
        /* 计算支付金额 */
        if ($user_type == 1) {
            $pay_money = 98;
            // $pay_money = 1;/* 测试一元 */
        } elseif ($user_type == 2) {
            $pay_money = 30000;
        } elseif ($user_type == 3) {
            $user_type = 1;
            $pay_money = 118;
            $actype    = 1;
        }
        /* 判断会员身份，低于当前层级可购买升级 */
        $user_identity = DbUser::getUserOne(['id' => $uid], 'user_identity')['user_identity']; /* 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人 */
        if ($user_identity >= $user_type + 1) {
            return ['code' => '3003', 'msg' => '购买权益等级低于当前权益'];
        }
        /* 先查询是否有已存在未结算订单 */
        $has_member_order = DbOrder::getMemberOrder(['uid' => $uid, 'from_uid' => $parent_id, 'user_type' => $user_type, 'pay_status' => 1, 'actype' => $actype], '*', true);
        if ($has_member_order) {
            /* 判断订单金额是否与最新订单金额相等 */
            if ($pay_money != $has_member_order['pay_money']) {
                $has_member_order['pay_money'] = $pay_money;
                /* 更新支付金额 */
                DbOrder::updateMemberOrder(['pay_money' => $pay_money, 'pay_type' => $pay_type], ['id' => $has_member_order['id']]);
            }
            // unset($has_member_order['from_uid']);
            Db::table('pz_log_error')->insert(['title' => '/index/order/createMemberOrder', 'data' => json_encode([
                    'member_order_id' => $has_member_order['id'],
                    'uid'             => $uid,
                    'user_type'       => $user_type,
                    'actype'          => $actype,
                    'parent_id'       => $parent_id,
                    'old_parent_id'   => $old_parent_id,
                    'pay_money'       => $pay_money,
                ])]
            );
            $has_member_order['from_uid'] = enUid($has_member_order['from_uid']);
            return ['code' => '200', 'order_data' => $has_member_order];
        } else {
            $order              = [];
            $order['order_no']  = createOrderNo('mem');
            $order['uid']       = $uid;
            $order['user_type'] = $user_type;
            $order['pay_money'] = $pay_money;
            $order['pay_type']  = $pay_type;
            $order['actype']    = $actype;
            if ($parent_id) {
                $order['from_uid'] = $parent_id;
            }
            $add               = DbOrder::addMemberOrder($order);
            $order['from_uid'] = enUid($parent_id);
            Db::table('pz_log_error')->insert(['title' => '/index/order/createMemberOrder', 'data' => json_encode([
                    'member_order_id' => $add,
                    'uid'             => $uid,
                    'user_type'       => $user_type,
                    'actype'          => $actype,
                    'pay_money'       => $pay_money,
                    'parent_id'       => $parent_id,
                    'old_parent_id'   => $old_parent_id,
                ])]
            );
            return ['code' => '200', 'order_data' => $order];
        }
    }

    /**
     * 确认收货
     * @param $conId
     * @param $orderNo
     * @return array
     * @author rzc
     */
    public function confirmOrder($orderNo, $conId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $order = DbOrder::getOrder('id', ['order_no' => $orderNo, 'uid' => $uid, 'order_status' => 5], true);
        if (empty($order)) {
            return ['code' => '3003']; //没有可确认的订单
        }
        DbOrder::updataOrder(['order_status' => 6, 'rece_time' => time()], $order['id']);
        return ['code' => 200, 'msg' => '确认成功'];
    }

    /**
     * 查询订单分包
     * @param $conId
     * @param $order_no
     * @return array
     * @author rzc
     */
    public function getOrderSubpackage($order_no, $conId) {
        $uid = $this->getUidByConId($conId);
        // $uid = 23697;
        if (empty($uid)) {
            return ['code' => '3005'];
        }
        $result = DbOrder::getOrder('id,order_status', ['uid' => $uid, 'order_no' => $order_no], true);
        if (empty($result)) {
            return ['code' => '3004', 'msg' => '订单不存在'];
        }
        if ($result['order_status'] < 5) {
            return ['code' => '3006', 'msg' => '未发货的订单无法查询分包信息'];
        }
        $order_child  = DbOrder::getOrderChild('id', ['order_id' => $result['id']]);
        $order_childs = [];
        foreach ($order_child as $key => $value) {
            $order_childs[] = $value['id'];
        }
        $order_goods_id = DbOrder::getOrderGoods('id', [['order_child_id', 'IN', $order_childs]]);
        $order_goods_ids = [];
        foreach ($order_goods_id as $goods => $goods_id) {
            $order_goods_ids[] = $goods_id['id'];
        }
        // print_r($order_goods_id);die;
        $has_order_express = DbOrder::getOrderExpress('express_no,express_key,express_name', [['order_goods_id', 'IN', $order_goods_ids]], false, true);
        $order_subpackage  = [];
        foreach ($has_order_express as $order => $express) {
            $where               = [
                'express_no'   => $express['express_no'],
                'express_key'  => $express['express_key'],
                'express_name' => $express['express_name'],
            ];
            $has_express_goodsid = DbOrder::getOrderExpress('order_goods_id', $where);
            foreach ($has_express_goodsid as $has_express => $goods) {
                $express_goods = DbOrder::getOrderGoods('goods_name,sku_json,sku_image,sku_id', [['id', '=', $goods['order_goods_id']]], false, false, true);
                // print_r($express_goods);die;
                if (empty($express_goods['sku_image'])) {
                    $express_goods['sku_image'] = DbGoods::getOneGoodsSku(['id' => $express_goods['sku_id']], 'sku_image', true)['sku_image'];
                }
                $express_goods['sku_json']  = json_decode($express_goods['sku_json'], true);
                $express['express_goods'][] = $express_goods;
            }
            $key = $express['express_no'] . '&' . $express['express_key'];
            // $key = 'shentong&3701622486414';
            $expresslog = $this->redis->get($this->redisDeliverOrderKey . $key);
            if (!empty($expresslog)) {
                $expresslog              = json_decode($expresslog, true);
                $express['express_info'] = $expresslog['data'][0]['context'];
            } else {
                $express['express_info'] = '';
            }
            $order_subpackage[] = $express;
        }
        $package_num = count($has_order_express);
        return ['code' => 200, 'package_num' => $package_num, 'order_no' => $order_no, 'order_subpackage' => $order_subpackage];
    }

    /**
     * 查询订单物流流转信息
     * @param $express_key
     * @param $express_no
     * @return array
     * @author rzc
     */
    public function getExpressLog($express_key, $express_no, $order_no, $conId) {
        $uid = $this->getUidByConId($conId);
        // $uid = 23697;
        if (empty($uid)) {
            return ['code' => '3005'];
        }
        $order_address = DbOrder::getOrder('id,order_status,province_id,city_id,area_id,address,message', ['uid' => $uid, 'order_no' => $order_no], true);
        if (empty($order_address)) {
            return ['code' => '3004', 'msg' => '订单不存在'];
        }
        if ($order_address['order_status'] < 5) {
            return ['code' => '3006', 'msg' => '未发货的订单无法查询分包信息'];
        }
        $order_child  = DbOrder::getOrderChild('id', ['order_id' => $order_address['id']]);
        $order_childs = [];
        foreach ($order_child as $key => $value) {
            $order_childs[] = $value['id'];
        }
        $order_goods_id = DbOrder::getOrderGoods('id', [['order_child_id', 'IN', $order_childs]]);
        $order_goods_ids = [];
        foreach ($order_goods_id as $goods => $goods_id) {
            $order_goods_ids[] = $goods_id['id'];
        }
        // $where               = [
        //     'express_no'   => $express_no,
        //     'express_key'  => $express_key
        // ];
        $where = [
            ['express_no', '=', $express_no],
            ['express_key', '=', $express_key],
            ['order_goods_id', 'in', $order_goods_ids],
        ];
        $has_express_goodsid = DbOrder::getOrderExpress('order_goods_id', $where);
        // print_r($has_express_goodsid);die;
        $express_goods = [];
        if (empty($has_express_goodsid)) {
            return ['code' => '3007', 'msg' => '无效的分包信息'];
        }
        foreach ($has_express_goodsid as $has_express => $goods) {
            $deliver_express_goods = DbOrder::getOrderGoods('sku_id,goods_name,sku_json', [['id', '=', $goods['order_goods_id']]], false, false, true);
            // print_r($express_goods);die;
            $deliver_express_goods['sku_json'] = json_decode($deliver_express_goods['sku_json'], true);
            $express_goods[]                   = $deliver_express_goods;
        }
        $express                  = [];
        $express['province_name'] = DbProvinces::getAreaOne('*', ['id' => $order_address['province_id']])['area_name'];
        $express['city_name']     = DbProvinces::getAreaOne('*', ['id' => $order_address['city_id']])['area_name'];
        $express['area_name']     = DbProvinces::getAreaOne('*', ['id' => $order_address['area_id']])['area_name'];
        $express['address']       = $order_address['address'];
        $express['message']       = $order_address['message'];
        $key                      = $express_key . '&' . $express_no;
        // $key = 'shentong&3701622486414';
        $expresslog = $this->redis->get($this->redisDeliverOrderKey . $key);
        if (empty($expresslog)) {
            $expresslog         = [];
            $express['is_sign'] = 2;
            return ['code' => 200, 'address' => $express, 'express_goods' => $express_goods, 'expresslog' => $expresslog];
        } else {
            $expresslog = json_decode($expresslog, true);
        }
        if ($expresslog['state'] == 3) {
            $express['is_sign'] = 1;
        } else {
            $express['is_sign'] = 2;
        }
        return ['code' => 200, 'address' => $express, 'express_goods' => $express_goods, 'expresslog' => $expresslog['data']];
        // $express =
    }

    /**
     * 给微信用户发送模板消息
     * @param $orderNo
     * @param $conId
     * @return array
     * @author rzc
     */
    public function sendModelMessage($orderNo, $conId) {
        $uid = $this->getUidByConId($conId);
        // $uid = 23697;
        if (empty($uid)) {
            return ['code' => '3005'];
        }
        $order_id = DbOrder::getOrder('id,create_time,pay_time,order_status,order_money', ['uid' => $uid, 'order_no' => $orderNo], true);
        if (empty($order_id)) {
            return ['code' => '3004', 'msg' => '订单不存在'];
        }
        $logPayRes                 = DbOrder::getLogPay(['order_id' => $order_id['id'], 'payment' => 1], 'id,order_id,payment,prepay_id', true);
        $user_wxinfo               = DbUser::getUserWxinfo(['uid' => $uid], 'openid', true);
        $order                     = DbOrder::getOrderDetail(['uid' => $uid, 'order_no' => $orderNo], '*');
        $data['keyword1']['value'] = $order_id['create_time'];
        $data['keyword1']['color'] = '#157efb';
        $data['keyword2']['value'] = $orderNo;
        $data['keyword2']['color'] = '#333';
        $keyword3                  = '';
        // $goo
        // 商品名称
        foreach ($order as $key => $value) {
            //    echo $value['sku_json'];die;
            $data['keyword3']['value'] = $keyword3 . $value['goods_name'] . $value['goods_price'] . ' X ' . $value['goods_num'] . ' 【' . json_decode($value['sku_json'])[0] . '】 ';
        }
        $data['keyword3']['color'] = '#333';
        switch ($order_id['order_status']) {
            case '1':
                $data['keyword4']['value'] = '待付款';
                break;
            case '2':
                $data['keyword4']['value'] = '取消订单';
                break;
            case '3':
                $data['keyword4']['value'] = '已关闭';
                break;
            case '4':
                $data['keyword4']['value'] = '已付款';
                break;
            case '5':
                $data['keyword4']['value'] = '已发货';
                break;
            case '6':
                $data['keyword4']['value'] = '已收货';
                break;
            case '7':
                $data['keyword4']['value'] = '待评价';
                break;
            case '8':
                $data['keyword4']['value'] = '退款申请确认';
                break;
            case '9':
                $data['keyword4']['value'] = '退款中';
                break;
            case '10':
                $data['keyword4']['value'] = '退款成功';
                break;
        }
        $data['keyword4']['color'] = '#333';
        $data['keyword5']['value'] = $order_id['order_money'];
        $data['keyword5']['color'] = '#333';
        $data['keyword6']['value'] = $order_id['pay_time'];
        $data['keyword6']['color'] = '#333';
        $send_data                = [];
        $send_data['touser']      = $user_wxinfo['openid'];
        $send_data['template_id'] = 'sTxQPX6BWBAo7In_nr9KbTlV6tEAhINijB2rSjHrKz8';
        $send_data['page']        = 'pages/order/orderDetail/orderDetail?orderno=' . $orderNo;
        $send_data['form_id']     = $logPayRes['prepay_id'];
        $send_data['data']        = $data;
        // $send_data['emphasis_keyword']        = 'keyword2.DATA';
        // print_r(json_encode($send_data,true));die;
        $access_token = $this->getWeiXinAccessToken();
        if (empty($access_token)) {
            return ['code' => ''];
        }
        // echo $access_token;die;
        $requestUrl = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
        // print_r(json_encode($send_data,true));die;
        $result = $this->sendRequest2($requestUrl, $send_data);
        print_r($result);
        die;
    }

    function sendRequest2($requestUrl, $data = []) {
        $curl = curl_init();
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Content-Length:' . strlen($data)]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    
    public function quickSettlementAudio($conId, $buid = '', $skuId, $num, $goods_id, $userCouponId = 0){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3004'];
        }
        $balance = DbUser::getUserInfo(['id' => $uid, 'balance_freeze' => 2], 'user_identity,balance', true);
        $user_identity = $balance['user_identity'];
        $balance = $balance['balance'] ?? 0;
        $summary = $this->quickAudioSummary($uid, $buid, $skuId, $num, $goods_id, $userCouponId);
        if ($summary['code'] != '200') {
            return $summary;
        }
        $goods = $summary['goods_list'][0];
        $target_users = $goods['target_users']; //适用人群
        $user = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if ($user['user_identity'] < $target_users) {
            if ($target_users == 2){
                return ['code' => 3010, 'msg' => '该商品钻石会员及以上身份专享'];
            }elseif ($target_users == 3){
                return ['code' => 3011, 'msg' => '该商品创业店主及以上身份专享'];
            }elseif ($target_users == 4){
                return ['code' => 3012, 'msg' => '该商品合伙人及以上身份专享'];
            }
        }
        $shopList = DbShops::getShops([['uid', '=', $buid]], 'id,uid,shop_name,shop_image'); //购买的所有店铺信息列表
        if (empty($shopList)) {
            $shopList = DbShops::getShops([['id', '=', '1']], 'id,uid,shop_name,shop_image'); //不是boss就查总店
        }
        $shopList     = array_combine(array_column($shopList, 'id'), $shopList);
        $supplierId   = $summary['goods_list'][0]['supplier_id']; //供应商id
        $supplierList = DbGoods::getSupplier('id,name,image,title,desc', [['id', '=', $supplierId], ['status', '=', 1]]);
        $supplier = [];
        foreach ($supplierList as $sl) {
            $glList = [];
            $sList  = []; //门店
            foreach ($summary['goods_list'] as $gl) {
                if ($gl['supplier_id'] == $sl['id']) {
                    unset($gl['freight_id']);
                    unset($gl['cost_price']);
                    unset($gl['margin_price']);
                    unset($gl['weight']);
                    unset($gl['volume']);
                    unset($gl['spec']);
                    unset($gl['status']);
                    unset($gl['stock']);
                    unset($gl['supplier_id']);
                    unset($gl['buySum']);
                    unset($gl['shopBuySum']);
                    $glList[$gl['id']] = $gl;
//                    $shopKey           = array_keys($cart[$gl['id']]['track']);
                    $shopKey = array_keys($shopList);
                    foreach ($shopKey as $s) {
                        if (!isset($sList[$s])) {
                            $sList[] = $shopList[$s];
                        }
                    }
                }
            }
            foreach ($sList as $sk => $s) {
                $ggList = [];
                foreach ($glList as $kg => $g) {
                    if (in_array($s['id'], array_keys($shopList))) {
                        $bSum        = $num; //店铺购买的数量
                        $g['buySum'] = $bSum;
                        $ggList[] = $g;
                    }
                }
                $sList[$sk]['goods_list'] = $ggList;
            }
            $sl['shop_list'] = $sList;
            array_push($supplier, $sl);
        }
        unset($summary['goods_list']);
        $summary['supplier_list']      = $supplier;
        $summary['balance']            = $balance;
        return $summary;
    }

    private function quickAudioSummary($uid, $buid, $skuId, $num, $goods_id, $userCouponId) {
        $goods = DbGoods::getOneGoods(['id' => $goods_id, 'goods_type' => 2, 'status' => 1], 'goods_type,supplier_id,target_users,cate_id,goods_name,subtitle,status');
        if (empty($goods)) {
            return ['code' => '3005'];
        }
        $goodsSku = DbGoods::getAudioSkuRelation([['goods_id', '=', $goods_id],['id', '=', $skuId]]);
        if (empty($goodsSku)) {
            return ['code' => '3006'];
        }
        $goodsSku = $goodsSku[0];
        $shopInfo = DbShops::getShopInfo('id', ['uid' => $buid]);
        if (empty($shopInfo)) {
            $shopId = 1;
        } else {
            $shopId = $shopInfo['id'];
        }
        $goodsSku['supplier_id'] = $goods['supplier_id'];
        $goodsSku['goods_name']  = $goods['goods_name'];
        $goodsSku['goods_type']  = $goods['goods_type'];
        $goodsSku['target_users'] = $goods['target_users'];
        $goodsSku['subtitle']    = $goods['subtitle'];
        $goodsSku['status']      = $goods['status'];
        $goodsSku['buySum']      = $num;
        $goodsSku['end_time']    = $goodsSku['end_time'] * $num;
        $goodsSku['shopBuySum']  = [$shopId => $num];
        $totalGoodsPrice         = bcmul($goodsSku['retail_price'], $num, 2); //商品总价
        $couponPrice = 0;
        if (!empty($userCouponId)) {//有优惠券
            $userCoupon = DbCoupon::getUserCoupon([
                ['id', '=', $userCouponId],
                ['uid', '=', $uid],
                ['is_use', '=', 2],//未使用的
                ['end_time', '>', time()],//未过期的
            ], 'id,price,gs_id,level', true);
            if (empty($userCoupon)) {
                return ['code' => '3013'];
            }
            if ($userCoupon['level'] == 1) {//单品券
                if ($userCoupon['gs_id'] != $goodsSku['goods_id']) {
                    return ['code' => '3013'];
                }
            }
            if ($userCoupon['level'] == 2) {//专题券
                $couponGoodsIdList = DbGoods::getSubjectRelation([['subject_id', '=', $userCoupon['gs_id']]], 'goods_id');
                $couponGoodsIdList = array_column($couponGoodsIdList, 'goods_id');
                if (!in_array($goodsSku['goods_id'], $couponGoodsIdList)) {
                    return ['code' => '3013'];
                }
            }
            $couponPrice = $userCoupon['price'];
        }
        $distrProfits         = getDistrProfits($goodsSku['retail_price'], $goodsSku['cost_price'], 0);//可分配利润
        $goodsSku['rebate']   = $this->getRebate($distrProfits, $num);
        $goodsSku['integral'] = $this->getIntegral($goodsSku['retail_price'], $goodsSku['cost_price'], 0);
        if ($totalGoodsPrice < 0) {
            return ['code' => '3009'];
        }
        $discountMoney = $couponPrice;
        $totalPrice    = bcsub($totalGoodsPrice, $discountMoney, 2);
        if ($totalPrice <= 0) {
            $totalPrice = 0;
        }
        return ['code' => '200', 'goods_count' => $num, 'rebate_all' => $goodsSku['rebate'], 'total_goods_price' => $totalGoodsPrice, 'total_price' => $totalPrice, 'discount_money' => $discountMoney, 'goods_list' => [$goodsSku]];
    }

    public function quickCreateAudioOrder($conId, $buid, int $sku_id, int $num, int $goods_id, $payType, int $userCouponId = 0){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $balance = DbUser::getUserInfo(['id' => $uid, 'balance_freeze' => 2], 'user_identity,balance', true);
        $user_identity = $balance['user_identity'];
        $balance = $balance['balance'] ?? 0;
        $summary = $this->quickAudioSummary($uid, $buid, $sku_id, $num, $goods_id, $userCouponId);
        if ($summary['code'] != '200') {
            return $summary;
        }
        $goods = $summary['goods_list'][0];
        $target_users = $goods['target_users']; //适用人群
        $user = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if ($user['user_identity'] < $target_users) {
            if ($target_users == 2){
                return ['code' => 3010, 'msg' => '该商品钻石会员及以上身份专享'];
            }elseif ($target_users == 3){
                return ['code' => 3011, 'msg' => '该商品创业店主及以上身份专享'];
            }elseif ($target_users == 4){
                return ['code' => 3012, 'msg' => '该商品合伙人及以上身份专享'];
            }
        }
        $attr = [];
        foreach ($goods['audios'] as $key => $value) {
            $attr[] = $value['pivot']['audio_pri_id'];
        }
        $user_audioData = [];
        $user_audio_updateData = [];
        $audio_time = [];
        foreach ($attr as $at => $ar) {
            $u_audio = DbAudios::getUserAudio(['uid' => $uid,'audio_id' => $ar],'id,end_time',true);
            if ($u_audio) {//更新
                if ($u_audio['end_time'] >= time()){
                    $audio_time[$ar]      = $u_audio['end_time'] - time() + $goods['end_time'];
                    $u_audio['end_time'] += $goods['end_time'];
                }else {
                    $u_audio['end_time'] = $goods['end_time']+time();
                    $audio_time[$ar]     = $goods['end_time'];
                }
                $up_data = [
                    'end_time' => $u_audio['end_time'],
                ];
                $user_audio_updateData[$u_audio['id']] = $up_data;
                unset($user_audio);
            }else {
                $user_audio = [
                    'uid' => $uid,
                    'audio_id' => $ar,
                    'end_time' => $goods['end_time']+time(),
                ];
                array_push($user_audioData,$user_audio);
                $audio_time[$ar] = $goods['end_time'];
                unset($user_audio);
            }
        }
        $orderGoodsData = [];
        foreach ($goods['shopBuySum'] as $kgl => $gl) {
            for ($i = 0; $i < $gl; $i++) {
                $goodsData = [
                    'goods_id'     => $goods['goods_id'],
                    'goods_name'   => $goods['goods_name'],
                    'sku_id'       => $goods['id'],
                    'sup_id'       => $goods['supplier_id'],
                    'boss_uid'     => $buid,
                    'goods_type'   => $goods['goods_type'],
                    'goods_price'  => $goods['retail_price'],
                    'margin_price' => getDistrProfits($goods['retail_price'], $goods['cost_price'], 0),
                    'integral'     => $goods['integral'],
                    'goods_num'    => 1,
                    'buy_time'     => $goods['end_time'] / $num,
                    'sku_json'     => json_encode($attr),
                ];
                array_push($orderGoodsData, $goodsData);
            }
        }
        $supplier             = DbGoods::getSupplier('id,name', [['id', '=', $goods['supplier_id']]]);
        $supplierData         = [];
        foreach ($supplier as $sval) {
            $sval['express_money'] = 0;
            $sval['supplier_id']   = $sval['id'];
            $sval['supplier_name'] = $sval['name'];
            unset($sval['id']);
            unset($sval['name']);
            array_push($supplierData, $sval);
        }
         /*
         * 子订单内容
         */
        $orderNo        = createOrderNo(); //创建订单号
        $deductionMoney = 0; //商票抵扣金额
        $thirdMoney     = 0; //第三方支付金额
        $isPay          = false;
        $tradingData    = []; //交易日志
        if ($payType == 2) { //商票支付
            $userInfo = DbUser::getUserInfo(['id' => $uid], 'balance,balance_freeze', true);
            if ($userInfo['balance_freeze'] == '2') { //商票未冻结
                if ($summary['total_price'] > $userInfo['balance']) {
                    $deductionMoney = $userInfo['balance'] > 0 ? $userInfo['balance'] : 0; //可支付的商票
                    $thirdMoney     = bcsub($summary['total_price'], $deductionMoney, 2);
                } else {
                    $isPay          = true; //可以直接商票支付完成
                    $deductionMoney = $summary['total_price'];
                }
            } else {
                $thirdMoney = $summary['total_price'];
                if ($thirdMoney = 0) {
                    $isPay          = true; //无需支付
                }
            }
            $tradingData = [
                'uid'          => $uid,
                'trading_type' => 1,
                'change_type'  => 1,
                'money'        => -$deductionMoney,
                'befor_money'  => $userInfo['balance'],
                'after_money'  => bcsub($userInfo['balance'], $deductionMoney, 2),
                'message'      => '',
                'create_time'  => time(),
            ];
        } else if ($payType == 1) { //第三方支付
            $thirdMoney = $summary['total_price'];
            if ($thirdMoney == 0) {
                $isPay          = true; //无需支付
            }
        }
        $orderData = [
            'order_no'        => $orderNo,
            'third_order_id'  => 0,
            'uid'             => $uid,
            'order_type'      => 4,
            'order_status'    => $isPay ? 6 : 1,
            'order_money'     => bcadd($summary['total_price'], $summary['discount_money'], 2), //订单金额(优惠金额+实际支付的金额)
            'deduction_money' => $deductionMoney, //商票抵扣金额
            'pay_money'       => $summary['total_price'], //实际支付(第三方支付金额+商票抵扣金额)
            'goods_money'     => $summary['total_goods_price'], //商品金额
            'third_money'     => $thirdMoney, //第三方支付金额
            'discount_money'  => $summary['discount_money'], //优惠金额
            'pay_type'        => $payType,
            'third_pay_type'  => 2, //第三方支付类型1.支付宝 2.微信 3.银联 (暂时只能微信)
            'message'         => '',
            'pay_time'        => $isPay ? time() : 0,
        ];
        $stockSku = [$sku_id => $goods['buySum']];
        Db::startTrans();
        try {
            $orderId = DbOrder::addOrder($orderData);
            if (empty($orderId)) {
                Db::rollback();
                return ['code' => '3009'];
            }
            foreach ($supplierData as $sdkey => $sdval) {
                $supplierData[$sdkey]['order_id'] = $orderId;
            }
            $childOrder    = DbOrder::addOrderChilds($supplierData);
            $childSupplier = $childOrder->toArray();
            $childSupplier = array_column($childSupplier, 'id', 'supplier_id');
            foreach ($orderGoodsData as $ogdK => $ogdV) {
                $orderGoodsData[$ogdK]['order_child_id'] = $childSupplier[$ogdV['sup_id']];
            }
            DbOrder::addOrderGoods($orderGoodsData);
            DbGoods::decStock($stockSku);
            DbUser::modifyBalance($uid, $deductionMoney, $modify = 'dec');
            if (!empty($tradingData)) {
                DbOrder::addLogTrading($tradingData);
            }
            if (!empty($userCouponId)) {
                DbCoupon::updateUserCoupon([
                    'is_use'   => 1,
                    'order_id' => $orderId,
                ], $userCouponId);
            }
            if ($isPay) {
                $redisListKey = Config::get('rediskey.order.redisOrderBonus');
                $this->redis->rPush($redisListKey, $orderId);
                $redisaudioKey = Config::get('rediskey.audio.redisAudioVisual');
                if (!empty($user_audioData)) {
                    DbAudios::addUserAudio($user_audioData);
                }
                if (!empty($user_audio_updateData)) {
                    foreach ($user_audio_updateData as $up => $audio_u) {
                        DbAudios::updateUserAudio($audio_u,$up);
                    }
                }
                foreach ($audio_time as $audio => $time) {
                    $this->redis->set($redisaudioKey.$uid.':'.$audio, $audio);
                    $this->redis->expire($redisaudioKey.$uid.':'.$audio, $time);
                }
            }
            $this->resetUserInfo($uid);
            Db::commit();
            return ['code' => '200', 'order_no' => $orderNo, 'is_pay' => $isPay ? 1 : 2];
        } catch (\Exception $e) {
            // exception($e);
            Db::rollback();
            return ['code' => '3009'];
        }
    }
    
    /**
     * 查询订单商品是否有表格
     * @param $orderNo
     * @param $conId
     * @return array
     * @author rzc
     */
    public function isOrderSheet($orderNo, $conId){
        $uid = $this->getUidByConId($conId);
        // $uid = 23697;
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $order = DbOrder::getOrderDetail(['uid' => $uid, 'order_no' => $orderNo], 'order_status,goods_id');
        if (empty($order)){
            return ['code' => '3003'];
        }
        $goods = [];
        foreach ($order as $key => $value) {
            if ($value['order_status'] < 4){
                return ['code' => '3004'];//该订单未付款，无法提交表格
            }
            $goods[] = $value['goods_id'];
        }
        $goods = array_unique($goods);
        $goods_sheet = DbGoods::getGoodsList2([['id', 'in',$goods]],'id,goods_sheet');
        $sheet_list = [];
        foreach ($goods_sheet as $gs => $sheet) {
            if ($sheet['goods_sheet'] != 0){
                $sheet_info = $this->sheetInfo($sheet['goods_sheet']);
                if ($sheet === false) {
                    return ['code' => '3005'];
                }
                $sheet_info['goods_id'] = $sheet['id'];
                array_push($sheet_list,$sheet_info);
            }
        }
        return ['code' => '200','order_no' => $orderNo,'sheet_list' => $sheet_list];
        
    }

    private function sheetInfo($id){
        $sheet = DbGoods::getSheet([['id','=',$id]], 'id,name,create_time',true);
        if (empty($sheet)) {
            return false;
        }
        $sheet_options = DbGoods::getSheetOptionRelation(['sheet_id' => $sheet['id']],'*');
        $sheet_optionsList = [];
        foreach ($sheet_options as $key => $value) {
            $sheet_optionsList[] = $value['sheet_option'];
        }
        $sheet['options'] = $sheet_optionsList;
        return $sheet;
    }

    public function submitOrderSheet ($orderNo, $conId, $from){
        if (DbOrder::getOrderGoodsSheet(['order_no' => $orderNo],'id')) {
            return ['code' => '3008'];
        }
        $order_sheet = $this->isOrderSheet($orderNo, $conId);
        if ($order_sheet['code'] != '200') {
            return $order_sheet;
        }
        $sheet_list = $order_sheet['sheet_list'];
        $goodsIds = [];
        foreach ($sheet_list as $sheet => $list) {
            if (!isset($from[$list['goods_id']])) {
                return ['code' => '3006'];//表格选项不完整
            }
            foreach ($list['options'] as $ls => $options) {
                // if ($options['name']) {}
                    if (!isset($options['name'],$from[$list['goods_id']][$options['name']])) {
                        return ['code' => '3006'];//表格选项不完整
                    }
                    $value = $from[$list['goods_id']][$options['name']];
                    switch ($options['name']) {
                        case "name" :
                            $res = empty($value) ? false : true;
                            break;
                        case "idcard" :
                            $res = checkIdcard($value);
                            break;
                        case "medicare_card" :
                            $res = strlen($value) > 16 ? false : true;
                            break;
                        case "mobile":
                            $res = checkMobile($value);
                            break;
                        case "phone":
                            $res = checkMobile($value);
                            break;
                        default:
                            $res = true;
                    }
                    if ($res === false) {
                        return ['code' => '3007']; //信息校验失败
                    }
                    if ($options['name'] == 'rassenger_information') {
                        $rassenger_information = DbUser::getAirplanePassenger([['id','in',$value]],'*');
                        if (count($rassenger_information) != count(explode(',',$value))) {
                            return ['code' => '3010'];
                        }
                        $from[$list['goods_id']][$options['name']] = $rassenger_information;
                    }
            }
        }
        // print_r($from);die;
        $new_from = [];
        foreach ($from as $f => $rom) {
            $sheet = [];
            $sheet['from'] = json_encode($rom);
            $sheet['goods_id'] = $f;
            $sheet['order_no'] = $orderNo;
            $new_from[] = $sheet;
        }
        Db::startTrans();
        try {
            DbOrder::saveAllOrderGoodsSheet($new_from);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//领取失败
        }
    }

    public function getOrderSheet($orderNo, $goods_id = 0){
        $where = ['order_no' => $orderNo];
        $row = false;
        if (!empty($goods_id)) {
            $where = ['order_no' => $orderNo, 'goods_id' => $goods_id];
            $row = true;
        }
        $result = DbOrder::getOrderGoodsSheet($where, 'order_no,goods_id,from',$row);
        $from = [];
        if (!empty($result)) {
            $options = DbGoods::getSheetOption([],'name,title');
            $new_options = [];
            foreach ($options as $op => $ns) {
                $new_options[$ns['name']] = $ns['title'];
            }
            if (!empty($goods_id)){
                $result['from'] = json_decode($result['from'],true);
                foreach ($result['from'] as $key => $value) {
                    if (isset($key,$new_options[$key])) {
                        $from[$new_options[$key]] = $value;
                    }
                }
                unset($result['from']);
                $result['from'] = $from;
            }else {
                foreach ($result as $key => $value) {
                    $value['from'] = json_decode($value['from'],true);
                    foreach ($value['from'] as $vf => $vfrom) {
                        if (isset($key,$new_options[$vf])) {
                            $from[$new_options[$vf]] = $vfrom;
                        }
                    }
                    unset($result[$key]['from']);
                    $result[$key]['from'] = $from;
                    unset($from);
                }
            }
        }
        return ['code' => '200', 'fromList' => $result];
    }

    public function getAddOnItems($conId, $supplier_id, $skuIdList, $userAddressId, $page, $pageNum){
        $offset = ($page - 1) * $pageNum;
        $limit = ' LIMIT '.$offset.','.$pageNum;
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if ($this->checkCart($skuIdList, $uid) === false) {
            return ['code' => '3005']; //商品未加入购物车
        }
        
        $cart = $this->getCartGoods($skuIdList, $uid);
        
        if ($cart === false) {
            return ['code' => '3005'];
        }
        $userAddress      = DbUser::getUserAddress('id,city_id', ['id' => $userAddressId], true);
        if (empty($userAddress)){
            return ['code' => '3004'];
        }
        $cityId           = $userAddress['city_id'];
        $goodsSku = DbGoods::getSkuGoods([['goods_sku.id', 'in', $skuIdList], ['stock', '>', '0'], ['goods_sku.status', '=', '1']], 'id,goods_id,stock,freight_id,market_price,retail_price,cost_price,margin_price,weight,volume,sku_image,spec', 'id,supplier_id,goods_name,goods_type,target_users,subtitle,status,giving_rights');

        //商品库存不足
        // $diff     = array_diff($skuIdList, array_column($goodsSku, 'id'));
        // if (!empty($diff)) {
        //     $eGoodsList = [];
        //     foreach ($diff as $di) {
        //         $oneGoods = DbGoods::getOneGoods(['id' => $cart[$di]['goods_id']], 'id,goods_name,subtitle,image,giving_rights');
        //         $attrList = DbGoods::getAttrList([['id', 'in', explode(',', $cart[$di]['spec'])]], 'attr_name');
        //         $attrList = array_column($attrList, 'attr_name');
        //         array_push($eGoodsList, $oneGoods['goods_name'] . '(' . implode('、', $attrList) . ')');
        //     }
        //     return ['code' => '3004', 'goodsError' => $eGoodsList]; //商品售罄列表
        // }

        //此供应商统计
        $goodsList            = [];
        $freightPrice         = 0; //此供应商商品购买总价
        $freightCount         = 0; //此供应商商品购买数量
        $freightWeight        = 0; //此供应商商品购买重量
        $freightVolume        = 0; //此供应商商品购买体积
        foreach ($goodsSku as $key => $value) {
            $value['supplier_id'] = $value['goods']['supplier_id'];
            if ($value['supplier_id'] != $supplier_id){
                continue;
            }
            $cartSum       = intval($cart[$value['id']]['sum']);
            $fPrice        = bcmul($value['retail_price'], $cartSum, 2);
            $freightPrice  = bcadd($freightPrice, $fPrice, 2);
            $fCount        = bcmul(1, $cartSum, 2);
            $freightCount  = bcadd($freightCount, $fCount, 0);
            $fWeight       = bcmul($value['weight'], $cartSum, 2);
            $freightWeight = bcadd($freightWeight, $fWeight, 2);
            $fVolume       = bcmul($value['volume'], $cartSum, 2);
            $freightVolume = bcadd($freightVolume, $fVolume, 2);
            unset($value['goods']);
            array_push($goodsList, $value);
            $freight_id   = $value['freight_id'];//运费模板ID
        }

        // $goodsIdList = array_unique(array_column($goodsList, 'goods_id'));

        //商品不支持配送部分
        // $freight_id = array_values(array_unique(array_column($goodsList, 'freight_id')));
        $freightList   = DbGoods::getFreightAndDetail([['freight_id', '=', $freight_id]], $cityId, 'id,freight_id,price,after_price,total_price,unit_price', 'id,supid,stype', 'freight_detail_id,city_id', $freight_id);
        // $freightDiff   = array_values(array_diff($freight_id, array_keys($freightList)));
        // if (!empty($freightDiff)) {
        //     $eGoodsList = [];
        //     foreach ($freightDiff as $fd) {
        //         foreach ($goodsList as $gl) {
        //             if ($gl['freight_id'] == $fd) {
        //                 array_push($eGoodsList, $gl['goods_name'] . '(' . implode('、', $gl['attr']) . ')');
        //             }
        //         }
        //     }
        //     return ['code' => '3006', 'freightError' => $eGoodsList]; //商品不支持配送
        // }
        $freightList = array_values($freightList)[0];
        $supplierFreightText = DbGoods::getSupplierFreight('desc',$freight_id)['desc'];
        $addOnItems = 0;
        $freightSupplierPriceText = '';
        $orderBy = '';
        if ($freightList['total_price'] <= $freightPrice) { //该供应商的当前运费模版下购买的总价超过包邮价可以包邮
            $addOnItems = $freightList['total_price']-$freightPrice;
            $freightSupplierPriceText = '再凑'.$addOnItems.'元可享受包邮';
            $orderBy = ' ORDER BY abs(`gs`.`retail_price` - "'.$addOnItems.'") ASC';
        }
        if ($freightList['stype'] == 1) { //件数
            if ($freightList['unit_price'] > $freightCount) { //购买件数超过当前模版的满件包邮条件可以包邮
                $addOnItems = $freightCount-$freightList['unit_price'];
                $freightSupplierPriceText = '再凑'.$addOnItems.'件可享受包邮';
                $orderBy = ' ORDER BY `g`.`id` DESC';
            }
        } else if ($freightList['stype'] == 2) { //重量
            if ($freightList['unit_price'] > $freightWeight) { //购买重量超过当前模版的满件包邮条件可以包邮
                $addOnItems = $freightWeight-$freightList['unit_price'];
                $freightSupplierPriceText = '再凑'.$addOnItems.'千克可享受包邮';
                $orderBy = ' ORDER BY abs(`gs`.`weight` - "'.$addOnItems.'") ASC';
            }
        } else if ($freightList['stype'] == 3) { //体积
            if ($freightList['unit_price'] > $freightVolume) { //购买件数超过当前模版的满件包邮条件可以包邮
                $addOnItems = $freightVolume-$freightList['unit_price'];
                $freightSupplierPriceText = '再凑'.$addOnItems.'立方米可享受包邮';
                $orderBy = ' ORDER BY abs(`gs`.`volume` - "'.$addOnItems.'") ASC';
            }
        }
        if ($addOnItems <= 0) {
            $freightSupplierPriceText = '已满足包邮条件，是否继续凑单';
            // return ['code' => '3007', 'freightSupplierPriceText' => '已满足包邮条件，是否继续凑单'];//已满足包邮条件
        }
        $sql = 'SELECT DISTINCT(`gs`.`goods_id`),`g`.`id`,`g`.`supplier_id`,`g`.`cate_id`,`g`.`goods_name`,`g`.`goods_type`,`g`.`title`,`g`.`subtitle`,`g`.`image` 
        FROM  pz_goods_sku AS gs LEFT JOIN pz_goods AS g ON `gs`.`goods_id` = `g`.`id` 
        WHERE `gs`.`freight_id` = '.$freight_id.' AND `g`.`status` = 1 '. $orderBy . $limit;
        $result = Db::query($sql);
        
        foreach ($result as $key => $value) {
            /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            $result[$key]['spec'] = $goods_spec;
            $result[$key]['goods_sku'] = $goods_sku; */
            $result[$key]['goods_name'] = htmlspecialchars_decode($value['goods_name']);

            $brokerage       = [];
            $integral_active = [];
            // print_r($value['id']);die;
            if ($value['goods_type'] == 1) {
                $where                            = [['goods_id', '=', $value['id']], ['status', '=', 1], ['stock', '<>', 0]];
                $field                            = 'market_price';
                $result[$key]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                $field                            = 'retail_price';
                $result[$key]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                $retail_price                     = [];
                list($goods_spec, $goods_sku)     = $this->getGoodsSku($value['id']);

                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']]    = $sku['retail_price'];
                        $brokerage[$sku['id']]       = $sku['brokerage'];
                        $integral_active[$sku['id']] = $sku['integral_active'];
                    }
                    $result[$key]['min_brokerage']       = $brokerage[array_search(min($retail_price), $retail_price)];
                    $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
                    unset($retail_price);
                    $result[$key]['spec']      = $goods_spec;
                    $result[$key]['goods_sku'] = $goods_sku;
                } else {
                    $result[$key]['min_brokerage']       = 0;
                    $result[$key]['min_integral_active'] = 0;
                    $result[$key]['spec']                = [];
                    $result[$key]['goods_sku']           = [];
                }
            } else if ($value['goods_type'] == 2) {
                $goods_sku                        = DbGoods::getAudioSkuRelation([['goods_id', '=', $value['id']]]);
                $where                            = ['goods_id' => $value['id']];
                $field                            = 'market_price';
                $result[$key]['min_market_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                $field                            = 'retail_price';
                $result[$key]['min_retail_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']]    = $sku['retail_price'];
                        $brokerage[$sku['id']]       = bcmul(getDistrProfits($sku['retail_price'], $sku['cost_price'], 0), 0.75, 2);
                        $integral_active[$sku['id']] = bcmul(bcsub(bcsub($sku['retail_price'], $sku['cost_price'], 4), 0, 2), 2, 0);
                    }
                    // print_r($brokerage);
                    // print_r(array_search(min($retail_price), $retail_price));
                    $result[$key]['min_brokerage']       = $brokerage[array_search(min($retail_price), $retail_price)];
                    $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
                    // echo $value['id'];die;
                    unset($retail_price);
                } else {
                    $result[$key]['min_brokerage']       = 0;
                    $result[$key]['min_integral_active'] = 0;
                }
            }
        }
        return ['code' => '200', 'freightSupplierPriceText' => $freightSupplierPriceText, 'supplierFreightText' => $supplierFreightText, 'goodsList' => $result];//已满足包邮条件
    }

        /**
     * 获取商品SKU及规格名称等
     * @param $goods_id
     * @param $source
     * @return array
     * @author rzc
     */
    public function getGoodsSku($goods_id) {
        $field            = 'goods_id,spec_id';
        $where            = [["goods_id", "=", $goods_id]];
        $goods_first_spec = DbGoods::getOneGoodsSpec($where, $field, 1);
        $goods_spec       = [];
        if ($goods_first_spec) {
            $field = 'id,spe_name';
            foreach ($goods_first_spec as $key => $value) {
                $where  = ['id' => $value['spec_id']];
                $result = DbGoods::getOneSpec($where, $field);

                $goods_attr_field = 'attr_id';
                $goods_attr_where = ['goods_id' => $goods_id, 'spec_id' => $value['spec_id']];
                $goods_first_attr = DbGoods::getOneGoodsSpec($goods_attr_where, $goods_attr_field);
                $attr_where       = [];
                foreach ($goods_first_attr as $goods => $attr) {
                    $attr_where[] = $attr['attr_id'];
                }
                $attr_field     = 'id,spec_id,attr_name';
                $attr_where     = [['id', 'in', $attr_where], ['spec_id', '=', $value['spec_id']]];
                $result['list'] = DbGoods::getAttrList($attr_where, $attr_field);
                $goods_spec[]   = $result;
            }
        }
        $field = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,cost_price,integral_price,spec,sku_image';
        // $where = [["goods_id", "=", $goods_id],["status", "=",1],['retail_price','<>', 0]];
        $where     = [["goods_id", "=", $goods_id], ["status", "=", 1]];
        $goods_sku = DbGoods::getOneGoodsSku($where, $field);
        /* brokerage：佣金；计算公式：(商品售价-商品进价-其它运费成本-售价*0.006)*0.9*(钻石再补贴：0.75) */
        /* integral_active：积分；计算公式：(商品售价-商品进价-其它运费成本)*2 */
        foreach ($goods_sku as $goods => $sku) {
            $goods_sku[$goods]['brokerage']       = bcmul(getDistrProfits($sku['retail_price'], $sku['cost_price'], $sku['margin_price']), 0.75, 2);
            $goods_sku[$goods]['integral_active'] = bcmul(bcsub(bcsub($sku['retail_price'], $sku['cost_price'], 4), $sku['margin_price'], 2), 2, 0);
            $sku_json                             = DbGoods::getAttrList([['id', 'in', $sku['spec']]], 'attr_name');
            $sku_name                             = [];
            if ($sku_json) {
                foreach ($sku_json as $sj => $json) {
                    $sku_name[] = $json['attr_name'];
                }
            }
            $goods_sku[$goods]['sku_name'] = $sku_name;
        }
        return [$goods_spec, $goods_sku];
    }
}
/* {"appid":"wx112088ff7b4ab5f3","attach":"2","bank_type":"CMB_DEBIT","cash_fee":"600","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"lzlqdk6lgavw1a3a8m69pgvh6nwxye89","openid":"o83f0wAGooABN7MsAHjTv4RTOdLM","out_trade_no":"PAYSN201806201611392442","result_code":"SUCCESS","return_code":"SUCCESS","sign":"108FD8CE191F9635F67E91316F624D05","time_end":"20180620161148","total_fee":"600","trade_type":"JSAPI","transaction_id":"4200000112201806200521869502"} */
