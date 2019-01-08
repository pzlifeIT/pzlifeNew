<?php
namespace app\common\action\admin;

use think\Db;
use third\PHPTree;
use app\facade\DbGoods;
class Goods
{
    /**
     * 商品列表
     * @return array
     * @author wujunjie
     * 2018/12/26-10:25
     */
    public function goodsList($page,$pageNum){
        $offset = $pageNum * ($page - 1);
        //查找所有商品数据
        $field = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,status";
        $goods_data = DbGoods::getGoodsList($field,$offset,$pageNum);
        $total = DbGoods::getGoodsListNum();
        if (empty($goods_data)){
            return ["msg"=>"商品数据不存在","code"=>3000];
        }
        foreach($goods_data as $k=>$v){
            //查找供应商
            $whereSupp = [["id","=",$v['supplier_id']]];
            $fieldSupp =  "id,tel,name";
            $supplier = DbGoods::getOneSupplier($whereSupp,$fieldSupp);
            if (empty($supplier)){
                return ["msg"=>"供应商数据空","code"=>3000];
            }
            $goods_data[$k]["supplier"] = $supplier["name"];
            //查找三级分类
            $whereCate = [["id","=",$v["cate_id"]]];
            $fieldCate = "id,pid,type_name";
            $cate = DbGoods::getOneCate($whereCate,$fieldCate);
            if (empty($cate)){
                return ["msg"=>"分类数据空","code"=>3000];
            }
            $goods_data[$k]["cate"] = $cate["type_name"];
        }
        return ["code"=>200,"total"=>$total,"data"=>$goods_data];
    }

