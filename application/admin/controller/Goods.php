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
     * @api              {post} / 获取sku列表
     * @apiDescription   getGoodsSku
     * @apiGroup         admin_goods
     * @apiName          getGoodsSku
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:没有商品sku / 3001:没有这个商品
     * @apiSuccess (返回) {Array} data 返回数据
     * @apiSuccess (data) {Number} goods_id 商品id
     * @apiSuccess (data) {Number} stock 库存
     * @apiSuccess (data) {Number} market_price 市场价
     * @apiSuccess (data) {Number} retail_price 零售价
     * @apiSuccess (data) {Number} cost_price 成本价
     * @apiSuccess (data) {Number} margin_price 毛利
     * @apiSuccess (data) {Number} sku_image 规格详情图
     * @apiSuccess (data) {Number} spec 属性id列表
     * @apiSuccess (data) {Number} attr 属性列表
     * @apiSampleRequest /admin/goods/getgoodssku
     * @return array
     * @author zyr
     */
    public function getGoodsSku() {
        $goodsId = trim($this->request->post('goods_id'));//商品id
        if (!is_numeric($goodsId)) {
            return ['code' => '3001'];
        }
        $result = $this->app->goods->getGoodsSku(intval($goodsId));
        return $result;
    }

    public function editGoodsSku() {
        $skuId          = trim($this->request->post('sku_id'));
        $stock          = trim($this->request->post('stock'));//库存
        $marketPrice    = trim($this->request->post('market_price'));//市场价
        $retailPrice    = trim($this->request->post('retail_price'));//零售价
        $costPrice      = trim($this->request->post('cost_price'));//成本价
        $marginPrice    = trim($this->request->post('margin_price'));//其他运费成本
        $integralPrice  = trim($this->request->post('integral_price'));//积分售价
        $integralActive = trim($this->request->post('integral_active'));//积分赠送
        $skuImage       = trim($this->request->post('sku_image'));//规格详情图
        if (!is_numeric($skuId)) {//id必须为数字
            return ['code' => '3001'];
        }
        if (!is_numeric($stock) || intval($stock) < 0) {//库存必须为大于或等于0的数字
            return ['code' => '3002'];
        }
        if (!is_numeric($marketPrice) || !is_numeric($retailPrice) || !is_numeric($costPrice) || !is_numeric($marketPrice) || floatval($marketPrice) < 0 || floatval($retailPrice) < 0 || floatval($costPrice) < 0 || floatval($marginPrice)) {//价格必须为大于或等于0的数字
            return ['code' => '3003'];
        }
        if (!is_numeric($integralPrice) || !is_numeric($integralActive) || intval($integralPrice) < 0 || intval($integralActive) < 0) {//积分必须为大于或等于0的数字
            return ['code' => '3004'];
        }
    }

    /**
     * @api              {post} / 添加商品的规格属性
     * @apiDescription   addGoodsSpec
     * @apiGroup         admin_goods
     * @apiName          addGoodsSpec
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} attr_id 属性id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:属性id必须为数字 / 3002:商品id必须为数字 / 3003:属性不存在 / 3004:商品不存在 / 3005:规格不能为空 / 3006:商品已有该规格属性 / 3007:提交失败 / 3008:没有任何操作 / 3009:提交的属性分类和商品分类不同
     * @apiSampleRequest /admin/goods/addgoodsspec
     * @return array
     * @author zyr
     */
    public function addGoodsSpec() {
        $goodsId = trim($this->request->post('goods_id'));//商品id
        $attrId  = trim($this->request->post('attr_id'));//属性id
        if (!is_numeric($goodsId)) {
            return ['code' => '3002'];//商品id必须为数字
        }
        if (!is_numeric($attrId)) {
            return ['code' => '3001'];//属性id必须为数字
        }
        $restlt = $this->app->goods->addGoodsSpec(intval($attrId), intval($goodsId));
        return $restlt;
    }

    /**
     * @api              {post} / 删除商品的规格属性
     * @apiDescription   delGoodsSpec
     * @apiGroup         admin_goods
     * @apiName          delGoodsSpec
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} attr_id 属性id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:属性id必须为数字 / 3002:商品id必须为数字 / 3003:属性不存在 / 3004:商品不存在 / 3005:规格不能为空 /3006:该商品未绑定这个属性 / 3007:提交失败/ 3008:没有任何操作 / 3009:提交的属性分类和商品分类不同
     * @apiSampleRequest /admin/goods/delgoodsspec
     * @return array
     * @author zyr
     */
    public function delGoodsSpec() {
        $goodsId = trim($this->request->post('goods_id'));//商品id
        $attrId  = trim($this->request->post('attr_id'));//属性id
        if (!is_numeric($goodsId)) {
            return ['code' => '3002'];//商品id必须为数字
        }
        if (!is_numeric($attrId)) {
            return ['code' => '3001'];//属性id必须为数字
        }
        $restlt = $this->app->goods->delGoodsSpec(intval($attrId), intval($goodsId));
        return $restlt;
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
     * @apiSuccess (返回) {Array} images_detatil 商品详情图
     * @apiSuccess (返回) {Array} images_carousel 商品轮播图
     * @apiSuccess (返回) {Array} sku sku数据
     * @apiSuccess (返回) {Array} specAttr
     * @apiSuccess (goods_data) {Number} supplier_id 供应商id
     * @apiSuccess (goods_data) {Number} cate_id 分类id
     * @apiSuccess (goods_data) {String} goods_name 商品名称
     * @apiSuccess (goods_data) {Number} goods_type 普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (goods_data) {String} subtitle 天然碱性苏打水
     * @apiSuccess (goods_data) {String} image 标题图
     * @apiSuccess (goods_data) {Number} status 1.上架 2.下架
     * @apiSuccess (goods_data) {String} goods_class 三级分类
     * @apiSuccess (goods_data) {String} supplier_name 供应商名称
     * @apiSuccess (spec_attr) {Number} spec_id 规格id
     * @apiSuccess (spec_attr) {Number} attr_id 属性id
     * @apiSuccess (spec_attr) {Number} spec_name 规格名称
     * @apiSuccess (spec_attr) {Number} attr_name 属性名称
     * @apiSuccess (images_detatil) {Number} image_type 1.详情图 2.轮播图
     * @apiSuccess (images_detatil) {Number} image_path 图片地址
     * @apiSuccess (images_carousel) {Number} image_type 1.详情图 2.轮播图
     * @apiSuccess (images_carousel) {Number} image_path 图片地址
     * @apiSuccess (sku) {Number} goods_id 商品id
     * @apiSuccess (sku) {Number} stock 库存
     * @apiSuccess (sku) {Number} market_price 市场价
     * @apiSuccess (sku) {Number} retail_price 零售价
     * @apiSuccess (sku) {Number} cost_price 成本价
     * @apiSuccess (sku) {Number} margin_price 毛利
     * @apiSuccess (sku) {Number} sku_image 规格详情图
     * @apiSuccess (sku) {Number} spec 属性id列表
     * @apiSuccess (sku) {Number} attr 属性列表
     * @apiSampleRequest /admin/goods/getonegoods
     * @author wujunjie
     * 2019/1/2-16:48
     */
    public function getOneGoods() {
        $id = trim(input("post.id"));
        if (!is_numeric($id)) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $res = $this->app->goods->getOneGoods($id);
        return $res;
    }

    /**
     * 删除商品
     */
//    public function delGoods() {
//        $id = trim(input("post.id"));
//        if (!is_numeric($id)) {
//            return ["msg" => "参数错误", "code" => 3002];
//        }
//        $res = $this->app->goods->delGoods($id);
//        return $res;
//    }

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
