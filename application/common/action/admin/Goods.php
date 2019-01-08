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
            return ["msg" => "商品基本数据获取失败", "code" => 3000];
        }
        //根据商品id找到商品图片表里面的数据
        $where       = [["goods_id", "=", $id]];
        $field       = "goods_id,source_type,image_type,image_path";
        $images_data = DbGoods::getOneGoodsImage($where, $field);
        if (empty($images_data)) {
            return ["msg" => "商品图片获取失败", "code" => 3000];
        }
        if ($goods_data["goods_type"] == 1) {
            //根据商品id获取sku表数据
            $where = [["goods_id", "=", $id]];
            $field = "goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,integral_price,integral_active,spec,sku_image";
            $sku   = DbGoods::getOneGoodsSku($where, $field);
            if (empty($sku)) {
                return ["msg" => "sku数据获取失败", "code" => 3000];
            }
        }

        return ["code" => 200, "goods_data" => $goods_data, "images_data" => $images_data, "sku" => $sku];
    }

    public function delGoods($id) {
        //开启事务
        Db::startTrans();
        try {
            //删除商品基本数据
            DbGoods::delGoods($id);
            DbGoods::delGoodsImage($id);
            DbGoods::delGoodsSku($id);
            DbGoods::delGoodsRelation($id);
            Db::commit();
            return ["msg" => "删除成功", "code" => 200];
        } catch (\Exception $e) {
            Db::rollback();
            return ["msg" => "删除失败", "code" => 3001];
        }

    }

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
}