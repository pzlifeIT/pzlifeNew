<?php

namespace app\common\db\rights;

use app\common\model\DiamondvipBinding;
use app\common\model\DiamondvipGet;
use app\common\model\Diamondvips;
use app\common\model\ShopApply;

class DbRights {
    /**
     * 新增一条分享机会
     * @param $data
     * @return array
     */
    public function creatDiamondvip($data) {
        $Diamondvips = new Diamondvips;
        $Diamondvips -> save($data);
        return $Diamondvips->id;
    }

    /**
     * 修改分享机会
     * @param $data
     * @param $id
     * @return array
     */
    public function updateDiamondvip($data,$id){
        $Diamondvips = new Diamondvips;
        return $Diamondvips -> save($data,['id' => $id]);
    }

    /**
     * 查询钻石分享
     * @param $field
     * @param $where
     * @param $row
     * @param $orderBy
     * @param $sc
     * @param $limit
     * @return array
     */
    public function getDiamondvips($where, $field, $row = false, $orderBy = '', $sc = '', $limit = ''){
        $obj = Diamondvips::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 查询钻石分享表记录条数
     * @return number
     */
    public function getCountDiamondvip(){
        return Diamondvips::count();
    }

    /**
     * 添加绑定钻石会员卡
     * @param $data
     * @return number
     */
    public function creatDiamondvipBinding($data) {
        $DiamondvipBinding = new DiamondvipBinding;
        $DiamondvipBinding -> save($data);
        return $DiamondvipBinding->id;
    }

    /**
     * 查询绑定钻石会员卡
     * @param $field
     * @param $where
     * @param $row
     * @param $orderBy
     * @param $sc
     * @param $limit
     * @return array
     */
    public function getDiamondvipBinding($where, $field, $row = false, $orderBy = '', $sc = '', $limit = ''){
        $obj = DiamondvipBinding::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 添加领取钻石记录
     * @param $data
     * @return number
     */
    public function receiveDiamondvip($data){
        $DiamondvipGet = new DiamondvipGet;
        $DiamondvipGet->save($data);
        return $DiamondvipGet->id;
    }

    /**
     * 修改领取钻石记录
     * @param $data
     * @return number
     */
    public function editGetDiamondvip($data,$id){
        $DiamondvipGet = new DiamondvipGet;
        return $DiamondvipGet->save($data,['id' => $id]);
    }

    /**
     * 查询钻石会员领取记录
     * @param $field
     * @param $where
     * @param $row
     * @param $orderBy
     * @param $sc
     * @param $limit
     * @return array
     */
    public function getDiamondvip($where, $field, $row = false, $orderBy = '', $sc = '', $limit = ''){
        $obj = DiamondvipGet::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }


    /**
     * 查询钻石分享表记录条数
     * @param $where
     * @return number
     */
    public function getCountDiamondvips($where){
        if ($where) {
            return DiamondvipGet::where($where)->count();
        }
        return DiamondvipGet::count();
    }

    /**
     * 查询分享开店BOSS记录
     * @param $field
     * @param $where
     * @param $row
     * @param $orderBy
     * @param $sc
     * @param $limit
     * @return array
     */
    public function getShopApply($where, $field, $row = false, $orderBy = '', $sc = '', $limit = ''){
        $obj = ShopApply::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 添加开店邀请记录
     * @param $data
     * @return number
     */
    public function saveShopApply($data){
        $ShopApply = new ShopApply;
        $ShopApply->save($data);
        return $ShopApply->id;
    }

    /**
     * 修改开店邀请记录
     * @param $data
     * @param $id
     * @return number
     */
    public function editShopApply($data,$id){
        $ShopApply = new ShopApply;
        return $ShopApply->save($data,['id'=>$id]);
    }

    /**
     * 获取记录数目
     * @param $where
     * @return number
     */
    public function countShopApply($where){
        return ShopApply::where($where)->count();
    }
}