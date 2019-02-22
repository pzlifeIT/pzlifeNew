<?php

namespace app\common\action\index;

use app\facade\DbUser;
use app\facade\DbShops;
use Config;
use app\facade\DbGoods;
use app\facade\DbOrder;
use think\Db;

class Order extends CommonIndex {
    private $redisCartUserKey;
    private $prefix = 'skuid:';

    public function __construct() {
        parent::__construct();
        $this->redisCartUserKey = Config::get('rediskey.cart.redisCartUserKey');
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
            return ['code' => '3003'];//没有可取消的订单
        }
        $orderChild    = DbOrder::getOrderChild('id', [['order_id', '=', $order['id']]]);
        $orderChildIds = array_column($orderChild, 'id');
        $orderGoods    = DbOrder::getOrderGoods('id,sku_id,goods_num', [['order_child_id', 'in', $orderChildIds]]);
        $data          = [
            'order_status' => 2,
        ];
        Db::startTrans();
        try {
            foreach ($orderGoods as $og) {
                DbGoods::modifyStock($og['sku_id'], $og['goods_num'], 'inc');//退回库存
            }
            DbOrder::updataOrder($data, $order['id']);//改订单状态
            DbUser::modifyBalance($uid, $order['deduction_money'], 'inc');//退还用户商票
            $this->resetUserInfo($uid);
            Db::commit();
            return ['code' => '200'];//取消成功
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//取消失败
        }
    }

    /**
     * 结算页面
     * @param $conId
     * @param $skuIdList
     * @param $userAddressId
     * @return array
     * @author zyr
     */
    public function createSettlement($conId, $skuIdList, int $userAddressId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if ($this->checkCart($skuIdList, $uid) === false) {
            return ['code' => '3005'];//商品未加入购物车
        }
        $cityId = 0;
        if (!empty($userAddressId)) {
            $userAddress = DbUser::getUserAddress('city_id', ['id' => $userAddressId], true);
            if (empty($userAddress)) {
                return ['code' => '3003'];
            }
            $cityId = $userAddress['city_id'];
        }
        $balance = DbUser::getUserInfo(['id' => $uid, 'balance_freeze' => 2], 'balance', true);
        $balance = $balance['balance'] ?? 0;
        $summary = $this->summary($uid, $skuIdList, $cityId);
        if ($summary['code'] != '200') {
            return $summary;
        }
        $cart = $this->getCartGoods($skuIdList, $uid);
        if ($cart == false) {
            return ['code' => '3005'];
        }
        $cartShops = array_column($cart, 'shops');//所有购买涉及的门店
        $shops     = [];
        array_map(function ($value) use (&$shops) {
            $shops = array_merge($shops, array_values($value));
        }, $cartShops);
        $shops          = array_values(array_unique($shops));//去重后的门店
        $shopList       = DbShops::getShops([['id', 'in', $shops]], 'id,uid,shop_name,shop_image');//购买的所有店铺信息列表
        $shopList       = array_combine(array_column($shopList, 'id'), $shopList);
        $goodsList      = $summary['goods_list'];
        $supplierIdList = array_unique(array_column($goodsList, 'supplier_id'));//要结算商品的供应商id列表
        $supplierList   = DbGoods::getSupplier('id,name,image,title,desc', [['id', 'in', $supplierIdList], ['status', '=', 1]]);
        $supplier       = [];
        foreach ($supplierList as $sl) {
            $glList = [];
            $sList  = [];//门店
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
                        $bSum        = $cart[$g['id']]['track'][$s['id']];//店铺购买的数量
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
        $summary['supplier_list'] = $supplier;
        $summary['balance']       = $balance;
        return $summary;
    }

    /**
     * 创建订单
     * @param $conId
     * @param $skuIdList 1,2,3
     * @param $userAddressId 1 收货地址id
     * @param $payType 2 支付方式1:所有第三方支付2:商票支付
     * @return array
     * @author zyr
     */
    public function createOrder($conId, $skuIdList, int $userAddressId, int $payType) {
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
            return ['code' => '3005'];//参数有误，商品未加入购物车
        }
        $summary = $this->summary($uid, $skuIdList, $cityId);
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
        $cartShops = array_column($cart, 'shops');//所有购买涉及的门店
        $shops     = [];
        array_map(function ($value) use (&$shops) {
            $shops = array_merge($shops, array_values($value));
        }, $cartShops);
        $shops    = array_values(array_unique($shops));//去重后的门店
        $shopList = DbShops::getShops([['id', 'in', $shops]], 'id,uid');//购买的所有店铺信息列表
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
                        'boss_uid'     => $shopList[$kgl],
                        'goods_price'  => $gList['retail_price'],
                        'margin_price' => $this->getDistrProfits($gList['retail_price'], $gList['cost_price'], $gList['margin_price']),
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
        $orderNo        = createOrderNo();//创建订单号
        $deductionMoney = 0;//商票抵扣金额
        $thirdMoney     = 0;//第三方支付金额
        $discountMoney  = 0;//优惠金额
        $isPay          = false;
        if ($payType == 2) {//商票支付
            $userInfo = DbUser::getUserInfo(['id' => $uid], 'balance,balance_freeze', true);
            if ($userInfo['balance_freeze'] == '2') {//商票未冻结
                if ($summary['total_price'] > $userInfo['balance']) {
                    $deductionMoney = $userInfo['balance'];//可支付的商票
                    $thirdMoney     = bcsub($summary['total_price'], $deductionMoney, 2);
                } else {
                    $isPay          = true;//可以直接商票支付完成
                    $deductionMoney = $summary['total_price'];
                }
            } else {
                $thirdMoney = $summary['total_price'];
            }
        } else if ($payType == 1) {//第三方支付
            $thirdMoney = $summary['total_price'];
        }
        $orderData = [
            'order_no'        => $orderNo,
            'third_order_id'  => 0,
            'uid'             => $uid,
            'order_status'    => $isPay ? 4 : 1,
            'order_money'     => bcadd($summary['total_price'], $discountMoney, 2),//订单金额(优惠金额+实际支付的金额)
            'deduction_money' => $deductionMoney,//商票抵扣金额
            'pay_money'       => $summary['total_price'],//实际支付(第三方支付金额+商票抵扣金额)
            'goods_money'     => $summary['total_goods_price'],//商品金额
            'third_money'     => $thirdMoney,//第三方支付金额
            'discount_money'  => $discountMoney,//优惠金额
            'pay_type'        => $payType,
            'third_pay_type'  => 2,//第三方支付类型1.支付宝 2.微信 3.银联 (暂时只能微信)
            'linkman'         => $userAddress['name'],
            'linkphone'       => $userAddress['mobile'],
            'province_id'     => $userAddress['province_id'],
            'city_id'         => $userAddress['city_id'],
            'area_id'         => $userAddress['area_id'],
            'address'         => $userAddress['address'],
            'message'         => '',
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
     * @return array
     * @author zyr
     */
    private function summary($uid, $skuIdList, $cityId) {
        $cart = $this->getCartGoods($skuIdList, $uid);
        if ($cart === false) {
            return ['code' => '3005'];
        }
        $goodsSku = DbGoods::getSkuGoods([['goods_sku.id', 'in', $skuIdList], ['stock', '>', '0'], ['goods_sku.status', '=', '1']], 'id,goods_id,stock,freight_id,market_price,retail_price,cost_price,margin_price,weight,volume,sku_image,spec', 'id,supplier_id,goods_name,goods_type,subtitle,status');
        $diff     = array_diff($skuIdList, array_column($goodsSku, 'id'));
        if (!empty($diff)) {
            $eGoodsList = [];
            foreach ($diff as $di) {
                $oneGoods = DbGoods::getOneGoods(['id' => $cart[$di]['goods_id']], 'id,goods_name,subtitle,image');
                $attrList = DbGoods::getAttrList([['id', 'in', explode(',', $cart[$di]['spec'])]], 'attr_name');
                $attrList = array_column($attrList, 'attr_name');
                array_push($eGoodsList, $oneGoods['goods_name'] . '(' . implode('、', $attrList) . ')');
            }
            return ['code' => '3004', 'goodsError' => $eGoodsList];//商品售罄列表
        }
        $goodsOversold        = [];//库存不够商品列表
        $goodsList            = [];
        $freightPrice         = [];//各个运费模版的商品购买价格
        $freightCount         = [];//各个运费模版的购买数量
        $freightWeight        = [];//各个运费模版的购买重量
        $freightVolume        = [];//各个运费模版的购买体积
        $rebateAll            = 0;//所有商品钻石返利总和
        $totalGoodsPrice      = 0;//所有商品总价
        $goodsCount           = 0;//购买商品总数
        $totalFreightPrice    = 0;//总运费
        $freightSupplierPrice = [];//各个供应商的运费
        foreach ($goodsSku as $value) {
            $value['supplier_id'] = $value['goods']['supplier_id'];
            $value['goods_name']  = $value['goods']['goods_name'];
            $value['goods_type']  = $value['goods']['goods_type'];
            $value['subtitle']    = $value['goods']['subtitle'];
            $value['status']      = $value['goods']['status'];
            $attr                 = DbGoods::getAttrList([['id', 'in', explode(',', $value['spec'])]], 'attr_name');
            $value['attr']        = array_column($attr, 'attr_name');
            unset($value['goods']);
            $cartSum = intval($cart[$value['id']]['sum']);
            if ($cartSum > $value['stock']) {//购买数量超过库存
                array_push($goodsOversold, $value['goods_name'] . '(' . implode('、', $value['attr']) . ')');
            }
            $value['buySum']                     = $cartSum;
            $value['shopBuySum']                 = $cart[$value['id']]['track'];
            $fPrice                              = bcmul($value['retail_price'], $cartSum, 2);
            $freightPrice[$value['freight_id']]  = isset($freightPrice[$value['freight_id']]) ? bcadd($freightPrice[$value['freight_id']], $fPrice, 2) : $fPrice;//同一个供应商模版id的商品价格累加
            $fCount                              = bcmul(1, $cartSum, 2);
            $freightCount[$value['freight_id']]  = isset($freightCount[$value['freight_id']]) ? bcadd($freightCount[$value['freight_id']], $fCount, 0) : $fCount;//同一个供应商模版id的商品数量累加
            $fWeight                             = bcmul($value['weight'], $cartSum, 2);
            $freightWeight[$value['freight_id']] = isset($freightWeight[$value['freight_id']]) ? bcadd($freightWeight[$value['freight_id']], $fWeight, 0) : $fWeight;//同一个供应商模版id的商品重量累加
            $fVolume                             = bcmul($value['volume'], $cartSum, 2);
            $freightVolume[$value['freight_id']] = isset($freightVolume[$value['freight_id']]) ? bcadd($freightVolume[$value['freight_id']], $fVolume, 0) : $fVolume;//同一个供应商模版id的商品体积累加
            $distrProfits                        = $this->getDistrProfits($value['retail_price'], $value['cost_price'], $value['margin_price']);//可分配利润
            $value['rebate']                     = $this->getRebate($distrProfits, $cartSum);
            $value['integral']                   = $this->getIntegral($value['retail_price'], $value['cost_price'], $value['margin_price']);
            $rebateAll                           = bcadd($this->getRebate($distrProfits, $cartSum), $rebateAll, 2);//钻石返利
//            $integralAll                         = bcadd($this->getIntegral($value['retail_price'], $value['cost_price'], $value['margin_price'], $cartSum));
            $totalGoodsPrice = bcadd(bcmul($value['retail_price'], $cartSum, 2), $totalGoodsPrice, 2);//商品总价
            $goodsCount      += $cartSum;
            array_push($goodsList, $value);
        }
        if (!empty($goodsOversold)) {
            return ['code' => '3007', 'goods_oversold' => $goodsOversold];//库存不足商品
        }
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
                return ['code' => '3006', 'freightError' => $eGoodsList];//商品不支持配送
            }
            foreach ($freightList as $flk => $fl) {
                if ($fl['total_price'] <= $freightPrice[$flk]) {//该供应商的当前运费模版下购买的总价超过包邮价可以包邮
                    $freightSupplierPrice[$fl['supid']] = isset($freightSupplierPrice[$fl['supid']]) ? bcadd($freightSupplierPrice[$fl['supid']], 0, 0) : 0;
                    continue;
                }
                $price = 0;
                if ($fl['stype'] == 1) {//件数
                    if ($fl['unit_price'] <= $freightCount[$flk]) {//购买件数超过当前模版的满件包邮条件可以包邮
                        continue;
                    }
                    $price = bcadd(bcmul(bcsub($freightCount[$flk], 1, 2), $fl['after_price'], 2), $fl['price'], 2);
                } else if ($fl['stype'] == 2) {//重量
                    if ($fl['unit_price'] <= $freightWeight[$flk]) {//购买重量超过当前模版的满件包邮条件可以包邮
                        continue;
                    }
                    $price = bcadd(bcmul(bcsub($freightWeight[$flk], 1, 2), $fl['after_price'], 2), $fl['price'], 2);
                } else if ($fl['stype'] == 3) {//体积
                    if ($fl['unit_price'] <= $freightVolume[$flk]) {//购买件数超过当前模版的满件包邮条件可以包邮
                        continue;
                    }
                    $price = bcadd(bcmul(bcsub($freightVolume[$flk], 1, 2), $fl['after_price'], 2), $fl['price'], 2);
                }
                $freightSupplierPrice[$fl['supid']] = isset($freightSupplierPrice[$fl['supid']]) ? bcadd($freightSupplierPrice[$fl['supid']], $price, 0) : $price;
                $totalFreightPrice                  = bcadd($totalFreightPrice, $price, 2);
            }
            /* 运费模版 end */
        }
        $totalPrice = bcadd($totalGoodsPrice, $totalFreightPrice, 2);
        return ['code' => '200', 'goods_count' => $goodsCount, 'rebate_all' => $rebateAll, 'total_goods_price' => $totalGoodsPrice, 'total_freight_price' => $totalFreightPrice, 'total_price' => $totalPrice, 'goods_list' => $goodsList, 'freight_supplier_price' => $freightSupplierPrice];
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
     * 获取钻石返利
     * @param $distrProfits 可分配利润
     * @param int $num 商品数量
     * @return string
     * @author zyr
     */
    private function getRebate($distrProfits, $num = 1) {
        $rebate = bcmul($distrProfits, 0.75, 2);
        $result = bcmul($rebate, $num, 2);
        return $result;
    }

    /**
     * 获取商品的可分配利润
     * @param $retailPrice
     * @param $costPrice
     * @param $marginPrice
     * @return string
     * @author zyr
     */
    private function getDistrProfits($retailPrice, $costPrice, $marginPrice) {
        $profits      = bcsub(bcsub($retailPrice, $costPrice, 2), $marginPrice, 2);//利润(售价-进价-其他成本)
        $distrProfits = bcmul($profits, 0.9, 2);//可分配利润
        return $distrProfits;
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
        $profits  = bcsub(bcsub($retailPrice, $costPrice, 2), $marginPrice, 2);//利润(售价-进价-其他成本)
        $integral = bcmul($profits, 2, 0);
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
        // $uid = $this->getUidByConId($conId);
        $uid = 23697;
        if (empty($uid)) {
            return ['code' => '3005'];
        }
        $offset = ($page - 1) * $pagenum;
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $field = 'id,order_no,third_order_id,uid,order_status,order_money,deduction_money,pay_money,goods_money,discount_money,deduction_money,third_money';
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
            $order_child = DbOrder::getOrderChild('*', ['order_id' => $value['id']]);

            $express_money = 0;
            foreach ($order_child as $order => $child) {
                $order_goods     = DbOrder::getOrderGoods('goods_id,goods_name,order_child_id,sku_id,sup_id,goods_type,goods_price,margin_price,integral,goods_num,sku_json', ['order_child_id' => $child['id']], false, true);
                $order_goods_num = DbOrder::getOrderGoods('sku_id,COUNT(goods_num) as goods_num', ['order_child_id' => $child['id']], 'sku_id');
                foreach ($order_goods as $og => $goods) {
                    foreach ($order_goods_num as $ogn => $goods_num) {
                        if ($goods_num['sku_id'] == $goods['sku_id']) {
                            $order_goods[$og]['goods_num'] = $goods_num['goods_num'];
                            $order_goods[$og]['sku_json']  = json_decode($order_goods[$og]['sku_json'], true);
                        }
                    }
                }
                // dump( Db::getLastSql());die;

                $order_child[$order]['order_goods'] = $order_goods;

                $express_money += $child['express_money'];
            }
            $result[$key]['express_money'] = $express_money;
            $result[$key]['order_child']   = $order_child;
        }
        return ['code' => '200', 'order_list' => $result];
    }

    /**
     * 创建用户权益订单
     * @param $conId
     * @param $user_type
     * @return array
     * @author rzc
     */
    public function createMemberOrder($conId, $user_type, $pay_type) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        /* 计算支付金额 */
        if ($user_type == 1) {
            $pay_money = 100;
        } elseif ($user_type == 2) {
            $pay_money = 15000;
        } elseif ($user_type == 3) {
            $user_type = 1;
            $pay_money = 1000;
        }
        /* 判断会员身份，低于当前层级可购买升级 */
        $user_identity = DbUser::getUserOne(['id' => $uid], 'user_identity')['user_identity'];/* 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人 */

        if ($user_identity >= $user_type + 1) {
            return ['code' => '3003', 'msg' => '购买权益等级低于当前权益'];
        }

        /* 先查询是否有已存在未结算订单 */
        $has_member_order = DbOrder::getMemberOrder(['uid' => $uid, 'user_type' => $user_type, 'pay_status' => 1], '*', true);
        if ($has_member_order) {
            /* 判断订单金额是否与最新订单金额相等 */
            if ($pay_money != $has_member_order['pay_money']) {
                $has_member_order['pay_money'] = $pay_money;
                /* 更新支付金额 */
                DbOrder::updateMemberOrder(['pay_money' => $pay_money, 'pay_type' => $pay_type], ['uid' => $uid, 'user_type' => $user_type, 'pay_status' => 1]);
            }
            return ['code' => '200', 'order_data' => $has_member_order];
        } else {
            $order              = [];
            $order['order_no']  = createOrderNo('mem');
            $order['uid']       = $uid;
            $order['user_type'] = $user_type;
            $order['pay_money'] = $pay_money;
            $order['pay_type']  = $pay_type;
            DbOrder::addMemberOrder($order);
            return ['code' => '200', 'order_data' => $order];
        }

    }
}
/* {"appid":"wx112088ff7b4ab5f3","attach":"2","bank_type":"CMB_DEBIT","cash_fee":"600","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"lzlqdk6lgavw1a3a8m69pgvh6nwxye89","openid":"o83f0wAGooABN7MsAHjTv4RTOdLM","out_trade_no":"PAYSN201806201611392442","result_code":"SUCCESS","return_code":"SUCCESS","sign":"108FD8CE191F9635F67E91316F624D05","time_end":"20180620161148","total_fee":"600","trade_type":"JSAPI","transaction_id":"4200000112201806200521869502"} */
