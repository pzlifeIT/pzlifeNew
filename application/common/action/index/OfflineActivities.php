<?php

namespace app\common\action\index;

use app\common\action\notify\Note;
use app\common\action\qrcode\qrcodelogic;
use app\facade\DbCoupon;
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
        $goods        = $summary['goods_list'][0];
        $target_users = $goods['target_users']; //适用人群
        $user         = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if ($user['user_identity'] < $target_users) {
            if ($target_users == 2) {
                return ['code' => 3010, 'msg' => '该商品钻石会员及以上身份专享'];
            } elseif ($target_users == 3) {
                return ['code' => 3011, 'msg' => '该商品创业店主及以上身份专享'];
            } elseif ($target_users == 4) {
                return ['code' => 3012, 'msg' => '该商品合伙人及以上身份专享'];
            }
        }
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
    public function luckyDraw($conId, $hd_id, $timekey) {
        // $u = [23739 => 2, 23740 => 4, 23769 => 6, 23770 => 8];
        // if (Config::get('conf.platform_conf')[Config::get('app.deploy')] == 2) { //测试环境
        //     $u = [23739 => 2, 26683 => 4, 26684 => 6, 26686 => 8];
        // }

        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3001'];
        }
//        $uid = 1;
        if (empty(DbCoupon::getHd(['status' => 2], 'id', true))) {
            return ['code' => '3006'];
        }
        if ($this->initShopCount($hd_id, $timekey) === false) {
            return ['code' => '3004'];
        }
        //  else if (in_array($uid, array_keys($u))) {
        //     $shopNum = $u[$uid];
        // }
        else {
            $shopNum = $this->getDraw($hd_id, $uid);
        }
        $have_goods = DbCoupon::getHdGoods(['id' => $shopNum], 'kind,title,image,debris,stock,has,winnings_number,relevance', true);
        $new_has    = $have_goods['has'] + 1;
        Db::startTrans();
        try {
            if ($have_goods['debris'] > 1) { //碎片类型的奖品
                $has_winning = DbOfflineActivities::getWinning([['shop_num', '=', $shopNum], ['need_debris', '>', 1], ['uid', '=', $uid]], 'id,uid,debris', true);
                if (!empty($has_winning)) {
                    $new_debris = $has_winning['debris'] + 1;
                    DbOfflineActivities::updateWinning(['debris' => $new_debris], $has_winning['id']);
                    $winning_id = $has_winning['id'];
                } else {
                    $winning_id = DbOfflineActivities::addHdLucky([
                        'uid'         => $uid,
                        'shop_num'    => $shopNum,
                        'hd_num'      => $hd_id,
                        'kind'        => $have_goods['kind'],
                        'relevance'   => $have_goods['relevance'],
                        'debris'      => 1,
                        'need_debris' => $have_goods['debris'],
                        'goods_name'  => $have_goods['title'],
                        'image_path'  => $have_goods['image'],
                    ]);
                }

            } else {
                //积分
                $status = 1;
                if ($have_goods['kind'] == 4) {
                    $user_integral = [];
                    $user_integral = [
                        'result_integral' => $have_goods['relevance'],
                        'status'          => 2,
                        'uid'             => $uid,
                        'stype'           => 4,
                        'message'         => '抽奖积分到账',
                    ];
                    $status = 2;
                    DbUser::addLogIntegral($user_integral);
                    DbUser::modifyIntegral($uid, $have_goods['relevance'], 'inc');
                    $userRedisKey = Config::get('rediskey.user.redisKey');
                    $this->redis->del($userRedisKey . 'userinfo:' . $uid);
                }
                if ($have_goods['kind'] == 5) {
                    $has_winning = DbOfflineActivities::getWinning([['shop_num', '=', $shopNum], ['uid', '=', $uid]], 'id,uid,debris', true);
                    if (!empty($has_winning)) {
                        $new_debris = $has_winning['debris'] + 1;
                        DbOfflineActivities::updateWinning(['debris' => $new_debris], $has_winning['id']);
                        $winning_id = $has_winning['id'];
                    } else {
                        $winning_id = DbOfflineActivities::addHdLucky([
                            'uid'         => $uid,
                            'shop_num'    => $shopNum,
                            'hd_num'      => $hd_id,
                            'kind'        => $have_goods['kind'],
                            'debris'      => 1,
                            'need_debris' => $have_goods['debris'],
                            'goods_name'  => $have_goods['title'],
                            'image_path'  => $have_goods['image'],
                            'relevance'   => $have_goods['relevance'],
                        ]);
                    }
                } else {
                    $winning_id = DbOfflineActivities::addHdLucky([
                        'uid'        => $uid,
                        'shop_num'   => $shopNum,
                        'hd_num'     => $hd_id,
                        'kind'       => $have_goods['kind'],
                        'relevance'  => $have_goods['relevance'],
                        'goods_name' => $have_goods['title'],
                        'image_path' => $have_goods['image'],
                        'status'     => $status,
                    ]);
                }
            }
            DbCoupon::updateHdGoods(['has' => $new_has], $shopNum);
            Db::commit();
            return ['code' => '200', 'shop_num' => $shopNum, 'goods_name' => $have_goods['title'], 'image_path' => $have_goods['image'], 'winning_id' => $winning_id, 'need_debris' => $have_goods['debris'], 'kind' => $have_goods['kind'], 'relevance' => $have_goods['relevance']];
        } catch (\Exception $e) {
            // $this->redis->hSet($this->redisHdluckyDraw, $shopNum, bcadd($this->redis->hGet($this->redisHdluckyDraw, $shopNum), 1, 0));
            exception($e);
            Db::rollback();
            return ['code' => '3005'];
        }
    }

    private function getDraw($hd_id, $uid) {
        // $shopList = [ //抽奖商品
        //     1 => 1400, //2元商券
        //     3 => 2800, //深海野生脆虾北极虾 1包
        //     7 => 4200, //君乐宝涨芝士 1袋
        //     5 => 10000, //优加竹浆本色手帕 1包
        //     2 => 10000, //还真精品茶具 1套
        //     4 => 10000, //君乐宝纯享随机口味 一箱
        //     6 => 10000, //玛蒙德格兰赛干红葡萄酒 2瓶
        //     8 => 10000, //克林伯瑞桃红葡萄酒 2瓶
        // ];
        $shopList  = [];
        $LuckGoods = DbCoupon::getHdGoods(['hd_id' => $hd_id, 'status' => 1], 'id,probability,debris,stock,has,winnings_number', false);
        foreach ($LuckGoods as $key => $value) {
            if ($value['debris'] * $value['stock'] - $value['has'] > 0) {
                $has_shopNum = DbOfflineActivities::sumWinning(['uid' => $uid, 'hd_num' => $hd_id, 'shop_num' => $value['id']], 'debris');
                if ($has_shopNum < $value['debris'] * $value['winnings_number']) {
                    $shopList[$value['id']] = bcmul($value['probability'], 10000000);
                }
            }

        }
        if (empty($shopList)) {
            return ['code' => '3008'];
        }
        asort($shopList);
        $max     = max($shopList);
        $num     = mt_rand(1, $max);
        $shopNum = 0;
        foreach ($shopList as $sk => $sl) {
            if ($num <= $sl) {
                $shopNum = $sk;
                break;
            }
        }
        return $shopNum;
    }

    private function initShopCount($hd_id, $timekey) {
        // $shopCount = [ //抽奖商品库存
        //     1 => 50,
        //     2 => 0,
        //     3 => 50,
        //     4 => 0,
        //     5 => 200,
        //     6 => 0,
        //     7 => 50,
        //     8 => 0,
        // ];
        // if (!$this->redis->exists($this->redisHdluckyDraw)) {
        //     $this->redis->hMSet($this->redisHdluckyDraw, $shopCount);
        //     $this->redis->expire($this->redisHdluckyDraw, strtotime(date('Y-m-d')) + 3600 * 24 - time()); //设置过期
        // }
        // $allNum = $this->redis->hGetAll($this->redisHdluckyDraw);
        if (!DbCoupon::getHdGoods([['hd_id', '=', $hd_id], ['update_time', '>', $timekey]], 'id', true)) {
            return ['code' => '3006'];
        }
        // echo 1;die;
        $LuckGoods = DbCoupon::getHdGoods(['hd_id' => $hd_id, 'status' => 1], 'id,debris,stock,has');
        $allNum    = [];
        foreach ($LuckGoods as $key => $value) {
            $allNum[] = $value['debris'] * $value['stock'] - $value['has'];
        }
        $allNum = array_sum($allNum);
        if ($allNum <= 0) {
            return false;
        }
        return true;
    }

    public function LuckGoods() {

        $luckhd = DbCoupon::getHd(['status' => 2], 'id', true);
        if (!empty($luckhd)) {
            $LuckGoods = DbCoupon::getHdGoods(['hd_id' => $luckhd['id'], 'status' => 1], 'id,image,kind,title,debris,relevance', false, ['order' => 'asc']);
            return [
                'code'      => '200',
                'hd_id'     => $luckhd['id'],
                'LuckGoods' => $LuckGoods,
            ];
        }
        return [
            'code' => '3001',
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

    public function getUserHdLucky($conId, $page, $pagenum, $is_debris = 0, $id = 0) {
        $uid    = $this->getUidByConId($conId);
        $offect = ($page - 1) * $pagenum;
        if ($is_debris) {
            $luckhd = DbCoupon::getHd(['status' => 2], 'id', true);
            if (!empty($luckhd)) {
                $LuckGoods = DbCoupon::getHdGoods([['hd_id', '=', $luckhd['id']], ['status', '=', 1], ['debris', '>', 1], ['kind', '<>', 4]], 'id,image,kind,title,debris', false, ['order' => 'asc']);
                // print_r($LuckGoods);die;
            }

            $result = DbOfflineActivities::getHdLucky([['uid', '=', $uid], ['need_debris', '>', 1]], 'uid,kind,id,need_debris,debris,shop_num', false, ['id' => 'desc'], $offect . ',' . $pagenum);
            foreach ($LuckGoods as $key => $value) {
                $LuckGoods[$key]['has'] = 0;
                foreach ($result as $re => $lt) {
                    if ($lt['shop_num'] == $value['id']) {
                        $LuckGoods[$key]['has'] = $lt['debris'];
                    }
                }
            }
            $General_debris = DbCoupon::getHdGoods([['hd_id', '=', $luckhd['id']], ['status', '=', 1], ['kind', '=', 5]], 'id,image,kind,title,debris', true);
            if (!empty($General_debris)) {
                $userGeneral_debris    = DbOfflineActivities::getHdLucky([['uid', '=', $uid], ['shop_num', '=', $General_debris['id']]], 'uid,kind,id,need_debris,debris,shop_num', true);
                $General_debris['has'] = 0;
                if ($userGeneral_debris) {
                    $General_debris['has'] = $userGeneral_debris['debris'];
                }
            }

            return ['code' => 200, 'General_debris' => $General_debris, 'winnings' => $LuckGoods];
        } else {
            if ($id) {
                $result = DbOfflineActivities::getHdLucky(['id' => $id], '*', true);
                if ($result['kind'] == 2) {
                    $goods_sku = DbGoods::getOneSku([["id", "=", $result['relevance']]], 'retail_price,spec');
                    if (!empty($goods_sku)) {
                        $attr           = DbGoods::getAttrList([['id', 'in', explode(',', $goods_sku['spec'])]], 'attr_name');
                        $goods_sku_name = array_column($attr, 'attr_name');

                        $result['goods_sku_name'] = implode(',', $goods_sku_name);
                        $result['retail_price']   = $goods_sku['retail_price'];
                    }
                }
            } else {
                $result = DbOfflineActivities::getHdLucky(['uid' => $uid], '*', false, ['id' => 'desc'], $offect . ',' . $pagenum);
                foreach ($result as $key => $value) {
                    if ($value['kind'] == 2) {
                        $goods_sku = DbGoods::getOneSku([["id", "=", $value['relevance']]], 'spec');
                        if (!empty($goods_sku)) {
                            $attr           = DbGoods::getAttrList([['id', 'in', explode(',', $goods_sku['spec'])]], 'attr_name');
                            $goods_sku_name = array_column($attr, 'attr_name');

                            $result[$key]['goods_sku_name'] = implode(',', $goods_sku_name);
                        }

                    }
                }
            }

            return ['code' => 200, 'winnings' => $result];
        }

    }

    public function createOrderQrCode($data) {

        $qrcodelogic = new qrcodelogic($data, 470, '取货二维码');
        $qrcodelogic->coverbackground('../public/background.png', 3, 3);
        $qrcodelogic->output();
        exit;
        print_r($qrcodelogic);die;
    }

    /**
     * 碎片兑换
     * @param $conId
     * @param $use_id
     * @param $chage_id
     * @param $use_number
     * @return array
     * @author rzc
     */
    public function userDebrisChange($conId, $use_id, $chage_id, $use_number) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $use_goods = DbOfflineActivities::getHdLucky(['shop_num' => $use_id, 'kind' => 5, 'status' => 1, 'uid' => $uid], '*', true);
        if (empty($use_goods)) {
            return ['code' => '3003'];
        }
        $change_goods = DbOfflineActivities::getHdLucky([['shop_num', '=', $chage_id], ['status', '=', 1], ['need_debris', '>', 1], ['debris', '>', 1], ['uid', '=', $uid]], '*', true);
        if (empty($change_goods)) {
            return ['code' => '3004'];
        }
        if ($use_number > $use_goods['debris']) {
            return ['code' => '3005'];
        }
        $new_use = [
            'debris' => $use_goods['debris'] - $use_number,
        ];
        $new_change = [
            'debris' => $change_goods['debris'] + $use_number,
        ];

        Db::startTrans();
        try {
            DbOfflineActivities::updateWinning($new_use, $use_goods['id']);
            DbOfflineActivities::updateWinning($new_change, $change_goods['id']);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {

            exception($e);
            Db::rollback();
            return ['code' => '3005'];
        }
    }

    public function userDebrisCompound($conId, $use_id) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $change_goods = DbOfflineActivities::getHdLucky([['shop_num', '=', $use_id], ['status', '=', 1], ['need_debris', '>', 1], ['debris', '>', 1], ['uid', '=', $uid]], '*', true);
        if (empty($change_goods)) {
            return ['code' => '3004'];
        }
        if ($change_goods['debris'] < $change_goods['need_debris']) {
            return ['code' => '3005'];
        }
        $new_change = [
            'debris' => $change_goods['debris'] - $change_goods['need_debris'],
        ];
        $goods_sku = [];
        if ($change_goods['kind'] == 2) {
            $goods_sku      = DbGoods::getOneSku([["id", "=", $change_goods['relevance']]], 'spec');
            $attr           = DbGoods::getAttrList([['id', 'in', explode(',', $goods_sku['spec'])]], 'attr_name');
            $goods_sku_name = array_column($attr, 'attr_name');

            $goods_sku['goods_sku_name'] = implode(',', $goods_sku_name);
        }
        Db::startTrans();
        try {
            $winning_id = DbOfflineActivities::addHdLucky([
                'uid'        => $uid,
                'shop_num'   => $change_goods['shop_num'],
                'hd_num'     => $change_goods['hd_num'],
                'kind'       => $change_goods['kind'],
                'relevance'  => $change_goods['relevance'],
                'goods_name' => $change_goods['goods_name'],
                'image_path' => $change_goods['image_path'],
                'status'     => 1,
            ]);
            DbOfflineActivities::updateWinning($new_change, $change_goods['id']);
            Db::commit();
            return ['code' => '200', 'relevance' => $change_goods['relevance'], 'image_path' => $change_goods['image_path'], 'kind' => $change_goods['kind'], 'goods_sku' => $goods_sku];
        } catch (\Exception $e) {

            exception($e);
            Db::rollback();
            return ['code' => '3005'];
        }
    }

    public function receivePrize($conId, $receive_id, $user_address_id = 0) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $change_goods = DbOfflineActivities::getHdLucky([['id', '=', $receive_id], ['uid', '=', $uid], ['need_debris', '=', 1], ['kind', '<>', 5]], '*', true);
        if (empty($change_goods)) {
            return ['code' => '3003'];
        }
        if ($change_goods['kind'] == 2) { //商品类领奖
            $userAddress = DbUser::getUserAddress('uid,mobile,name,province_id,city_id,area_id,address', ['id' => $user_address_id], true);
            if (empty($userAddress)) {
                return ['code' => '3004'];
            }
            $goods_sku = DbGoods::getOneSku([["id", "=", $change_goods['relevance']]], '*');
            if (empty($goods_sku)) {
                return ['code' => '3006'];
            }
            $goods_data = DbGoods::getOneGoods(['id' => $goods_sku['goods_id']], 'supplier_id,goods_name');
            $supplier   = DbGoods::getSupplierData('id,tel,name', $goods_data['supplier_id']);
            $attr       = DbGoods::getAttrList([['id', 'in', explode(',', $goods_sku['spec'])]], 'attr_name');
            $attr       = array_column($attr, 'attr_name');
            $goodsData  = [
                'goods_id'     => $goods_sku['goods_id'],
                'goods_name'   => $goods_data['goods_name'],
                'sku_id'       => $goods_sku['id'],
                'sup_id'       => $goods_data['supplier_id'],
                'boss_uid'     => 1,
                'goods_price'  => $goods_sku['retail_price'],
                'margin_price' => 0,
                'integral'     => 0,
                'goods_num'    => 1,
                'sku_json'     => json_encode($attr),
            ];
            $orderchild = [
                'supplier_id'   => $supplier['id'],
                'supplier_name' => $supplier['name'],
            ];
            $orderNo   = createOrderNo(); //创建订单号
            $orderData = [
                'order_no'       => $orderNo,
                'third_order_id' => 0,
                'uid'            => $uid,
                'order_status'   => 4,
                'order_money'    => $goods_sku['retail_price'], //订单金额(优惠金额+实际支付的金额)
                'goods_money'    => $goods_sku['retail_price'], //商品金额
                'discount_money' => $goods_sku['retail_price'], //优惠金额
                'linkman'        => $userAddress['name'],
                'linkphone'      => $userAddress['mobile'],
                'province_id'    => $userAddress['province_id'],
                'city_id'        => $userAddress['city_id'],
                'area_id'        => $userAddress['area_id'],
                'address'        => $userAddress['address'],
                'message'        => '',
                'pay_time'       => time(),
            ];
        }
        if ($change_goods['kind'] == 1) { //优惠券类领奖
            $coupon = DbCoupon::getCoupon(['id' => $change_goods['relevance']], 'price,gs_id,level,stype,is_superposition,title,days', true);
            if (empty($coupon)) { //优惠券不存在
                return ['code' => '3006'];
            }
            $userCoupon = DbCoupon::getUserCoupon([
                ['coupon_id', '=', $change_goods['relevance']],
                ['is_use', '=', 2],
                ['uid', '=', $uid],
                ['end_time', '>=', time()],
            ], 'id', true); //未使用的
            if (!empty($userCoupon)) { //有未使用的优惠券
                return ['code' => '3007'];
            }
            $coupon['uid']       = $uid;
            $coupon['coupon_id'] = $change_goods['relevance'];
            $coupon['end_time']  = $coupon['days'] * 24 * 3600 + strtotime(date('Y-m-d'));
            unset($coupon['days']);
        }
        if ($change_goods['kind'] == 3) { //钻石卡类领奖
            $userInfo = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
            if ($userInfo['user_identity'] > 1) {
                return ['code' => '3008', 'msg' => '当前身份等级大于或等于钻石会员，无法领取'];
            }
            $receiveDiamondvip = [];
            $receiveDiamondvip = [
                'uid'    => $uid,
                'source' => 3,
                'source' => 1,
            ];
        }
        if ($change_goods['kind'] == 4) { //积分类领奖
            $user_integral = [];
            $user_integral = [
                'result_integral' => $change_goods['relevance'],
                'status'          => 2,
                'uid'             => $uid,
                'stype'           => 4,
                'message'         => '抽奖积分到账',
            ];

        }
        $new_change = [
            'status' => 2,
        ];
        $userRedisKey = Config::get('rediskey.user.redisKey');
        Db::startTrans();
        try {
            if (!empty($orderData)) {
                $orderId = DbOrder::addOrder($orderData);
                if (empty($orderId)) {
                    Db::rollback();
                    return ['code' => '3005'];
                }
                $orderchild['order_id']      = $orderId;
                $order_child_id              = DbOrder::addOneOrderChild($orderchild);
                $goodsData['order_child_id'] = $order_child_id;
                DbOrder::addOneOrderGood($goodsData);
            }
            if (!empty($coupon)) {
                DbCoupon::addUserCoupon($coupon);
            }
            if (!empty($receiveDiamondvip)) {
                DbUser::updateUser(['user_identity' => 2], $uid);
            }
            if (!empty($user_integral)) {
                DbUser::addLogIntegral($user_integral);
                DbUser::modifyIntegral($uid, $change_goods['relevance'], 'inc');
            }
            DbOfflineActivities::updateWinning($new_change, $change_goods['id']);
            Db::commit();
            $this->redis->del($userRedisKey . 'userinfo:' . $uid);
            return ['code' => '200'];
        } catch (\Exception $e) {

            exception($e);
            Db::rollback();
            return ['code' => '3005'];
        }
    }
}