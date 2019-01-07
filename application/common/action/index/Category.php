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

    public function getSecondCate(){
        $where = [["status","=",1],["tier","=",2]];
        $field = "id,pid,type_name";
        $cate = DbGoods::getGoodsClass($field,$where);
//        halt($res);
        if (empty($cate)){
            return ["msg"=>"未获取到数据","code"=>3000];
        }

        return ["code"=>200,"data"=>$cate];
    }
}