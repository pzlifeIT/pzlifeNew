<?php

namespace app\common\db\product;

use app\common\model\GoodsClass;
use app\common\model\Goods;
use app\common\model\GoodsClassImage;
use app\common\model\GoodsImage;
use app\common\model\GoodsRelation;
use app\common\model\GoodsSku;
use app\common\model\GoodsSubject;
use app\common\model\GoodsSubjectImage;
use app\common\model\GoodsSubjectRelation;
use app\common\model\SupAdmin;
use app\common\model\Supplier;
use app\common\model\GoodsSpec;
use app\common\model\GoodsAttr;
use app\common\model\SupplierFreight;
use app\common\model\SupplierFreightArea;
use app\common\model\SupplierFreightDetail;
use think\Db;

class DbGoods {
    private $supplier;
    private $goodsClassImage;
    private $goodsClass;
    private $supplierFreight;
    private $supplierFreightDetail;
    private $supplierFreightArea;
    private $goods;
    private $goodsAttr;

    public function __construct() {
        $this->supplier              = new Supplier();
        $this->goodsClassImage       = new GoodsClassImage();
        $this->goodsClass            = new GoodsClass();
        $this->supplierFreight       = new SupplierFreight();
        $this->supplierFreightDetail = new SupplierFreightDetail();
        $this->supplierFreightArea   = new SupplierFreightArea();
        $this->goods                 = new Goods();
        $this->goodsAttr             = new GoodsAttr();
    }

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
        $obj = GoodsClass::field($field);
        if (!empty($where)) {
            $obj = $obj->where($where);
        }
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
        $this->goodsClass->save($data);
        return $this->goodsClass->id;
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
     * @param $where
     * @param $offset
     * @param $pageNum
     * @param $orderBy
     * @return array
     * @author wujunjie
     * 2019/1/2-10:38
     */
    public function getGoodsList($field, $where, $offset, $pageNum, $orderBy = '') {
//        $obj = Goods::field($field)->withJoin(['supplier'=>['id','name']]);
        $obj = Goods::field($field)->with([
            'supplier'      => function ($query) {
                $query->field('id,name,title')->where(['status' => 1]);
            }, 'goodsClass' => function ($query2) {
                $query2->field('id,type_name');
            }]);
        if (!empty($where)) {
            $obj = $obj->where($where);
        }
        if (!empty($orderBy)) {
            $obj = $obj->order($orderBy);
        }
        if (!empty($pageNum)) {
            $obj = $obj->limit($offset, $pageNum);
        }
        return $obj->select()->toArray();
    }

    public function getGoodsList2($where, $field) {
        return Goods::field($field)->where($where)->select()->toArray();
    }

