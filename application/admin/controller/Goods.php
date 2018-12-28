<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\AdminController;
class Goods extends AdminController
{
    /**
     * @api              {post} / 商品列表
     * @apiDescription   getGoodsList
     * @apiGroup         admin_goods
     * @apiName          getGoodsList
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} data 返回数据
     * @apiSuccess (data) {Number} id 商品id
     * @apiSuccess (data) {Number} supplier_id 供应商id
     * @apiSuccess (data) {Number} cate_id 分类id
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {Number} goods_type 商品属性 1实物商品 2虚拟商品
     * @apiSuccess (data) {String} title 商品主标题
     * @apiSuccess (data) {String} subtitle 商品副标题
     * @apiSuccess (data) {String} supplier 供应商名称
     * @apiSuccess (data) {String} cate 分类名称
     * @apiSuccess (data) {Number} status 上下架状态 1上架 2下架
     * @apiSampleRequest /admin/goods/getgoodslist
     * @author wujunjie
     * 2018/12/26-18:04
     */
    public function getGoodsList(){
        $res = $this->app->goods->goodsList();
        return $res;
    }


    /**
     * @api              {post} / 添加商品
     * @apiDescription   saveAddGoods
     * @apiGroup         admin_goods
     * @apiName          saveAddGoods
     * @apiSuccess (返回) {String} code 200:成功 / 3001 保存失败 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiParam (入参) {Number} supplier_id 供应商id
     * @apiParam (入参) {Number} cate_id 三级分类id
     * @apiParam (入参) {String} goods_name 商品名称
     * @apiParam (入参) {Number} goods_type 商品类型 1普通商品 2 虚拟商品
     * @apiParam (入参) {String} title 主标题
     * @apiParam (入参) {String} subtitle 副标题
     * @apiParam (入参) {String} image 商品标题图
     * @apiParam (入参) {Number} status 上下架状态 1上架 2下架
     * @apiParam (入参) {Array} images 商品图片(一个数组单元是一个json字符串，有几张图片就有几个数组单元)
     * @apiParam (images) {Number} source_type 来源 1全部 / 2pc / 3app / 4微信
     * @apiParam (images) {Number} image_type 图片类型 1详情图 / 2轮播图
     * @apiParam (images) {String} image_path 图片内容
     * @apiParam (入参) {Array} skus sku数据 （一个数组单元是一个json字符串）
     * @apiParam (skus) {Number} stock 库存
     * @apiParam (skus) {double} market_price 市场价
     * @apiParam (skus) {double} retail_price 零售价
     * @apiParam (skus) {Int} presell_start_time 预售价开始时间
     * @apiParam (skus) {Int} presell_end_time 预售价结束时间
     * @apiParam (skus) {double} presell_price 预售价
     * @apiParam (skus) {double} active_price 活动价
     * @apiParam (skus) {Int} active_start_time 活动价开始时间
     * @apiParam (skus) {Int} active_end_time 活动价过期时间
     * @apiParam (skus) {double} margin_price 毛利
     * @apiParam (skus) {double} integral_price 积分售价
     * @apiParam (skus) {double} integral_active 积分赠送
     * @apiParam (skus) {Array} spec sku属性 {1:1,1:2}键名是一级规格id，键值是二级属性id
     * @apiParam (skus) {String} sku_image 规格图
     * @apiParam (入参) {Number} num 该商品的二级属性数量
     * @apiSampleRequest /admin/goods/saveaddgoods
     * @author wujunjie
     * 2018/12/28-16:54
     */
    public function saveAddGoods(){
        $post = input("post.");
        halt($post);
//        $supplier_id = trim(input("post.supplier_id"));//供应商id
//        $cate_id = trim(input("post.cate_id"));//分类ID
//        $goods_name = trim(input("post.goods_name"));//商品名称
//        $goods_type = trim(input("post.goods_type"));//商品类型 1实物/2虚拟
//        $title = trim(input("post.title"));//商品标题
//        $subtitle = trim(input("post.subtitle"));//商品副标题
//        $title_image = trim(input("post.title_image"));//商品标题图
//        $status = trim(input("post.status"));//上下架状态 1上架/2下架
//        $source_type = trim(input("post.source_type"));//来源 1全部/2pc/3app/4微信
//        $image_type = trim(input("post.image_type"));//图片类型 1详情图 / 2轮播图
//        $image_path = trim(input("post.image_path"));//图片内容
//        if (empty(is_numeric($supplier_id)) || empty(is_numeric($cate_id)) || empty($goods_name) ||empty(is_numeric($goods_type)) || empty($title) || empty($subtitle) || empty($title_image) || empty(is_numeric($status))){
//            return ["msg"=>"参数错误","code"=>"3002"];
//        }
        //调用方法存商品表
        $res = $this->app->goods->saveAddGoods($post);
        return $res;
    }
}
