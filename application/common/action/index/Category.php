<?php
namespace app\common\action\index;


use app\facade\DbGoods;
use third\PHPTree;
class Category{

    //获取一级分类
    public function getFirstCate(){
        $where = [["status","=",1]];
        $field = "id,pid,type_name,tier";
        $res = DbGoods::getGoodsClass($field,$where);
        foreach ($res as $k=>$v){
            if ($v["tier"] == 3){
                $where = [["class_id","=",$v["id"]]];
                $field = "id,image_path";
                $res[$k]["image"] = DbGoods::getClassImage($where,$field);
            }

        }
//        halt($res);
        if (empty($res)){
            return ["msg"=>"未获取到一级分类","code"=>3000];
        }
        $tree = new PHPTree($res);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $cate_tree = $tree->listTree();
        return ["code"=>200,"data"=>$cate_tree];
    }

    /**
     * 获取专题
     * @author rzc
     */
    public function getGoodsSubject(){
        $where = [["status","=",1]];
        $field = "id,pid,subject,tier";
        $res = DbGoods::getSubject($where, $field,false,true);
        if (empty($res)) {
            return ['code' => 3000,'msg'=>'未获取到专题' ];
        }
        foreach ($res as $key => $value) {
            if ($value['goods_subject_image']) {
                $res[$key]['goods_subject_image'] = $value['goods_subject_image'][0]['image_path'];
            }else{
                $res[$key]['goods_subject_image'] = '';
            }
            
        }
        $tree = new PHPTree($res);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $cate_tree = $tree->listTree();
        return ["code"=>200,"data"=>$cate_tree];
        
    }

    public function getSubjectDetail(int $subjectId){
        $where       = ['id' => $subjectId];
        $field       = 'id,pid,subject,status,tier,order_by';
        $subjectList = DbGoods::getSubject($where, $field, true, true);
        if (empty($subjectList)) {
            return ['code' => '3000'];
        }
        $subjectList['subject_image'] = $subjectList['goods_subject_image'][0]['image_path'] ?? '';
        $subjectList['subject_share_image'] = $subjectList['goods_subject_image'][0]['share_image_path'] ?? '';
        unset($subjectList['goods_subject_image']);
        return ['code' => '200', 'data' => $subjectList];
    }

    public function getGoodsIntegralSubject(){
        $where = [["status","=",1],['is_integral_show','=','2']];
        $field = "id,pid,subject,tier";
        $res = DbGoods::getSubject($where, $field,false,true);
        if (empty($res)) {
            return ['code' => 3000,'msg'=>'未获取到专题' ];
        }
        foreach ($res as $key => $value) {
            if ($value['goods_subject_image']) {
                $res[$key]['goods_subject_image'] = $value['goods_subject_image'][0]['image_path'];
            }else{
                $res[$key]['goods_subject_image'] = '';
            }
            
        }
        $tree = new PHPTree($res);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $cate_tree = $tree->listTree();
        return ["code"=>200,"data"=>$cate_tree];
        
    }
}