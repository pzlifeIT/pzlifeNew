<?php

namespace app\common\action\index;

use app\common\action\notify\Note;
use app\common\action\qrcode\qrcodelogic;
use app\facade\DbGoods;
use app\facade\DbOfflineActivities;
use app\facade\DbOrder;
use app\facade\DbShops;
use app\facade\DbUser;
use Config;
use function Qiniu\json_decode;
use think\Db;

class OfflineActivities extends CommonIndex {
    private $redisHdluckyDraw;

    public function __construct() {
        parent::__construct();
        $this->redisHdluckyDraw     = Config::get('rediskey.active.redisHdluckyDraw') . ':' . date('Ymd');
        $this->redisHdluckyDrawLock = Config::get('rediskey.active.redisHdluckyDrawLock');
    }

    public function getOfflineActivities($id) {
        $offlineactivities = DbOfflineActivities::getOfflineActivities(['id' => $id], '*', true);
        if (empty($offlineactivities)) {
            return ['code' => '200', 'data' => []];
        }
        if (strtotime($offlineactivities['stop_time']) < time()) {
            return ['code' => '200', 'data' => []];
        }
        $goods = DbOfflineActivities::getOfflineActivitiesGoods(['active_id' => $id], 'goods_id');
        if (!empty($goods)) {
            $goodsid = [];
            foreach ($goods as $key => $value) {
                $goodsid[] = $value['goods_id'];
            }
            $goodslist = DbGoods::getGoods('id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image', '', '', [['status', '=', 1], ['id', 'IN', $goodsid]]);
            if (!empty($goodslist)) {
                foreach ($goodslist as $l => $list) {
                    /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
                    $result[$key]['spec'] = $goods_spec;
                    $result[$key]['goods_sku'] = $goods_sku; */
                    $where                             = ['goods_id' => $list['id'], 'status' => 1];
                    $field                             = 'market_price';
                    $goodslist[$l]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                    $field                             = 'retail_price';
                    $goodslist[$l]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);

                }
            }
            $offlineactivities['goods'] = $goodslist;

        } else {
            $goods = [];
        }
        return ['code' => '200', 'data' => $offlineactivities];
    }

    public function createOfflineActivitiesOrder($conId, $buid, $skuId, $num, $payType) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $summary = $this->quickSummary($uid, $buid, $skuId, $num);
        if ($summary['code'] != '200') {
            return $summary;
        }
        $shopInfo = DbShops::getShopInfo('id', ['uid' => $buid]);
        if (empty($shopInfo)) {
            $buid = 1;
        }
        $goods = $summary['goods_list'][0];
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
                    // 'margin_price' => getDistrProfits($goods['retail_price'], $goods['cost_price'], $goods['margin_price']),
                    'margin_price' => 0,
                    'integral'     => $goods['integral'],
                    'goods_num'    => 1,
                    'sku_json'     => json_encode($goods['attr']),
                ];
                array_push($orderGoodsData, $goodsData);
            }
        }

        $supplier     = DbGoods::getSupplier('id,name', [['id', '=', $summary['supplier_id']], ['status', '=', '1']]);
        $supplierData = [];
        foreach ($supplier as $sval) {
            $sval['express_money'] = $summary['total_freight_price'];
            $sval['supplier_id']   = $sval['id'];
            $sval['supplier_name'] = $sval['name'];
            unset($sval['id']);
            unset($sval['name']);
            array_push($supplierData, $sval);
        }

        $orderNo        = createOrderNo(); //创建订单号
        $deductionMoney = 0; //商券抵扣金额
        $thirdMoney     = 0; //第三方支付金额
        $discountMoney  = 0; //优惠金额
        $isPay          = false;
        $tradingData    = []; //交易日志
        if ($payType == 2) { //商券支付
            $userInfo = DbUser::getUserInfo(['id' => $uid], 'balance,balance_freeze', true);
            if ($userInfo['balance_freeze'] == '2') { //商券未冻结
                if ($summary['total_price'] > $userInfo['balance']) {
                    $deductionMoney = $userInfo['balance'] > 0 ? $userInfo['balance'] : 0; //可支付的商券
                    $thirdMoney     = bcsub($summary['total_price'], $deductionMoney, 2);
                } else {
                    $isPay          = true; //可以直接商券支付完成
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
            'order_type'      => 2,
            'uid'             => $uid,
            'order_status'    => $isPay ? 4 : 1,
            'order_money'     => bcadd($summary['total_price'], $discountMoney, 2), //订单金额(优惠金额+实际支付的金额)
            'deduction_money' => $deductionMoney, //商券抵扣金额
            'pay_money'       => $summary['total_price'], //实际支付(第三方支付金额+商券抵扣金额)
            'goods_money'     => $summary['total_goods_price'], //商品金额
            'third_money'     => $thirdMoney, //第三方支付金额
            'discount_money'  => $discountMoney, //优惠金额
            'pay_type'        => $payType,
            'third_pay_type'  => 2, //第三方支付类型1.支付宝 2.微信 3.银联 (暂时只能微信)
            'message'         => '',
            'pay_time'        => $isPay ? time() : 0,
        ];

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
            if ($isPay) {
                $redisListKey = Config::get('rediskey.order.redisOrderBonus');
                $this->redis->rPush($redisListKey, $orderId);
            }
            $this->resetUserInfo($uid);
            Db::commit();
/* 发送提货码 */
            // $orderNo = 'odr19053116375854519810';
            $orderRes = DbOrder::getOrder('id,order_type,order_status,order_no,uid', ['order_no' => $orderNo], true);
            if ($orderRes['order_status'] == 4) {
                $skus       = [];
                $sku_goods  = [];
                $goods_name = [];

                foreach ($orderGoodsData as $order => $list) {
                    if (in_array($list['sku_id'], $skus)) {
                        $sku_goods[$list['sku_id']] = $sku_goods[$list['sku_id']] + 1;
                    } else {
                        $skus[]                     = $list['sku_id'];
                        $sku_goods[$list['sku_id']] = 1;
                        $sku_json                   = json_decode($list['sku_json'], true);
                        // print_r($sku_json);die;
                        $goods_name[$list['sku_id']] = $list['goods_name'] . '规格[' . join(',', $sku_json) . ']';
                    }

                    // print_r($goods_name);die;
                }
                $message       = '您购买的商品：{';
                $admin_message = '订单号:' . $orderNo . '商品:{';
                foreach ($goods_name as $goods => $name) {
                    $message .= $name . '数量[' . $sku_goods[$goods] . ']';
                    $admin_message .= $name . '数量[' . $sku_goods[$goods] . ']';
                }
                $message       = $message . '}订单号为' . $orderNo . '取货码为：Off' . $orderRes['id'];
                $admin_message = $admin_message . '取货码为：Off' . $orderRes['id'];
                $user_phone    = DbUser::getUserInfo(['id' => $uid], 'mobile', true);
                $Note          = new Note;
                // print_r($message);
                // print_r($admin_message);
                // die;
                /* 取消发送取货码 */
                // $send1 = $Note->sendSms($user_phone['mobile'], $message);
                // $send2 = $Note->sendSms('17091858983', $admin_message);
                // print_r($send1);
                // print_r($send2);
                // die;
            }

            return ['code' => '200', 'order_no' => $orderNo, 'is_pay' => $isPay ? 1 : 2];
        } catch (\Exception $e) {
            Db::rollback();
            exception($e);
            return ['code' => '3009'];
        }
    }

    private function quickSummary($uid, $buid, $skuId, $num) {
        $goodsSku = DbGoods::getSkuGoods([['goods_sku.id', '=', $skuId], ['stock', '>', '0'], ['goods_sku.status', '=', '1']], 'id,goods_id,stock,freight_id,market_price,retail_price,cost_price,margin_price,weight,volume,sku_image,spec', 'id,supplier_id,goods_name,goods_type,subtitle,status');
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
        $goodsSku['subtitle']    = $goodsSku['goods']['subtitle'];
        $goodsSku['status']      = $goodsSku['goods']['status'];
        $attr                    = DbGoods::getAttrList([['id', 'in', explode(',', $goodsSku['spec'])]], 'attr_name');
        $goodsSku['attr']        = array_column($attr, 'attr_name');
        unset($goodsSku['goods']);
        $goodsSku['buySum']     = $num;
        $goodsSku['shopBuySum'] = [$shopId => $num];
        $totalGoodsPrice        = bcmul($goodsSku['retail_price'], $num, 2); //商品总价

        // $distrProfits         = getDistrProfits($goodsSku['retail_price'], $goodsSku['cost_price'], $goodsSku['margin_price']);//可分配利润
        $distrProfits       = 0;
        $goodsSku['rebate'] = $this->getRebate($distrProfits, $num);
        // $goodsSku['integral'] = $this->getIntegral($goodsSku['retail_price'], $goodsSku['cost_price'], $goodsSku['margin_price']);
        $goodsSku['integral'] = 0;
        $freightPrice         = bcmul($goodsSku['retail_price'], $num, 2); //同一个供应商模版id的商品价格累加
        $freightCount         = bcmul(1, $num, 2); //同一个供应商模版id的商品数量累加
        $freightWeight        = bcmul($goodsSku['weight'], $num, 2); //同一个供应商模版id的商品重量累加
        $freightVolume        = bcmul($goodsSku['volume'], $num, 2); //同一个供应商模版id的商品体积累加

        $totalFreightPrice = 0;

        if ($totalGoodsPrice <= 0) {
            return ['code' => '3009'];
        }
        $totalFreightPrice = 0; //供应商的运费
        // print_r($goodsSku['supplier_id']);die;

        $totalPrice = bcadd($totalGoodsPrice, $totalFreightPrice, 2);
        return ['code' => '200', 'goods_count' => $num, 'rebate_all' => $goodsSku['rebate'], 'total_goods_price' => $totalGoodsPrice, 'total_freight_price' => $totalFreightPrice, 'total_price' => $totalPrice, 'supplier_id' => $goodsSku['supplier_id'], 'goods_list' => [$goodsSku]];
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
     * 抽奖操作
     * @param $conId
     * @return array
     * @author zyr
     */
    public function luckyDraw($conId) {
        $u = [23739 => 2, 23740 => 4, 23769 => 6, 23770 => 8];
        // if (Config::get('conf.platform_conf')[Config::get('app.deploy')] == 2) { //测试环境
        //     $u = [23739 => 2, 26683 => 4, 26684 => 6, 26686 => 8];
        // }
        $hdNum = 1;
        $uid   = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3001'];
        }
//        $uid = 1;
        if ($this->initShopCount() === false) {
//            return ['code' => '3004'];
            $shopNum = 5; //所有奖品抽完
        } else if (in_array($uid, array_keys($u))) {
            $shopNum = $u[$uid];
        } else {
            $shopNum = $this->getDraw();
        }
        $this->redis->hSet($this->redisHdluckyDraw, $shopNum, bcsub($this->redis->hGet($this->redisHdluckyDraw, $shopNum), 1, 0));
        $luckyRes = DbOfflineActivities::getHdLucky(['uid' => $uid, 'hd_num' => $hdNum], 'id,shop_num', true);
        if (!empty($luckyRes)) {
            $this->redis->hSet($this->redisHdluckyDraw, $shopNum, bcadd($this->redis->hGet($this->redisHdluckyDraw, $shopNum), 1, 0));
            return ['code' => '3003', 'shop_num' => $luckyRes['shop_num']];
        }
        Db::startTrans();
        try {
            $LuckGoods = $this->LuckGoods();
            foreach ($LuckGoods['LuckGoods'] as $key => $value) {
                if ($value['shop_num'] == $shopNum) {
                    $goods_name = $value['goods_name'];
                    $image_path = $value['image_path'];
                }
            }
            $winning_id = DbOfflineActivities::addHdLucky([
                'uid'        => $uid,
                'shop_num'   => $shopNum,
                'hd_num'     => $hdNum,
                'goods_name' => $goods_name,
                'image_path' => $image_path,
            ]);
            Db::commit();
            return ['code' => '200', 'shop_num' => $shopNum, 'goods_name' => $goods_name, 'image_path' => $image_path, 'winning_id' => $winning_id];
        } catch (\Exception $e) {
            $this->redis->hSet($this->redisHdluckyDraw, $shopNum, bcadd($this->redis->hGet($this->redisHdluckyDraw, $shopNum), 1, 0));
            Db::rollback();
            return ['code' => '3005'];
        }
    }

    private function getDraw() {
        $shopList = [ //抽奖商品
            1 => 1400, //2元商券
            3 => 2800, //深海野生脆虾北极虾 1包
            7 => 4200, //君乐宝涨芝士 1袋
            5 => 10000, //优加竹浆本色手帕 1包
            2 => 10000, //还真精品茶具 1套
            4 => 10000, //君乐宝纯享随机口味 一箱
            6 => 10000, //玛蒙德格兰赛干红葡萄酒 2瓶
            8 => 10000, //克林伯瑞桃红葡萄酒 2瓶
        ];
        $num     = mt_rand(1, 10000);
        $shopNum = 0;
        foreach ($shopList as $sk => $sl) {
            if ($num <= $sl) {
                $shopNum = $sk;
                break;
            }
        }
        $result = $this->redis->hget($this->redisHdluckyDraw, $shopNum);
        if ($result <= 0) {
            $shopNum = $this->getDraw();
        }
        return $shopNum;
    }

    private function initShopCount() {
        $shopCount = [ //抽奖商品库存
            1 => 50,
            2 => 0,
            3 => 50,
            4 => 0,
            5 => 200,
            6 => 0,
            7 => 50,
            8 => 0,
        ];
        if (!$this->redis->exists($this->redisHdluckyDraw)) {
            $this->redis->hMSet($this->redisHdluckyDraw, $shopCount);
            $this->redis->expire($this->redisHdluckyDraw, strtotime(date('Y-m-d')) + 3600 * 24 - time()); //设置过期
        }
        $allNum = $this->redis->hGetAll($this->redisHdluckyDraw);
        $allNum = array_sum($allNum);
        if ($allNum <= 0) {
            return false;
        }
        return true;
    }

    public function LuckGoods() {
        return [
            'code'      => '200',
            'LuckGoods' => [
                [
                    'shop_num'   => 1,
                    'goods_name' => '2元商券',
                    'image_path' => 'https://webimages.pzlive.vip/1shangquan.jpg',
                ],
                [
                    'shop_num'   => 2,
                    'goods_name' => '还真精品茶具 1套',
                    'image_path' => 'https://webimages.pzlive.vip/1beizi.jpg',
                ],
                [
                    'shop_num'   => 3,
                    'goods_name' => '深海野生脆虾北极虾 1包',
                    'image_path' => 'https://webimages.pzlive.vip/1xia.png',
                ],
                [
                    'shop_num'   => 4,
                    'goods_name' => '君乐宝纯享随机口味 一箱',
                    'image_path' => 'https://webimages.pzlive.vip/cx.jpg',
                ],
                [
                    'shop_num'   => 5,
                    'goods_name' => '优加竹浆本色手帕 1包',
                    'image_path' => 'https://webimages.pzlive.vip/1zj.jpg',
                ],
                [
                    'shop_num'   => 6,
                    'goods_name' => '玛蒙德格兰赛干红葡萄酒 2瓶',
                    'image_path' => 'https://webimages.pzlive.vip/gh.jpg',
                ],
                [
                    'shop_num'   => 7,
                    'goods_name' => '君乐宝涨芝士 1袋',
                    'image_path' => 'https://webimages.pzlive.vip/1zzs.jpg',
                ],
                [
                    'shop_num'   => 8,
                    'goods_name' => '克林伯瑞桃红葡萄酒 2瓶',
                    'image_path' => 'https://webimages.pzlive.vip/th.jpg',
                ],
            ],
        ];
    }

    public function getHdLucky($big = '') {
        if ($big) {
            $result = DbOfflineActivities::getHdLucky([['shop_num', 'in', '2,4,6,8']], '*');

        } else {
            $result = DbOfflineActivities::getHdLucky([], '*', false, ['id' => 'desc'], 15);
        }
        if (empty($result)) {
            return ['code' => '3000'];
        }
        foreach ($result as $key => $value) {
            $user_phone = DbUser::getUserInfo(['id' => $value['uid']], 'nick_name,mobile', true);
            if (!empty($user_phone)) {
                $mobile = substr($user_phone['mobile'], 0, 3) . '*****' . substr($user_phone['mobile'], -4);
            }
            $result[$key]['user'] = $mobile;
        }
        return ['code' => '200', 'winnings' => $result];

    }

    public function getUserHdLucky($conId, $page, $pagenum) {
        $uid    = $this->getUidByConId($conId);
        $offect = ($page - 1) * $pagenum;
        $result = DbOfflineActivities::getHdLucky(['uid' => $uid], '*', false, ['id' => 'desc'], $offect . ',' . $pagenum);
        return ['code' => 200, 'winnings' => $result];
    }

    public function createOrderQrCode($data){
        
       
        $qrcodelogic = new qrcodelogic($data, 470, '取货二维码');
        $qrcodelogic->coverbackground('../public/background.png', 3, 3);
        $qrcodelogic->output();
        exit;
        print_r($qrcodelogic);die;
    }
}