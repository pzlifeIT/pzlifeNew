<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Goods extends AdminController {
    /**
     * @api              {post} / 商品列表
     * @apiDescription   getGoodsList
     * @apiGroup         admin_goods
     * @apiName          getGoodsList
     * @apiParam (入参) {Number} [page] 当前页 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiParam (入参) {Number} [class_id] 分类id
     * @apiParam (入参) {Number} [goods_name] 商品名称
     * @apiParam (入参) {Number} [supplier_id] 当前页
     * @apiParam (入参) {Number} [status] 上下架状态 1.上架 2.下架
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:page只能为数字 / 3003:page_num只能为数字
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
        $pageNum = trim(input("post.page_num"));
        $page    = empty($page) ? 1 : $page;
        $pageNum = empty($pageNum) ? 10 : $pageNum;
        if (!is_numeric($page)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($pageNum)) {
            return ["code" => '3002'];
        }
        $res = $this->app->goods->goodsList(intval($page), intval($pageNum));
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
     * @apiSuccess (返回) {String} code 200:成功 / 3001:供应商id只能为数字 / 3002:分类id只能为数字 / 3003:商品名称不能空 / 3004:标题图不能空 / 3005:商品类型只能为数字 / 3006:商品名称重复 / 3007:提交的分类id不是三级分类 / 3008:供应商不存在 / 3009:修改失败 / 3010:图片没有上传过
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
     * @api              {post} / 获取一个sku信息
     * @apiDescription   getGoodsSku
     * @apiGroup         admin_goods
     * @apiName          getGoodsSku
     * @apiParam (入参) {Number} sku_id 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:没有商品sku / 3001:id必须为数字
     * @apiSuccess (返回) {Array} data 返回数据
     * @apiSuccess (data) {Number} goods_id 商品id
     * @apiSuccess (data) {Number} freight_id 运费模版id
     * @apiSuccess (data) {Number} stock 库存
     * @apiSuccess (data) {Number} market_price 市场价
     * @apiSuccess (data) {Number} retail_price 零售价
     * @apiSuccess (data) {Number} cost_price 成本价
     * @apiSuccess (data) {Number} margin_price 毛利
     * @apiSuccess (data) {Number} sku_image 规格详情图
     * @apiSuccess (data) {Number} spec 属性id列表
     * @apiSuccess (data) {String} freight_title 运费模版标题
     * @apiSuccess (data) {Number} attr 属性列表
     * @apiSuccess (data) {Number} integral_price 积分售价
     * @apiSuccess (data) {Number} integral_active 积分赠送
     * @apiSampleRequest /admin/goods/getgoodssku
     * @return array
     * @author zyr
     */
    public function getGoodsSku() {
        $skuId = trim($this->request->post('sku_id'));
        if (!is_numeric($skuId)) {
            return ['code' => '3001'];
        }
        $result = $this->app->goods->getGoodsSku(intval($skuId));
        return $result;
    }


    /**
     * @api              {post} / 编辑商品sku
     * @apiDescription   editGoodsSku
     * @apiGroup         admin_goods
     * @apiName          editGoodsSku
     * @apiParam (入参) {Number} sku_id
     * @apiParam (入参) {Number} stock 库存
     * @apiParam (入参) {Number} freight_id 运费模版id
     * @apiParam (入参) {Number} market_price 市场价
     * @apiParam (入参) {Number} retail_price 零售价
     * @apiParam (入参) {Number} cost_price 成本价
     * @apiParam (入参) {Number} margin_price 其他运费成本
     * @apiParam (入参) {Number} integral_price 积分售价
     * @apiParam (入参) {Number} integral_active 积分赠送
     * @apiParam (入参) {Number} sku_image 规格详情图
     * @apiSuccess (返回) {String} code 200:成功 / 3000:没有商品sku / 3001:id必须为数字 / 3002:库存必须为大于或等于0的数字 / 3003:价格必须为大于或等于0的数字 / 3004:积分必须为大于或等于0的数字 / 3005:图片没有上传过 / 3006:零售价不能小于成本价 / 3007:skuid不存在 / 3008:编辑失败 / 3009:选择的供应山id有误
     * @apiSampleRequest /admin/goods/editgoodssku
     * @return array
     * @author zyr
     */
    public function editGoodsSku() {
        $skuId          = trim($this->request->post('sku_id'));
        $stock          = trim($this->request->post('stock'));//库存
        $freightId      = trim($this->request->post('freight_id'));//运费模版
        $marketPrice    = trim($this->request->post('market_price'));//市场价
        $retailPrice    = trim($this->request->post('retail_price'));//零售价
        $costPrice      = trim($this->request->post('cost_price'));//成本价
        $marginPrice    = trim($this->request->post('margin_price'));//其他运费成本
        $integralPrice  = trim($this->request->post('integral_price'));//积分售价
        $integralActive = trim($this->request->post('integral_active'));//积分赠送
        $skuImage       = trim($this->request->post('sku_image'));//规格详情图
        if (!is_numeric($skuId) || !is_numeric($freightId)) {//id必须为数字
            return ['code' => '3001'];
        }
        if (!is_numeric($stock) || intval($stock) < 0) {//库存必须为大于或等于0的数字
            return ['code' => '3002'];
        }
        if (!is_numeric($marketPrice) || !is_numeric($retailPrice) || !is_numeric($costPrice) || !is_numeric($marketPrice) || floatval($marketPrice) < 0 || floatval($retailPrice) < 0 || floatval($costPrice) < 0 || floatval($marginPrice) < 0) {//价格必须为大于或等于0的数字
            return ['code' => '3003'];
        }
        if (!is_numeric($integralPrice) || !is_numeric($integralActive) || intval($integralPrice) < 0 || intval($integralActive) < 0) {//积分必须为大于或等于0的数字
            return ['code' => '3004'];
        }
        $retailPrice = floatval($retailPrice);
        $costPrice   = floatval($costPrice);
        if ($retailPrice < bcadd($costPrice, $marginPrice, 2)) {
            return ['code' => '3006'];//零售价不能小于成本价
        }
        $data   = [
            'stock'           => $stock,
            'freight_id'      => $freightId,
            'market_price'    => $marketPrice,
            'retail_price'    => $retailPrice,
            'cost_price'      => $costPrice,
            'margin_price'    => $marginPrice,
            'integral_price'  => $integralPrice,
            'integral_active' => $integralActive,
            'sku_image'       => $skuImage,
        ];
        $result = $this->app->goods->editGoodsSku($skuId, $data);
        return $result;
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
     * @apiParam (入参) {Number} [get_type] 获取内容类型 1.只获取goods_data 2. 获取spec_attr 3.获取images_detatil和images_carousel  4.获取sku   默认为1,2,3,4
     * @apiSuccess (返回) {String} code 200:成功 / 3000:商品基本数据获取失败 /3002:id必须是数字 / 3003:get_type错误
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
     * @author zyr
     */
    public function getOneGoods() {
        $getTypeArr = [1, 2, 3, 4];
        $id         = trim(input("post.id"));
        $getType    = trim($this->request->post('get_type'));
        $getType    = empty($getType) ? '1,2,3,4' : $getType;
        if (!is_numeric($id)) {
            return ["code" => 3002];
        }
        $getType = explode(',', $getType);
        foreach ($getType as $val) {
            if (!in_array($val, $getTypeArr)) {
                return ['code' => '3003'];
            }
        }
        $res = $this->app->goods->getOneGoods($id, $getType);
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
     * @api              {post} / 提交商品详情和轮播图
     * @apiDescription   uploadGoodsImages
     * @apiGroup         admin_goods
     * @apiName          uploadGoodsImages
     * @apiParam (入参) {Number} image_type 图片类型 1.详情图 2.轮播图
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Array} images 图片集合
     * @apiSuccess (返回) {String} code 200:成功 / 3001:图片类型有误 / 3002:商品id只能是数字 / 3003:图片不能空 / 3004:商品id不存在 / 3005:图片没有上传过 / 3006:上传失败
     * @apiSampleRequest /admin/goods/uploadgoodsimages
     * @return array
     * @author zyr
     */
    public function uploadGoodsImages() {
        $imageTypeArr = [1, 2];//1.详情图 2.轮播图
        $goodsId      = trim($this->request->post('goods_id'));
        $imageType    = trim($this->request->post('image_type'));
        $images       = $this->request->post('images');
        if (!is_numeric($imageType) || !in_array(intval($imageType), $imageTypeArr)) {
            return ['code' => '3001'];//图片类型有误
        }
        if (!is_numeric($goodsId)) {
            return ['code' => '3002'];//商品id只能是数字
        }
        if (empty($images)) {
            return ['code' => '3003'];//图片不能空
        }
        $result = $this->app->goods->uploadGoodsImages($goodsId, $imageType, $images);
        return $result;
    }


    /**
     * @api              {post} / 删除商品详情和轮播图
     * @apiDescription   delGoodsImage
     * @apiGroup         admin_goods
     * @apiName          delGoodsImage
     * @apiParam (入参) {Number} image_path 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:图片不能为空 / 3002:图片不存在 / 3003:上传失败
     * @apiSampleRequest /admin/goods/delgoodsimage
     * @return array
     * @author zyr
     */
    public function delGoodsImage() {
        $imagePath = trim($this->request->post('image_path'));
        if (empty($imagePath)) {
            return ['code' => '3001'];//图片不能为空
        }
        $result = $this->app->goods->delGoodsImage($imagePath);
        return $result;
    }

    /**
     * @api              {post} / 上下架
     * @apiDescription   upDownGoods
     * @apiGroup         admin_goods
     * @apiName          upDownGoods
     * @apiParam (入参) {Number} id 商品id
     * @apiParam (入参) {Number} type 上下架状态 1上架 / 2下架
     * @apiSuccess (返回) {String} code 200:成功 / 3001:商品不存在 / 3002:参数必须是数字 / 3003:请填写库存 / 3004:请填写零售价 / 3005:请填写成本价 / 3006:没有可售的规格商品 / 3007:上下架失败 / 3008:上下架失败 / 300:上下架失败
     * @apiSampleRequest /admin/goods/updowngoods
     * @author wujunjie
     * 2019/1/8-10:13
     */
    public function upDownGoods() {
        $id   = trim(input("post.id"));
        $type = trim(input("post.type"));
        if (!is_numeric($id) || !is_numeric($type)) {
            return ["code" => '3002'];
        }
        $res = $this->app->goods->upDown(intval($id), intval($type));
        return $res;
    }
}
