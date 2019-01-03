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
class DbGoods {

    /**
     * 根据status条件查询商品分类
     * @param $field 要获取的字段
     * @param $status where status条件
     * @return array
     */
    public function getGoodsClassByStatus($field, $status,$offest,$pageNum) {
        return GoodsClass::limit($offest,$pageNum)->where("status", $status)->field($field)->select()->toArray();
    }

    /**
     * 查询status状态的分类数量
     * @param $field
     * @param $status
     * @param $offest
     * @param $pageNum
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2019/1/3-11:58
     */
    public function getGoodsClassByStatusNum($status) {
        return GoodsClass::where("status", $status)->count();
    }
    /**
     * 获取所有商品分类
     * @param $field
     * @return array
     */
    public function getGoodsClassAll($field,$offest,$pageNum) {
        return GoodsClass::limit($offest,$pageNum)->field($field)->select()->toArray();
    }

    /**
     * 获取所有分类数据的数量
     * @param $field
     * @param $offest
     * @param $pageNum
     * @return mixed
     * @author wujunjie
     * 2019/1/3-11:58
     */
    public function getGoodsClassAllNum($field) {
        return GoodsClass::count();
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
     * 添加分类
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-17:51
     */
    public function addCate($data){
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
    public function editCate($data,$id){
        return (new GoodsClass())->save($data,["id"=>$id]);
    }
    /**
     * 获取商品列表
     * @param $field
     * @author wujunjie
     * 2019/1/2-10:38
     */
    public function getGoodsList($field){
       return $goods_data = Goods::field($field)->select()->toArray();
    }

    /**
     * 获取一条分类数据
     * @param $where
     * @param $field
     * @return array
     * @author wujunjie
     * 2019/1/2-14:08
     */
    public function getOneCate($where,$field){
       return GoodsClass::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 删除分类
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:05
     */
    public function delCate($id){
        return GoodsClass::destroy($id);
    }
    /**
     * 查找一条供应商数据
     * @param $goods_data
     * @return mixed
     * @author wujunjie
     * 2019/1/2-10:46
     */
    public function getOneSupplier($where,$field){
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
    public function getSpecList($field,$offset,$pageNum){
        return GoodsSpec::limit($offset,$pageNum)->field($field)->select()->toArray();
    }

    /**
     * 获取二级属性列表
     * @param $where
     * @param $field
     * @return array
     * @author wujunjie
     * 2019/1/2-14:51
     */
    public function getAttrList($where,$field){
        return GoodsAttr::where($where)->field($field)->select()->toArray();
    }

    /**
     * 获取一条以及规格
     * @author wujunjie
     * 2019/1/2-14:53
     */
    public function getOneSpec($where,$field){
        return GoodsSpec::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 获取一条二级属性
     * @author wujunjie
     * 2019/1/2-14:53
     */
    public function getOneAttr($where,$field){
        return GoodsAttr::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 添加一级属性
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:17
     */
    public function addSpec($data){
        return (new GoodsSpec())->save($data);
    }

    /**
     * 添加二级属性
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:20
     */
    public function addAttr($data){
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
    public function editSpec($data,$id){
        return (new GoodsSpec())->save($data,["id"=>$id]);
    }

    /**
     * 编辑二级属性
     * @param $data
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:24
     */
    public function editAttr($data,$id){
        return (new GoodsAttr())->save($data,["id"=>$id]);
    }

    /**
     * 删除一级属性
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:28
     */
    public function delSpec($id){
        return GoodsSpec::destroy($id);
    }

    /**
     * 删除二级属性
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:28
     */
    public function delAttr($id){
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
    public function getOneGoods($where,$field){
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
    public function getOneGoodsImage($where,$field){
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
    public function getOneGoodsSku($where,$field){
        return GoodsSku::where($where)->field($field)->select()->toArray();
    }

    /**
     * 添加商品
     * @param $data
     * @return mixed
     * @author wujunjie
     * 2019/1/2-18:46
     */
    public function addGoods($data){
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
    public function addGoodsImage($data){
        return (new GoodsImage())->save($data);
    }

    /**
     * 添加sku
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:47
     */
    public function addGoodsSku($data){
        return (new GoodsSku())->save($data);
    }

    /**
     * 添加商品属性关系表
     * @param $data
     * @return bool
     * @author wujunjie
     * 2019/1/2-18:47
     */
    public function addRelation($data){
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
    public function editGoods($data,$id){
        return (new Goods())->save($data,["id"=>$id]);
    }

    /**
     * 编辑商品图片
     * @param $data
     * @param $goods_id
     * @return bool
     * @author wujunjie
     * 2019/1/3-9:43
     */
    public function editGoodsImage($data,$goods_id){
        return (new GoodsImage())->save($data,["goods_id"=>$goods_id]);
    }

    /**
     * 编辑sku
     * @param $data
     * @param $goods_id
     * @return bool
     * @author wujunjie
     * 2019/1/3-9:43
     */
    public function editGoodsSku($data,$goods_id){
        return (new GoodsSku())->save($data,["goods_id"=>$goods_id]);
    }

    /**
     * 编辑商品属性关系表
     * @param $data
     * @param $goods_id
     * @return bool
     * @author wujunjie
     * 2019/1/3-9:43
     */
    public function editGoodsRelation($data,$goods_id){
        return (new GoodsRelation())->save($data,["goods_id"=>$goods_id]);
    }

    public function delGoods($id){
        return  Goods::destroy($id);
    }

    public function delGoodsImage($id){
        return GoodsImage::destroy(["goods_id"=>["=",$id]]);
    }

    public function delGoodsSku($id){
        return GoodsSku::destroy(["goods_id"=>["=",$id]]);
    }

    public function delGoodsRelation($id){
        return GoodsRelation::destroy(["goods_id"=>["=",$id]]);
    }
}