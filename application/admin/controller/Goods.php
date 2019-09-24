<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Goods extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 商品列表
     * @apiDescription   getGoodsList
     * @apiGroup         admin_goods
     * @apiName          getGoodsList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [page] 当前页 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiParam (入参) {Number} [cate_name] 分类名称
     * @apiParam (入参) {Number} [goods_name] 商品名称
     * @apiParam (入参) {Number} [goods_type] 商品类型 1实物商品 2虚拟商品
     * @apiParam (入参) {String} [supplier_name] 供应商名称
     * @apiParam (入参) {String} [supplier_title] 供应商标题
     * @apiParam (入参) {Number} [status] 上下架状态 1.上架 2.下架
     * @apiParam (入参) {Number} [goods_id] 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:page只能为数字 / 3002:page_num只能为数字 / 3003:goods_id只能为数字 / 3004:上下架状态参数有误 / 3005:商品属性参数有误
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
        $apiName       = classBasename($this) . '/' . __function__;
        $cmsConId      = trim($this->request->post('cms_con_id'));
        $page          = trim(input("post.page"));
        $pageNum       = trim(input("post.page_num"));
        $cateName      = trim(input("post.cate_name"));
        $goodsName     = trim(input("post.goods_name"));
        $goodsType     = trim(input("post.goods_type"));
        $supplierName  = trim(input("post.supplier_name"));
        $supplierTitle = trim(input("post.supplier_title"));
        $status        = trim(input("post.status"));
        $goodsId       = trim(input("post.goods_id"));
        $page          = empty($page) ? 1 : $page;
        $pageNum       = empty($pageNum) ? 10 : $pageNum;
        $goodsType     = empty($goodsType) ? 0 : $goodsType;
        $status        = empty($status) ? 0 : $status;
        $goodsId       = empty($goodsId) ? 0 : $goodsId;

        $goodsTypeAttr = [0, 1, 2]; //0为不查询
        $statusAttr    = [0, 1, 2]; //0为不查询
        if (!is_numeric($page)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($pageNum)) {
            return ["code" => '3002'];
        }
        if (!is_numeric($goodsId)) {
            return ["code" => '3003'];
        }
        if (!in_array($status, $statusAttr)) {
            return ['code' => '3004'];
        }
        if (!in_array($goodsType, $goodsTypeAttr)) {
            return ['code' => '3005'];
        }
        $res = $this->app->goods->goodsList(intval($page), intval($pageNum), $goodsId, $status, $goodsType, $cateName, $goodsName, $supplierName, $supplierTitle);
        $this->apiLog($apiName, [$cmsConId, $page, $pageNum, $cateName, $goodsName, $goodsType, $supplierName, $supplierTitle, $status, $goodsId], $res['code'], $cmsConId);
        return $res;
    }

    /**
     * @api              {post} / 添加商品基础信息
     * @apiDescription   saveAddGoods
     * @apiGroup         admin_goods
     * @apiName          saveAddGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:供应商id只能为数字 / 3002:分类id只能为数字 / 3003:商品名称不能空 / 3004:标题图不能空 / 3005:商品类型只能为数字 / 3006:商品名称重复 / 3007:提交的分类id不是三级分类 / 3008:供应商不存在 / 3009:添加失败 / 3010:图片没有上传过 / 3011:适用人群只能为数字 / 3012:分享图片没有上传过 / 3015:虚拟商品暂时不支持赠送权益
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiParam (入参) {Number} supplier_id 供应商id
     * @apiParam (入参) {Number} cate_id 三级分类id
     * @apiParam (入参) {String} goods_name 商品名称
     * @apiParam (入参) {Number} [goods_type] 商品类型 1普通商品 2 音频商品(默认1) 3 健康商品
     * @apiParam (入参) {Number} [target_users] 商品适用人群:1,全部;2,钻石及以上;3,创业店主及以上;4,合伙人及以上(默认1)
     * @apiParam (入参) {Number} [giving_rights] 商品赠送权益 1,不赠送;2,钻石;3,创业店主;4,合伙人(默认1)
     * @apiParam (入参) {String} [subtitle] 标题
     * @apiParam (入参) {String} image 商品标题图
     * @apiParam (入参) {String} [share_image] 商品分享标题图
     * @apiParam (入参) {String} [sheet_id] 商品表格ID
     * @apiSampleRequest /admin/goods/saveaddgoods
     * @return array
     * @author zyr
     */
    public function saveAddGoods() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $supplierId     = trim($this->request->post('supplier_id')); //供应商id
        $cateId         = trim($this->request->post('cate_id')); //分类id
        $goodsName      = trim($this->request->post('goods_name')); //商品名称
        $goodsType      = trim($this->request->post('goods_type')); //商品类型
        $targetUsers    = trim($this->request->post('target_users')); //适用人群
        $subtitle       = trim($this->request->post('subtitle')); //标题
        $image          = trim($this->request->post('image')); //商品标题图
        $share_image    = trim($this->request->post('share_image')); //商品标题图
        $giving_rights  = trim($this->request->post('giving_rights')); //商品赠送权益
        $share_image    = trim($this->request->post('share_image')); //商品分享图片
        $sheet_id       = trim($this->request->post('sheet_id')); //商品分享图片
        $sheet_id       = $sheet_id ? $sheet_id : 1;
        $goodsTypeArr   = [1, 2];
        $targetUsersArr = [1, 2, 3, 4];
        if (!is_numeric($supplierId)) {
            return ['code' => '3001']; //供应商id只能为数字
        }
        if (!is_numeric($cateId)) {
            return ['code' => '3002']; //分类id只能为数字
        }
        if (empty($goodsName)) {
            return ['code' => '3003']; //商品名称不能空
        }
        if (empty($image)) {
            return ['code' => '3004']; //标题图不能空
        }
        if (!empty($goodsType) && !in_array($goodsType, $goodsTypeArr)) {
            return ['code' => '3005']; //商品类型只能为数字
        }
        if (!empty($targetUsers) && !in_array($targetUsers, $targetUsersArr)) {
            return ['code' => '3011']; //适用人群只能为数字
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
        if (!empty($targetUsers)) {
            $data['target_users'] = intval($targetUsers);
        }
        if (!empty($subtitle)) {
            $data['subtitle'] = $subtitle;
        }
        if (!empty($share_image)) {
            $data['share_image'] = $share_image;
        }
        if (!empty($giving_rights) && in_array($giving_rights,[1, 2, 3, 4])) {
            $data['giving_rights'] = $giving_rights;
        }
        if ($goodsType == 2 && $giving_rights != 1) {
            return ['code' => '3015'];
        }
        if (!empty($sheet_id)){
            $data['goods_sheet'] = intval($sheet_id);
        }
        //调用方法存商品表
        $result = $this->app->goods->saveGoods($data);
        $this->apiLog($apiName, [$cmsConId, $supplierId, $cateId, $goodsName, $goodsType, $subtitle, $image], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改商品基础信息
     * @apiDescription   saveUpdateGoods
     * @apiGroup         admin_goods
     * @apiName          saveUpdateGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:供应商id只能为数字 / 3002:分类id只能为数字 / 3003:商品名称不能空 / 3004:标题图不能空 / 3005:商品类型只能为数字 / 3006:商品名称重复 / 3007:提交的分类id不是三级分类 / 3008:供应商不存在 / 3009:修改失败 / 3010:图片没有上传过 / 3012:分享图片没有上传过 / 3015:虚拟商品暂时不支持赠送权益
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} supplier_id 供应商id
     * @apiParam (入参) {Number} cate_id 三级分类id
     * @apiParam (入参) {String} goods_name 商品名称
     * @apiParam (入参) {Number} [goods_type] 商品类型 1普通商品 2 虚拟商品(默认1)
     * @apiParam (入参) {Number} [target_users] 商品适用人群:1,全部;2,钻石及以上;3,创业店主及以上;4,合伙人及以上(默认1)
     * @apiParam (入参) {Number} [giving_rights] 商品赠送权益 1,不赠送;2,钻石;3,创业店主;4,合伙人(默认1)
     * @apiParam (入参) {Number} [goods_sheet] 表格ID
     * @apiParam (入参) {String} [subtitle] 标题
     * @apiParam (入参) {String} [image] 商品标题图
     * @apiParam (入参) {String} [share_image] 商品分享标题图
     * @apiSampleRequest /admin/goods/saveupdategoods
     * @return array
     * @author zyr
     */
    public function saveUpdateGoods() {
        $apiName        = classBasename($this) . '/' . __function__;
        $cmsConId       = trim($this->request->post('cms_con_id')); //操作管理员
        $goodsId        = trim($this->request->post('goods_id')); //商品id
        $supplierId     = trim($this->request->post('supplier_id')); //供应商id
        $cateId         = trim($this->request->post('cate_id')); //分类id
        $goodsName      = trim($this->request->post('goods_name')); //商品名称
        $goodsType      = trim($this->request->post('goods_type')); //商品类型
        $targetUsers    = trim($this->request->post('target_users')); //适用人群
        $subtitle       = trim($this->request->post('subtitle')); //标题
        $image          = trim($this->request->post('image')); //商品标题图
        $share_image    = trim($this->request->post('share_image')); //商品标题图
        $giving_rights  = trim($this->request->post('giving_rights')); //商品赠送权益
        $sheet_id       = trim($this->request->post('goods_sheet')); //商品分享图片
        $sheet_id       = $sheet_id ? $sheet_id : 0;
        $goodsTypeArr   = [1, 2];
        $targetUsersArr = [1, 2, 3, 4];
        if (!is_numeric($supplierId)) {
            return ['code' => '3001']; //供应商id只能为数字
        }
        if (!is_numeric($cateId)) {
            return ['code' => '3002']; //分类id只能为数字
        }
        if (empty($goodsName)) {
            return ['code' => '3003']; //商品名称不能空
        }
        if (!empty($goodsType) && !in_array($goodsType, $goodsTypeArr)) {
            return ['code' => '3005']; //商品类型只能为数字
        }
        $data = [
            'supplier_id' => intval($supplierId),
            'cate_id'     => intval($cateId),
            'goods_name'  => $goodsName,
        ];
        if (!empty($goodsType)) {
            $data['goods_type'] = intval($goodsType);
        }
        if (!empty($targetUsers)) {
            if (!is_numeric($targetUsers) || !in_array($targetUsers, $targetUsersArr)) {
                return ['code' => '3011']; //适用人群只能为数字
            }
            $data['target_users'] = intval($targetUsers);
        }
        if (!empty($image)) {
            $data['image'] = $image;
        }
        if (!empty($subtitle)) {
            $data['subtitle'] = $subtitle;
        }
        if (!empty($share_image)) {
            $data['share_image'] = $share_image;
        }
        if (!empty($giving_rights) && in_array($giving_rights,[1, 2, 3, 4])) {
            $data['giving_rights'] = $giving_rights;
        }
        if ($goodsType == 2 && $giving_rights != 1) {
            return ['code' => '3015'];
        }
        // if (!empty($sheet_id)){
        $data['goods_sheet'] = intval($sheet_id);
        // }
        //调用方法存商品表
        print_r($data);die;
        $res = $this->app->goods->saveGoods($data, $goodsId);
        $this->apiLog($apiName, [$cmsConId, $goodsId, $supplierId, $cateId, $goodsName, $goodsType, $subtitle, $image], $res['code'], $cmsConId);
        return $res;
    }

    /**
     * @api              {post} / 获取一个sku信息
     * @apiDescription   getGoodsSku
     * @apiGroup         admin_goods
     * @apiName          getGoodsSku
     * @apiParam (入参) {String} cms_con_id
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
     * @apiSuccess (data) {Array} attr 属性列表
     * @apiSuccess (data) {Number} integral_price 积分售价
     * @apiSuccess (data) {Number} weight 重量(单位kg)用作计算运费
     * @apiSuccess (data) {Number} volume 体积(单位m³)用作计算运费
     * @apiSampleRequest /admin/goods/getgoodssku
     * @return array
     * @author zyr
     */
    public function getGoodsSku() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $skuId    = trim($this->request->post('sku_id'));
        if (!is_numeric($skuId)) {
            return ['code' => '3001'];
        }
        $result = $this->app->goods->getGoodsSku(intval($skuId));
        $this->apiLog($apiName, [$cmsConId, $skuId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 编辑商品sku
     * @apiDescription   editGoodsSku
     * @apiGroup         admin_goods
     * @apiName          editGoodsSku
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} sku_id
     * @apiParam (入参) {Int} stock 库存
     * @apiParam (入参) {Int} freight_id 运费模版id
     * @apiParam (入参) {Decimal} market_price 市场价
     * @apiParam (入参) {Decimal} retail_price 零售价
     * @apiParam (入参) {Decimal} cost_price 成本价
     * @apiParam (入参) {Decimal} margin_price 其他运费成本
     * @apiParam (入参) {Int} integral_price 积分售价
     * @apiParam (入参) {String} sku_image 规格详情图
     * @apiParam (入参) {Decimal} [weight] 重量(单位kg)用作计算运费
     * @apiParam (入参) {Decimal} [volume] 体积(单位m³)用作计算运费
     * @apiSuccess (返回) {String} code 200:成功 / 3000:没有商品sku / 3001:id必须为数字 / 3002:库存必须为大于或等于0的数字 / 3003:价格必须为大于或等于0的数字 / 3004:积分必须为大于或等于0的数字 / 3005:图片没有上传过 / 3006:零售价不能小于成本价 / 3007:skuid不存在 / 3008:编辑失败 / 3009:选择的供应山id有误 / 3010:请填写零售价和成本价 / 3011:选择重量模版必须填写重量 / 3012:选择体积模版必须填写体积 / 3013:商品下架才能编辑 / 3014:未选择运费模版
     * @apiSampleRequest /admin/goods/editgoodssku
     * @return array
     * @author zyr
     */
    public function editGoodsSku() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $skuId         = trim($this->request->post('sku_id'));
        $stock         = trim($this->request->post('stock')); //库存
        $freightId     = trim($this->request->post('freight_id')); //运费模版
        $marketPrice   = trim($this->request->post('market_price')); //市场价
        $retailPrice   = trim($this->request->post('retail_price')); //零售价
        $costPrice     = trim($this->request->post('cost_price')); //成本价
        $marginPrice   = trim($this->request->post('margin_price')); //其他运费成本
        $integralPrice = trim($this->request->post('integral_price')); //积分售价
        $weight        = trim($this->request->post('weight')); //重量
        $volume        = trim($this->request->post('volume')); //体积
        $skuImage      = trim($this->request->post('sku_image')); //规格详情图
        if (!is_numeric($skuId) || !is_numeric($freightId)) { //id必须为数字
            return ['code' => '3001'];
        }
        $freightId = intval($freightId);
        if ($freightId <= 0) {
            return ['code' => '3014'];
        }
        if (!is_numeric($stock) || intval($stock) < 0) { //库存必须为大于或等于0的数字
            return ['code' => '3002'];
        }
        if (!is_numeric($marketPrice) || !is_numeric($retailPrice) || !is_numeric($costPrice) || !is_numeric($marketPrice) || floatval($marketPrice) < 0 || floatval($retailPrice) < 0 || floatval($costPrice) < 0 || floatval($marginPrice) < 0) { //价格必须为大于或等于0的数字
            return ['code' => '3003'];
        }
        if (!is_numeric($integralPrice) || intval($integralPrice) < 0) { //积分必须为大于或等于0的数字
            return ['code' => '3004'];
        }
        $retailPrice = floatval($retailPrice);
        $costPrice   = floatval($costPrice);
        if ($retailPrice < bcadd($costPrice, $marginPrice, 2)) {
            return ['code' => '3006']; //零售价不能小于成本价
        }
        $data = [
            'stock'          => $stock,
            'freight_id'     => $freightId,
            'market_price'   => $marketPrice,
            'retail_price'   => $retailPrice,
            'cost_price'     => $costPrice,
            'margin_price'   => $marginPrice,
            'integral_price' => $integralPrice,
            'sku_image'      => $skuImage,
        ];
        $result = $this->app->goods->editGoodsSku($skuId, $data, $weight, $volume);
        $this->apiLog($apiName, [$cmsConId, $skuId, $stock, $freightId, $marketPrice, $retailPrice, $costPrice, $marginPrice, $integralPrice, $weight, $volume, $skuImage], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加商品的规格属性
     * @apiDescription   addGoodsSpec
     * @apiGroup         admin_goods
     * @apiName          addGoodsSpec
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} attr_id 属性id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:属性id必须为数字 / 3002:商品id必须为数字 / 3003:属性不存在 / 3004:商品不存在 / 3005:规格不能为空 / 3006:商品已有该规格属性 / 3007:提交失败 / 3008:没有任何操作 / 3009:提交的属性分类和商品分类不同 / 3013:商品下架才能编辑
     * @apiSampleRequest /admin/goods/addgoodsspec
     * @return array
     * @author zyr
     */
    public function addGoodsSpec() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $goodsId = trim($this->request->post('goods_id')); //商品id
        $attrId  = trim($this->request->post('attr_id')); //属性id
        if (!is_numeric($goodsId)) {
            return ['code' => '3002']; //商品id必须为数字
        }
        if (!is_numeric($attrId)) {
            return ['code' => '3001']; //属性id必须为数字
        }
        $result = $this->app->goods->addGoodsSpec(intval($attrId), intval($goodsId));
        $this->apiLog($apiName, [$cmsConId, $goodsId, $attrId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除商品的规格属性
     * @apiDescription   delGoodsSpec
     * @apiGroup         admin_goods
     * @apiName          delGoodsSpec
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} attr_id 属性id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:属性id必须为数字 / 3002:商品id必须为数字 / 3003:属性不存在 / 3004:商品不存在 / 3005:积分必须为大于或等于0的数字 /3006:该商品未绑定这个属性 / 3007:提交失败/ 3008:没有任何操作 / 3009:提交的属性分类和商品分类不同 / 3013:商品下架才能编辑
     * @apiSampleRequest /admin/goods/delgoodsspec
     * @return array
     * @author zyr
     */
    public function delGoodsSpec() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $goodsId = trim($this->request->post('goods_id')); //商品id
        $attrId  = trim($this->request->post('attr_id')); //属性id
        if (!is_numeric($goodsId)) {
            return ['code' => '3002']; //商品id必须为数字
        }
        if (!is_numeric($attrId)) {
            return ['code' => '3001']; //属性id必须为数字
        }
        $result = $this->app->goods->delGoodsSpec(intval($attrId), intval($goodsId));
        $this->apiLog($apiName, [$cmsConId, $goodsId, $attrId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加音频类商品的sku
     * @apiDescription   addAudioSku
     * @apiGroup         admin_goods
     * @apiName          addAudioSku
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {String} name 规格名称
     * @apiParam (入参) {String} audio_id_list 音频内容id列表(1,2,3,4逗号分割)
     * @apiParam (入参) {Decimal} market_price 市场价
     * @apiParam (入参) {Decimal} retail_price 零售价
     * @apiParam (入参) {Decimal} cost_price 成本价
     * @apiParam (入参) {Number} integral_price 积分售价
     * @apiParam (入参) {Number} end_time 结束时间(按小时记)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:音频内容id列表有误 / 3002:商品id必须为数字 / 3003:音频不存在无法添加 / 3004:价格必须为大于或等于0的数字且长度不超过8位数 / 3005:积分必须为大于或等于0的数字，且长度不超过10 / 3006:结束时间有误 / 3007:商品不是音频商品 / 3008:添加失败 / 3009:name为空或者超出30个字符长度
     * @apiSampleRequest /admin/goods/addaudiosku
     * @return array
     * @author zyr
     */
    public function addAudioSku() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $goodsId       = trim($this->request->post('goods_id'));//商品id
        $name          = trim($this->request->post('name'));//规格名称
        $audioIdList   = trim($this->request->post('audio_id_list'));//audio主键
        $marketPrice   = trim($this->request->post('market_price'));//市场价
        $retailPrice   = trim($this->request->post('retail_price'));//零售价
        $costPrice     = trim($this->request->post('cost_price'));//成本价
        $integralPrice = trim($this->request->post('integral_price', 0));//积分售价
        $endTime       = trim($this->request->post('end_time'));//结束时间(按小时记)
        if (!is_numeric($goodsId)) {
            return ['code' => '3002'];//商品id必须为数字
        }
        $audioIdList = explode(',', $audioIdList);
        $audioIdList = array_map(function ($v) {
            if (is_numeric($v) && intval($v) > 0) {
                return intval($v);
            }
            return 0;
        }, $audioIdList);
        if (in_array(0, $audioIdList)) {
            return ['code' => '3001'];
        }
        if (!is_numeric($marketPrice) || !is_numeric($retailPrice) || !is_numeric($costPrice) || floatval($marketPrice) < 0 || floatval($retailPrice) < 0 || floatval($costPrice) < 0 || mb_strlen(floatval($marketPrice),'utf8') > 11 || mb_strlen(floatval($retailPrice),'utf8') > 11 || mb_strlen(floatval($costPrice),'utf8') > 11) {//价格必须为大于或等于0的数字
            return ['code' => '3004'];
        }
        if (!is_numeric($integralPrice) || intval($integralPrice) < 0 || mb_strlen($costPrice,'utf8') > 10) {//积分必须为大于或等于0的数字
            return ['code' => '3005'];
        }
        $marketPrice = floatval($marketPrice);
        $retailPrice = floatval($retailPrice);
        $costPrice   = floatval($costPrice);
        if (!is_numeric($endTime) || intval($endTime) < 0) {
            return ['code' => '3006'];
        }
        if (empty($name) || mb_strlen($name,'utf8') > 30) {
            return ['code' => '3009'];
        }
        //$audioIdList = implode(',', $audioIdList);
        $result = $this->app->goods->addAudioSku(intval($goodsId), $audioIdList, $marketPrice, $retailPrice, $costPrice, $integralPrice, $endTime, $name);
        $this->apiLog($apiName, [$cmsConId, $goodsId, implode(',', $audioIdList), $marketPrice, $retailPrice, $costPrice, $integralPrice, $name], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取商品的音频sku
     * @apiDescription   getGoodsAudioSku
     * @apiGroup         admin_goods
     * @apiName          getGoodsAudioSku
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} sku_id 音频sku_id
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3002:商品id和sku_id必须为数字 / 
     * @apiSampleRequest /admin/goods/getGoodsAudioSku
     * @return array
     * @author rzc
     */
    public function getGoodsAudioSku(){
        $sku_id        = trim($this->request->post('sku_id'));//商品id
        $goodsId       = trim($this->request->post('goods_id'));//商品id
        if (!is_numeric($sku_id) || !is_numeric($goodsId) || intval($sku_id) < 1 || intval($goodsId) < 1) {
            return ['code' => '3002'];
        }
        $result = $this->app->goods->getGoodsAudioSku($sku_id, $goodsId);
        return $result;
    }

    /**
     * @api              {post} / 修改音频类商品的sku
     * @apiDescription   saveAudioSku
     * @apiGroup         admin_goods
     * @apiName          saveAudioSku
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} sku_id 音频sku_id
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {String} [name] 规格名称
     * @apiParam (入参) {String} [audio_id_list] 音频内容id列表(1,2,3,4逗号分割)
     * @apiParam (入参) {Decimal} [market_price] 市场价
     * @apiParam (入参) {Decimal} [retail_price] 零售价
     * @apiParam (入参) {Decimal} cost[_price 成本价
     * @apiParam (入参) {Number} [integral_price] 积分售价
     * @apiParam (入参) {Number} [end_time] 结束时间(按小时记)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:音频内容id列表有误 / 3002:商品id和sku_id必须为数字 / 3003:音频不存在无法添加 / 3004:价格必须为大于或等于0的数字,并且价格长度不超过8位数 / 3005:积分必须为大于或等于0的数字且长度不超过10位 / 3006:结束时间有误 / 3007:商品不是音频商品 / 3008:更新失败 / 3009:该sku不存在 / 3010:该用户没有权限 / 3011:已上架商品无法编辑 / 3012:name长度超出30
     * @apiSampleRequest /admin/goods/saveAudioSku
     * @return array
     * @author rzc
     */
    public function saveAudioSku(){
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $sku_id        = trim($this->request->post('sku_id'));//商品id
        $goodsId       = trim($this->request->post('goods_id'));//商品id
        $name          = trim($this->request->post('name'));//规格名称
        $audioIdList   = trim($this->request->post('audio_id_list'));//audio主键
        $marketPrice   = trim($this->request->post('market_price'));//市场价
        $retailPrice   = trim($this->request->post('retail_price'));//零售价
        $costPrice     = trim($this->request->post('cost_price'));//成本价
        $integralPrice = trim($this->request->post('integral_price', 0));//积分售价
        $endTime       = trim($this->request->post('end_time'));//结束时间(按小时记)
        if (!is_numeric($goodsId) || !is_numeric($sku_id)) {
            return ['code' => '3002'];//商品id必须为数字
        }
        if (!empty($audioIdList)){
            $audioIdList = explode(',', $audioIdList);
            $audioIdList = array_map(function ($v) {
                if (is_numeric($v) && intval($v) > 0) {
                    return intval($v);
                }
                return 0;
            }, $audioIdList);
            if (in_array(0, $audioIdList)) {
                return ['code' => '3001'];
            }
        }
        if (mb_strlen($marketPrice,'utf8') > 8) {}
        if (!empty($marketPrice)) {//价格必须为大于或等于0的数字
            if (!is_numeric($marketPrice) || floatval($marketPrice) < 0 || mb_strlen(floatval($marketPrice),'utf8') > 11) {
                return ['code' => '3004'];
            }
        }
        if (!empty($retailPrice)) {//价格必须为大于或等于0的数字
            if (!is_numeric($retailPrice) || floatval($retailPrice) < 0 || mb_strlen(floatval($retailPrice),'utf8') > 11) {
                return ['code' => '3004'];
            }
        }
        if (!empty($costPrice)) {//价格必须为大于或等于0的数字
            if (!is_numeric($costPrice) || floatval($costPrice) < 0 || mb_strlen(floatval($costPrice),'utf8') > 11) {
                return ['code' => '3004'];
            }
        }
        if (!empty($integralPrice)){
            if (!is_numeric($integralPrice) || intval($integralPrice) < 0 || mb_strlen($integralPrice,'utf8') > 10) {//积分必须为大于或等于0的数字
                return ['code' => '3005'];
            }
        }
        $marketPrice = floatval($marketPrice);
        $retailPrice = floatval($retailPrice);
        $costPrice   = floatval($costPrice);
        if (!empty($endTime)) {
            if (!is_numeric($endTime) || intval($endTime) < 0) {
                return ['code' => '3006'];
            }
        }
        if (!empty($name) && mb_strlen($name, 'utf8') > 30) {
            return ['code' => '3012'];
        }
        //$audioIdList = implode(',', $audioIdList);
        $result = $this->app->goods->saveAudioSku(intval($goodsId), intval($sku_id), $audioIdList, $marketPrice, $retailPrice, $costPrice, $integralPrice, $endTime, $name);
        if (!empty($audioIdList)) {
            $audioIdList = implode(',', $audioIdList);
        }else{
            $audioIdList = '';
        }
        $this->apiLog($apiName, [$cmsConId, $goodsId, $sku_id, $audioIdList, $marketPrice, $retailPrice, $costPrice, $integralPrice, $endTime, $name], $result['code'], $cmsConId);
        return $result;

    }

    /**
     * @api              {post} / 获取一个商品数据
     * @apiDescription   getOneGoods
     * @apiGroup         admin_goods
     * @apiName          getOneGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 商品id
     * @apiParam (入参) {Number} [get_type] 获取内容类型 1.只获取goods_data 2. 获取spec_attr 3.获取images_detatil和images_carousel  4.获取sku   默认为1,2,3,4
     * @apiParam (入参) {Number} [goods_type] 1.普通商品 2.音频商品
     * @apiSuccess (返回) {String} code 200:成功 / 3000:商品基本数据获取失败 /3002:id必须是数字 / 3003:get_type错误 / 3004:goods_type错误 / 3005:该商品不属于这个类型
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
     * @apiSuccess (goods_data) {Number} target_users 商品适用人群:1,全部;2,钻石及以上;3,创业店主及以上;4,合伙人及以上
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
     * @apiParam (入参) {Decimal} [weight] 重量(单位kg)用作计算运费
     * @apiParam (入参) {Decimal} [volume] 体积(单位m³)用作计算运费
     * @apiSampleRequest /admin/goods/getonegoods
     * @author zyr
     */
    public function getOneGoods() {
        $apiName      = classBasename($this) . '/' . __function__;
        $cmsConId     = trim($this->request->post('cms_con_id')); //操作管理员
        $getTypeArr   = [1, 2, 3, 4];
        $id           = trim(input("post.id"));
        $getType      = trim($this->request->post('get_type'));
        $getType      = empty($getType) ? '1,2,3,4' : $getType;
        $goodsType    = trim($this->request->post('goods_type', 1));
        $goodsTypeArr = [1, 2];
        if (!is_numeric($id)) {
            return ["code" => 3002];
        }
        $getType = explode(',', $getType);
        foreach ($getType as $val) {
            if (!in_array($val, $getTypeArr)) {
                return ['code' => '3003'];
            }
        }
        if (!in_array($goodsType, $goodsTypeArr)) {
            return ['code' => '3004'];
        }
        $res = $this->app->goods->getOneGoods($id, $getType, $goodsType);
        $this->apiLog($apiName, [$cmsConId, $id, $getType, $goodsType], $res['code'], $cmsConId);
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
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} image_type 图片类型 1.详情图 2.轮播图
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Array} images 图片集合
     * @apiSuccess (返回) {String} code 200:成功 / 3001:图片类型有误 / 3002:商品id只能是数字 / 3003:图片不能空 / 3004:商品id不存在 / 3005:图片没有上传过 / 3006:上传失败
     * @apiSampleRequest /admin/goods/uploadgoodsimages
     * @return array
     * @author zyr
     */
    public function uploadGoodsImages() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $imageTypeArr = [1, 2]; //1.详情图 2.轮播图
        $goodsId      = trim($this->request->post('goods_id'));
        $imageType    = trim($this->request->post('image_type'));
        $images       = $this->request->post('images');
        if (!is_numeric($imageType) || !in_array(intval($imageType), $imageTypeArr)) {
            return ['code' => '3001']; //图片类型有误
        }
        if (!is_numeric($goodsId)) {
            return ['code' => '3002']; //商品id只能是数字
        }
        if (empty($images)) {
            return ['code' => '3003']; //图片不能空
        }
        $result = $this->app->goods->uploadGoodsImages($goodsId, $imageType, $images);
        $this->apiLog($apiName, [$cmsConId, $goodsId, $imageType, $images], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除商品详情和轮播图
     * @apiDescription   delGoodsImage
     * @apiGroup         admin_goods
     * @apiName          delGoodsImage
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} image_path 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:图片不能为空 / 3002:图片不存在 / 3003:上传失败
     * @apiSampleRequest /admin/goods/delgoodsimage
     * @return array
     * @author zyr
     */
    public function delGoodsImage() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $imagePath = trim($this->request->post('image_path'));
        if (empty($imagePath)) {
            return ['code' => '3001']; //图片不能为空
        }
        $result = $this->app->goods->delGoodsImage($imagePath);
        $this->apiLog($apiName, [$cmsConId, $imagePath], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 对商品图进行排序
     * @apiDescription   sortImageDetail
     * @apiGroup         admin_goods
     * @apiName          sortImageDetail
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} image_path 商品id
     * @apiParam (入参) {Number} order_by 排序
     * @apiSuccess (返回) {String} code 200:成功 / 3001:图片不能为空 / 3002:图片不存在 / 3003:排序字段只能为数字 / 3004:上传失败
     * @apiSampleRequest /admin/goods/sortimagedetail
     * @return array
     * @author zyr
     */
    public function sortImageDetail() {
        $apiName   = classBasename($this) . '/' . __function__;
        $cmsConId  = trim($this->request->post('cms_con_id')); //操作管理员
        $imagePath = trim($this->request->post('image_path'));
        $orderBy   = trim($this->request->post('order_by'));
        if (empty($imagePath)) {
            return ['code' => '3001']; //图片不能为空
        }
        if (!is_numeric($orderBy)) {
            return ['code' => '3003']; //排序字段只能为数字
        }
        $result = $this->app->goods->sortImageDetail($imagePath, intval($orderBy));
        $this->apiLog($apiName, [$cmsConId, $imagePath, $orderBy], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 上下架
     * @apiDescription   upDownGoods
     * @apiGroup         admin_goods
     * @apiName          upDownGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 商品id
     * @apiParam (入参) {Number} type 上下架状态 1上架 / 2下架
     * @apiSuccess (返回) {String} code 200:成功 / 3001:商品不存在 / 3002:参数必须是数字 / 3003:没有可售库存 / 3004:请填写零售价 / 3005:请填写成本价 / 3006:没有详情图 / 3007:没有轮播图 /3008:上下架失败/ 3009:请选择分类
     * @apiSampleRequest /admin/goods/updowngoods
     * @author wujunjie
     * 2019/1/8-10:13
     */
    public function upDownGoods() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id   = trim(input("post.id"));
        $type = trim(input("post.type"));
        if (!is_numeric($id) || !is_numeric($type)) {
            return ["code" => '3002'];
        }
        $result = $this->app->goods->upDown(intval($id), intval($type));
        $this->apiLog($apiName, [$cmsConId, $id, $type], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取表格内容选项
     * @apiDescription   getSheetOption
     * @apiGroup         admin_goods
     * @apiName          getSheetOption
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSampleRequest /admin/goods/getSheetOption
     * @author rzc
     */
    public function getSheetOption(){
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $result = $this->app->goods->getSheetOption();
        return $result;
    }

    /**
     * @api              {post} / 获取表格模板
     * @apiDescription   getSheet
     * @apiGroup         admin_goods
     * @apiName          getSheet
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [page]
     * @apiParam (入参) {Number} [pageNum]
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSampleRequest /admin/goods/getSheet
     * @author rzc
     */
    public function getSheet(){
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $page          = trim($this->request->post('page'));
        $pageNum       = trim($this->request->post('pageNum'));
        $page          = empty($page) ? 1 : $page;
        $pageNum       = empty($pageNum) ? 10 : $pageNum;
        $result = $this->app->goods->getSheet($page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 添加表格模板
     * @apiDescription   addSheet
     * @apiGroup         admin_goods
     * @apiName          addSheet
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} name 表格名称
     * @apiParam (入参) {Number} options 选项ID，用‘,’连接
     * @apiSuccess (返回) {String} code 200:成功 / 3001:存在相同名称的表格 / 3002:表格名称为空 / 3003:options为空 / 3005:数据回滚，添加失败
     * @apiSampleRequest /admin/goods/addSheet
     * @author rzc
     */
    public function addSheet(){
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $name     = trim($this->request->post('name'));
        $options  = trim($this->request->post('options'));
        if (empty($name)){
            return ['code' => '3002'];
        }
        if (empty($options)){
            return ['code' => '3003'];
        }
        $options = explode(',',$options);
        $result   = $this->app->goods->addSheet($name, $options);
        return $result;
    }

    /**
     * @api              {post} / 修改表格模板
     * @apiDescription   editSheet
     * @apiGroup         admin_goods
     * @apiName          editSheet
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [name] 表格名称
     * @apiParam (入参) {Number} [options] 选项ID，用‘,’连接
     * @apiParam (入参) {Number} id 表格id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:存在相同名称的表格  / 3005:修改失败
     * @apiSampleRequest /admin/goods/editSheet
     * @author rzc
     */
    public function editSheet(){
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $name     = trim($this->request->post('name'));
        $options  = trim($this->request->post('options'));
        $id       = trim($this->request->post('id'));
        if (!empty($options)){
            $options = explode(',',$options);
        }
        $result   = $this->app->goods->editSheet($name, $id, $options);
        return $result;
    }

    /**
     * @api              {post} / 获取表格详情
     * @apiDescription   getSheetInfo
     * @apiGroup         admin_goods
     * @apiName          getSheetInfo
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 表格id
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSampleRequest /admin/goods/getSheetInfo
     * @author rzc
     */
    public function getSheetInfo(){
        $id       = trim($this->request->post('id'));
        if (empty($id) || !is_numeric($id) || intval($id) < 1){
            return ['code' => 3001];
        }
        $result   = $this->app->goods->getSheetInfo($id);
        return $result;
    }

}
