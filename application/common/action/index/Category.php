<?php
namespace app\common\action\index;


use app\facade\DbGoods;
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
}