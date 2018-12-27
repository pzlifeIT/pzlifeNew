<?php
namespace app\common\admin;

use app\common\model\Goods as G;
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

    /**
     * 添加商品页面
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/26-18:03
     */
    public function addGoodsPage(){
        //获取分类数据，获取供应商数据
        $cate = GoodsClass::where("status",1)->field("id,pid,type_name,tier")->select()->toArray();
        if (empty($cate)){
            return ["msg"=>"分类数据未获取到","code"=>3000];
        }
        $tree = new PHPTree($cate);
        $tree->setParam("pk","id");
        $cate = $tree->listTree();
        $supplier = Supplier::field("id,name")->select()->toArray();
        if (empty($supplier)){
            return ["msg"=>"未获取到供应商数据","code"=>3000];
        }
        return ["cate"=>$cate,"supplier"=>$supplier,"code"=>200];
    }

    public function saveAddGoods($post){
        //保存添加的商品数据，需要操作多表，进行存储的时候需要开启事务，有一张表失败就回滚
        //同时需要操作goods表，goods_image表，goods_sku表，goods_relation表商品类目关系表
        //goods表images表直接存sku表需要处理，商品类目表需要处理
        //分成多个方法进行处理数据库存储，在一个事务中调用这些方法
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
            //一张图片对应一条数据
            for($i=0;$i<count($post["image_path"]);$i++){
                (new GoodsImage())->save([
                    "goods_id"=>$goods_id,
                    "source_type"=>$post["source_type"],
                    "image_type"=>$post["image_type"],
                    "image_path"=>$post["image_path"]
                ]);
            }

        }catch (\Exception $e){
            Db::rollback();

        }
    }
}