<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Goods extends AdminController {
    /**
     * @api              {post} / 商品列表
     * @apiDescription   getGoodsList
     * @apiGroup         admin_goods
     * @apiName          getGoodsList
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Number} total 条数
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
    public function getGoodsList() {
        $page    = trim(input("post.page"));
        $page    = empty($page) ? 1 : intval($page);
        $pageNum = trim(input("post.page_num"));
        $pageNum = empty($pageNum) ? 10 : intval($pageNum);
        if (!is_numeric($page) || !is_numeric($pageNum)) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $res = $this->app->goods->goodsList($page, $pageNum);
        return $res;
    }


    /**
     * @api              {post} / 添加商品基础信息
     * @apiDescription   saveAddGoods
     * @apiGroup         admin_goods
     * @apiName          saveAddGoods
     * @apiSuccess (返回) {String} code 200:成功 / 3001:供应商id只能为数字 / 3002:分类id只能为数字 / 3003:商品名称不能空 / 3004:标题图不能空 / 3005:商品类型只能为数字 / 3006:商品名称重复 / 3007:提交的分类id不是三级分类 / 3008:供应商不存在 / 3009:添加失败 / 3010:图片没有上传过
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiParam (入参) {Number} supplier_id 供应商id
     * @apiParam (入参) {Number} cate_id 三级分类id
     * @apiParam (入参) {String} goods_name 商品名称
     * @apiParam (入参) {Number} [goods_type] 商品类型 1普通商品 2 虚拟商品(默认1)
     * @apiParam (入参) {String} [subtitle] 标题
     * @apiParam (入参) {String} image 商品标题图
     * @apiSampleRequest /admin/goods/saveaddgoods
     * @return array
     * @author zyr
     */
    public function saveAddGoods() {
        $supplierId   = trim($this->request->post('supplier_id'));//供应商id
        $cateId       = trim($this->request->post('cate_id'));//分类id
        $goodsName    = trim($this->request->post('goods_name'));//商品名称
        $goodsType    = trim($this->request->post('goods_type'));//商品类型
        $subtitle     = trim($this->request->post('subtitle'));//标题
        $image        = trim($this->request->post('image'));//商品标题图
        $goodsTypeArr = [1, 2];
        if (!is_numeric($supplierId)) {
            return ['code' => '3001'];//供应商id只能为数字
        }
        if (!is_numeric($cateId)) {
            return ['code' => '3002'];//分类id只能为数字
        }
        if (empty($goodsName)) {
            return ['code' => '3003'];//商品名称不能空
        }
        if (empty($image)) {
            return ['code' => '3004'];//标题图不能空
        }
        if (!empty($goodsType) && !in_array($goodsType, $goodsTypeArr)) {
            return ['code' => '3005'];//商品类型只能为数字
        }
        $data = [
            'supplier_id' => intval($supplierId),
            'cate_id'     => intval($cateId),
            'goods_name'  => $goodsName,
        ];
        if (!empty($image)) {
            $data['image'] = $image;
        }
        if (!empty($goodsType)) {
            $data['goods_type'] = intval($goodsType);
        }
        if (!empty($subtitle)) {
            $data['subtitle'] = $subtitle;
        }
        //调用方法存商品表
        $res = $this->app->goods->saveGoods($data);
        return $res;
    }

    /**
     * @api              {post} / 修改商品基础信息
     * @apiDescription   saveUpdateGoods
     * @apiGroup         admin_goods
     * @apiName          saveUpdateGoods
     * @apiSuccess (返回) {String} code 200:成功 / 3001:供应商id只能为数字 / 3002:分类id只能为数字 / 3003:商品名称不能空 / 3004:标题图不能空 / 3005:商品类型只能为数字 / 3006:商品名称重复 / 3007:提交的分类id不是三级分类 / 3008:供应商不存在 / 3009:修改失败
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} supplier_id 供应商id
     * @apiParam (入参) {Number} cate_id 三级分类id
     * @apiParam (入参) {String} goods_name 商品名称
     * @apiParam (入参) {Number} [goods_type] 商品类型 1普通商品 2 虚拟商品(默认1)
     * @apiParam (入参) {String} [subtitle] 标题
     * @apiParam (入参) {String} [image] 商品标题图
     * @apiSampleRequest /admin/goods/saveupdategoods
     * @return array
     * @author zyr
     */
    public function saveUpdateGoods() {
        $goodsId      = trim($this->request->post('goods_id'));//商品id
        $supplierId   = trim($this->request->post('supplier_id'));//供应商id
        $cateId       = trim($this->request->post('cate_id'));//分类id
        $goodsName    = trim($this->request->post('goods_name'));//商品名称
        $goodsType    = trim($this->request->post('goods_type'));//商品类型
        $subtitle     = trim($this->request->post('subtitle'));//标题
        $image        = trim($this->request->post('image'));//商品标题图
        $goodsTypeArr = [1, 2];
        if (!is_numeric($supplierId)) {
            return ['code' => '3001'];//供应商id只能为数字
        }
        if (!is_numeric($cateId)) {
            return ['code' => '3002'];//分类id只能为数字
        }
        if (empty($goodsName)) {
            return ['code' => '3003'];//商品名称不能空
        }
        if (!empty($goodsType) && !in_array($goodsType, $goodsTypeArr)) {
            return ['code' => '3005'];//商品类型只能为数字
        }
        $data = [
            'supplier_id' => intval($supplierId),
            'cate_id'     => intval($cateId),
            'goods_name'  => $goodsName,
        ];
        if (!empty($goodsType)) {
            $data['goods_type'] = intval($goodsType);
        }
        if (!empty($image)) {
            $data['image'] = $image;
        }
        if (!empty($subtitle)) {
            $data['subtitle'] = $subtitle;
        }
        //调用方法存商品表
        $res = $this->app->goods->saveGoods($data, $goodsId);
        return $res;
    }

    /**
     * @api              {post} / 获取一个商品数据
     * @apiDescription   getOneGoods
     * @apiGroup         admin_goods
     * @apiName          getOneGoods
     * @apiParam (入参) {Number} id 商品id
     * @apiSuccess (返回) {String} code 200:成功 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSuccess (返回) {Array} goods_data 商品数据
     * @apiSuccess (返回) {Array} images_data 商品图片数据
     * @apiSuccess (返回) {Array} sku sku数据
     * @apiSampleRequest /admin/goods/getonegoods
     * @author wujunjie
     * 2019/1/2-16:48
     */
    public function getOneGoods() {
        $id = trim(input("post.id"));
        if (!is_numeric($id)) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $res = $this->app->goods->getOneGoodsImage($id);
        return $res;
    }

    /**
     * @api              {post} / 删除商品
     * @apiDescription   delGoods
     * @apiGroup         admin_goods
     * @apiName          delGoods
     * @apiSuccess (返回) {String} code 200:成功 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/goods/delgoods
     * @author wujunjie
     * 2019/1/3-10:21
     */
    public function delGoods() {
        $id = trim(input("post.id"));
        if (!is_numeric($id)) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $res = $this->app->goods->delGoods($id);
        return $res;
    }

    /**
     * @api              {post} / 上下架
     * @apiDescription   upDownGoods
     * @apiGroup         admin_goods
     * @apiName          upDownGoods
     * @apiParam (入参) {Number} id 商品id
     * @apiParam (入参) {Number} type 上下架状态 1上架 / 2下架
     * @apiSuccess (返回) {String} code 200:成功 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @author wujunjie
     * 2019/1/8-10:13
     */
    public function upDownGoods() {
        $id   = trim(input("post.id"));
        $type = trim(input("post.type"));
        if (!is_numeric($id) || !is_numeric($type)) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $res = $this->app->goods->upDown($id);
        return $res;
    }
}
