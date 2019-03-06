<?php

namespace app\index\controller;

use app\index\MyController;

class Recommend extends MyController {
    protected $beforeActionList = [
//        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'getRecommend'],//除去getFirstCate其他方法都进行isLogin前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 首页显示内容
     * @apiDescription   getRecommend
     * @apiGroup         index_Recommend
     * @apiName          getRecommend
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /index/Recommend/getRecommend
     * @apiSuccess (data) {object_array} recommends 结果
     * @apiSuccess (recommends) {String} id 主键ID
     * @apiSuccess (recommends) {String} model_id 模板id 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiSuccess (recommends) {String} title 标题
     * @apiSuccess (recommends) {String} image_path 图片路径
     * @apiSuccess (recommends) {String} jump_type 跳转类型: 1:专题 2:商品 3:路径
     * @apiSuccess (recommends) {String} jump_content 跳转内容
     * @apiSuccess (recommends) {String} model_order 模板排序
     * @apiSuccess (recommends) {String} is_show 模块是否显示,1:显示,2:不显示
     * @apiSuccess (recommends[son]) {String} id 主键ID
     * @apiSuccess (recommends[son]) {String} model_id 模板id 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiSuccess (recommends[son]) {String} title 标题
     * @apiSuccess (recommends[son]) {String} image_path 图片路径
     * @apiSuccess (recommends[son]) {String} parent_id 关联上级ID
     * @apiSuccess (recommends[son]) {String} jump_type 跳转类型: 1:专题 2:商品 3:路径
     * @apiSuccess (recommends[son]) {String} jump_content 跳转内容
     * @apiSuccess (recommends[son]) {String} show_type 展示类型: 1:图片 2:商品
     * @apiSuccess (recommends[son]) {String} show_data 展示商品ID
     * @apiSuccess (recommends[son]) {String} show_days 展示每周天数
     * @apiSuccess (recommends[son]) {String} tier 层级
     * @apiSuccess (recommends[son]) {String} is_show 模块是否显示,1:显示,2:不显示
     * @apiSuccess (recommends[son]) {String} model_order 模板排序
     * @apiSuccess (recommends[son]) {String} goods_id 商品ID
     * @apiSuccess (recommends[son]) {String} supplier_id 商品供应商ID
     * @apiSuccess (recommends[son]) {String} cate_id 商品分类ID
     * @apiSuccess (recommends[son]) {String} goods_name 商品名称
     * @apiSuccess (recommends[son]) {String} goods_title 商品标题
     * @apiSuccess (recommends[son]) {String} goods_subtitle 商品副标题
     * @apiSuccess (recommends[son]) {String} goods_image 商品图片
     * @apiSuccess (recommends[son]) {String} goods_status 商品状态
     * @apiSuccess (recommends[son]) {String} goods_min_brokerage 商品最小钻石返利
     * @apiSuccess (recommends[son]) {String} goods_min_integral_active 商品最小赠送积分
     * @apiSuccess (recommends[son][third]) {String} id 主键ID
     * @apiSuccess (recommends[son][third]) {String} model_id 模板id 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiSuccess (recommends[son][third]) {String} title 标题
     * @apiSuccess (recommends[son][third]) {String} image_path 图片路径
     * @apiSuccess (recommends[son][third]) {String} parent_id 关联上级ID
     * @apiSuccess (recommends[son][third]) {String} jump_type 跳转类型: 1:专题 2:商品 3:路径
     * @apiSuccess (recommends[son][third]) {String} jump_content 跳转内容
     * @apiSuccess (recommends[son][third]) {String} show_type 展示类型: 1:图片 2:商品
     * @apiSuccess (recommends[son][third]) {String} show_data 展示商品ID
     * @apiSuccess (recommends[son][third]) {String} show_days 展示每周天数
     * @apiSuccess (recommends[son][third]) {String} tier 层级
     * @apiSuccess (recommends[son][third]) {String} is_show 模块是否显示,1:显示,2:不显示
     * @apiSuccess (recommends[son][third]) {String} model_order 模板排序
     * @apiSuccess (recommends[son][third]) {String} goods_id 商品ID
     * @apiSuccess (recommends[son][third]) {String} supplier_id 商品供应商ID
     * @apiSuccess (recommends[son][third]) {String} cate_id 商品分类ID
     * @apiSuccess (recommends[son][third]) {String} goods_name 商品名称
     * @apiSuccess (recommends[son][third]) {String} goods_title 商品标题
     * @apiSuccess (recommends[son][third]) {String} goods_subtitle 商品副标题
     * @apiSuccess (recommends[son][third]) {String} goods_image 商品图片
     * @apiSuccess (recommends[son][third]) {String} goods_status 商品状态
     * @apiSuccess (recommends[son][third]) {String} goods_retail_price 商品零售价
     * @apiSuccess (recommends[son][third]) {String} goods_min_brokerage 商品最小钻石返利
     * @apiSuccess (recommends[son][third]) {String} goods_min_integral_active 商品最小赠送积分
     * @author rzc
     * 2019/3/6
     */
    public function getRecommend() {
        $res = $this->app->recommend->getRecommend();
        return $res;
    }
}
