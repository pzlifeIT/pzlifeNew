<?php
namespace app\common\action\index;


use app\facade\DbGoods;
use third\PHPTree;
class Category{

    //获取一级分类
    public function getFirstCate(){
        $where = [["status","=",1],["tier","=",1]];
        $field = "id,pid,type_name";
        $res = DbGoods::getGoodsClass($field,$where);
        if (empty($res)){
            return ["msg"=>"未获取到一级分类","code"=>3000];
        }
        return ["code"=>200,"data"=>$res];
    }
    //根据传过来的一级分类id找到对应的二级分类
    public function getSecondCate($id){
        $where = [["status","=",1],["pid","=",$id]];
        $field = "id,pid,type_name";
        $cate = DbGoods::getGoodsClass($field,$where);
        if (empty($cate)){
            return ["msg"=>"未获取到数据","code"=>3000];
        }
        //根据二级分类获取对应的三级
        foreach ($cate as $k=>$v){
            $where = [["status","=",1],["pid","=",$v["id"]]];
            $field = "id,pid,type_name";
            $cate[$k]["_child"] = DbGoods::getGoodsClass($field,$where);
        }
        return ["code"=>200,"data"=>$cate];
    }
}