<?php

namespace app\common\db\product;

use app\common\model\GoodsClass;
use app\common\model\Goods;
use app\common\model\GoodsImage;
use app\common\model\GoodsRelation;
use app\common\model\GoodsSku;
use app\common\model\Supplier;
use app\common\model\GoodsSpec;
use app\common\model\GoodsAttr;
use app\common\model\SupplierFreight;

class DbGoods {

    public function getTier($id) {
        return GoodsClass::field('type_name,tier')->findOrEmpty($id)->toArray();
    }

    /**
     * 根据status条件查询商品分类
     * @param $field 要获取的字段
     * @param $status where status条件
     * @return array
     */
    public function getGoodsClassByStatus($field, $status, $offest, $pageNum) {
        return GoodsClass::limit($offest, $pageNum)->where("status", $status)->field($field)->select()->toArray();
    }

    /**
     * 查询status状态的分类数量
     * @param $field
     * @param $status
     * @param $offest
     * @param $pageNum
     * @author wujunjie
     * 2019/1/3-11:58
     */
    public function getGoodsClassByStatusNum($status) {
        return GoodsClass::where("status", $status)->count();
    }

    /**
     * 获取所有分类数据的数量
     * @param $where
     * @return mixed
     * @author wujunjie
     * 2019/1/3-11:58
     */
    public function getGoodsClassAllNum(array $where = []) {
        if (!empty($where)) {
            return GoodsClass::where($where)->count();
        }
        return GoodsClass::count();
    }

    /**
     * 根据where条件查询商品(可分页查)
     * @param $field
     * @param $where
     * @param $offset
     * @param $pageNum
     * @return array
     */
    public function getGoodsClass($field, $where, $offset = 0, $pageNum = 0) {
        $obj = GoodsClass::where($where)->field($field);
        if ($offset == 0 && $pageNum == 0) {
            return $obj->select()->toArray();
        }
        return $obj->limit($offset, $pageNum)->select()->toArray();
    }

    /**
     * 添加分类
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-17:51
     */
    public function addCate($data) {
        return (new GoodsClass())->save($data);
    }

    /**
     * 编辑分类
     * @param $data
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-17:55
     */
    public function editCate($data, $id) {
        return (new GoodsClass())->save($data, ["id" => $id]);
    }

    /**
     * 获取商品列表
     * @param $field
     * @author wujunjie
     * 2019/1/2-10:38
     */
    public function getGoodsList($field,$offset,$pageNum) {
        return Goods::limit($offset,$pageNum)->field($field)->select()->toArray();
    }

