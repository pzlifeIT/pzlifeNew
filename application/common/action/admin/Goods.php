<?php

namespace app\common\action\admin;

use app\facade\DbAudios;
use app\facade\DbImage;
use app\facade\DbLabel;
use Overtrue\Pinyin\Pinyin;
use think\Db;
use app\facade\DbGoods;
use Config;

class Goods extends CommonIndex {
    private $transformRedisKey;
    private $labelLibraryRedisKey;
    private $labelLibraryHeatRedisKey;

    public function __construct() {
        parent::__construct();
        $this->transformRedisKey        = Config::get('rediskey.label.redisLabelTransform');
        $this->labelLibraryRedisKey     = Config::get('rediskey.label.redisLabelLibrary');
        $this->labelLibraryHeatRedisKey = Config::get('rediskey.label.redisLabelLibraryHeat');
    }

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
    public function goodsList(int $page, int $pageNum, $goodsId = 0, $status = 0, $goodsType = 0, $cateName = '', $goodsName = '', $supplierName = '', $supplierTitle = '') {
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
        if (!empty($supplierTitle)) {
            $supplierArr = DbGoods::getSupplier('id', [['title', 'like', '%' . $supplierTitle . '%']]);
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
        $field      = "id,image,supplier_id,cate_id,goods_name,goods_type,target_users,title,subtitle,status,is_integral_sale";
        $goods_data = DbGoods::getGoodsList($field, $where, $offset, $pageNum, 'id desc');
        $total      = DbGoods::getGoodsListNum($where);
        if (empty($goods_data)) {
            return ["msg" => "商品数据不存在", "code" => '3000'];
        }
        foreach ($goods_data as $gk => $gd) {
            $goods_data[$gk]['supplier'] = '';
            if (isset($gd['supplier'])) {
                $goods_data[$gk]['supplier']       = $gd['supplier']['name'];
                $goods_data[$gk]['supplier_title'] = $gd['supplier']['title'];
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
        if ($data['goods_sheet']) {
            $goods_sheet = DbGoods::getSheet(['id' => $data['goods_sheet']],'id',true);
            if (empty($goods_sheet)){
                return ['code' => '3013'];
            }
        }

        $logImage = [];
        $logShareImage = [];
        if (!empty($goodsId)) {//更新操作
            $goodsRepe = DbGoods::getOneGoods([['goods_name', '=', $data['goods_name']], ['id', '<>', $goodsId]], 'id');
            if (!empty($goodsRepe)) {//商品name重复
                return ['code' => '3006', 'goods_id' => $goodsRepe['id']];
            }
            $goods       = DbGoods::getOneGoods(['id' => $goodsId], 'image,share_image');
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
            $oldLogShareImage = [];
            if (!empty($data['share_image'])) {
                $share_image = filtraImage(Config::get('qiniu.domain'), $data['share_image']);
                $logShareImage    = DbImage::getLogImage($share_image, 2); //判断时候有未完成的图片
                if (empty($logShareImage)) {//分享图片不存在
                    return ['code' => '3012'];//分享图片没有上传过
                }
                $oldShareImage = $goods['share_image'];
                if (!empty($oldShareImage)) {
                    if (stripos($oldShareImage, 'http') === false) {//新版本图片
                        $oldLogShareImage = DbImage::getLogImage($oldShareImage, 1);//之前在使用的图片日志
                    }
                }
                $data['share_image'] = $share_image;
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
                if (!empty($logShareImage)) {
                    DbImage::updateLogImageStatus($logShareImage, 1);//更新状态为已完成
                }
                if (!empty($oldLogShareImage)) {
                    DbImage::updateLogImageStatus($oldLogShareImage, 3);//更新状态为弃用
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
            if (isset($data['share_image'])) {
                $share_image    = filtraImage(Config::get('qiniu.domain'), $data['share_image']);
                $logShareImage = DbImage::getLogImage($share_image, 2);//判断时候有未完成的图片
                if (empty($logShareImage)) {//图片不存在
                    return ['code' => '3012'];//图片没有上传过
                }
                $data['share_image'] = $share_image;
            }
            Db::startTrans();
            try {
                $data['image'] = $image;
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
                DbImage::updateLogImageStatus($logShareImage, 1);//更新状态为已完成
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
        $supplierIdList = DbGoods::getSupplierFreights(['supid' => $supplierId, 'status' => 1], 'id');
        $supplierIdList = array_column($supplierIdList, 'id');
        if (!in_array($data['freight_id'], $supplierIdList)) {
            return ['code' => '3009'];
        }
        $data['weight'] = $weight;
        $data['volume'] = $volume;
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
        $goodsRow = DbGoods::getOneGoods(['id' => $goodsId], 'status,supplier_id');
        if ($goodsRow['status'] == 1) {
            return ['code' => '3013'];//商品下架才能编辑
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
        $skuWhere                = ['goods_id' => $goodsId, 'status' => 1];
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
                array_push($delId, ['id' => $sku['id'], 'status' => 2]);
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
        $goodsRow = DbGoods::getOneGoods(['id' => $goodsId], 'status,supplier_id');
        if ($goodsRow['status'] == 1) {
            return ['code' => '3013'];//商品下架才能编辑
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
        $skuWhere     = ['goods_id' => $goodsId, 'status' => 1];
        $goodsSkuList = DbGoods::getOneGoodsSku($skuWhere, 'id,spec');
        $delId        = [];//需要删除的sku id
        $delImage     = [];
        foreach ($goodsSkuList as $sku) {
            if (!in_array($sku['spec'], $carte)) {
                array_push($delId, ['id' => $sku['id'], 'status' => 2]);
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
     * 添加商品的音频商品规格属性
     * @param $goodsId
     * @param $name
     * @param $audioIdList
     * @param $marketPrice
     * @param $retailPrice
     * @param $costPrice
     * @param $integralPrice
     * @param $endTime
     * @return array
     */
    public function addAudioSku($goodsId, $audioIdList, $marketPrice, $retailPrice, $costPrice, $integralPrice, $endTime, $name) {
        $where    = [["id", "=", $goodsId]];
        $field    = "id,goods_type";
        $goodsOne = DbGoods::getOneGoods($where, $field);
        if ($goodsOne['goods_type'] != 2) {
            return ['code' => '3007'];
        }
        $audioCount = DbAudios::countAudio([['id', 'in', $audioIdList]]);
        if ($audioCount != count($audioIdList)) {//音频不存在无法添加
            return ['code' => '3003'];
        }
        $skuData = [
            'goods_id'       => $goodsId,
            'name'           => $name,
            'market_price'   => $marketPrice,
            'retail_price'   => $retailPrice,
            'cost_price'     => $costPrice,
            'integral_price' => $integralPrice,
            'end_time'       => $endTime * 3600,
        ];
        Db::startTrans();
        try {
            $audioSkuId = DbAudios::saveAudioSku($skuData);
            $relation   = [];
            foreach ($audioIdList as $val) {
                array_push($relation, ['audio_pri_id' => $val, 'audio_sku_id' => $audioSkuId]);
            }
            DbAudios::saveAllAudioSkuRelation($relation);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            print_r($e);
            Db::rollback();
            return ['code' => '3008'];//更新失败
        }
    }

    public function getGoodsAudioSku($sku_id, $goodsId){
        $sku = DbAudios::getAudiosSku(['id' => $sku_id, 'goods_id' => $goodsId], '*', true);
        if (empty($sku)){
            return ['code' => '200', 'sku' => []];
        }
        $sku['end_time'] = $sku['end_time'] / 3600;
        $sku['audioIdList'] = DbAudios::getAudioSkuRelation(['audio_sku_id' => $sku_id],'audio_pri_id');
        return ['code' => '200', 'sku' => $sku];
    }

    /**
     * 修改商品的音频商品规格属性
     * @param $goodsId
     * @param $sku_id
     * @param $name
     * @param $audioIdList
     * @param $marketPrice
     * @param $retailPrice
     * @param $costPrice
     * @param $integralPrice
     * @param $endTime
     * @return array
     */
    public function saveAudioSku($goodsId, $sku_id, $audioIdList = '', $marketPrice = 0, $retailPrice = 0, $costPrice = 0, $integralPrice = 0, $endTime = 0, $name = ''){
        $where    = [["id", "=", $goodsId]];
        $field    = "id,goods_type,status";
        $goodsOne = DbGoods::getOneGoods($where, $field);
        if ($goodsOne['goods_type'] != 2) {
            return ['code' => '3007'];
        }
        if ($goodsOne['status'] == 1) {
            return ['code' => '3011'];
        }
        if (!DbAudios::getAudiosSku(['id' => $sku_id], 'id', true)){
            return ['code' => '3009'];
        }
        $relation   = [];
        $delrelation = [];
        if (!empty($audioIdList)){
            $audioCount = DbAudios::countAudio([['id', 'in', $audioIdList]]);
            if ($audioCount != count($audioIdList)) {//音频不存在无法添加
                return ['code' => '3003'];
            }
            $has_relation = DbAudios::getAudioSkuRelation(['audio_sku_id' => $sku_id],'id');
            foreach ($has_relation as $value) {
                array_push($delrelation,$value['id']);
            }
            foreach ($audioIdList as $val) {
                array_push($relation, ['audio_pri_id' => $val, 'audio_sku_id' => $sku_id]);
            }
        }
        $data = [];
        if (isset($marketPrice)) {
            $data['market_price'] = $marketPrice;
        }
        if (isset($retailPrice)) {
            $data['retail_price'] = $retailPrice;
        }
        if (isset($costPrice)) {
            $data['cost_price'] = $costPrice;
        }
        if (isset($integralPrice)) {
            $data['integral_price'] = $integralPrice;
        }
        if (isset($endTime)) {
            $data['end_time'] = $endTime * 3600;
        }
        if (!empty($name)) {
            $data['name'] = $name;
        }
        //print_r($data);die;
        Db::startTrans();
        try {
            DbAudios::updateAudiosSku($data, $sku_id);
            if (!empty($delrelation)) {
                $del = DbAudios::delAudioSkuRelation($delrelation);
            }
            if (!empty($relation)) {
                DbAudios::saveAllAudioSkuRelation($relation);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ['code' => '3008'];//更新失败
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


        $result = DbGoods::getSku(['id' => $skuId, 'status' => 1], 'id,goods_id,freight_id,stock,market_price,retail_price,cost_price,margin_price,integral_price,weight,volume,spec,sku_image,integral_sale_stock');
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'data' => $result[0]];
    }

    /**
     * 获取一条商品数据
     * @param $id
     * @param $getType
     * @param $goodsType
     * @return array
     * @author wujunjie
     * 2019/1/2-16:42
     */
    public function getOneGoods($id, $getType, $goodsType) {
        //根据商品id找到商品表里面的基本数据
        $where    = [["id", "=", $id]];
        $field    = "id,supplier_id,cate_id,goods_name,goods_type,target_users,title,subtitle,image,status,share_image,giving_rights,goods_sheet,is_integral_sale";
        $goodsOne = DbGoods::getOneGoods($where, $field);
        if ($goodsOne['goods_type'] != $goodsType) {
            return ['code' => '3005'];//该商品不属于这个类型
        }
        $goods_data = [];
        if (in_array(1, $getType)) {
            $goods_data = $goodsOne;
            if (empty($goods_data)) {
                return ["code" => 3000];
            }
            $goodsClass                  = DbGoods::getTier($goods_data['cate_id']);
            $goods_data['goods_class']   = $goodsClass['type_name'] ?? '';
            $goods_data['goods_name'] = htmlspecialchars_decode($goods_data['goods_name']);
        }
        $supplier                    = DbGoods::getOneSupplier(['id' => $goodsOne['supplier_id']], 'name');
        $goods_data['supplier_name'] = '';
        $goods_data['supplier_name'] = $supplier['name'];
        //根据商品id找到商品图片表里面的数据
        $imagesDetatil  = [];
        $imagesCarousel = [];
        if (in_array(3, $getType)) {
            $where          = [["goods_id", "=", $id], ['image_type', 'in', [1, 2]]];
            $field          = "goods_id,image_type,image_path,order_by";
            $images_data    = DbGoods::getOneGoodsImage($where, $field, 'order_by asc,id asc');
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
        if (in_array(2, $getType) && $goodsType == 1) {
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
            if ($goodsType == 1) {
                $where = [["goods_id", "=", $id], ['status', '=', 1]];
                $field = "id,goods_id,freight_id,stock,market_price,retail_price,cost_price,margin_price,integral_price,weight,volume,spec,sku_image,integral_sale_stock";
                $sku   = DbGoods::getSku($where, $field);
            }
            if ($goodsType == 2) {
                $sku = DbGoods::getAudioSkuRelation([['goods_id', '=', $id]]);
                foreach ($sku as &$v) {
                    $v['end_time'] = $v['end_time'] / 3600;
                    $v['audios']   = array_map(function ($var) {
                        unset($var['pivot']);
                        return $var;
                    }, $v['audios']);
                }
                unset($v);
            }
        }
        $redisGoodsDetailKey = Config::get('rediskey.index.redisGoodsDetail') . $id;
        $source_type         = [1, 2, 3, 4];
        foreach ($source_type as $st) {
            $key = $redisGoodsDetailKey . ':' . $st;
            $this->redis->del($key);
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
     * 对图片排序
     * @param $imagePath
     * @param $orderBy
     * @return array
     */
    public function sortImageDetail($imagePath, $orderBy) {
        $imagePath  = filtraImage(Config::get('qiniu.domain'), $imagePath);//要排序的图片
        $goodsImage = DbGoods::getOneGoodsImage(['image_path' => $imagePath], 'id,order_by');
        if (empty($goodsImage)) {
            return ['code' => '3002'];
        }
        $goodsImageId = array_column($goodsImage, 'id');
        $goodsImageId = $goodsImageId[0];
        $oldOrderBy   = $goodsImage[0]['order_by'];
        if ($oldOrderBy == $orderBy) {//排序不改变无需更新
            return ["code" => '200'];
        }
        Db::startTrans();
        try {
            DbGoods::updateGoodsImage(['order_by' => $orderBy], $goodsImageId);
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
        $where     = [["id", "=", $id]];
        $field     = "goods_name,cate_id,goods_type";
        $res       = DbGoods::getOneGoods($where, $field);
        $labelName = $res['goods_name'];
        if (empty($res)) {
            return ["code" => '3001'];
        }
        if (empty($res['cate_id'])) {
            return ['code' => '3009'];
        }
        $labelGoodsRelation    = DbLabel::getLabelGoodsRelation(['goods_id' => $id], 'label_lib_id');//该商品的所有标签
        $labelGoodsRelationId  = array_column($labelGoodsRelation, 'label_lib_id');
        $labelGoodsRelation2   = DbLabel::getLabelGoodsRelationByGoods([//标签是否挂了其他已上架的商品
            ['gr.label_lib_id', 'in', $labelGoodsRelationId],
            ['gr.goods_id', '<>', $id],
            ['g.status', '=', '1'],
        ], 'gr.label_lib_id');
        $labelGoodsRelationId2 = empty($labelGoodsRelation2) ? [] : array_column($labelGoodsRelation2, 'label_lib_id');
        $labelRelationId       = array_diff($labelGoodsRelationId, $labelGoodsRelationId2);
        if ($type == 1) {// 上架
            $stockAll = 0;
            $sku      = DbGoods::getOneGoodsSku(['status' => '1', 'goods_id' => $id], 'id,stock,freight_id,retail_price,cost_price,sku_image');
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
            if ($res['goods_type'] == 1) {
                if ($stockAll <= 0) {
                    return ['code' => '3003'];//没有可售库存
                }
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
        } else {
            $redisGoodsDetailKey = Config::get('rediskey.index.redisGoodsDetail') . $id;
            $source_type         = [1, 2, 3, 4];
            foreach ($source_type as $st) {
                $key = $redisGoodsDetailKey . ':' . $st;
                $this->redis->del($key);
            }
            foreach ($labelRelationId as $lri) {
                $labelLib  = DbLabel::getLabelLibrary(['id' => $lri], 'label_name', true);
                $transList = $this->getTransformPinyin($labelLib['label_name']);
                $delFlag   = false;
                foreach ($transList as $tlk => $tl) {
                    $labelKey = $this->redis->hGet($this->transformRedisKey, $tl);
                    if ($labelKey === false) {
                        continue;
                    }
                    $labelLibraryIdList = json_decode($labelKey, true);
                    if (!in_array($lri, $labelLibraryIdList)) {
                        continue;
                    }
                    $indexKey = array_search($lri, $labelLibraryIdList);
                    if ($indexKey === false) {
                        continue;
                    }
                    array_splice($labelLibraryIdList, $indexKey, 1);
                    if (!empty($labelLibraryIdList)) {
                        $this->redis->hSet($this->transformRedisKey, $tl, json_encode($labelLibraryIdList));
                    } else {
                        $this->redis->hDel($this->transformRedisKey, $tl);
                        $delFlag = true;
                    }
                }
                if ($delFlag === true) {
//                    $this->redis->zDelete($this->labelLibraryHeatRedisKey, $lri);
                    $this->redis->hDel($this->labelLibraryRedisKey, $lri);
                }
            }
        }
        $data       = [//修改状态
            "status" => $type
        ];
        $flag       = false;
        $labelLibId = 0;
        Db::startTrans();
        try {
            DbGoods::editGoods($data, $id);
            if ($type == 1) {
                $labelRelationFlag = true;
                $labelLib          = DbLabel::getLabelLibrary(['label_name' => $labelName], 'id', true);
                if (!empty($labelLib)) { //标签库有该标签
                    $labelLibId         = $labelLib['id'];
                    $labelGoodsRelation = DbLabel::getLabelGoodsRelation(['label_lib_id' => $labelLibId, 'goods_id' => $id], 'id', true);
                    if (!empty($labelGoodsRelation)) { //标签已关联该商品
                        $labelRelationFlag = false;
                    }
                }
                if (empty($labelLibId)) { //标签库没有就添加
                    $labelLibId = DbLabel::addLabelLibrary(['label_name' => $labelName]);
                    $flag       = true;
                } else {
                    if ($labelRelationFlag === true) {
                        DbLabel::modifyHeat($labelLibId);
                    }
                }
                if ($labelRelationFlag === true) {
                    DbLabel::addLabelGoodsRelation(['goods_id' => $id, 'label_lib_id' => $labelLibId]); //添加标签商品关联
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008'];
        }
        if ($type == 1) {
            if (!empty($labelRelationId)) {
                foreach ($labelRelationId as $lri) {
                    $labelLib  = DbLabel::getLabelLibrary(['id' => $lri], 'label_name,the_heat', true);
                    $transList = $this->getTransformPinyin($labelLib['label_name']);
                    $this->setTransform($transList, $lri);
                    $this->setLabelLibrary($lri, $labelLib['label_name']);
                    $this->redis->zAdd($this->labelLibraryHeatRedisKey, $labelLib['the_heat'], $lri);
                }
            }
            if ($flag === true) {
                $this->setTransform($this->getTransformPinyin($labelName), $labelLibId);
                $this->setLabelLibrary($labelLibId, $labelName);
                $this->setLabelHeat($labelLibId, true);//执行zAdd
            }
        }
        return ["msg" => '成功', "code" => '200'];
    }

    /**
     * 上下架
     * @param $id
     * @param $type 1.上架 ,2.下架
     * @return array
     * @author zyr
     * 2019/1/8-10:13
     */
    public function upDownIntegralGoods(int $id, int $is_integral_sale) {
        //判断传过来的id是否有效
        $where     = [["id", "=", $id]];
        $field     = "goods_name,cate_id,goods_type";
        $res       = DbGoods::getOneGoods($where, $field);
        $labelName = $res['goods_name'];
        if (empty($res)) {
            return ["code" => '3001'];
        }
        if (empty($res['cate_id'])) {
            return ['code' => '3009'];
        }
        $labelGoodsRelation    = DbLabel::getLabelGoodsRelation(['goods_id' => $id], 'label_lib_id');//该商品的所有标签
        $labelGoodsRelationId  = array_column($labelGoodsRelation, 'label_lib_id');
        $labelGoodsRelation2   = DbLabel::getLabelGoodsRelationByGoods([//标签是否挂了其他已上架的商品
            ['gr.label_lib_id', 'in', $labelGoodsRelationId],
            ['gr.goods_id', '<>', $id],
            ['g.status', '=', '1'],
        ], 'gr.label_lib_id');
        $labelGoodsRelationId2 = empty($labelGoodsRelation2) ? [] : array_column($labelGoodsRelation2, 'label_lib_id');
        $labelRelationId       = array_diff($labelGoodsRelationId, $labelGoodsRelationId2);
        if ($is_integral_sale == 1) {// 上架
            $stockAll = 0;
            $sku      = DbGoods::getOneGoodsSku(['status' => '1', 'goods_id' => $id], 'id,stock,freight_id,retail_price,cost_price,sku_image');
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
            if ($res['goods_type'] == 1) {
                if ($stockAll <= 0) {
                    return ['code' => '3003'];//没有可售库存
                }
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
        } else {
            $redisGoodsDetailKey = Config::get('rediskey.index.redisGoodsDetail') . $id;
            $source_type         = [1, 2, 3, 4];
            foreach ($source_type as $st) {
                $key = $redisGoodsDetailKey . ':' . $st;
                $this->redis->del($key);
            }
            foreach ($labelRelationId as $lri) {
                $labelLib  = DbLabel::getLabelLibrary(['id' => $lri], 'label_name', true);
                $transList = $this->getTransformPinyin($labelLib['label_name']);
                $delFlag   = false;
                foreach ($transList as $tlk => $tl) {
                    $labelKey = $this->redis->hGet($this->transformRedisKey, $tl);
                    if ($labelKey === false) {
                        continue;
                    }
                    $labelLibraryIdList = json_decode($labelKey, true);
                    if (!in_array($lri, $labelLibraryIdList)) {
                        continue;
                    }
                    $indexKey = array_search($lri, $labelLibraryIdList);
                    if ($indexKey === false) {
                        continue;
                    }
                    array_splice($labelLibraryIdList, $indexKey, 1);
                    if (!empty($labelLibraryIdList)) {
                        $this->redis->hSet($this->transformRedisKey, $tl, json_encode($labelLibraryIdList));
                    } else {
                        $this->redis->hDel($this->transformRedisKey, $tl);
                        $delFlag = true;
                    }
                }
                if ($delFlag === true) {
//                    $this->redis->zDelete($this->labelLibraryHeatRedisKey, $lri);
                    $this->redis->hDel($this->labelLibraryRedisKey, $lri);
                }
            }
        }
        $data       = [//修改状态
            "is_integral_sale" => $is_integral_sale
        ];
        $flag       = false;
        $labelLibId = 0;
        Db::startTrans();
        try {
            DbGoods::editGoods($data, $id);
            if ($is_integral_sale == 1) {
                $labelRelationFlag = true;
                $labelLib          = DbLabel::getLabelLibrary(['label_name' => $labelName], 'id', true);
                if (!empty($labelLib)) { //标签库有该标签
                    $labelLibId         = $labelLib['id'];
                    $labelGoodsRelation = DbLabel::getLabelGoodsRelation(['label_lib_id' => $labelLibId, 'goods_id' => $id], 'id', true);
                    if (!empty($labelGoodsRelation)) { //标签已关联该商品
                        $labelRelationFlag = false;
                    }
                }
                if (empty($labelLibId)) { //标签库没有就添加
                    $labelLibId = DbLabel::addLabelLibrary(['label_name' => $labelName]);
                    $flag       = true;
                } else {
                    if ($labelRelationFlag === true) {
                        DbLabel::modifyHeat($labelLibId);
                    }
                }
                if ($labelRelationFlag === true) {
                    DbLabel::addLabelGoodsRelation(['goods_id' => $id, 'label_lib_id' => $labelLibId]); //添加标签商品关联
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008'];
        }
        if ($is_integral_sale == 1) {
            if (!empty($labelRelationId)) {
                foreach ($labelRelationId as $lri) {
                    $labelLib  = DbLabel::getLabelLibrary(['id' => $lri], 'label_name,the_heat', true);
                    $transList = $this->getTransformPinyin($labelLib['label_name']);
                    $this->setTransform($transList, $lri);
                    $this->setLabelLibrary($lri, $labelLib['label_name']);
                    $this->redis->zAdd($this->labelLibraryHeatRedisKey, $labelLib['the_heat'], $lri);
                }
            }
            if ($flag === true) {
                $this->setTransform($this->getTransformPinyin($labelName), $labelLibId);
                $this->setLabelLibrary($labelLibId, $labelName);
                $this->setLabelHeat($labelLibId, true);//执行zAdd
            }
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

    private function getTransformPinyin($name) {
        if (empty($name)) {
            return [];
        }
        $pinyin       = new Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        $withoutTone2 = implode('', $pinyin->convert($name, PINYIN_UMLAUT_V));
        $withoutTone  = $pinyin->permalink($name, '', PINYIN_UMLAUT_V);
        $ucWord       = $pinyin->abbr($name, '');
        $ucWord2      = $pinyin->abbr($name, '', PINYIN_KEEP_NUMBER);
        $ucWord3      = $pinyin->abbr($name, '', PINYIN_KEEP_ENGLISH);
        $data         = [
            strtolower($name), //全名
            strtolower($withoutTone), //包含非中文的全拼音
            strtolower($withoutTone2), //不包含非中文的全拼音
            strtolower($ucWord3), //拼音首字母,包含字母
            strtolower($ucWord2), //拼音首字母,包含数字
            strtolower($ucWord), //拼音首字母,不包含非汉字内容
        ];
        return array_filter(array_unique($data));
    }

    /**
     * 标签转换后存储
     * @param $trans 标签转换后的列表
     * @param $labelLibId 标签库id
     * @author zyr
     */
    private function setTransform($trans, $labelLibId) {
        $redisKey = $this->transformRedisKey;
        foreach ($trans as $t) {
            if (!$this->redis->hSetNx($redisKey, $t, json_encode([$labelLibId]))) {
                $transLabel = json_decode($this->redis->hGet($redisKey, $t), true);
                if (!in_array($labelLibId, $transLabel)) {
                    array_push($transLabel, $labelLibId);
                    $this->redis->hSet($redisKey, $t, json_encode($transLabel));
                }
            }
        }
    }

    /**
     * @description:
     * @param $labelLibId 标签库id
     * @param $name 标签名
     * @author zyr
     */
    private function setLabelLibrary($labelLibId, $name) {
        $redisKey = $this->labelLibraryRedisKey;
        $this->redis->hSetNx($redisKey, $labelLibId, $name);
    }

    private function setLabelHeat($labelLibId, $heat) {
        $redisKey = $this->labelLibraryHeatRedisKey;
        if ($heat === true) {
            $this->redis->zAdd($redisKey, 1, $labelLibId);
        } else {
            $this->redis->zIncrBy($redisKey, 1, $labelLibId);
        }
    }

    public function getSheetOption(){
        $data = [
            [
                'name' => 'name',
                'title' => '就诊人姓名',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'idcard',
                'title' => '身份证号码',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'medicare_card',
                'title' => '上海医保/医疗卡号（如有请填写）',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'mobile',
                'title' => '联系人电话',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'hospital_name',
                'title' => '挂号医院名称',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'registration_department',
                'title' => '挂号科室',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'experts_name',
                'title' => '专家姓名',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'appointment_time',
                'title' => '预约时间',
                'type' => '2',
                'create_time' => time(),
            ],
            [
                'name' => 'appointment_period',
                'title' => '预约时间段',
                'type' => '2',
                'create_time' => time(),
            ],
            [
                'name' => 'is_adjustment',
                'title' => '预约时间接受调节',
                'type' => '3',
                'create_time' => time(),
            ],
            [
                'name' => 'other_treatment',
                'title' => '外院治疗情况',
                'type' => '5',
                'create_time' => time(),
            ],
            [
                'name' => 'anamnesis',
                'title' => '既往病史',
                'type' => '5',
                'create_time' => time(),
            ],
            [
                'name' => 'get_report_barcode',
                'title' => '取报告条码',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'get_report_time',
                'title' => '需要取报告时间',
                'type' => '2',
                'create_time' => time(),
            ],
            [
                'name' => 'get_report_address',
                'title' => '需要取报告地点',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'need_escort_time',
                'title' => '需要陪护时间',
                'type' => '2',
                'create_time' => time(),
            ],
            [
                'name' => 'need_escort_place',
                'title' => '需要陪护地点',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'sex',
                'title' => '性别',
                'type' => '3',
                'create_time' => time(),
            ],
            [
                'name' => 'nick_name',
                'title' => '姓名',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'age',
                'title' => '年龄',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'phone',
                'title' => '手机号码',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'passport_number',
                'title' => '护照号码',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'e_mail',
                'title' => '邮箱',
                'type' => '1',
                'create_time' => time(),
            ],
            [
                'name' => 'appeal',
                'title' => '诉求',
                'type' => '5',
                'create_time' => time(),
            ],
            [
                'name' => 'child_household_register_image',
                'title' => '儿童户口本',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'birth_certificate_image',
                'title' => '出生证明',
                'type' => '6',
                'create_time' => time(),
            ],
            [
                'name' => 'passport_image',
                'title' => '护照原件',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'photograph',
                'title' => '照片',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'id_card_copies',
                'title' => '身份证复印件',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'household_register_copies',
                'title' => '户口本复印件',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'visa_application_form',
                'title' => '签证申请表',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'business_License_copies',
                'title' => '营业执照复印件盖公章',
                'type' => '6',
                'create_time' => time(),
            ],
            [
                'name' => 'incumbency_certification',
                'title' => '在职证明',
                'type' => '6',
                'create_time' => time(),
            ],
            [
                'name' => 'assets_certification',
                'title' => '资产证明',
                'type' => '6',
                'create_time' => time(),
            ],
            [
                'name' => 'iqama',
                'title' => '居住证',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'half_year_social_security',
                'title' => '半年社保',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'Wage_bill',
                'title' => '工资流水',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'half_bank_card_statement',
                'title' => '半年银行卡对账单',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'copy_of_credit_card_Statement',
                'title' => '信用卡对账单',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'half_year_tax_bill',
                'title' => '半年税单',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'child_birth_certificate_image',
                'title' => '儿童出生证明',
                'type' => '6',
                'create_time' => time(),
            ],
            [
                'name' => 'student_identity_card',
                'title' => '学生证',
                'type' => '6',
                'create_time' => time(),
            ],
            [
                'name' => 'certificate_of_criminal_record',
                'title' => '犯罪记录证明',
                'type' => '6',
                'create_time' => time(),
            ],
            [
                'name' => 'marriage_certificate_copies',
                'title' => '结婚证复印件',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'house_property_copies',
                'title' => '房产证复印件',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'retirement_card',
                'title' => '退休证原件',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'retirement_card_copies',
                'title' => '退休证复印件',
                'type' => '7',
                'create_time' => time(),
            ],
            [
                'name' => 'rassenger_information',
                'title' => '乘机人信息',
                'type' => '4',
                'create_time' => time(),
            ],
        ];
        // DbGoods::saveAllSheetOption($data);
        $result = DbGoods::getSheetOption([],'*');
        return ['code' => 200,'options' => $result];
    }

    public function getSheet($page, $pageNum){
        $offset = $pageNum * ($page - 1);
        $result = DbGoods::getSheet([],'*',$offset.','.$pageNum);
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $options = [];
                $sheet_options = DbGoods::getSheetOptionRelation(['sheet_id' => $value['id']],'*');
                foreach ($sheet_options as $sheet => $option) {
                    $options[] = $option['sheet_option']['title'];
                }
                // print_r($sheet_options);die;
                $result[$key]['options'] = join('，',$options);
            }
        }
        return ['code' => '200', 'sheetlist' => $result];
    }


    public function addSheet($name, $options){
        if (DbGoods::getSheet(['name' => $name], 'id',true)) {
            return ['code' => '3001'];
        }
        
        Db::startTrans();
        try {
            $sheet_id = DbGoods::addSheet(['name' => $name]);
            if ($sheet_id && $options){
                $sheet_options = [];
                foreach ($options as $key => $value) {
                    $sheet_options[$key]['option_id'] = $value;
                    $sheet_options[$key]['sheet_id'] = $sheet_id;
                }
                DbGoods::addAllSheetOptionRelation($sheet_options);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//领取失败
        }
    }

    public function editSheet($name = '', $id, $options = []){
        if (!empty($name)) {
            if (DbGoods::getSheet([['name' , '=', $name],['id','<>',$id]], 'id',true)) {
                return ['code' => '3001'];
            }
        }
        Db::startTrans();
        try {
            if (!empty($name)){
                DbGoods::saveSheet(['name' => $name],$id);
            }
            if ($id && $options){
                DbGoods::delSheetOptionRelation(['sheet_id' => $id]);
                $sheet_options = [];
                foreach ($options as $key => $value) {
                    $sheet_options[$key]['option_id'] = $value;
                    $sheet_options[$key]['sheet_id'] = $id;
                }
                DbGoods::addAllSheetOptionRelation($sheet_options);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//领取失败
        }
       
    }

    public function getSheetInfo($id){
        $sheet = DbGoods::getSheet([['id','=',$id]], 'id,name,create_time',true);
        if (empty($sheet)) {
            return ['code' => '200', 'sheet' =>[]];
        }
        $sheet_options = DbGoods::getSheetOptionRelation(['sheet_id' => $sheet['id']],'*');
        $sheet_optionsList = [];
        foreach ($sheet_options as $key => $value) {
            $sheet_optionsList[] = $value['sheet_option'];
        }
        $sheet['options'] = $sheet_optionsList;
        return ['code' => '200', 'sheet' =>$sheet];
    }
}