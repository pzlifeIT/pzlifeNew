<?php

namespace app\common\action\admin;

use app\facade\DbImage;
use think\Db;
use app\facade\DbGoods;
use Config;

class Goods {
    /**
     * 商品列表
     * @return array
     * @author wujunjie
     * 2018/12/26-10:25
     */
    public function goodsList($page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        //查找所有商品数据
        $field      = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,status";
        $goods_data = DbGoods::getGoodsList($field, $offset, $pageNum);
        $total      = DbGoods::getGoodsListNum();
        if (empty($goods_data)) {
            return ["msg" => "商品数据不存在", "code" => 3000];
        }
        foreach ($goods_data as $k => $v) {
            //查找供应商
            $whereSupp = [["id", "=", $v['supplier_id']]];
            $fieldSupp = "id,tel,name";
            $supplier  = DbGoods::getOneSupplier($whereSupp, $fieldSupp);
            if (empty($supplier)) {
                return ["msg" => "供应商数据空", "code" => 3000];
            }
            $goods_data[$k]["supplier"] = $supplier["name"];
            //查找三级分类
            $whereCate = [["id", "=", $v["cate_id"]]];
            $fieldCate = "id,pid,type_name";
            $cate      = DbGoods::getOneCate($whereCate, $fieldCate);
//            if (empty($cate)){
//                return ["msg"=>"分类数据空","code"=>3000];
//            }
            $goods_data[$k]["cate"] = empty($cate) ? '' : $cate["type_name"];
        }
        return ["code" => 200, "total" => $total, "data" => $goods_data];
    }


    /**
     * 保存商品基础信息(添加或更新)
     * @param $data
     * @param $goodsId
     * @return array
     * @author zyr
     */
    public function saveGoods($data, $goodsId = 0) {
        $goods = DbGoods::getOneGoods(['goods_name' => $data['goods_name']], 'id');
        if (!empty($goods)) {//商品name重复
            return ['code' => '3006', 'goods_id' => $goods['id']];
        }
        $cate = DbGoods::getOneCate(['id' => $data['cate_id']], 'tier');
        if ($cate['tier'] != 3) {//分类id不是三级
            return ['code' => '3007'];
        }
        $supplier = DbGoods::getOneSupplier(['id' => $data['supplier_id']], 'id');
        if (empty($supplier)) {//供应商id不存在
            return ['code' => '3008'];
        }
        $goods    = DbGoods::getOneGoods(['id' => $goodsId], 'image');
        $logImage = [];
        if (!empty($goodsId)) {//更新操作
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
        $goodsSkuList            = DbGoods::getOneGoodsSku($skuWhere, 'id,spec');
        $delId                   = [];//需要删除的sku id
        $delRelation             = [];
//        print_r($goodsSkuList);die;
        foreach ($goodsSkuList as $sku) {
            if (in_array($sku['spec'], $carte)) {
                $delKey = array_search($sku['spec'], $carte);
                if ($delKey !== false) {
                    array_splice($carte, $delKey, 1);
                }
            } else {
                array_push($delId, $sku['id']);
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
        $delRelation  = [];
        foreach ($goodsSkuList as $sku) {
            if (!in_array($sku['spec'], $carte)) {
                array_push($delId, $sku['id']);
            }
        }
        $delRelationId = DbGoods::getGoodsRelation(['goods_id' => $goodsId, 'attr_id' => $attrId], 'id');
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
     * @param $goodsId
     * @return array
     */
    public function getGoodsSku($goodsId) {
//        $aaa = DbGoods::getGoodsSku(['id'=> 18], 'id,goods_name', 'goods_id,stock,spec');
//        $aaa = DbGoods::getSpecAttr([['id', 'in', [1, 2, 3]]], 'id,spe_name', 'spec_id,attr_name', 'goods_id,spec_id');
//        print_r($aaa);
//        die;
//        DbGoods::getOneGoodsSku(['goods_id'=>$goodsId],'goods_id');

        $goods = DbGoods::getGoods('id', '0,1', 'id', ['id' => $goodsId]);
        if (empty($goods)) {
            return ['code' => '3001'];
        }
        $result = DbGoods::getSku(['goods_id' => $goodsId], 'goods_id,stock,market_price,retail_price,cost_price,margin_price,sku_image,spec');
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 获取一条商品数据
     * @param $id
     * @return array
     * @author wujunjie
     * 2019/1/2-16:42
     */
    public function getOneGoods($id) {
        //根据商品id找到商品表里面的基本数据
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
        //根据商品id找到商品图片表里面的数据
        $where          = [["goods_id", "=", $id], ['image_type', 'in', [1, 2]]];
        $field          = "goods_id,image_type,image_path";
        $images_data    = DbGoods::getOneGoodsImage($where, $field);
        $imagesDetatil  = [];//商品详情图
        $imagesCarousel = [];//商品轮播图
        foreach ($images_data as $im) {
            if ($im['image_type'] == 1) {
                array_push($imagesDetatil, $im);
            }
            if ($im['image_type'] == 2) {
                array_push($imagesCarousel, $im);
            }
        }

        $specAttr    = [];
        $specAttrRes = DbGoods::getGoodsSpecAttr(['goods_id' => $id], 'spec_id,attr_id', 'id,cate_id,spe_name', 'id,spec_id,attr_name');
        foreach ($specAttrRes as $specVal) {
            $specVal['spec_name'] = $specVal['goods_spec']['spe_name'];
            $specVal['attr_name'] = $specVal['goods_attr']['attr_name'];
            unset($specVal['goods_spec']);
            unset($specVal['goods_attr']);
            array_push($specAttr, $specVal);
        }
//        if (empty($images_data)) {
//            return ["msg" => "商品图片获取失败", "code" => 3000];
//        }
//        if ($goods_data["goods_type"] == 1) {
        //根据商品id获取sku表数据
        $where = [["goods_id", "=", $id]];
        $field = "goods_id,stock,market_price,retail_price,cost_price,margin_price,integral_price,integral_active,spec,sku_image";
        $sku   = DbGoods::getSku($where, $field);
//        if (empty($sku)) {
//            return ["msg" => "sku数据获取失败", "code" => 3000];
//        }
//        }
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
     * 上下架
     * @param $id
     * @param $type
     * @return array
     * @author wujunjie
     * 2019/1/8-10:13
     */
    public function upDown($id, $type) {
        //判断传过来的id是否有效
        $where = [["id", "=", $id]];
        $field = "goods_name";
        $res   = DbGoods::getOneGoods($where, $field);
        if (empty($res)) {
            return ["msg" => "数据错误", "code" => 3001];
        }
        //修改状态
        $data = [
            "status" => $type
        ];
        $res  = DbGoods::editGoods($data, $id);
        if (empty($res)) {
            return ['msg' => "上下架失败", "code" => 3002];
        }
        return ["msg" => '成功', "code" => 200];
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