    /**
     * 获取商品条数
     * @param $where
     * @return float|string
     * @author wujunjie
     * 2019/1/3-19:08
     */
    public function getGoodsListNum($where = []) {
        if (!empty($where)) {
            return Goods::where($where)->count();
        }
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
        return Supplier::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 获取一级规格
     * @param $field
     * @param array $where
     * @return array
     * @author wujunjie
     * 2019/1/2-14:47
     */
    public function getSpecList($field, $where, $offset = 0, $pageNum = 0) {
        $obj = GoodsSpec::field($field);
        if (!empty($where)) {
            $obj = $obj->where($where);
        }
        //获取不分页
        if ($offset == 0 && $pageNum == 0) {
            return $obj->select()->toArray();
        }
        //获取并分页
        return $obj->limit($offset, $pageNum)->select()->toArray();
    }

    /**
     * 获取一级规格数据条数
     * @author wujunjie
     * 2019/1/3-18:57
     */
    public function getSpecListNum($where) {
        return GoodsSpec::where($where)->count();
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
     * @param $where
     * @param $field
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
     * @param $orderBy
     * @return array
     * @author wujunjie
     * 2019/1/2-16:26
     */
    public function getOneGoodsImage($where, $field, $orderBy = '') {
        return GoodsImage::where($where)->field($field)->order($orderBy)->select()->toArray();
    }

    /**
     * 获取一个商品的sku
     * @param $where
     * @param $field
     * @param $row
     * @return array
     * @author wujunjie
     * 2019/1/2-16:44
     */
    public function getOneGoodsSku($where, $field, $row = false) {
        if ($row === true) {
            return GoodsSku::where($where)->field($field)->findOrEmpty()->toArray();
        }
        return GoodsSku::where($where)->field($field)->select()->toArray();
    }

    public function getSkuGoods($where, $field1, $field2) {
        return GoodsSku::field($field1)->withJoin([
            'goods' => function ($query) use ($field2) {
                $query->withField($field2)->where(['goods.status' => 1]);
            }])->where($where)->select()->toArray();
    }


    /**
     * 获取商品及对应对sku
     * @param $where
     * @param $field
     * @param $field2
     * @return array
     */
    public function getSpecAttr($where, $field, $field2) {
        return GoodsSpec::field($field)->with([
            'goodsAttr' => function ($query) use ($field2) {
                $query->field($field2);
            }])->where($where)->select()->toArray();
    }

//    public function getSpecAttr2($where, $field, $field2, $field3) {
//        return GoodsSpec::field($field)->with([
//            'goodsAttr'        => function ($query) use ($field2) {
//                $query->field($field2);
//            }, 'goodsRelation' => function ($query2) use ($field3) {
//                $query2->field($field3);
//            }])->where($where)->select()->toArray();
//    }

    public function getGoodsAndSku($where, $field, $field2) {
        return Goods::field($field)->with([
            'goodsSku' => function ($query) use ($field2) {
                $query->field($field2);
            }])->where($where)->select()->toArray();
    }

    public function getSku($where, $field) {
        $sku    = GoodsSku::field($field)->where($where)->select()->toArray();
        $result = [];
        foreach ($sku as $val) {
            $goodsAttr    = GoodsAttr::where([['id', 'in', explode(',', $val['spec'])]])->field('attr_name')->select()->toArray();
            $attr         = array_column($goodsAttr, 'attr_name');
            $val['attr']  = $attr;
            $freightTitle = '';
            if (!empty($val['freight_id'])) {
                $freightArr   = SupplierFreight::where(['id' => $val['freight_id']])->field('title')->findOrEmpty()->toArray();
                $freightTitle = $freightArr['title'];
            }
            $val['freight_title'] = $freightTitle;
            array_push($result, $val);
        }
        return $result;
    }

    /**
     * 获取商品的规格属性及关联名称
     * @param $where
     * @param $field
     * @param $field2
     * @param $field3
     * @return array
     */
    public function getGoodsSpecAttr($where, $field, $field2, $field3) {
        return GoodsRelation::field($field)->with([
            'goodsSpec'    => function ($query) use ($field2) {
                $query->field($field2);
            }, 'goodsAttr' => function ($query2) use ($field3) {
                $query2->field($field3);
            }])->where($where)->select()->toArray();
    }

    /**
     * 获取一条商品规格属性
     * @param $where
     * @param $field
     * @return array
     * @author rzc
     */
    public function getOneSku($where, $field) {
        return GoodsSku::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 获取一条商品规格某字段最值
     * @param $where
     * @param $field
     * @return array
     * @author rzc
     */
    public function getOneSkuMost($where, $most, $field) {
        /* 最小 */
        if ($most == 1) {
            return GoodsSku::where($where)->min($field);
        } elseif ($most == 2) {
            return GoodsSku::where($where)->max($field);
        }

    }

    /**
     * 获取一条商品类目属性关系
     * @param $where
     * @param $field
     * @return array
     * @author rzc
     */
    public function getOneGoodsSpec($where, $field, $distinct = 0) {
        if ($distinct == 1) {
            return GoodsRelation::distinct(true)->field($field)->where($where)->select()->toArray();
        }
        return GoodsRelation::field($field)->where($where)->select()->toArray();
    }

    /**
     * 获取商品类目属性关系
     * @param $where
     * @param $field
     * @return array
     */
    public function getGoodsRelation($where, $field) {
        return GoodsRelation::where($where)->field($field)->select()->toArray();
    }

    /**
     * 获取一条商品类目属性关系
     * @param $where
     * @param $field
     * @return array
     */
    public function getGoodsRelationOne($where, $field) {
        return GoodsRelation::where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 添加商品
     * @param $data
     * @return mixed
     * @author wujunjie
     * 2019/1/2-18:46
     */
    public function addGoods($data) {
        $res = $this->goods->save($data);
        if ($res) {
            return $this->goods->id;
        }
        return $res;
    }

    /**
     * 更新商品图片
     * @param $data
     * @param $id
     * @author zyr
     */
    public function updateGoodsImage($data, $id) {
        $goodsImage = new GoodsImage();
        $goodsImage->save($data, ['id' => $id]);
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
     * 批量添加图片
     * @param $data
     * @return bool
     */
    public function addGoodsImageList($data) {
        $goodsImage = new GoodsImage();
        return $goodsImage->saveAll($data);
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
     * 编辑sku详情
     * @param $data
     * @param $skuId
     */
    public function editGoodsSku($data, $skuId) {
        $goodsSku = new GoodsSku();
        $goodsSku->save($data, ['id' => $skuId]);
    }

    /**
     * 改库存
     * @param $skuIdList
     * @param string $modify 增加/减少inc/dec
     * @author zyr
     */
    public function decStock($skuIdList, $modify = 'dec') {
        foreach ($skuIdList as $skuId => $num) {
            $sku        = GoodsSku::get($skuId);
            $sku->stock = [$modify, $num];
            $sku->save();
        }
    }

    /**
     * 改一个库存
     * @param $skuId
     * @param $num
     * @param string $modify 增加/减少inc/dec
     * @return bool
     * @author zyr
     */
    public function modifyStock($skuId, $num, $modify = 'inc') {
        $sku        = GoodsSku::get($skuId);
        $sku->stock = [$modify, 1];
        return $sku->save();
    }

    public function addSkuList($data) {
        $goodsSku = new GoodsSku();
        return $goodsSku->saveAll($data);
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
        return $this->goods->save($data, ["id" => $id]);
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
//    public function editGoodsSku($data, $goods_id) {
//        return (new GoodsSku())->save($data, ["goods_id" => $goods_id]);
//    }

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

    /**
     * 删除商品
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/8-10:09
     */
    public function delGoods($id) {
        return Goods::destroy($id);
    }

    /**
     * 删除商品图
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/8-10:09
     */
    public function delGoodsImage($id) {
        return GoodsImage::destroy($id);
    }

    /**
     * 删除sku
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/8-10:09
     */
    public function delGoodsSku($id) {
        return GoodsSku::destroy(["goods_id" => ["=", $id]]);
    }

    /**
     * 删除sku(状态修改为无效)
     * @param $delId
     * @param $id
     * @return bool
     * @author
     */
    public function delSku($delId) {
        $goodSku = new GoodsSku();
        return $goodSku->saveAll($delId);
//        return GoodsSku::destroy($isList);
    }


    public function deleteGoodsRelation($delId) {
        return GoodsRelation::destroy($delId);
    }

    /**
     * 删除商品类目
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/8-10:09
     */
    public function delGoodsRelation($id) {
        return GoodsRelation::destroy(["goods_id" => ["=", $id]]);
    }

    /**
     * 获取供应商列表
     * @param $field
     * @param $where
     * @param $order
     * @param $limit
     * @return array
     */
    public function getSupplier($field, array $where = [], $order = '', $limit = '') {
        $obj = Supplier::field($field);
        if (!empty($where)) {
            $obj = $obj->where($where);
        }
        if (!empty($order)) {
            $obj = $obj->order($order);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        return $obj->select()->toArray();
    }

    /**
     * 获取供应商表中所有数据计数
     * @return num
     */
    public function getSupplierCount($where) {
        if (!empty($where)) {
            return Supplier::where($where)->count();
        }
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
        return $this->supplier->save($data);
    }

    /**
     * 修改供应商
     * @param $data
     * @param $id
     * @return bool
     */
    public function updateSupplier($data, $id) {
//        return Supplier::where('id', $id)->update($data);
        return $this->supplier->save($data, ['id' => $id]);
    }

    /**
     * 获取供应商快递模板列表
     * @param $field
     * @param $supid
     * @param $status
     * @return bool
     */
    public function getSupplierFreights($where, $field) {
        return SupplierFreight::field($field)->where($where)->select()->toArray();
    }

    public function getFreightAndDetail($where, $cityId, $field, $field2, $field3, $freightId) {
//        $list = SupplierFreightDetail::field($field)->with(
//            ['supplierFreight'       => function ($query2) use ($field2) {
//                $query2->field($field2);
//            }, 'supplierFreightArea' => function ($query) use ($field3, $cityId) {
//                $query->field($field3)->where(['city_id' => $cityId]);
//            }]
//        )->where($where)->select()->toArray();
        $list = SupplierFreightArea::field('id,city_id')->withJoin(
            ['supplierFreightDetail' => function ($query) use ($field, $freightId) {
                $query->withField($field)->where([['freight_id', 'in', $freightId]]);
            },
            ])->where(['city_id' => $cityId])->select()->toArray();
//        return $list;
        $result = [];
        foreach ($list as $val) {
            $freightId                = $val['supplier_freight_detail']['freight_id'];
            $val['freight_detail_id'] = $val['supplier_freight_detail']['id'];
            $val['price']             = $val['supplier_freight_detail']['price'];
            $val['after_price']       = $val['supplier_freight_detail']['after_price'];
            $val['total_price']       = $val['supplier_freight_detail']['total_price'];
            $val['unit_price']        = $val['supplier_freight_detail']['unit_price'];
            unset($val['supplier_freight_detail']);
            unset($val['id']);
            unset($val['city_id']);
            $supplierFreight = SupplierFreight::field('supid,stype')->where(['id' => $freightId])->findOrEmpty()->toArray();
            $val['supid']    = $supplierFreight['supid'];
            $val['stype']    = $supplierFreight['stype'];
//            array_push($result, $val);
            $result[$freightId] = $val;
        }
        return $result;
    }

    /**
     * 获取供应商快递模板列表详情
     * @param $field
     * @param $supid
     * @return bool
     */
    public function getSupplierFreight($field, $id) {
        return SupplierFreight::field($field)->where('id', $id)->findOrEmpty()->toArray();
    }

    /**
     * 查询某字段的供应商信息（精确查询）
     * @param $field
     * @param $value
     * @param $getField
     * @return array
     */
    public function getSupplierWhereFile($field, $value, $getField = '*') {
        return Supplier::field($getField)->where($field, $value)->findOrEmpty()->toArray();
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

    /**
     * 获取供应商某一快递模板运费列表
     * @param $field
     * @param $limit
     * @param $freight_id
     * @param $order
     * @return bool
     */
    public function getSupplierFreightdetailList($field, $limit, $freight_id) {
        return SupplierFreightDetail::field($field)->where('freight_id', $freight_id)->order('id', 'desc')->limit($limit)->select()->toArray();
    }

    /**
     * 获取供应商某一快递模板运费总记录条数
     * @param $status
     * @param $supid
     * @return bool
     */
    public function getSupplierFreightdetailCount($freight_id) {
        return SupplierFreightDetail::where('freight_id', $freight_id)->count();
    }

    /**
     * 新建供应商快递模板
     * @param $data
     * @return bool
     */
    public function addSupplierFreight($data) {
        $this->supplierFreight->save($data);
        return $this->supplierFreight->id;
    }

    /**
     * 修改供应商快递模板
     * @param $data
     * @param $id
     * @return bool
     */
    public function updateSupplierFreight($data, $id) {
        return $this->supplierFreight->save($data, ['id' => $id]);
    }

    /**
     * 添加分类图片
     * @param $data
     * @return mixed
     */
    public function addClassImage($data) {
        $this->goodsClassImage->save($data);
        return $this->goodsClassImage->id;
    }

    /**
     * 更新分类图片
     * @param $data
     * @param $id
     * @return bool
     */
    public function updateClassImage($data, $id) {
        return $this->goodsClassImage->save($data, ['id' => $id]);
    }


    /**
     * 获取分类的图片
     * @param $where
     * @param $field
     * @return array
     */
    public function getClassImage($where, $field) {
        return $this->goodsClassImage->where($where)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 获取供应商快递模板运费详情
     * @param $field
     * @param $id
     * @return bool
     */
    public function getSupplierFreightdetailRow($field, $id) {
        return SupplierFreightDetail::field($field)->where('id', $id)->findOrEmpty()->toArray();
    }

    /**
     * 添加运费模版价格详情
     * @param $data
     * @return mixed
     */
    public function addSupplierFreightdetail($data) {
        $this->supplierFreightDetail->save($data);
        return $this->supplierFreightDetail->id;
    }

    /**
     * 添加运费模版价格详情
     * @param $data
     * @return mixed
     * @author
     */
    public function editSupplierFreightdetail($data, $id) {
        return $this->supplierFreightDetail->save($data, ['id' => $id]);
    }

    /**
     * 获取运费模版价格详情列表
     * @param $where
     * @param string $field
     * @return array
     */
    public function getSupplierFreightDetail($where, $field = '*') {
        $obj = $this->supplierFreightDetail->field($field);
        if (!empty($where)) {
            $obj = $obj->where($where);
        }
        return $obj->select()->toArray();
    }

    /**
     * 获取运费详情地区价格关系
     * @param $where
     * @param string $field
     * @return array
     */
    public function getSupplierFreightArea($where, $field = '*') {
        $obj = $this->supplierFreightArea->field($field);
        if (!empty($where)) {
            $obj = $obj->where($where);
        }
        return $obj->select()->toArray();
    }

    public function addSupplierFreightArea($data) {
        return $this->supplierFreightArea->saveAll($data);
    }

    /**
     * 根据Where获取三级分类商品列表
     * @param $field
     * @param $order
     * @param $limit
     * @param $where
     * @return array
     */
    public function getGoods($field, $limit = false, $order, $where) {
        $obj = Goods::field($field)->where($where)->order($order, 'desc');
        if ($limit == true) {
            $obj = $obj->limit($limit);
        }
        return $obj->select()->toArray();
    }

    /**
     * 获取专题信息
     * @param $where
     * @param $field
     * @param bool $row
     * @param bool $image
     * @return array
     * @author zyr
     */
    public function getSubject($where, $field, $row = false, $image = false) {
        $obj = GoodsSubject::where($where)->field($field);
        if ($image === true) {
            $obj = $obj->with([
                'goodsSubjectImage' => function ($query) {
                    $query->field('subject_id,source_type,image_path');
                }]);
        }
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        } else {
            return $obj->order('order_by', 'asc')->select()->toArray();
        }
    }

    /**
     * 添加一个专题
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addSubject($data) {
        $goodsSubject = new GoodsSubject();
        $goodsSubject->save($data);
        return $goodsSubject->id;
    }

    /**
     * 编辑一个专题
     * @param $data
     * @param $id
     * @return bool
     */
    public function updateSubject($data, $id) {
        $goodsSubject = new GoodsSubject();
        return $goodsSubject->save($data, ['id' => $id]);
    }

    /**
     * 添加一个专题图片
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addSubjectImage($data) {
        $goodsSubjectImage = new GoodsSubjectImage();
        $goodsSubjectImage->save($data);
        return $goodsSubjectImage->id;
    }

    /**
     * 修改专题图片
     * @param $data
     * @param $id
     * @return bool
     * @author zyr
     */
    public function updateSubjectImage($data, $id) {
        $goodsSubjectImage = new GoodsSubjectImage();
        return $goodsSubjectImage->save($data, ['id' => $id]);
    }

    /**
     * 获取商品专题图片
     * @param $where
     * @param $field
     * @param bool $row
     * @return array
     */
    public function getSubjectImage($where, $field, $row = false) {
        $obj = GoodsSubjectImage::where($where)->field($field);
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        return $obj->select()->toArray();
    }

    /**
     * 获取商品专题关联关系
     * @param $where
     * @param $field
     * @param bool $row
     * @return array
     */
    public function getSubjectRelation($where, $field, $row = false, $limit = false) {
        $obj = GoodsSubjectRelation::where($where)->field($field);
        if ($row === true) {
            return $obj->findOrEmpty()->toArray();
        }
        if ($limit) {
            return $obj->limit($limit)->select()->toArray();
        }
        return $obj->select()->toArray();
    }

    /**
     * 添加商品专题关联关系
     * @param $data
     * @return mixed
     */
    public function addSubjectRelation($data) {
        $goodsSubjectRelation = new GoodsSubjectRelation();
        $goodsSubjectRelation->save($data);
        return $goodsSubjectRelation->id;
    }

    /**
     * 删除商品专题关联
     * @param $data
     * @return bool
     * @author zyr
     */
    public function delSubjectRelation($data) {
        return GoodsSubjectRelation::destroy($data);
    }

    public function delSubject($data) {
        return GoodsSubject::destroy($data);
    }

    public function getSupAdmin($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = SupAdmin::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function getSupAdminCount($where) {
        return SupAdmin::where($where)->count();
    }

    public function addSupAdmin($data){
        $supAdmin  =new SupAdmin();
        $supAdmin->save($data);
        return $supAdmin->id;
    }

}