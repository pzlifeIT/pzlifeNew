<?php

namespace app\common\db\product;

use app\common\model\GoodsClass;
use app\common\model\SupplierFreight;
use app\common\model\Supplier;
use app\common\action\admin\Suppliers;

class DbGoods {

    /**
     * 根据status条件查询商品分类
     * @param $field 要获取的字段
     * @param $status where status条件
     * @return array
     */
    public function getGoodsClassByStatus($field, $status) {
        return GoodsClass::where("status", $status)->field($field)->select()->toArray();
    }

    /**
     * 获取所有商品分类
     * @param $field
     * @return array
     */
    public function getGoodsClassAll($field) {
        return GoodsClass::field($field)->select()->toArray();
    }

    /**
     * 根据where条件查询商品分类
     * @param $field
     * @param $where
     * @return array
     */
    public function getGoodsClass($field, $where) {
        return GoodsClass::where($where)->field($field)->select()->toArray();
    }

    /**
     * 获取所有供应商分类
     * @param $field
     * @param $order
     * @param $limit
     * @return array
     */
    public function getSupplier($field,$order,$limit){
        return Supplier::field($field)->order($order)->limit($limit)->select()->toArray();
    }

    /**
     * 获取供应商表中所有数据计数
     * @return num
     */
    public function getSupplierCount(){
        return Supplier::count();
    }

    /**
     * 获取供应商详细数据根据ID
     * @param $field
     * @param $supplierId
     * @return array
     */
    public function getSupplierData($field,$supplierId){
        return Supplier::field($field)->where('id',$supplierId)->findOrEmpty()->toArray();
    }

    /**
     * 新增供应商
     * @param $data
     * @return bool
     */
    public function addSupplier($data){
        return Supplier::insert($data);
    }
    
    /**
     * 修改供应商
     * @param $data
     * @param $id
     * @return bool
     */
    public function updateSupplier($data,$id){
        return Supplier::where('id',$id)->update($data);
    }

    /**
     * 获取供应商快递模板列表
     * @param $field
     * @param $supid
     * @return bool
     */
    public function getSupplierFreights($field,$supid){
        return SupplierFreight::field($field)->where('supid',$supid)->select()->toArray();
    }

    /**
     * 获取供应商快递模板列表详情
     * @param $field
     * @param $supid
     * @return bool
     */
    public function getSupplierFreightdetail($field,$id){
        return SupplierFreight::field($field)->where('id',$id)->findOrEmpty()->toArray();
    }

    /**
     * 查询某字段的供应商信息（精确查询）
     * @param $field
     * @param $value
     * @return bool
     */
    public function getSupplierWhereFile($field,$value)
    {
        return Supplier::where($field,$value)->findOrEmpty()->toArray();
    }

    /**
     * 查询某字段的供应商信息且ID不等传入ID（精确查询）
     * @param $field
     * @param $value
     * @param $id
     * @return bool
     */
    public function getSupplierWhereFileByID($field,$value,$id){
        return Supplier::where($field,$value)->where('id','<>',$id)->findOrEmpty()->toArray();
    }

    /**
     * 停用或者启用供应商模板
     * @param $status
     * @param $supid
     * @return bool
     */
    public function updateSupplierFreights($status,$supid){
        return SupplierFreight::where('supid',$supid)->update(['status'=>$status]);
    }
}