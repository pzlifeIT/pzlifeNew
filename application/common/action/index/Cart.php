<?php

namespace app\common\action\index;

use app\facade\DbGoods;
use app\facade\DbShops;
use app\facade\DbUser;
use function Qiniu\json_decode;
use Config;

class Cart extends CommonIndex {
    private $redisCartUserKey = 'index:cart:user:';

    public function __construct() {
        parent::__construct();
        $this->redisCartUserKey = Config::get('rediskey.cart.redisCartUserKey');
        $this->redisIntegralCartUserKey = Config::get('rediskey.cart.redisIntegralCartUserKey');
    }

    /**
     * 加入购物车
     * @param $uid
     * @param $goods_skuid
     * @param $buy_num
     * @param $track_id
     * @author rzc
     */
    public function addCartGoods($conId, $goods_skuid, $buy_num, $parent_id) {
        // phpinfo();
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'id,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }

        /* 获取该商品规格属性ID */
        $field     = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,integral_price,spec,sku_image,status';
        $where     = [["id", "=", $goods_skuid]];
        $goods_sku = DbGoods::getOneSku($where, $field);
        $field = 'id,uid,shop_name,shop_image,server_mobile,status';
        $where = ['uid' => $parent_id];
        
        $shop  = DbShops::getShopInfo($field, $where);
        if (!$shop) {
            $track_id = 1;
        }else{
            $track_id = $shop['id'];
        }
        
        if (!$goods_sku || $goods_sku['status'] == 2) {
            return ['code' => 3003, 'msg' => '该商品规格不存在'];
        }
        if ($goods_sku['stock'] < 1) {
            return ['code' => 3010, 'msg' => '该规格库存不足'];
        }
        if ($buy_num> $goods_sku['stock']) {
            return ['code' => '3006', 'msg' => '库存不足购买数量'];
        }
        /* 获取商品基础信息 */
        $where      = [["id", "=", $goods_sku['goods_id']], ["status", "=", 1]];
        $field      = "id,supplier_id,cate_id,goods_name,goods_type,target_users,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return ['code' => 3000, 'msg' => '商品不存在或者已下架'];
        }
        if ($user['user_identity'] < $goods_data['target_users']) {
            if ($goods_data['target_users'] == 2){
                return ['code' => 3005, 'msg' => '该商品钻石会员及以上身份专享'];
            }elseif ($goods_data['target_users'] == 3){
                return ['code' => 3007, 'msg' => '该商品创业店主及以上身份专享'];
            }elseif ($goods_data['target_users'] == 4){
                return ['code' => 3008, 'msg' => '该商品合伙人及以上身份专享'];
            }
        }
        /* 获取商品所在分类 */
        /*  $field = 'id,type_name';
         $where = [["id", '=', $goods_data['cate_id']]];
         $goods_class = DbGoods::getOneCate($where, $field); */

        // /* 获取商品对应供应商 */
        /*  $field = 'id,tel,name';
         $supplier = DbGoods::getSupplierData($field, $goods_data['supplier_id']); */

        /* 判断该用户购物车是否添加过该商品SKU,有就变更数量，没有就新增一条新数据 */

        
        $cart['goods_id'] = $goods_sku['goods_id'];
        $cart['track']    = [$track_id => $buy_num]; /* 购买店铺：购买数量 */
        $cart['spec']     = $goods_sku['spec']; /* 规格属性 */
        // $cart['from_uid'] = $parent_id; /* 推荐人ID */
        $hash_cart        = json_encode($cart);
        $key              = 'skuid:' . $goods_skuid;

