<?php
namespace app\common\action\admin;

use app\common\model\Goods as G;
use app\common\model\GoodsRelation;
use app\common\model\GoodsSku;
use app\common\model\Supplier;
use app\common\model\GoodsClass;
use think\Db;
use third\PHPTree;
use app\common\model\GoodsImage;
class Goods
{
    /**
     * 商品列表
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/26-10:25
     */
    public function goodsList(){
        //查找所有商品数据
        $goods_data = (new G())->field("id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,status")->select()->toArray();
        if (empty($goods_data)){
            return ["msg"=>"商品数据不存在","code"=>3000];
        }
        //找到供应商和三级分类
        foreach($goods_data as $k=>$v){
            $supplier = Supplier::where("id",$v["supplier_id"])->field("id,tel,name")->find()->toArray();
            $goods_data[$k]["supplier"] = $supplier["name"];
            $cate = GoodsClass::where("id",$v["cate_id"])->field("id,pid,type_name")->find()->toArray();
            $goods_data[$k]["cate"] = $cate["type_name"];
        }
        return ["code"=>200,"data"=>$goods_data];
    }

    public function saveAddGoods($post){
        halt($post);
        //开启事务
        Db::startTrans();
        try{
            $g = new G();
            $g->save([
                "supplier_id"=>$post["supplier_id"],
                "cate_id"=>$post["cate_id"],
                "goods_name"=>$post["goods_name"],
                "goods_type"=>$post["goods_type"],
                "title"=>$post["title"],
                "subtitle"=>$post["subtitle"],
                "image"=>$post["image"],
                "status"=>$post["status"],
                "create_time"=>$post["create_time"]
            ]);
            $goods_id = $g->id;
            //一张图片对应一条数据,有多少张图片就存多少条数据
            //图片还分详情图和轮播图
            //将详情图和轮播图组合成一个二维数组,每一个数组单元包含type和path
            //将传过来的图片数据转成二维数组，循环二维数组，有多少存多少
            $images = json_decode($post["images"],true);
            foreach($images as $k=>$v){
                (new GoodsImage())->save([
                    "goods_id"=>$goods_id,
                    "source_type"=>$v["source_type"],
                    "image_type"=>$v["type"],
                    "image_path"=>$v["path"]
                ]);
            }
            //sku有多少条数据取决于sku属性
            $skus = json_decode($post["skus"],true);
            foreach ($skus as $k=>$v){
                (new GoodsSku())->save([
                    "goods_id"=>$goods_id,
                    "stock"=>$v["stock"],
                    "market_price"=>$v["market_price"],
                    "retail_price"=>$v["retail_price"],
                    "presell_start_time"=>$v["presell_start_time"],
                    "presell_end_time"=>$v["presell_end_time"],
                    "presell_price"=>$v["presell_price"],
                    "active_price"=>$v["active_price"],
                    "active_start_time"=>$v["active_start_time"],
                    "active_end_time"=>$v["active_end_time"],
                    "margin_price"=>$v["margin_price"],
                    "integral_price"=>$v["integral_price"],
                    "integral_active"=>$v["integral_active"],
                    "spec"=>$v["spec"],
                    "sku_image"=>$v["sku_image"]
                ]);
            }
            //直接从前台传过来一个
            /**
            [
            {spec_id:1,attr_id:1},颜色为红色
             {spec_id:1,attr_id:2}，颜色为白色
             {spec_id:2,attr_id:3}，尺寸为x
             {spec_id:2,attr_id:4}，尺寸为xl
            ];
             */
            $relation = json_decode($post["relation"],true);
            foreach ($relation as $k=>$v){
                (new GoodsRelation())->save([
                    "goods_id"=>$goods_id,
                    "spec_id"=>$v["spec_id"],
                    "attr_id"=>$v["attr_id"]
                ]);
            }
            //提交事务
            Db::commit();
            return ["msg"=>"添加成功","code"=>200];
        }catch (\Exception $e){
            //回滚事务
            Db::rollback();
            return ["msg"=>"添加失败","code"=>3001];
        }
    }
}