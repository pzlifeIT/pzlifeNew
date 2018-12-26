<?php
namespace app\common\admin;

use app\common\model\Goods as G;
use app\common\model\Supplier;
use app\common\model\GoodsClass;
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
        $goods_data = (new G())->field("id,supplier_id,cate_id,goods_name,goods_type,title,subtitle")->select()->toArray();
        if (empty($goods_data)){
            return ["msg"=>"商品数据不存在","code"=>3000];
        }
        //找到供应商和三级分类
        foreach($goods_data as $k=>$v){
            $goods_data["$k"]["supplier"] = Supplier::where("id",$v["supplier_id"])->field("id,tel,name")->find()->toArray();
            $goods_data[$k]["cate"] = GoodsClass::where("id",$v["cate_id"])->field("id,pid,type_name")->find()->toArray();
        }
        return ["code"=>200,"data"=>$goods_data];
    }
}