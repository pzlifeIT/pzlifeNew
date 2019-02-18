<?php

namespace app\index\controller;

use app\index\MyController;

class Goods extends MyController
{
    /**
     * @api              {post} / 分类商品列表
     * @apiDescription   getCategoryGoods
     * @apiGroup         index_Goods
     * @apiName          getCategoryGoods
     * @apiParam (入参) {Number} cate_id 对应商品三级分类id
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在 
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} cate_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题 
     * @apiSuccess (data) {String} subtitle 副标题 
     * @apiSuccess (data) {String} image 商品标题图 
     * @apiSuccess (data) {String} min_market_price 最低市场价 
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石返利
     * @apiSampleRequest /index/goods/getCategoryGoods
     * @author rzc
     */
    public function getCategoryGoods(){
        $cate_id = trim($this->request->post('cate_id'));
        $page = trim($this->request->post('page'));
        $page_num = trim($this->request->post('page_num'));

        $goodslist = $this->app->goods->getCategoryGoods($cate_id,$page,$page_num);
        return $goodslist;
    }

    /**
     * @api              {post} / 商品详情
     * @apiDescription   getGoods
     * @apiGroup         index_Goods
     * @apiName          getGoods
     * @apiParam (入参) {Number} goods_id 对应商品id
     * @apiParam (入参) {Number} source 来源 1.全部 2.pc 3.app 4.微信
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} goods_data 商品数据
     * @apiSuccess (返回) {Array} goods_banner 商品轮播图
     * @apiSuccess (返回) {Array} goods_details 商品详情图
     * @apiSuccess (返回) {Array} goods_spec 商品规格属性
     * @apiSuccess (返回) {Array} goods_sku 商品SKU属性
     * @apiSuccess (goods_data) {String} id 商品ID
     * @apiSuccess (goods_data) {String} supplier_id 供应商ID
     * @apiSuccess (goods_data) {String} cate_id 分类ID
     * @apiSuccess (goods_data) {String} goods_name 商品名称
     * @apiSuccess (goods_data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (goods_data) {String} title 主标题 
     * @apiSuccess (goods_data) {String} subtitle 副标题 
     * @apiSuccess (goods_data) {String} image 商品标题图 
     * @apiSuccess (goods_banner) {String} goods_id 商品ID
     * @apiSuccess (goods_banner) {String} image_type 图片类型 1.详情图 2.轮播图
     * @apiSuccess (goods_banner) {String} image_path 图片地址 
     * @apiSuccess (goods_details) {String} goods_id 商品ID
     * @apiSuccess (goods_details) {String} image_type 图片类型 1.详情图 2.轮播图
     * @apiSuccess (goods_details) {String} image_path 图片地址
     * @apiSuccess (goods_spec) {String} id 类目id
     * @apiSuccess (goods_spec) {String} cate_id 商品三级分类
     * @apiSuccess (goods_spec) {String} spe_name 类目名称
     * @apiSuccess (goods_spec[list]) {String} id 二级规格属性ID
     * @apiSuccess (goods_spec[list]) {String} spec_id 商品一级类目
     * @apiSuccess (goods_spec[list]) {String} attr_name 商品二级类目名称
     * @apiSuccess (goods_sku) {String} id 商品skuid
     * @apiSuccess (goods_sku) {String} goods_id 商品skuid
     * @apiSuccess (goods_sku) {String} stock 库存
     * @apiSuccess (goods_sku) {String} market_price 市场价
     * @apiSuccess (goods_sku) {String} retail_price 零售价
     * @apiSuccess (goods_sku) {String} presell_start_time 预售价开始时间
     * @apiSuccess (goods_sku) {String} presell_end_time 预售价结束时间
     * @apiSuccess (goods_sku) {String} presell_price 预售价
     * @apiSuccess (goods_sku) {String} active_price 活动价
     * @apiSuccess (goods_sku) {String} active_start_time 活动价开始时间
     * @apiSuccess (goods_sku) {String} active_end_time 活动价过期时间
     * @apiSuccess (goods_sku) {String} margin_price 其他运费成本
     * @apiSuccess (goods_sku) {String} brokerage 钻石返利
     * @apiSuccess (goods_sku) {String} integral_price 积分售价
     * @apiSuccess (goods_sku) {String} integral_active 积分赠送
     * @apiSuccess (goods_sku) {String} spec sku属性列表
     * @apiSuccess (goods_sku) {String} sku_image 规格详情图
     * @apiSampleRequest /index/goods/getGoods
     * @author rzc
     */
    public function getGoods(){
        $goods_id = trim($this->request->post('goods_id'));
        $source = trim($this->request->post('source'));
        $result = $this->app->goods->getGoodsinfo($goods_id,intval($source));
        return $result;
    }
    
    /**
     * @api              {post} / 专题商品列表
     * @apiDescription   getSubjectGoods
     * @apiGroup         index_Goods
     * @apiName          getSubjectGoods
     * @apiParam (入参) {Number} subject_id 对应专题三级分类id
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在 
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} subject_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题 
     * @apiSuccess (data) {String} subtitle 副标题 
     * @apiSuccess (data) {String} image 商品标题图 
     * @apiSuccess (data) {String} min_market_price 最低市场价 
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石返利
     * @apiSampleRequest /index/goods/getSubjectGoods
     * @author rzc
     */
    public function getSubjectGoods(){
        $subject_id = trim($this->request->post('subject_id'));
        $page = trim($this->request->post('page'));
        $page_num = trim($this->request->post('page_num'));

        $goodslist = $this->app->goods->getSubjectGoods($subject_id,$page,$page_num);
        return $goodslist;
    }

    /**
     * @api              {post} / 搜索商品列表(未完成)
     * @apiDescription   getSearchGoods
     * @apiGroup         index_Goods
     * @apiName          getSearchGoods
     * @apiParam (入参) {String} search 搜索内容
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在 
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} subject_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题 
     * @apiSuccess (data) {String} subtitle 副标题 
     * @apiSuccess (data) {String} image 商品标题图 
     * @apiSuccess (data) {String} min_market_price 最低市场价 
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石返利
     * @apiSampleRequest /index/goods/getSearchGoods
     * @author rzc
     */
    public function getSearchGoods(){

    }
}
