<?php

namespace app\common\action\admin;

use app\facade\DbImage;
use think\Db;
use app\facade\DbGoods;
use Config;

class Goods {
    /**
     * 商品列表
     * @param $page
     * @param $pageNum
     * @param $goodsId
     * @param $status
     * @param $goodsType
     * @param string $cateName
     * @param string $goodsName
     * @param string $supplierName
     * @return array
     * @author zyr
     */
    public function goodsList(int $page, int $pageNum, $goodsId = 0, $status = 0, $goodsType = 0, $cateName = '', $goodsName = '', $supplierName = '') {
        $offset = $pageNum * ($page - 1);
        //查找所有商品数据
        $where = [];
        if (!empty($cateName)) {
            $classIdArr = DbGoods::getGoodsClass('id', [['type_name', 'like', '%' . $cateName . '%'], ['tier', '=', '3']]);
            $classId    = array_column($classIdArr, 'id');
            array_push($where, ['cate_id', 'in', $classId]);
        }
        if (!empty($supplierName)) {
            $supplierArr = DbGoods::getSupplier('id', [['name', 'like', '%' . $supplierName . '%']]);
            $supplierId  = array_column($supplierArr, 'id');
            array_push($where, ['pz_goods.supplier_id', 'in', $supplierId]);
        }
        if (!empty($goodsName)) {
            array_push($where, ['pz_goods.goods_name', 'like', '%' . $goodsName . '%']);
        }
        if (!empty($goodsId)) {
            array_push($where, ['pz_goods.id', '=', $goodsId]);
        }
        if (!empty($status)) {
            array_push($where, ['pz_goods.status', '=', $status]);
        }
        if (!empty($goodsType)) {
            array_push($where, ['pz_goods.goods_type', '=', $goodsType]);
        }
        $field      = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,status";
        $goods_data = DbGoods::getGoodsList($field, $where, $offset, $pageNum);
        $total      = DbGoods::getGoodsListNum($where);
        if (empty($goods_data)) {
            return ["msg" => "商品数据不存在", "code" => '3000'];
        }
        foreach ($goods_data as $gk => $gd) {
            $goods_data[$gk]['supplier'] = '';
            if (isset($gd['supplier'])) {
                $goods_data[$gk]['supplier'] = $gd['supplier']['name'];
            }
            $goods_data[$gk]['cate'] = '';
            if (isset($gd['goods_class'])) {
                $goods_data[$gk]['cate'] = $gd['goods_class']['type_name'];
                unset($goods_data[$gk]['goods_class']);

            }
        }
        return ["code" => '200', "total" => $total, "data" => $goods_data];
    }