        $oldcart = $this->redis->hget($this->redisCartUserKey . $uid, $key);
        if ($oldcart) {
            $oldcart = json_decode($oldcart, true);
            if ($buy_num > 0) {
                if (array_key_exists($track_id, $oldcart['track'])) {
                    $oldcart['track'][$track_id] += $buy_num;
                } else {
                    $oldcart['track'][$track_id] = $buy_num;
                    // $oldcart['from_uid'] = $parent_id; /* 推荐人ID */
                }
            } else { 
                foreach ($oldcart['track'] as $ol => $value) {
                    if ($value + $buy_num > 0){
                        $oldcart['track'][$ol] = $value + $buy_num;
                        break;
                    } else {
                        unset($oldcart['track'][$ol]);
                        $buy_num  = $value + $buy_num;
                    }
                }
            }
            // print_r($oldcart);die;
            // $oldcart['from_uid'] = $parent_id; /* 推荐人ID */
            if ($oldcart['track'][$track_id] < 1) {
                unset($oldcart['track'][$track_id]);
            }
            $oldcart = json_encode($oldcart);
            $thecart = $this->redis->hset($this->redisCartUserKey . $uid, $key, $oldcart);

        } else {
            if ($buy_num <1) {
                return ['code' => '3004'];
            }
            $thecart = $this->redis->hset($this->redisCartUserKey . $uid, $key, $hash_cart);
        }
        $has_cart =  $this->redis->hgetall($this->redisCartUserKey . $uid);
        foreach ($has_cart as $has => $value) {
            $value = json_decode($value,true);
            $value['from_uid'] = $parent_id;
            $this->redis->hset($this->redisCartUserKey . $uid, $has, json_encode($value));
        }
        // print_r($has_cart);die;
        $expirat_time = $this->redis->expire($this->redisCartUserKey . $uid, 2592000);
        return ['code' => '200', 'msg' => '添加成功'];

    }

    /**
     * 查询购物车
     * @param $uid
     * @author rzc
     */
    public function getUserCart($conId) {

        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $valid = [];
        $failure = [];
        $failure_supplier = [];
        $valid_supplier = [];
        $cart = $this->redis->hgetall($this->redisCartUserKey . $uid);
        if ($cart) {
            $expirat_time = $this->redis->expire($this->redisCartUserKey . $uid, 2592000);
            $old_valid    = [];/* 有效商品 */
            $old_failure  = [];/* 失效商品 */
            foreach ($cart as $key => $value) {
                /* $key示例：$key = 'skuid:18'; */
                $buy_goods     = [];
                $num = 0;
                $skuid         = substr($key, 6);
                $buy_goods     = json_decode($value, true);
                $buy_track_num = $buy_goods['track'];
                /*  $buy_goods_sku = []; */
                $cart_buy = [];

                /* $track_id = $track_id; */
                /* 获取店铺信息 */
               
                /* 查询商品信息 */
                $field     = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,cost_price,active_end_time,margin_price,integral_price,spec,sku_image,status';
                $where     = [["id", "=", $skuid]];
                $goods_sku = DbGoods::getOneSku($where, $field);
                /* 该规格查询不到直接失效 */

                /* 获取商品基础信息 */
                $where                  = [["id", "=", $buy_goods['goods_id']]];
                $field                  = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
                $goods_data             = DbGoods::getOneGoods($where, $field);
                // $goods_data['track_id'] = $track_id;
                // $goods_data['buy_num']  = $num;
                /* print_r($goods_data); */
               
                /* 获取购物车购买规格属性 */
                // $attr_field = 'id,spec_id,attr_name';
                // $attr_where = [['id', 'in', $goods_sku['spec']]];

                // $goods_sku_name = DbGoods::getAttrList($attr_where, $attr_field);
                $attr                    = DbGoods::getAttrList([['id', 'in', explode(',', $goods_sku['spec'])]], 'attr_name');
                $goods_sku_name        = array_column($attr, 'attr_name');

                $goods_sku['goods_sku_name'] = implode(',',$goods_sku_name);
                
                /*  print_r($goods_sku_name);die; */

                /* 获取商品所在分类 */
                $field       = 'id,type_name';
                $where       = [["id", '=', $goods_data['cate_id']]];
                $goods_class = DbGoods::getOneCate($where, $field);

                /* 获取商品对应供应商 */
                $field    = 'id,tel,name';
                $supplier = DbGoods::getSupplierData($field, $goods_data['supplier_id']);

                $cart_buy                  = $goods_sku;
                $cart_buy['goods_name']    = $goods_data['goods_name'];
                $cart_buy['cate_id']       = $goods_class['id'];
                $cart_buy['supplier_id']   = $supplier['id'];
                $cart_buy['supplier_name'] = $supplier['name'];
                $cart_buy['supplier_tel']  = $supplier['tel'];
                $cart_buy['goods_name']    = $goods_data['goods_name'];
                $cart_buy['goods_type']    = $goods_data['goods_type'];
                $cart_buy['title']         = $goods_data['title'];
                $cart_buy['subtitle']      = $goods_data['subtitle'];
                $cart_buy['status']        = $goods_data['status'];
                // $cart_buy['track_id']      = $goods_data['track_id'];
                // $cart_buy['buy_num']       = $goods_data['buy_num'];
                foreach ($buy_track_num as $track_id => $tnum) {
                    $num += $tnum;
                }
                $cart_buy['num'] = $num;
                $cart_buy['brokerage']     =  bcmul(bcmul(getDistrProfits($goods_sku['retail_price'],$goods_sku['cost_price'],$goods_sku['margin_price']), 0.75, 2),$cart_buy['num'],2);
                // $cart_buy['brokerage']     =  bcmul(bcmul(getDistrProfits($goods_sku['retail_price'],$goods_sku['cost_price'],$goods_sku['margin_price']), 0.75, 2),$goods_data['buy_num'],2);
                // print_r($cart_buy);
                 /* 失效商品处理：商品无库存、商品下架、商品主信息查询不到 */
                 if (!$goods_sku['stock'] || !$goods_data || $goods_data['status'] == 2 || $goods_sku['status']==2) {
                    // $old_failure[$track_id][] = $cart_buy;
                    // $failure_goods[$supplier['id']] = $supplier;
                    $failure_goods[] = $cart_buy;
                    if (!in_array($supplier,$failure_supplier)) {
                        array_push($failure_supplier,$supplier);
                    }
                    continue;
                }
                /* 若无此规格，则该商品暂时以失效处理 */
                if (!$goods_sku_name) {
                    // $old_failure[$track_id][] = $cart_buy;
                    // $failure_goods[$supplier['id']] = $supplier;
                    $failure_goods[] = $cart_buy;
                    if (!in_array($supplier,$failure_supplier)) {
                        array_push($failure_supplier,$supplier);
                    }
                    continue;
                }
                // $old_valid[$track_id][] = $cart_buy;
                // $valid_goods[$supplier['id']] = $supplier;
                // $valid_goods[$supplier['id']]['goods'] = [];
                // $valid_goods[$supplier['id']]['goods'][] = $cart_buy;
                $valid_goods[] = $cart_buy;
                if (!in_array($supplier,$valid_supplier)) {
                    array_push($valid_supplier,$supplier);
                }
            }
          
            //有效商品
            foreach ($valid_supplier as $key => $value) {
                foreach ($valid_goods as $val => $gs) {
                   if ($gs['supplier_id'] == $value['id']){
                       $valid_supplier[$key]['goods'][] = $gs;
                   }
                }
                
            }
            //失效商品
            foreach ($failure_supplier as $key => $value) {
                foreach ($failure_goods as $val => $gs) {
                    if ($gs['supplier_id'] == $value['id']){
                        $failure_supplier[$key]['goods'][] = $gs;
                    }
                }
            }
            // $valid = [];
            // foreach ($old_valid as $old => $val) {
            //     //    print_r($val);
            //     $field         = 'id,uid,shop_name,shop_image,server_mobile,status';
            //     $where         = ['id' => $old];
            //     $shop          = DbShops::getShopInfo($field, $where);
            //     $shop['goods'] = $val;
            //     $valid[]       = $shop;
            // }
            // $failure = [];
            // foreach ($old_failure as $old => $val) {
            //     //    print_r($val);
            //     $field         = 'id,uid,shop_name,shop_image,server_mobile,status';
            //     $where         = ['id' => $old];
            //     $shop          = DbShops::getShopInfo($field, $where);
            //     $shop['goods'] = $val;
            //     $failure[]     = $shop;
            // }

            return ['code' => 200, 'valid' => $valid_supplier, 'failure' => $failure_supplier];
        } else {
            return ['code' => '3000', 'msg' => '购物车中未添加商品'];
        }

    }

    /**
     * 修改购物车商品数量(添加购物车已满足修改购物车数量功能，故此功能暂废用)
     * @param $uid
     * @param $goods_skuid
     * @param $buy_num
     * @param $track_id
     * @author rzc
     */
    public function updateCartGoods($conId, $goods_skuid, $buy_num, $track_id) {

        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $expirat_time = $this->redis->expire($this->redisCartUserKey . $uid, 2592000);
        $cart         = $this->redis->hget($this->redisCartUserKey . $uid, 'skuid:' . $goods_skuid);
        if (!$cart) {
            return ['code' => '3007'];//此条信息不存在
        }
        /* 获取该商品规格属性ID */
        $field     = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,integral_price,spec,sku_image';
        $where     = [["id", "=", $goods_skuid]];
        $goods_sku = DbGoods::getOneSku($where, $field);
        if (!$goods_sku) {
            return ['code' => 3008, 'msg' => '该商品规格不存在'];
        }

        $cart = json_decode($cart, true);
        /* 获取商品基础信息 */
        $where      = [["id", "=", $cart['goods_id']], ["status", "=", 1]];
        $field      = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);

        if (empty($goods_data)) {
            return ['code' => 3000, 'msg' => '商品不存在或者已下架'];
        }
        $key = 'skuid:' . $goods_skuid;
        /* 如果数量为0则视为删除此店此规格 */
        if ($buy_num < 1) {//减
        // print_r($cart);die;
            if ($cart['track'][$track_id] + $buy_num < 1) {
                unset($cart['track'][$track_id]);
            }else{
                $cart['track'][$track_id] += $buy_num;
            }
            if ($cart['track']) {
                $new_cart = json_encode($cart);
                $thecart  = $this->redis->hset($this->redisCartUserKey . $uid, $key, $new_cart);
            } else {
                $thecart = $this->redis->hdel($this->redisCartUserKey . $uid, $key);
            }

        } else {//加
            
            if ($buy_num > $goods_sku['stock']) {
                return ['code' => '3009', 'msg' => '库存不足', 'stock' => $goods_sku['stock']];
            }
            $cart['track'][$track_id] = $buy_num;
            $new_cart                 = json_encode($cart);
            $thecart                  = $this->redis->hset($this->redisCartUserKey . $uid, $key, $new_cart);
        }
        return ['code' => 200, 'msg' => '修改成功'];

    }

    /**
     * 批量删除购物车商品
     * @param $uid
     * @param $goods_skuid
     * @param $buy_num
     * @param $track_id
     * @author rzc
     */
    public function editUserCart($conId, $del_shopid, $del_skuid) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        // if (empty($del_shopid)) {
        //     return ['code' => '3002', 'msg' => '删除店铺ID为空'];
        // }
        if (empty($del_skuid)) {
            return ['code' => '3004', 'msg' => '删除SKU_ID为空'];
        }
        // $del_shopid = explode(',', $del_shopid);
        $del_skuid  = explode(',', $del_skuid);
        // if (count($del_shopid) != count($del_skuid)) {
        //     return ['code' => '3005', 'msg' => '删除店铺ID与删除SKU_ID长度不符'];
        // }
        $expirat_time = $this->redis->expire($this->redisCartUserKey . $uid, 2592000);
        // print_r($del_skuid);die;
        // foreach ($del_skuid as $del => $skuid) {
        //     $sku[$skuid][] = $del_shopid[$del];
        // }
        /* 查询参数是否有效 */
        // foreach ($del_skuid as $key => $value) {
        //     $cart = $this->redis->hget($this->redisCartUserKey . $uid, 'skuid:' . $value);
        //     if (!$cart) {
        //         return ['code' => '3006', 'msg' => '传入参数中含有无效参数'];
        //     }
        //     $cart = json_decode($cart, true);
        //     foreach ($cart['track'] as $track => $track_cart) {
        //         if (!in_array($track, $value)) {
        //             return ['code' => '3006', 'msg' => '传入参数中含有无效参数'];
        //         }
        //     }
        // }
        foreach ($del_skuid as $key => $value) {
            $cart = $this->redis->hget($this->redisCartUserKey . $uid, 'skuid:' . $value);
            if (!$cart) {
                continue;
            }
            // $cart = json_decode($cart, true);
            // foreach ($value as $skey => $shopid) {
            //     unset($cart['track'][$shopid]);
            // }
            $redis_key = 'skuid:' . $value;
            // if ($cart['track']) {
            //     $new_cart = json_encode($cart);
            //     $thecart  = $this->redis->hset($this->redisCartUserKey . $uid, $redis_key, $new_cart);
            // } else {
                $thecart = $this->redis->hdel($this->redisCartUserKey . $uid, $redis_key);
            // }
        }
        $newcart = $this->redis->hgetall($this->redisCartUserKey . $uid);
        if (!$newcart) {
            $thecart = $this->redis->hdel($this->redisCartUserKey . $uid);
        }
        return ['code' => '200', 'msg' => '删除成功'];
    }

    public function getUserCartNum($conId){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $total = 0;
        $cart = $this->redis->hgetall($this->redisCartUserKey . $uid);
        if ($cart) {
            $expirat_time = $this->redis->expire($this->redisCartUserKey . $uid, 2592000);
            
            foreach ($cart as $key => $value) {
                /* $key示例：$key = 'skuid:18'; */
                $buy_goods     = [];
                $skuid         = substr($key, 6);
                $buy_goods     = json_decode($value, true);
                $buy_track_num = $buy_goods['track'];
                /*  $buy_goods_sku = []; */

                foreach ($buy_track_num as $track_id => $num) {
                    $cart_buy = [];

                    /* $track_id = $track_id; */
                    /* 获取店铺信息 */
                   
                    /* 查询商品信息 */
                    $field     = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,cost_price,active_end_time,margin_price,integral_price,spec,sku_image,status';
                    $where     = [["id", "=", $skuid]];
                    $goods_sku = DbGoods::getOneSku($where, $field);
                    /* 该规格查询不到直接失效 */

                    /* 获取商品基础信息 */
                    $where                  = [["id", "=", $buy_goods['goods_id']]];
                    $field                  = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
                    $goods_data             = DbGoods::getOneGoods($where, $field);
                    $goods_data['track_id'] = $track_id;
                    $goods_data['buy_num']  = $num;
                    /* print_r($goods_data); */
                   
                    /* 获取购物车购买规格属性 */
                    $attr_field = 'id,spec_id,attr_name';
                    $attr_where = [['id', 'in', $goods_sku['spec']]];

                    $goods_sku_name = DbGoods::getAttrList($attr_where, $attr_field);

                    
                    /*  print_r($goods_sku_name);die; */

                    /* 获取商品所在分类 */
                    $field       = 'id,type_name';
                    $where       = [["id", '=', $goods_data['cate_id']]];
                    $goods_class = DbGoods::getOneCate($where, $field);

                     /* 失效商品处理：商品无库存、商品下架、商品主信息查询不到 */
                     if (!$goods_sku['stock'] || !$goods_data || $goods_data['status'] == 2 || $goods_sku['status']==2) {
                        
                        continue;
                    }
                    /* 若无此规格，则该商品暂时以失效处理 */
                    if (!$goods_sku_name) {
                       
                        continue;
                    }
                   $total = $total + $num;
                }

            }
        }
        return ['code' => '200','total' => $total];
    }

    public function getUserIntegralCart($conId){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $valid = [];
        $failure = [];
        $failure_supplier = [];
        $valid_supplier = [];
        $cart = $this->redis->hgetall($this->redisIntegralCartUserKey . $uid);
        if ($cart) {
            $expirat_time = $this->redis->expire($this->redisIntegralCartUserKey . $uid, 2592000);
            $old_valid    = [];/* 有效商品 */
            $old_failure  = [];/* 失效商品 */
            foreach ($cart as $key => $value) {
                /* $key示例：$key = 'skuid:18'; */
                $buy_goods     = [];
                $num = 0;
                $skuid         = substr($key, 6);
                $buy_goods     = json_decode($value, true);
                $buy_track_num = $buy_goods['track'];
                /*  $buy_goods_sku = []; */
                $cart_buy = [];

                /* $track_id = $track_id; */
                /* 获取店铺信息 */
               
                /* 查询商品信息 */
                $field     = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,cost_price,active_end_time,margin_price,integral_price,spec,sku_image,status';
                $where     = [["id", "=", $skuid]];
                $goods_sku = DbGoods::getOneSku($where, $field);
                /* 该规格查询不到直接失效 */

                /* 获取商品基础信息 */
                $where                  = [["id", "=", $buy_goods['goods_id']]];
                $field                  = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
                $goods_data             = DbGoods::getOneGoods($where, $field);
                // $goods_data['track_id'] = $track_id;
                // $goods_data['buy_num']  = $num;
                /* print_r($goods_data); */
               
                /* 获取购物车购买规格属性 */
                // $attr_field = 'id,spec_id,attr_name';
                // $attr_where = [['id', 'in', $goods_sku['spec']]];

                // $goods_sku_name = DbGoods::getAttrList($attr_where, $attr_field);
                $attr                    = DbGoods::getAttrList([['id', 'in', explode(',', $goods_sku['spec'])]], 'attr_name');
                $goods_sku_name        = array_column($attr, 'attr_name');

                $goods_sku['goods_sku_name'] = implode(',',$goods_sku_name);
                
                /*  print_r($goods_sku_name);die; */

                /* 获取商品所在分类 */
                $field       = 'id,type_name';
                $where       = [["id", '=', $goods_data['cate_id']]];
                $goods_class = DbGoods::getOneCate($where, $field);

                /* 获取商品对应供应商 */
                $field    = 'id,tel,name';
                $supplier = DbGoods::getSupplierData($field, $goods_data['supplier_id']);

                $cart_buy                  = $goods_sku;
                $cart_buy['goods_name']    = $goods_data['goods_name'];
                $cart_buy['cate_id']       = $goods_class['id'];
                $cart_buy['supplier_id']   = $supplier['id'];
                $cart_buy['supplier_name'] = $supplier['name'];
                $cart_buy['supplier_tel']  = $supplier['tel'];
                $cart_buy['goods_name']    = $goods_data['goods_name'];
                $cart_buy['goods_type']    = $goods_data['goods_type'];
                $cart_buy['title']         = $goods_data['title'];
                $cart_buy['subtitle']      = $goods_data['subtitle'];
                $cart_buy['status']        = $goods_data['status'];
                // $cart_buy['track_id']      = $goods_data['track_id'];
                // $cart_buy['buy_num']       = $goods_data['buy_num'];
                foreach ($buy_track_num as $track_id => $tnum) {
                    $num += $tnum;
                }
                $cart_buy['num'] = $num;
                // $cart_buy['brokerage']     =  bcmul(bcmul(getDistrProfits($goods_sku['retail_price'],$goods_sku['cost_price'],$goods_sku['margin_price']), 0.75, 2),$cart_buy['num'],2);
                // $cart_buy['brokerage']     =  bcmul(bcmul(getDistrProfits($goods_sku['retail_price'],$goods_sku['cost_price'],$goods_sku['margin_price']), 0.75, 2),$goods_data['buy_num'],2);
                // print_r($cart_buy);
                 /* 失效商品处理：商品无库存、商品下架、商品主信息查询不到 */
                 if (!$goods_sku['stock'] || !$goods_data || $goods_data['status'] == 2 || $goods_sku['status']==2) {
                    // $old_failure[$track_id][] = $cart_buy;
                    // $failure_goods[$supplier['id']] = $supplier;
                    $failure_goods[] = $cart_buy;
                    if (!in_array($supplier,$failure_supplier)) {
                        array_push($failure_supplier,$supplier);
                    }
                    continue;
                }
                /* 若无此规格，则该商品暂时以失效处理 */
                if (!$goods_sku_name) {
                    // $old_failure[$track_id][] = $cart_buy;
                    // $failure_goods[$supplier['id']] = $supplier;
                    $failure_goods[] = $cart_buy;
                    if (!in_array($supplier,$failure_supplier)) {
                        array_push($failure_supplier,$supplier);
                    }
                    continue;
                }
                // $old_valid[$track_id][] = $cart_buy;
                // $valid_goods[$supplier['id']] = $supplier;
                // $valid_goods[$supplier['id']]['goods'] = [];
                // $valid_goods[$supplier['id']]['goods'][] = $cart_buy;
                $valid_goods[] = $cart_buy;
                if (!in_array($supplier,$valid_supplier)) {
                    array_push($valid_supplier,$supplier);
                }
            }
          
            //有效商品
            foreach ($valid_supplier as $key => $value) {
                foreach ($valid_goods as $val => $gs) {
                   if ($gs['supplier_id'] == $value['id']){
                       $valid_supplier[$key]['goods'][] = $gs;
                   }
                }
                
            }
            //失效商品
            foreach ($failure_supplier as $key => $value) {
                foreach ($failure_goods as $val => $gs) {
                    if ($gs['supplier_id'] == $value['id']){
                        $failure_supplier[$key]['goods'][] = $gs;
                    }
                }
            }
            // $valid = [];
            // foreach ($old_valid as $old => $val) {
            //     //    print_r($val);
            //     $field         = 'id,uid,shop_name,shop_image,server_mobile,status';
            //     $where         = ['id' => $old];
            //     $shop          = DbShops::getShopInfo($field, $where);
            //     $shop['goods'] = $val;
            //     $valid[]       = $shop;
            // }
            // $failure = [];
            // foreach ($old_failure as $old => $val) {
            //     //    print_r($val);
            //     $field         = 'id,uid,shop_name,shop_image,server_mobile,status';
            //     $where         = ['id' => $old];
            //     $shop          = DbShops::getShopInfo($field, $where);
            //     $shop['goods'] = $val;
            //     $failure[]     = $shop;
            // }

            return ['code' => 200, 'valid' => $valid_supplier, 'failure' => $failure_supplier];
        } else {
            return ['code' => '3000', 'msg' => '购物车中未添加商品'];
        }

    }

    public function addIntegralCartGoods($conId, $goods_skuid, $buy_num, $parent_id = 1) {
        // phpinfo();
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $user = DbUser::getUserOne(['id' => $uid], 'id,user_identity');
        if (empty($user)) {
            return ['code' => '3003'];
        }

        /* 获取该商品规格属性ID */
        $field     = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,integral_price,spec,sku_image,status';
        $where     = [["id", "=", $goods_skuid]];
        $goods_sku = DbGoods::getOneSku($where, $field);
        $field = 'id,uid,shop_name,shop_image,server_mobile,status';
        $where = ['uid' => $parent_id];
        
        $shop  = DbShops::getShopInfo($field, $where);
        if (!$shop) {
            $track_id = 1;
        }else{
            $track_id = $shop['id'];
        }
        
        if (!$goods_sku || $goods_sku['status'] == 2) {
            return ['code' => 3003, 'msg' => '该商品规格不存在,无法兑换'];
        }
        if ($goods_sku['integral_sale_stock'] < 1) {
            return ['code' => 3010, 'msg' => '该规格库存不足,无法兑换'];
        }
        if ($buy_num> $goods_sku['integral_sale_stock']) {
            return ['code' => '3006', 'msg' => '库存不足购买数量'];
        }
        /* 获取商品基础信息 */
        $where      = [["id", "=", $goods_sku['goods_id']], ["status", "=", 1]];
        $field      = "id,supplier_id,cate_id,goods_name,goods_type,target_users,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return ['code' => 3000, 'msg' => '商品不存在或者已下架'];
        }
        if ($user['user_identity'] < $goods_data['target_users']) {
            if ($goods_data['target_users'] == 2){
                return ['code' => 3005, 'msg' => '该商品钻石会员及以上身份专享'];
            }elseif ($goods_data['target_users'] == 3){
                return ['code' => 3007, 'msg' => '该商品创业店主及以上身份专享'];
            }elseif ($goods_data['target_users'] == 4){
                return ['code' => 3008, 'msg' => '该商品合伙人及以上身份专享'];
            }
        }
        /* 获取商品所在分类 */
        /*  $field = 'id,type_name';
         $where = [["id", '=', $goods_data['cate_id']]];
         $goods_class = DbGoods::getOneCate($where, $field); */

        // /* 获取商品对应供应商 */
        /*  $field = 'id,tel,name';
         $supplier = DbGoods::getSupplierData($field, $goods_data['supplier_id']); */

        /* 判断该用户购物车是否添加过该商品SKU,有就变更数量，没有就新增一条新数据 */

        
        $cart['goods_id'] = $goods_sku['goods_id'];
        $cart['track']    = [$track_id => $buy_num]; /* 购买店铺：购买数量 */
        $cart['spec']     = $goods_sku['spec']; /* 规格属性 */
        // $cart['from_uid'] = $parent_id; /* 推荐人ID */
        $hash_cart        = json_encode($cart);
        $key              = 'skuid:' . $goods_skuid;

        $oldcart = $this->redis->hget($this->redisIntegralCartUserKey . $uid, $key);
        if ($oldcart) {
            $oldcart = json_decode($oldcart, true);
            if ($buy_num > 0) {
                if (array_key_exists($track_id, $oldcart['track'])) {
                    $oldcart['track'][$track_id] += $buy_num;
                } else {
                    $oldcart['track'][$track_id] = $buy_num;
                    // 推荐人ID
                    // $oldcart['from_uid'] = $parent_id; 
                }
            } else { 
                foreach ($oldcart['track'] as $ol => $value) {
                    if ($value + $buy_num > 0){
                        $oldcart['track'][$ol] = $value + $buy_num;
                        break;
                    } else {
                        unset($oldcart['track'][$ol]);
                        $buy_num  = $value + $buy_num;
                    }
                }
            }
            // print_r($oldcart);die;
            // $oldcart['from_uid'] = $parent_id; /* 推荐人ID */
            if ($oldcart['track'][$track_id] < 1) {
                unset($oldcart['track'][$track_id]);
            }
            $oldcart = json_encode($oldcart);
            $thecart = $this->redis->hset($this->redisIntegralCartUserKey . $uid, $key, $oldcart);

        } else {
            if ($buy_num <1) {
                return ['code' => '3004'];
            }
            $thecart = $this->redis->hset($this->redisIntegralCartUserKey . $uid, $key, $hash_cart);
        }
        $has_cart =  $this->redis->hgetall($this->redisIntegralCartUserKey . $uid);
        foreach ($has_cart as $has => $value) {
            $value = json_decode($value,true);
            $value['from_uid'] = $parent_id;
            $this->redis->hset($this->redisIntegralCartUserKey . $uid, $has, json_encode($value));
        }
        // print_r($has_cart);die;
        $expirat_time = $this->redis->expire($this->redisIntegralCartUserKey . $uid, 2592000);
        return ['code' => '200', 'msg' => '添加成功'];

    }
}