    /**
     * 添加商品
     * @param $post
     * @return array
     * @author wujunjie
     * 2019/1/3-9:27
     */
    public function saveAddGoods($post){
        //判断分类，供应商，属性是否存在
        $where = [["id","=",$post["cate_id"]]];
        $field = "id";
        $cate = DbGoods::getOneSpec($where,$field);
        $field = "id";
        $supplier = DbGoods::getSupplierData($field,$post["supplier_id"]);
        if (empty($cate) || empty($supplier)){
            return ["msg"=>"数据不存在","code"=>3000];
        }
        //开启事务
        Db::startTrans();
        try{
            $data = [
                "supplier_id"=>$post["supplier_id"],
                "cate_id"=>$post["cate_id"],
                "goods_name"=>$post["goods_name"],
                "goods_type"=>$post["goods_type"],
                "title"=>$post["title"],
                "subtitle"=>$post["subtitle"],
                "image"=>$post["image"],
                "status"=>$post["status"],
                "create_time"=>$post["create_time"]
            ];
            $goods_id = DbGoods::addGoods($data);

            $images = json_decode($post["images"],true);
            foreach($images as $k=>$v){
                $data = [
                    "goods_id"=>$goods_id,
                    "source_type"=>$v["source_type"],
                    "image_type"=>$v["type"],
                    "image_path"=>$v["path"]
                ];
                DbGoods::addGoodsImage($data);
            }
            //sku有多少条数据取决于sku属性
            if ($post["goods_type"] == 1){
                $skus = json_decode($post["skus"],true);
                foreach ($skus as $k=>$v){
                    $data = [
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
                    ];
                    DbGoods::addGoodsSku($data);
                }

                //直接从前台传过来一个
                $relation = json_decode($post["relation"],true);
                foreach ($relation as $k=>$v){
                    $data = [
                        "goods_id"=>$goods_id,
                        "spec_id"=>$v["spec_id"],
                        "attr_id"=>$v["attr_id"]
                    ];
                    DbGoods::addRelation($data);
                }
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

    /**
     * 获取一条商品数据
     * @param $id
     * @return array
     * @author wujunjie
     * 2019/1/2-16:42
     */
    public function getOneGoods($id){
        //根据商品id找到商品表里面的基本数据
        $where = [["id","=",$id]];
        $field = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where,$field);
        if (empty($goods_data)){
            return ["msg"=>"商品基本数据获取失败","code"=>3000];
        }
        //根据商品id找到商品图片表里面的数据
        $where = [["goods_id","=",$id]];
        $field = "goods_id,source_type,image_type,image_path";
        $images_data = DbGoods::getOneGoodsImage($where,$field);
        if (empty($images_data)){
            return ["msg"=>"商品图片获取失败","code"=>3000];
        }
        if ($goods_data["goods_type"] == 1){
            //根据商品id获取sku表数据
            $where = [["goods_id","=",$id]];
            $field = "goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,integral_price,integral_active,spec,sku_image";
            $sku = DbGoods::getOneGoodsSku($where,$field);
            if (empty($sku)){
                return ["msg"=>"sku数据获取失败","code"=>3000];
            }
        }

        return ["code"=>200,"goods_data"=>$goods_data,"images_data"=>$images_data,"sku"=>$sku];
    }

    /**
     * 编辑商品
     * @param $post
     * @return array
     * @author wujunjie
     * 2019/1/2-18:49
     */
    public function editGoods($post){
        $where = [["id","=",$post["id"]]];
        $field = "id";
        $res = DbGoods::getOneGoods($where,$field);
        $where = [["id","=",$post["cate_id"]]];
        $field = "id";
        $cate = DbGoods::getOneSpec($where,$field);
        $field = "id";
        $supplier = DbGoods::getSupplierData($field,$post["supplier_id"]);
        if (empty($cate) || empty($supplier) || empty($res)){
            return ["msg"=>"数据不存在","code"=>3000];
        }

        //开启事务
        Db::startTrans();
        try{
            $data = [
                "supplier_id"=>$post["supplier_id"],
                "cate_id"=>$post["cate_id"],
                "goods_name"=>$post["goods_name"],
                "goods_type"=>$post["goods_type"],
                "title"=>$post["title"],
                "subtitle"=>$post["subtitle"],
                "image"=>$post["image"],
                "status"=>$post["status"],
                "create_time"=>$post["create_time"]
            ];
            DbGoods::editGoods($data,$post["id"]);
            $images = json_decode($post["images"],true);
            foreach($images as $k=>$v){
                $data = [
                    "source_type"=>$v["source_type"],
                    "image_type"=>$v["type"],
                    "image_path"=>$v["path"]
                ];
                DbGoods::editGoodsImage($data,$post["id"]);
            }
            if ($post["goods_type"] == 1){
                //sku有多少条数据取决于sku属性
                $skus = json_decode($post["skus"],true);
                foreach ($skus as $k=>$v){
                    $data = [
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
                    ];
                    DbGoods::editGoodsSku($data,$post["id"]);
                }
                //直接从前台传过来一个
                $relation = json_decode($post["relation"],true);
                foreach ($relation as $k=>$v){
                    $data = [
                        "spec_id"=>$v["spec_id"],
                        "attr_id"=>$v["attr_id"]
                    ];
                    DbGoods::editGoodsRelation($data,$post["id"]);
                }
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

    public function delGoods($id){
        //开启事务
        Db::startTrans();
        try{
            //删除商品基本数据
            DbGoods::delGoods($id);
            DbGoods::delGoodsImage($id);
            DbGoods::delGoodsSku($id);
            DbGoods::delGoodsRelation($id);
            Db::commit();
            return ["msg"=>"删除成功","code"=>200];
        }catch (\Exception $e){
            Db::rollback();
            return ["msg"=>"删除失败","code"=>3001];
        }

    }

    /**
     * 上下架
     * @param $id
     * @param $type
     * @return array
     * @author wujunjie
     * 2019/1/8-10:13
     */
    public function upDown($id,$type){
        //判断传过来的id是否有效
        $where = [["id","=",$id]];
        $field = "goods_name";
        $res = DbGoods::getOneGoods($where,$field);
        if (empty($res)){
            return ["msg"=>"数据错误","code"=>3001];
        }
        //修改状态
        $data = [
          "status"=>$type
        ];
        $res = DbGoods::editGoods($data,$id);
        if (empty($res)){
            return ['msg'=>"上下架失败","code"=>3002];
        }
        return ["msg"=>'成功',"code"=>200];
    }
}