<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\AdminController;
use upload\Imageupload;

class Goods extends AdminController
{
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
    public function getGoodsList(){
        $page = trim(input("post.page"));
        $page = empty($page) ? 1 : intval($page);
        $pageNum = trim(input("post.pageNum"));
        $pageNum = empty($pageNum) ? 10 : intval($pageNum);
        if (!is_numeric($page) || !is_numeric($pageNum)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->goods->goodsList($page,$pageNum);
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
     * @apiParam (入参) {Array} images 商品图片(一个数组单元是一个json字符串，有几张图片就有几个数组单元,下面有样式)
     * @apiParamExample (images) {Array} 商品图片
     * [
     * {"source_type":1,"image_type":1,"image_path":""},
     *{"source_type":1,"image_type":1,"image_path":""},
     *{"source_type":1,"image_type":1,"image_path":""},
     *{"source_type":1,"image_type":1,"image_path":""},
     * ]
     * @apiParam (images) {Number} source_type 来源 1全部 / 2pc / 3app / 4微信
     * @apiParam (images) {Number} image_type 图片类型 1详情图 / 2轮播图
     * @apiParam (images) {String} image_path 图片内容
     * @apiParam (入参) {Array} skus sku数据 （一个数组单元是一个json字符串）
     * @apiParamExample (skus) {Array} 商品图片
     * [
     * {"stock":1,"market_price":1,"retail_price":"","presell_start_time":"","sku":"{1:1,2:1}"...},
     * {"stock":1,"market_price":1,"retail_price":"","presell_start_time":"","sku":"{1:1,2:1}"...},
     * {"stock":1,"market_price":1,"retail_price":"","presell_start_time":"","sku":"{1:1,2:1}"...},
     * {"stock":1,"market_price":1,"retail_price":"","presell_start_time":"","sku":"{1:1,2:1}"...},
     * ]
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
     * @apiParam (入参) {Array} relation 规格属性 （一个数组单元是一个json字符串）
     * @apiParamExample (relation) {Array} 规格属性数据样式:
     * [
     * {"spec_id":1,"attr_id":1},颜色为红色
     *{"spec_id":1,"attr_id":2}，颜色为白色
     *{"spec_id":2,"attr_id":3}，尺寸为x
     *{"spec_id":2,"attr_id":4}，尺寸为xl
     * ]
     * @apiSampleRequest /admin/goods/saveaddgoods
     * @author wujunjie
     * 2018/12/28-16:54
     */
    public function saveAddGoods(){
        $post = input("post.");
        if (empty($post)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        //调用方法存商品表
        $res = $this->app->goods->saveAddGoods($post);
        return $res;
    }

    /**
     * 上传图片
     * @return array
     * @author wujunjie
     * @throws \Exception
     * 2019/1/2-15:51
     */
    public function uploadImage(){
        $image = $this->request->file("image");
        $imageInfo = $image->getInfo();
        $upload = new Imageupload();
        $fileName = $upload->getNewName($imageInfo["name"]);
        $uploadImage = $upload->uploadFile($imageInfo["tmp_name"],$fileName);
        if (empty($uploadImage)){
            return ["msg"=>"图片上传失败","coed"=>3004];
        }
        return ["msg"=>"上传成功","code"=>200];
    }

    /**
     * @api              {post} / 获取一个商品数据
     * @apiDescription   getOneGoods
     * @apiGroup         admin_goods
     * @apiName          getOneGoods
     * @apiSuccess (返回) {String} code 200:成功 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSuccess (返回) {Array} goods_data 商品数据
     * @apiSuccess (返回) {Array} images_data 商品图片数据
     * @apiSuccess (返回) {Array} sku sku数据
     * @apiSampleRequest /admin/goods/getonegoods
     * @author wujunjie
     * 2019/1/2-16:48
     */
    public function getOneGoods(){
        $id = trim(input("post.id"));
        if (empty($id)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->goods->getOneGoodsImage($id);
        return $res;
    }

    /**
     * @api              {post} / 保存编辑后的商品
     * @apiDescription   saveAddGoods
     * @apiGroup         admin_goods
     * @apiName          saveAddGoods
     * @apiSuccess (返回) {String} code 200:成功 / 3001 保存失败 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiParam (入参) {Number} [id] 商品id 必传
     * @apiParam (入参) {Number} [supplier_id] 供应商id
     * @apiParam (入参) {Number} cate_id 三级分类id
     * @apiParam (入参) {String} goods_name 商品名称
     * @apiParam (入参) {Number} goods_type 商品类型 1普通商品 2 虚拟商品
     * @apiParam (入参) {String} title 主标题
     * @apiParam (入参) {String} subtitle 副标题
     * @apiParam (入参) {String} image 商品标题图
     * @apiParam (入参) {Number} status 上下架状态 1上架 2下架
     * @apiParam (入参) {Array} images 商品图片(一个数组单元是一个json字符串，有几张图片就有几个数组单元,下面有样式)
     * @apiParamExample (images) {Array} 商品图片
     * [
     * {"source_type":1,"image_type":1,"image_path":""},
     *{"source_type":1,"image_type":1,"image_path":""},
     *{"source_type":1,"image_type":1,"image_path":""},
     *{"source_type":1,"image_type":1,"image_path":""},
     * ]
     * @apiParam (images) {Number} source_type 来源 1全部 / 2pc / 3app / 4微信
     * @apiParam (images) {Number} image_type 图片类型 1详情图 / 2轮播图
     * @apiParam (images) {String} image_path 图片内容
     * @apiParam (入参) {Array} skus sku数据 （一个数组单元是一个json字符串）
     * @apiParamExample (skus) {Array} 商品图片
     * [
     * {"stock":1,"market_price":1,"retail_price":"","presell_start_time":"","sku":"{1:1,2:1}"...},
     * {"stock":1,"market_price":1,"retail_price":"","presell_start_time":"","sku":"{1:1,2:1}"...},
     * {"stock":1,"market_price":1,"retail_price":"","presell_start_time":"","sku":"{1:1,2:1}"...},
     * {"stock":1,"market_price":1,"retail_price":"","presell_start_time":"","sku":"{1:1,2:1}"...},
     * ]
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
     * @apiParam (入参) {Array} relation 规格属性 （一个数组单元是一个json字符串）
     * @apiParamExample (relation) {Array} 规格属性数据样式:
     * [
     * {"spec_id":1,"attr_id":1},颜色为红色
     *{"spec_id":1,"attr_id":2}，颜色为白色
     *{"spec_id":2,"attr_id":3}，尺寸为x
     *{"spec_id":2,"attr_id":4}，尺寸为xl
     * ]
     * @apiSampleRequest /admin/goods/saveaddgoods
     * @author wujunjie
     * 2019/1/2-17:02
     */
    public function editGoods(){
        $post = trim(input("post."));
        if (empty($post["id"]) || empty($post)){
            return ['msg'=>'参数有误',"code"=>"3000"];
        }
        $res = $this->app->goods->editGoods($post);
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
    public function delGoods(){
        $id = trim(input("post.id"));
        if (empty($id)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->goods->delGoods($id);
        return $res;
    }
}
