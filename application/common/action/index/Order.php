<?php

namespace app\common\action\index;

use app\facade\DbShops;
use Config;
use app\facade\DbGoods;
use think\Db;

class Order extends CommonIndex {
    private $redisCartUserKey;
    private $prefix = 'skuid:';

    public function __construct() {
        parent::__construct();
        $this->redisCartUserKey = Config::get('rediskey.cart.redisCartUserKey');
    }

    /**
     * 结算页面
     * @param $conId
     * @param $skuIdList
     * @param $cityId
     * @return array
     * @author zyr
     */
    public function createSettlement($conId, $skuIdList, $cityId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if ($this->checkCart($skuIdList, $uid) === false) {
            return ['code' => '3005'];//商品未加入购物车
        }
        $summary = $this->summary($uid, $skuIdList, $cityId);
        if ($summary['code'] != '200') {
            return $summary;
        }
        $cart      = $this->getCartGoods($skuIdList, $uid);
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
        return $summary;
    }

    public function createOrder($conId, $skuIdList, $cityId) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        if ($this->checkCart($skuIdList, $uid) === false) {
            return ['code' => '3005'];//参数有误，商品未加入购物车
        }
        $summary = $this->summary($uid, $skuIdList, $cityId);
        print_r($summary);
        die;
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
        $cart     = $this->getCartGoods($skuIdList, $uid);
        $goodsSku = DbGoods::getSkuGoods([['goods_sku.id', 'in', $skuIdList], ['stock', '>', '0']], 'id,goods_id,stock,freight_id,market_price,retail_price,cost_price,margin_price,integral_active,weight,volume,sku_image,spec', 'id,supplier_id,goods_name,goods_type,subtitle,status');
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
        $goodsOversold     = [];//库存不够商品列表
        $goodsList         = [];
        $freightPrice      = [];//各个运费模版的运费价格
        $freightCount      = [];//各个运费模版的购买数量
        $freightWeight     = [];//各个运费模版的购买重量
        $freightVolume     = [];//各个运费模版的购买体积
        $rebateAll         = 0;//所有商品钻石返利总和
        $totalGoodsPrice   = 0;//所有商品总价
        $goodsCount        = 0;//购买商品总数
        $totalFreightPrice = 0;//总运费
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
            $fPrice                              = bcmul($value['retail_price'], $cartSum, 2);
            $freightPrice[$value['freight_id']]  = isset($freightPrice[$value['freight_id']]) ? bcadd($freightPrice[$value['freight_id']], $fPrice, 2) : $fPrice;//同一个供应商模版id的商品价格累加
            $fCount                              = bcmul(1, $cartSum, 2);
            $freightCount[$value['freight_id']]  = isset($freightCount[$value['freight_id']]) ? bcadd($freightCount[$value['freight_id']], $fCount, 0) : $fCount;//同一个供应商模版id的商品数量累加
            $fWeight                             = bcmul($value['weight'], $cartSum, 2);
            $freightWeight[$value['freight_id']] = isset($freightWeight[$value['freight_id']]) ? bcadd($freightWeight[$value['freight_id']], $fWeight, 0) : $fWeight;//同一个供应商模版id的商品重量累加
            $fVolume                             = bcmul($value['volume'], $cartSum, 2);
            $freightVolume[$value['freight_id']] = isset($freightVolume[$value['freight_id']]) ? bcadd($freightVolume[$value['freight_id']], $fVolume, 0) : $fVolume;//同一个供应商模版id的商品体积累加
            $distrProfits                        = $this->getDistrProfits($value['retail_price'], $value['cost_price'], $value['margin_price']);//可分配利润
            $value['rebate']                     = $this->getRebate($distrProfits);
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
                $totalFreightPrice = bcadd($totalFreightPrice, $price, 2);
            }
            /* 运费模版 end */
        }
        $totalPrice = bcadd($totalGoodsPrice, $totalFreightPrice);
        return ['code' => '200', 'goods_count' => $goodsCount, 'rebate_all' => $rebateAll, 'total_goods_price' => $totalGoodsPrice, 'total_freight_price' => $totalFreightPrice, 'total_price' => $totalPrice, 'goods_list' => $goodsList];
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
        $profits      = bcsub(bcsub($retailPrice, $costPrice), $marginPrice, 2);//利润(售价-进价-其他成本)
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
        $profits  = bcsub(bcsub($retailPrice, $costPrice), $marginPrice, 2);//利润(售价-进价-其他成本)
        $integral = bcmul($profits, 2, 0);
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
        $buyList      = $this->redis->hMGet($this->redisCartUserKey . $uid, $skuIdListNew);
        $result       = [];
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
        if(!$this->redis->exists($this->redisCartUserKey . $uid)){
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
}