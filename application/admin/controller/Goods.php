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
     * @apiDescription   addGoodsPage
     * @apiGroup         admin_goods
     * @apiName          addGoodsPage
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} cate 分类数据
     * @apiSuccess (返回) {Array} supplier 供应商
     * @apiSuccess (cate) {Number} id 分类ID
     * @apiSuccess (cate) {Number} pid 父级分类ID
     * @apiSuccess (cate) {Number} tier 层级 1 一级/ 2 二级/3 三级
     * @apiSuccess (cate) {String} type_name 分类名称
     * @apiSuccess (cate) {Array} _child 子分类数据
     * @apiSuccess (_child) {Number} id 分类ID
     * @apiSuccess (_child) {Number} pid 父级分类ID
     * @apiSuccess (_child) {Number} tier 层级 1 一级/ 2 二级/3 三级
     * @apiSuccess (_child) {String} type_name 分类名称
     * @apiSuccess (_child) {Array} _child 子分类数据
     * @apiSuccess (supplier) {Number} id 供应商id
     * @apiSuccess (supplier) {String} name 供应商名字
     * @author wujunjie
     * 2018/12/27-9:52
     */
    public function addGoodsPage(){
        $res = $this->app->goods->addGoodsPage();
        return $res;
    }

    public function saveAddGoods(){
        $supplier_id = trim(input("post.supplier_id"));
        $cate_id = trim(input("post.cate_id"));
        $goods_name = trim(input("post.goods_name"));
        $goods_type = trim(input("post.goods_type"));
        $title = trim(input("title"));
        $subtitle = trim(input("subtitle"));
        $title_image = trim(input("post.title_image"));
        $status = trim(input("post.status"));

    }
}
