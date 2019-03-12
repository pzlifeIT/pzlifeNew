<?php

namespace app\common\action\index;

use app\facade\DbGoods;
use app\facade\DbShops;
use function Qiniu\json_decode;
use Config;

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
                ['goods_name','like',$search]
            ];
        }
        
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
                $where = [
                    ['status','=','1'],
                    ['goods_name','like',$search],
                    ['id','not in',$has_goods_ids]
                ];
            }
            $result = DbGoods::getGoodsList('*',$where, $offset, $pagenum);
        }
        if (empty($result)) {
            return ['code' => '3000'];
        }
        foreach ($result as $key => $value) {
            list($goods_spec,$goods_sku) = $this->getGoodsSku($value['goods_id']);
            $result[$key]['goods_sku'] = $goods_sku;
        }
        return ['code' => '200','goods_list' =>$result];
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
            return ['code' => '3005'];
        }
    }
}