    /**
     * 获取商品条数
     * @return float|string
     * @author wujunjie
     * 2019/1/3-19:08
     */
    public function getGoodsListNum(){
        return Goods::count();
    }
    /**
     * 获取一条分类数据
     * @param $where
     * @param $field
     * @return array
     * @author wujunjie
     * 2019/1/2-14:08
     */
    public function getOneCate($where, $field) {
        return GoodsClass::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 删除分类
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:05
     */
    public function delCate($id) {
        return GoodsClass::destroy($id);
    }

    /**
     * 查找一条供应商数据
     * @param $goods_data
     * @return mixed
     * @author wujunjie
     * 2019/1/2-10:46
     */
    public function getOneSupplier($where, $field) {
        return Supplier::where($where)->field($field)->find()->toArray();
    }

    /**
     * 获取一级规格
     * @param $field
     * @param array $where
     * @return array
     * @author wujunjie
     * 2019/1/2-14:47
     */
    public function getSpecList($field, $offset = 0, $pageNum = 0) {
        //只获取不分页
        if ($offset == 0 && $pageNum == 0){
            return GoodsSpec::field($field)->select()->toArray();
        }
        //获取并分页
        return GoodsSpec::limit($offset, $pageNum)->field($field)->select()->toArray();
    }

    /**
     * 获取一级规格数据条数
     * @author wujunjie
     * 2019/1/3-18:57
     */
    public function getSpecListNum(){
        return GoodsSpec::count();
    }
    /**
     * 获取二级属性列表
     * @param $where
     * @param $field
     * @return array
     * @author wujunjie
     * 2019/1/2-14:51
     */
    public function getAttrList($where, $field) {
        return GoodsAttr::where($where)->field($field)->select()->toArray();
    }

    /**
     * 获取一条以及规格
     * @author wujunjie
     * 2019/1/2-14:53
     */
    public function getOneSpec($where, $field) {
        return GoodsSpec::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 获取一条二级属性
     * @author wujunjie
     * 2019/1/2-14:53
     */
    public function getOneAttr($where, $field) {
        return GoodsAttr::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 添加一级属性
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:17
     */
    public function addSpec($data) {
        return (new GoodsSpec())->save($data);
    }

    /**
     * 添加二级属性
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:20
     */
    public function addAttr($data) {
        return (new GoodsAttr())->save($data);
    }

    /**
     * 编辑一级规格
     * @param $data
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:23
     */
    public function editSpec($data, $id) {
        return (new GoodsSpec())->save($data, ["id" => $id]);
    }

    /**
     * 编辑二级属性
     * @param $data
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:24
     */
    public function editAttr($data, $id) {
        return (new GoodsAttr())->save($data, ["id" => $id]);
    }

    /**
     * 删除一级属性
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:28
     */
    public function delSpec($id) {
        return GoodsSpec::destroy($id);
    }

    /**
     * 删除二级属性
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:28
     */
    public function delAttr($id) {
        return GoodsAttr::destroy($id);
    }

    /**
     * 获取一条商品数据
     * @param $where
     * @param $field
     * @return array
     * @author wujunjie
     * 2019/1/2-16:14
     */
    public function getOneGoods($where, $field) {
        return Goods::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 获取一个商品的图片
     * @param $where
     * @param $field
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2019/1/2-16:26
     */
    public function getOneGoodsImage($where, $field) {
        return GoodsImage::where($where)->field($field)->select()->toArray();
    }

    /**
     * 获取一个商品的sku
     * @param $where
     * @param $field
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2019/1/2-16:44
     */
    public function getOneGoodsSku($where, $field) {
        return GoodsSku::where($where)->field($field)->select()->toArray();
    }

    /**
     * 添加商品
     * @param $data
     * @return mixed
     * @author wujunjie
     * 2019/1/2-18:46
     */
    public function addGoods($data) {
        $g = new Goods();
        $g->save($data);
        return $g->id;
    }

    /**
     * 添加商品图片
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:47
     */
    public function addGoodsImage($data) {
        return (new GoodsImage())->save($data);
    }

    /**
     * 添加sku
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:47
     */
    public function addGoodsSku($data) {
        return (new GoodsSku())->save($data);
    }

    /**
     * 添加商品属性关系表
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:47
     */
    public function addRelation($data) {
        return (new GoodsRelation())->save($data);
    }

    /**
     * 编辑商品
     * @param $data
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/3-9:42
     */
    public function editGoods($data, $id) {
        return (new Goods())->save($data, ["id" => $id]);
    }

    /**
     * 编辑商品图片
     * @param $data
     * @param $goods_id
     * @return bool
     * @author wujunjie
     * 2019/1/3-9:43
     */
    public function editGoodsImage($data, $goods_id) {
        return (new GoodsImage())->save($data, ["goods_id" => $goods_id]);
    }

    /**
     * 编辑sku
     * @param $data
     * @param $goods_id
     * @return bool
     * @author wujunjie
     * 2019/1/3-9:43
     */
    public function editGoodsSku($data, $goods_id) {
        return (new GoodsSku())->save($data, ["goods_id" => $goods_id]);
    }

    /**
     * 编辑商品属性关系表
     * @param $data
     * @param $goods_id
     * @return bool
     * @author wujunjie
     * 2019/1/3-9:43
     */
    public function editGoodsRelation($data, $goods_id) {
        return (new GoodsRelation())->save($data, ["goods_id" => $goods_id]);
    }

    public function delGoods($id) {
        return Goods::destroy($id);
    }

    public function delGoodsImage($id) {
        return GoodsImage::destroy(["goods_id" => ["=", $id]]);
    }

    public function delGoodsSku($id) {
        return GoodsSku::destroy(["goods_id" => ["=", $id]]);
    }

    public function delGoodsRelation($id) {
        return GoodsRelation::destroy(["goods_id" => ["=", $id]]);
    }

    /**
     * 获取所有供应商分类
     * @param $field
     * @param $order
     * @param $limit
     * @return array
     */
    public function getSupplier($field, $order, $limit) {
        return Supplier::field($field)->order($order)->limit($limit)->select()->toArray();
    }

    /**
     * 获取供应商表中所有数据计数
     * @return num
     */
    public function getSupplierCount() {
        return Supplier::count();
    }

    /**
     * 获取供应商详细数据根据ID
     * @param $field
     * @param $supplierId
     * @return array
     */
    public function getSupplierData($field, $supplierId) {
        return Supplier::field($field)->where('id', $supplierId)->findOrEmpty()->toArray();
    }

    /**
     * 新增供应商
     * @param $data
     * @return bool
     */
    public function addSupplier($data) {
        return Supplier::insert($data);
    }

    /**
     * 修改供应商
     * @param $data
     * @param $id
     * @return bool
     */
    public function updateSupplier($data, $id) {
        return Supplier::where('id', $id)->update($data);
    }

    /**
     * 获取供应商快递模板列表
     * @param $field
     * @param $supid
     * @return bool
     */
    public function getSupplierFreights($field, $supid) {
        return SupplierFreight::field($field)->where('supid', $supid)->select()->toArray();
    }

    /**
     * 获取供应商快递模板列表详情
     * @param $field
     * @param $supid
     * @return bool
     */
    public function getSupplierFreightdetail($field, $id) {
        return SupplierFreight::field($field)->where('id', $id)->findOrEmpty()->toArray();
    }

    /**
     * 查询某字段的供应商信息（精确查询）
     * @param $field
     * @param $value
     * @return bool
     */
    public function getSupplierWhereFile($field, $value) {
        return Supplier::where($field, $value)->findOrEmpty()->toArray();
    }

    /**
     * 查询某字段的供应商信息且ID不等传入ID（精确查询）
     * @param $field
     * @param $value
     * @param $id
     * @return bool
     */
    public function getSupplierWhereFileByID($field, $value, $id) {
        return Supplier::where($field, $value)->where('id', '<>', $id)->findOrEmpty()->toArray();
    }

    /**
     * 停用或者启用供应商模板
     * @param $status
     * @param $supid
     * @return bool
     */
    public function updateSupplierFreights($status, $supid) {
        return SupplierFreight::where('supid', $supid)->update(['status' => $status]);
    }
}