    /**
     * 保存商品基础信息(添加或更新)
     * @param $data
     * @param $goodsId
     * @return array
     * @author zyr
     */
    public function saveGoods($data, $goodsId = 0) {
        $cate = DbGoods::getOneCate(['id' => $data['cate_id']], 'tier');
        if ($cate['tier'] != 3) {//分类id不是三级
            return ['code' => '3007'];
        }
        $supplier = DbGoods::getOneSupplier(['id' => $data['supplier_id']], 'id');
        if (empty($supplier)) {//供应商id不存在
            return ['code' => '3008'];
        }
        $logImage = [];
        if (!empty($goodsId)) {//更新操作
            $goodsRepe = DbGoods::getOneGoods([['goods_name', '=', $data['goods_name']], ['id', '<>', $goodsId]], 'id');
            if (!empty($goodsRepe)) {//商品name重复
                return ['code' => '3006', 'goods_id' => $goodsRepe['id']];
            }
            $goods       = DbGoods::getOneGoods(['id' => $goodsId], 'image');
            $oldLogImage = [];
            if (!empty($data['image'])) {//提交了图片
                $image    = filtraImage(Config::get('qiniu.domain'), $data['image']);
                $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
                if (empty($logImage)) {//图片不存在
                    return ['code' => '3010'];//图片没有上传过
                }
                $oldImage = $goods['image'];
                $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);
                if (!empty($oldImage)) {//之前有图片
                    if (stripos($oldImage, 'http') === false) {//新版本图片
                        $oldLogImage = DbImage::getLogImage($oldImage, 1);//之前在使用的图片日志
                    }
                }
                $data['image'] = $image;
            }
//            print_r($oldLogImage);die;
            Db::startTrans();
            try {
                $updateRes = DbGoods::editGoods($data, $goodsId);
                if (!empty($logImage)) {
                    DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
                }
                if (!empty($oldLogImage)) {
                    DbImage::updateLogImageStatus($oldLogImage, 3);//更新状态为弃用
                }
                if ($updateRes) {
                    Db::commit();
                    return ['code' => '200'];
                }
                Db::rollback();
                return ['code' => '3009'];//修改失败
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3009'];//修改失败
            }
        } else {//添加操作
            $image    = filtraImage(Config::get('qiniu.domain'), $data['image']);
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3010'];//图片没有上传过
            }
            Db::startTrans();
            try {
                $data['image'] = $image;
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
                $gId = DbGoods::addGoods($data);//添加后的商品id
                if ($gId === false) {
                    Db::rollback();
                    return ['code' => '3009'];//添加失败
                }
                Db::commit();
                return ['code' => '200', 'goods_id' => $gId];
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3009'];
            }
        }
    }

    /**
     * 编辑商品sku
     * @param $skuId
     * @param $data
     * @param $weight
     * @param $volume
     * @return array
     */
    public function editGoodsSku($skuId, $data, $weight, $volume) {
        $sku = DbGoods::getOneGoodsSku(['id' => $skuId], 'id,goods_id,sku_image', true);
        if (empty($sku)) {
            return ['code' => '3007'];//skuid不存在
        }
        if ($data['stock'] > 0) {
            if ($data['retail_price'] <= 0 || $data['cost_price'] <= 0) {
                return ['code' => '3010'];//请填写零售价和成本价
            }
        }
        $goodsId  = $sku['goods_id'];
        $goodsRow = DbGoods::getOneGoods(['id' => $goodsId], 'status,supplier_id');
        if ($goodsRow['status'] == 1) {
            return ['code' => '3013'];//商品下架才能编辑
        }
        $freightId    = $data['freight_id'];
        $supplieStype = DbGoods::getSupplierFreight('stype', $freightId);
        if ($supplieStype['stype'] == 2) {//重量
            if (!is_numeric($weight) || $weight <= 0) {
                return ['code' => '3011'];//选择重量模版必须填写重量
            }
        }
        if ($supplieStype['stype'] == 3) {//体积
            if (!is_numeric($volume) || $volume <= 0) {
                return ['code' => '3012'];//选择体积模版必须填写体积
            }
        }
        $image = $data['sku_image'];
        unset($data['sku_image']);
        $logImage = [];
        $oldImage = [];
        if (!empty($image)) {
            $image    = filtraImage(Config::get('qiniu.domain'), $image);//去除域名
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3005'];//图片没有上传过
            }
            $oldImage          = DbImage::getLogImage(filtraImage(Config::get('qiniu.domain'), $sku['sku_image']), 1);//之前在使用的图片日志
            $data['sku_image'] = $image;
        }
        $supplierId     = $goodsRow['supplier_id'];//供应商id
        $supplierIdList = DbGoods::getSupplierFreights('id', $supplierId);
        $supplierIdList = array_column($supplierIdList, 'id');
        if (!in_array($data['freight_id'], $supplierIdList)) {
            return ['code' => '3009'];
        }
        Db::startTrans();
        try {
            if (!empty($logImage)) {
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            if (!empty($oldImage)) {
                DbImage::updateLogImageStatus($oldImage, 3);//更新状态为弃用
            }
            DbGoods::editGoodsSku($data, $skuId);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008'];
        }
    }

    /**
     * 添加商品的规格属性
     * @param $attrId
     * @param $goodsId
     * @return array
     */
    public function addGoodsSpec(int $attrId, int $goodsId) {
        $checkRes = $this->checkGoods($attrId, $goodsId);
        if ($checkRes['code'] !== '200') {
            return $checkRes;
        }
        $specId       = $checkRes['spec_id'];
        $relationArr  = DbGoods::getGoodsRelation(['goods_id' => $goodsId], 'spec_id,attr_id');//商品的类目属性关系
        $relationList = [];//现有的规格属性列表
        foreach ($relationArr as $val) {
            if ($val['spec_id'] == $specId && $val['attr_id'] == $attrId) {
                return ['code' => '3006'];//商品已有该规格属性
            }
            $relationList[$val['spec_id']][] = $val['attr_id'];
        }
        $relationList[$specId][] = $attrId;
        $carte                   = $this->cartesian(array_values($relationList));
        $skuWhere                = ['goods_id' => $goodsId];
        $goodsSkuList            = DbGoods::getOneGoodsSku($skuWhere, 'id,spec,sku_image');
        $delId                   = [];//需要删除的sku id
        $delImage                = [];
//        print_r($goodsSkuList);die;
        foreach ($goodsSkuList as $sku) {
            if (in_array($sku['spec'], $carte)) {
                $delKey = array_search($sku['spec'], $carte);
                if ($delKey !== false) {
                    array_splice($carte, $delKey, 1);
                }
            } else {
                array_push($delId, $sku['id']);
                if (!empty($sku['sku_image'])) {
                    array_push($delImage, filtraImage(Config::get('qiniu.domain'), $sku['sku_image']));
                }
            }
        }
        $updateImageListSave = [];
        if (!empty($delImage)) {
            $updateImageList = DbImage::getLogImageList([['image_path', 'in', $delImage], ['status', '=', 1]], 'id');
            foreach ($updateImageList as $uil) {
                $uil['status'] = 3;
                array_push($updateImageListSave, $uil);
            }
        }
        $data = [];
        foreach ($carte as $ca) {
            array_push($data, ['spec' => $ca, 'goods_id' => $goodsId]);
        }
        Db::startTrans();
        try {
            $flag = false;
            if (!empty($delId)) {
                DbGoods::delSku($delId);
                $flag = true;
            }
            if (!empty($data)) {
                DbGoods::addRelation(['goods_id' => $goodsId, 'spec_id' => $specId, 'attr_id' => $attrId]);
                DbGoods::addSkuList($data);
                $flag = true;
            }
            if (!empty($updateImageListSave)) {
                DbImage::updateLogImageStatusList($updateImageListSave);
            }
            if ($flag === false) {
                Db::rollback();
                return ['code' => '3008'];
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            print_r($e);
            die;
            Db::rollback();
            return ['code' => '3007'];
        }
    }

    /**
     * 删除商品的规格属性
     * @param $attrId
     * @param $goodsId
     * @return array
     */
    public function delGoodsSpec(int $attrId, int $goodsId) {
        $checkRes = $this->checkGoods($attrId, $goodsId);
        if ($checkRes['code'] !== '200') {
            return $checkRes;
        }
        $specId      = $checkRes['spec_id'];
        $relationArr = DbGoods::getGoodsRelation(['goods_id' => $goodsId], 'spec_id,attr_id');//商品的类目属性关系
        if (!in_array($attrId, array_column($relationArr, 'attr_id'))) {
            return ['code' => '3006'];//该商品未绑定这个属性
        }
        $relationList = [];//现有的规格属性列表
        foreach ($relationArr as $val) {
            if ($val['spec_id'] == $specId && $val['attr_id'] == $attrId) {
                continue;//商品已有该规格属性,需要删除
//                return ['code' => '3006'];
            }
            $relationList[$val['spec_id']][] = $val['attr_id'];
        }
        $carte        = $this->cartesian(array_values($relationList));
        $skuWhere     = ['goods_id' => $goodsId];
        $goodsSkuList = DbGoods::getOneGoodsSku($skuWhere, 'id,spec');
        $delId        = [];//需要删除的sku id
        $delImage     = [];
        foreach ($goodsSkuList as $sku) {
            if (!in_array($sku['spec'], $carte)) {
                array_push($delId, $sku['id']);
                if (!empty($sku['sku_image'])) {
                    array_push($delImage, filtraImage(Config::get('qiniu.domain'), $sku['sku_image']));
                }
            } else {
                $delKey = array_search($sku['spec'], $carte);
                if ($delKey !== false) {
                    array_splice($carte, $delKey, 1);
                }
            }
        }
        $updateImageListSave = [];
        if (!empty($delImage)) {
            $updateImageList = DbImage::getLogImageList([['image_path', 'in', $delImage], ['status', '=', 1]], 'id');
            foreach ($updateImageList as $uil) {
                $uil['status'] = 3;
                array_push($updateImageListSave, $uil);
            }
        }
        $delRelationId = DbGoods::getGoodsRelation(['goods_id' => $goodsId, 'attr_id' => $attrId], 'id');
        $data          = [];
        foreach ($carte as $ca) {
            array_push($data, ['spec' => $ca, 'goods_id' => $goodsId]);
        }
        Db::startTrans();
        try {
            $flag = false;
            if (!empty($delId)) {
                DbGoods::delSku($delId);
                $flag = true;
            }
            if (!empty($delRelationId)) {
                DbGoods::deleteGoodsRelation(array_column($delRelationId, 'id'));
                $flag = true;
            }
            if (!empty($data)) {
                DbGoods::addSkuList($data);
                $flag = true;
            }
            if (!empty($updateImageListSave)) {
                DbImage::updateLogImageStatusList($updateImageListSave);
            }
            if ($flag === false) {
                Db::rollback();
                return ['code' => '3008'];
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3007'];
        }
    }

    /**
     * 获取sku列表
     * @param $skuId
     * @return array
     */
    public function getGoodsSku($skuId) {
//        $aaa = DbGoods::getGoodsSku(['id'=> 18], 'id,goods_name', 'goods_id,stock,spec');
//        $aaa = DbGoods::getSpecAttr([['id', 'in', [1, 2, 3]]], 'id,spe_name', 'spec_id,attr_name', 'goods_id,spec_id');
//        print_r($aaa);
//        die;
//        DbGoods::getOneGoodsSku(['goods_id'=>$goodsId],'goods_id');

//        $goods = DbGoods::getGoods('id', '0,1', 'id', ['id' => $goodsId]);
//        if (empty($goods)) {
//            return ['code' => '3001'];
//        }


        $result = DbGoods::getSku(['id' => $skuId], 'id,goods_id,freight_id,stock,market_price,retail_price,cost_price,margin_price,integral_price,integral_active,weight,volume,spec,sku_image');
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'data' => $result[0]];
    }

    /**
     * 获取一条商品数据
     * @param $id
     * @return array
     * @author wujunjie
     * 2019/1/2-16:42
     */
    public function getOneGoods($id, $getType) {
        //根据商品id找到商品表里面的基本数据
        $goods_data = [];
        if (in_array(1, $getType)) {
            $where      = [["id", "=", $id]];
            $field      = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
            $goods_data = DbGoods::getOneGoods($where, $field);
            if (empty($goods_data)) {
                return ["code" => 3000];
            }
            $goodsClass                  = DbGoods::getTier($goods_data['cate_id']);
            $goods_data['goods_class']   = $goodsClass['type_name'] ?? '';
            $supplier                    = DbGoods::getOneSupplier(['id' => $goods_data['supplier_id']], 'name');
            $goods_data['supplier_name'] = $supplier['name'];
        }
        //根据商品id找到商品图片表里面的数据
        $imagesDetatil  = [];
        $imagesCarousel = [];
        if (in_array(3, $getType)) {
            $where          = [["goods_id", "=", $id], ['image_type', 'in', [1, 2]]];
            $field          = "goods_id,image_type,image_path";
            $images_data    = DbGoods::getOneGoodsImage($where, $field);
            $imagesDetatil  = [];//商品详情图
            $imagesCarousel = [];//商品轮播图
            foreach ($images_data as $im) {
                if (stripos($im['image_path'], 'http') === false) {//新版本图片
                    $im['image_path'] = Config::get('qiniu.domain') . '/' . $im['image_path'];
                }
                if ($im['image_type'] == 1) {
                    array_push($imagesDetatil, $im);
                }
                if ($im['image_type'] == 2) {
                    array_push($imagesCarousel, $im);
                }
            }
        }

        $specAttr = [];
        if (in_array(2, $getType)) {
            $specAttrRes = DbGoods::getGoodsSpecAttr(['goods_id' => $id], 'id,spec_id,attr_id', 'id,cate_id,spe_name', 'id,spec_id,attr_name');
            foreach ($specAttrRes as $specVal) {
                $specVal['spec_name'] = $specVal['goods_spec']['spe_name'];
                $specVal['attr_name'] = $specVal['goods_attr']['attr_name'];
                unset($specVal['goods_spec']);
                unset($specVal['goods_attr']);
                array_push($specAttr, $specVal);
            }
        }

        //根据商品id获取sku表数据
        $sku = [];
        if (in_array(4, $getType)) {
            $where = [["goods_id", "=", $id]];
            $field = "id,goods_id,freight_id,stock,market_price,retail_price,cost_price,margin_price,integral_price,integral_active,weight,volume,spec,sku_image";
            $sku   = DbGoods::getSku($where, $field);
        }
        return ["code" => 200, "goods_data" => $goods_data, 'spec_attr' => $specAttr, 'images_detatil' => $imagesDetatil, "images_carousel" => $imagesCarousel, "sku" => $sku];
    }

//    public function delGoods($id) {
//        //开启事务
//        Db::startTrans();
//        try {
//            //删除商品基本数据
//            DbGoods::delGoods($id);
//            DbGoods::delGoodsImage($id);
//            DbGoods::delGoodsSku($id);
//            DbGoods::delGoodsRelation($id);
//            Db::commit();
//            return ["msg" => "删除成功", "code" => 200];
//        } catch (\Exception $e) {
//            Db::rollback();
//            return ["msg" => "删除失败", "code" => 3001];
//        }
//
//    }

    /**
     * 上传商品的轮播图和详情图
     * @param $goodsId
     * @param $imageType 1.详情图 2.轮播图
     * @param $images
     * @return array
     */
    public function uploadGoodsImages($goodsId, $imageType, $images) {
        $goods = DbGoods::getOneGoods(['id' => $goodsId], 'id');
        if (empty($goods)) {
            return ['code' => '3004'];
        }
        $data    = [];
        $logData = [];
        $orderBy = 0;
        foreach ($images as $img) {
            $image    = filtraImage(Config::get('qiniu.domain'), $img);//去除域名
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3005'];//图片没有上传过
            }
            $logImage['status'] = 1;//更新为完成状态
            $orderBy++;
            $row = [
                'goods_id'    => $goodsId,
                'source_type' => 4,
                'image_type'  => $imageType,
                'image_path'  => $image,
                'order_by'    => $orderBy,
            ];
            array_push($logData, $logImage);
            array_push($data, $row);
        }
//        print_r($data);die;
        Db::startTrans();
        try {
            DbGoods::addGoodsImageList($data);
            DbImage::updateLogImageStatusList($logData);//更新状态为已完成
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3006"];
        }
    }


    /**
     * 删除商品详情和轮播图
     * @param $imagePath
     * @return array
     */
    public function delGoodsImage($imagePath) {
        $imagePath  = filtraImage(Config::get('qiniu.domain'), $imagePath);//要删除的图片
        $goodsImage = DbGoods::getOneGoodsImage(['image_path' => $imagePath], 'id');
        if (empty($goodsImage)) {
            return ['code' => '3002'];
        }
        $goodsImageId = array_column($goodsImage, 'id');
        $goodsImageId = $goodsImageId[0];
        $oldLogImage  = [];
        if (stripos($imagePath, 'http') === false) {//新版本图片
            $oldLogImage = DbImage::getLogImage($imagePath, 1);//之前在使用的图片日志
        }
        Db::startTrans();
        try {
            if (!empty($oldLogImage)) {
                DbImage::updateLogImageStatus($oldLogImage, 3);//更新状态为弃用
            }
            DbGoods::delGoodsImage($goodsImageId);
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3003"];
        }
    }

    /**
     * 上下架
     * @param $id
     * @param $type 1.上架 ,2.下架
     * @return array
     * @author zyr
     * 2019/1/8-10:13
     */
    public function upDown(int $id, int $type) {
        //判断传过来的id是否有效
        $where = [["id", "=", $id]];
        $field = "goods_name";
        $res   = DbGoods::getOneGoods($where, $field);
        if (empty($res)) {
            return ["code" => '3001'];
        }
        if ($type == 1) {// 上架
            $stockAll = 0;
            $sku      = DbGoods::getOneGoodsSku([], 'id,stock,freight_id,retail_price,cost_price,sku_image');
            foreach ($sku as $s) {
                $stockAll = bcadd($stockAll, $s['stock'], 2);
                if ($s['stock'] > 0) {
                    if ($s['retail_price'] == 0) {
                        return ['code' => '3004'];//请填写零售价
                    }
                    if ($s['cost_price'] == 0) {
                        return ['code' => '3005'];//请填写成本价
                    }
                }
            }
            if ($stockAll <= 0) {
                return ['code' => '3003'];//没有可售库存
            }
            //1.详情图 2.轮播图
            $goodsImage     = DbGoods::getOneGoodsImage(['goods_id' => $id], 'id,image_type');
            $goodsImageType = array_unique(array_column($goodsImage, 'image_type'));
            if (!in_array(1, $goodsImageType)) {//没有详情图
                return ['code' => '3006'];
            }
            if (!in_array(2, $goodsImageType)) {//没有轮播图
                return ['code' => '3007'];
            }
        }
        $data = [//修改状态
            "status" => $type
        ];
        $res  = DbGoods::editGoods($data, $id);
        if (empty($res)) {
            return ["code" => '3008'];
        }
        return ["msg" => '成功', "code" => '200'];
    }

    /**
     * 验证规格属性和商品是否存在
     * @param $attrId
     * @param $goodsId
     * @return array
     */
    private function checkGoods($attrId, $goodsId) {
        $goodsAttrOne = DbGoods::getOneAttr(['id' => $attrId], 'spec_id,attr_name');
        if (empty($goodsAttrOne)) {
            return ['code' => '3003'];//属性不存在
        }
        $specId   = $goodsAttrOne['spec_id'];
        $goodsOne = DbGoods::getOneGoods(['id' => $goodsId], 'id,cate_id');
        if (empty($goodsOne)) {
            return ['code' => '3004'];//商品不存在
        }
        $goodsSpecOne = DbGoods::getOneSpec(['id' => $specId], 'id,cate_id');
        if (empty($goodsSpecOne)) {
            return ['code' => '3005'];//规格为空
        }
        if ($goodsSpecOne['cate_id'] != $goodsOne['cate_id']) {
            return ['code' => '3009'];//提交的属性分类和商品分类不同
        }
        return ['code' => '200', 'spec_id' => $specId];
    }

    private function cartesian($sets) {
        $result = [];// 保存结果
        if (count($sets) == 1) {
            foreach ($sets[0] as $s) {
                $result[] = $s;
            }
        }
        for ($i = 0, $count = count($sets); $i < $count - 1; $i++) {// 循环遍历集合数据
            if ($i == 0) {// 初始化
                $result = $sets[$i];
            }
            $tmp = array();// 保存临时数据
            // 结果与下一个集合计算笛卡尔积
            foreach ($result as $res) {
                foreach ($sets[$i + 1] as $set) {
                    $tmp[] = $res . ',' . $set;
                }
            }
            $result = $tmp;// 将笛卡尔积写入结果
        }
        foreach ($result as &$r) {
            $v = $r;
            if (!is_array($r)) {
                $v = explode(',', $r);
            }
            sort($v, SORT_NUMERIC);
            $r = implode(',', $v);
        }
        return $result;
    }
}