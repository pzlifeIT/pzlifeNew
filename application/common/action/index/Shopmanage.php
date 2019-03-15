<?php

namespace app\common\action\index;

use app\facade\DbGoods;
use app\facade\DbShops;
use function Qiniu\json_decode;
use Config;
use think\Db;

class Shopmanage extends CommonIndex {
    /**
     * 查询首页显示内容
     * @param $conId
     * @param $type
     * @param $search
     * @author rzc
     */
    public function getShopGoods($conId,$type,$search = false,$page,$pagenum){
        $uid = $this->getUidByConId($conId);
        $Goods = new Goods;
        // print_r($uid);die;
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $offset = ($page -1) * $pagenum;
        if ($offset < 0) {
            return ['code' => '3000'];
        }
       
        $shopinfo = DbShops::getShopInfo('id', ['uid'=>$uid]);
        if (empty($shopinfo)) {
            return ['code' => '3005'];
        }
        $limit = $offset.','.$pagenum;
       
        $where1 =[
            ['shop_id','=',$shopinfo['id']],
            ['status','=',$type],
        ];
        $where2 = [['status','=','1']];
        if (!empty($search)) {
            $where2 = [
                ['status','=','1'],
                ['goods_name','like','%'.$search.'%']
            ];
        }
        // print_r($limit);die;
        if ($type == 1) {
            $result = DbShops::getShopWithGoods($where1,$where2, '*','*',false,'','',$limit);
        }elseif ($type == 2) {
            $result = DbShops::getShopWithGoods($where1,$where2, '*','*',false,'','',$limit);
        }elseif ($type == 3) {
            $has_goods = DbShops::getShopGoods(['shop_id'=>$shopinfo['id']],'id');
           
            $has_goods_ids = [];
            foreach ($has_goods as $has => $goods) {
                $has_goods_ids[] = $goods['id'];
            }
            if (!empty($search)) {
                $where2 = [
                    ['status','=','1'],
                    ['goods_name','like','%'.$search.'%'],
                    ['id','not in',$has_goods_ids]
                ];
            }
            // print_r($where2);die;
            $result = DbGoods::getGoodsList('*',$where2, $offset, $pagenum);
            
        }
        // echo Db::getLastSql();die;
        if (empty($result)) {
            return ['code' => '3000'];
        }
        // print_r($result);die;
        $new_goods = [];
        foreach ($result as $key => $value) {
            
            // print_r($value);
            if ($type == 3) {
                $value['goods_id'] = $value['id'];
               
            }
            list($goods_spec,$goods_sku) = $Goods->getGoodsSku($value['goods_id']);
            
            
            
            if ($type == 3){
                $value['goods_sku'] = $goods_sku;
                $value['min_retail_price'] = DbGoods:: getOneSkuMost(['goods_id'=>$value['goods_id']], 1, 'retail_price');
                $value['max_retail_price'] = DbGoods:: getOneSkuMost(['goods_id'=>$value['goods_id']], 2, 'retail_price');
                $new_goods[] = $value;
            }else{
                if (empty($value['goods'])) {
                    continue;
                }
                $value['goods']['min_retail_price'] = DbGoods:: getOneSkuMost(['goods_id'=>$value['goods_id']], 1, 'retail_price');
                $value['goods']['max_retail_price'] = DbGoods:: getOneSkuMost(['goods_id'=>$value['goods_id']], 2, 'retail_price');
                $value['goods']['goods_sku'] = $goods_sku;
                $new_goods[] = $value['goods'];
            }
        //    print_r($new_goods);
            // $result[$key]['goods_sku'] = $goods_sku;
        }
        // die;
        return ['code' => '200','type' => $type,'goods_list' =>$new_goods];
    }

    /**
     * 商品上下架
     * @param $conId
     * @param $type
     * @author rzc
     */
    public function autoShopGoods($conId,$type,$goods_id){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3000'];
        }
        $shopinfo = DbShops::getShopInfo('id', ['uid'=>$uid]);  
        if (empty($shopinfo)) {
            return ['code' => '3006'];
        }
        $goods = DbShops::getShopGoods(['shop_id' => $shopinfo['id'],'goods_id' => $goods_id],'*',true);
        if ($type == 1) {//上架
            
            if (empty($goods)) {
                $goods_warehouse = DbGoods::getOneGoods(['id'=>$goods_id,'status' => 1], '*');
                if (empty($goods_warehouse)) {
                    return ['code' => '3007'];
                }
                $shop_goods = [];
                $shop_goods['shop_id'] = $shopinfo['id'];
                $shop_goods['goods_id'] = $goods_warehouse['id'];
                $shop_goods['status'] = 1;
                DbShops::addShopGoods($shop_goods);
                return ['code' => '200'];
            }
            if ($goods['status'] == 1) {
                return ['code' => '3008'];
            }
            DbShops::updateShopGoods(['status'=>1],$goods['id']);
            return ['code' => '200'];
        }else {//下架
            if (empty($goods)) {
                return ['code' => '3009'];
            }
            DbShops::deleteShopGoods($goods['id']);
            return ['code' => '200'];
        }
    }
}
