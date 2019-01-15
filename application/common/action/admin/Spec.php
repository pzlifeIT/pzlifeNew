<?php

namespace app\common\action\admin;

use app\common\model\GoodsSpec;
use app\facade\DbGoods;
class Spec
{
    /**
     * 获取属性列表
     * @return array
     * @author wujunjie
     * 2018/12/25-9:59
     */
    public function getSpecList($page,$pageNum){
        $offset = $pageNum * ($page - 1);
        if ($offset < 0){
            return ["3000"];
        }
        //根据一级属性表的cate_id招到三级分类，根据id找到对应的二级属性
//        $spec = GoodsSpec::field("id,cate_id,spe_name")->select()->toArray();
        $where = [];
        $field = "id,cate_id,spe_name";
        $spec = DbGoods::getSpecList($field,$where,$offset,$pageNum);
        $total = DbGoods::getSpecListNum();
        if (empty($spec)){
            return ["msg"=>"未获取到数据","code"=>3000];
        }
        foreach($spec as $k=>$v){
            // 查找分类
            $whereCate = [["id","=",$v["cate_id"]]];
            $fieldCate = "id,type_name";
            $type_name = DbGoods::getOneCate($whereCate,$fieldCate);
            if (!empty($type_name)){
                $spec[$k]['category'] = $type_name["type_name"];
            }

            //查找二级属性
            $whereAttr = [["spec_id","=",$v["id"]]];
            $fieldAttr = "id,spec_id,attr_name";
            $spec[$k]["attr"] = DbGoods::getAttrList($whereAttr,$fieldAttr);
        }
        return ["code"=>200,"total"=>$total,"data"=>$spec];
    }

    /**
     * 添加二级属性页面
     * @return array
     * @author wujunjie
     * 2018/12/25-10:51
     */
    public function addAttrPage(){
        //可选一级属性
        $where = [];
        $field = "id,cate_id,spe_name";
        $spec = DbGoods::getSpecList($field,$where);
        if (empty($spec)){
            return ["msg"=>"未获取到规格数据","code"=>3000];
        }
        foreach ($spec as $k=>$v){
            // 查找分类
            $whereCate = [["id","=",$v["cate_id"]]];
            $fieldCate = "id,type_name";
            $type_name = DbGoods::getOneCate($whereCate,$fieldCate);
            if (!empty($type_name)){
                $spec[$k]['category'] = $type_name["type_name"];
            }
        }
        return ["code"=>200,"spec"=>$spec];
    }

    /**
     * 保存添加的一级属性
     * @param $cate_id
     * @param $spec_name
     * @return array
     * @author wujunjie
     * 2018/12/25-11:26
     */
    private function saveSpec($cate_id,$spec_name){
        //判断传过来的是不是分类id
        $where = [["id","=",$cate_id]];
        $field = "type_name";
        $res = DbGoods::getOneCate($where,$field);
        if (empty($res)){
            return ["msg"=>"分类不存在","code"=>3002];
        }
        $data = [
            "cate_id"=>$cate_id,
            "spe_name"=>$spec_name,
        ];
        $res = DbGoods::addSpec($data);
        if (empty($res)){
            return ["msg"=>"保存失败","code"=>3001];
        }
        return ["code"=>200,"msg"=>"保存成功"];
    }

    /**
     * 保存添加的二级属性
     * @param $spec_id
     * @param $attr_name
     * @return array
     * @author wujunjie
     * 2018/12/25-11:26
     */
    private function saveAttr($spec_id,$attr_name){
        //判断传过来的是不是一级属性id
        $where = [["id","=",$spec_id]];
        $field = "id";
        $res = DbGoods::getOneSpec($where,$field);
        if (empty($res)){
            return ["msg"=>"一级属性不存在","code"=>3002];
        }
        $data = [
            "spec_id"=>$spec_id,
            "attr_name"=>$attr_name,
            "create_time"=>time()
        ];
        $res = DbGoods::addAttr($data);
        if (empty($res)){
            return ["msg"=>"保存失败","code"=>3001];
        }
        return ["code"=>200,"msg"=>"保存成功"];
    }

    /**
     * 外部调用保存一级，二级添加的属性
     * @param $type
     * @param $top_id
     * @param $name
     * @return array
     * @author wujunjie
     * 2018/12/25-11:26
     */
    public function saveSpecAttr($type,$top_id,$name){
        //根据类型判断一下是添加的一级还是二级
        if ($type == 1){
            $res = $this->saveSpec($top_id,$name);
        }elseif ($type == 2){
            $res = $this->saveAttr($top_id,$name);
        }
        return $res;
    }

    /**
     * 编辑一级属性页面
     * @param $id
     * @return array
     * @author wujunjie
     * 2018/12/25-14:27
     */
    private function editSpecPage($id){
        //获取数据
        $field = "id,cate_id,spe_name";
        $where = [["id","=",$id]];
        $data = DbGoods::getOneSpec($where,$field);
        if (empty($data)){
            return ["msg"=>"未获取到该条属性数据","code"=>3000];
        }
        return ["code"=>200,"spec"=>$data];
    }

    /**
     * 编辑二级属性页面
     * @param $id
     * @return array
     * @author wujunjie
     * 2018/12/25-14:44
     */
    private function editAttrPage($id){
        $where  = [["id","=",$id]];
        $field = "id,spec_id,attr_name";
        $data = DbGoods::getOneAttr($where,$field);
        if (empty($data)){
            return ["msg"=>"未获取到该条数据","code"=>3000];
        }
        return ["code"=>200,"attr"=>$data];
    }

    /**
     * 编辑规格属性
     * @param $id
     * @param $type
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/28-9:51
     */
    public function getEditData($id,$type){
        switch ($type){
            case 1:
                $res = $this->editSpecPage($id);
                break;
            case 2:
                $res = $this->editAttrPage($id);
        }
        return $res;
    }
    /**
     * 保存修改后的一级属性
     * @param $id
     * @param $sa_name
     * @return array
     * @author wujunjie
     * 2018/12/25-15:39
     */
    private function saveEditSpec($id,$sa_name){
        //判断传过来的id是不是表中的数据
        $where = [["id","=",$id]];
        $field = "id";
        $res = DbGoods::getOneSpec($where,$field);
        if (empty($res)){
            return ["msg"=>"数据不存在","code"=>3000];
        }
        $data = [
            "spe_name"=>$sa_name
        ];
        $res = DbGoods::editSpec($data,$id);
        if (empty($res)){
            return ["msg"=>"保存失败","code"=>3001];
        }
        return ["msg"=>"保存成功","code"=>200];
    }

    /**
     * 保存修改后的二级属性
     * @param $id
     * @param $top_id
     * @param $sa_name
     * @return array
     * @author wujunjie
     * 2018/12/25-15:39
     */
    private function saveEditAttr($id,$sa_name){
        //判断传过来的id是不是表中的数据
        $where = [["id","=",$id]];
        $field = "id";
        $res = DbGoods::getOneAttr($where,$field);
        if (empty($res)){
            return ["msg"=>"数据不存在","code"=>3000];
        }
        $data = [
            "attr_name"=>$sa_name
        ];
        $res = DbGoods::editAttr($data,$id);
        if (empty($res)){
            return ["msg"=>"保存失败","code"=>3001];
        }
        return ["msg"=>"保存成功","code"=>200];
    }

    /**
     * 外部调用保存修改的属性
     * @param $type
     * @param $id
     * @param $sa_name
     * @return array
     * @author wujunjie
     * 2018/12/25-15:40
     */
    public function saveEditSpecAttr($type,$id,$sa_name){
        switch ($type){
            case 1:
               $res = $this->saveEditSpec($id,$sa_name);
               break;
            case 2:
                $res = $this->saveEditAttr($id,$sa_name);
                break;
        }
        return $res;
    }

    /**
     * 删除一级属性
     * @param $id
     * @return array
     * @author wujunjie
     * 2018/12/25-16:23
     */
    private function delSpec($id){
        //删除一级属性的时候需要判断有没有二级属性
        $where = [["spec_id","=",$id]];
        $field = "id";
        $res = DbGoods::getOneAttr($where,$field);
        if ($res){
            return ["msg"=>"请先删除二级属性","code"=>3003];
        }
        $res = DbGoods::delSpec($id);
        if (empty($res)){
            return ["msg"=>"删除失败","code"=>3001];
        }
        return ["msg"=>"删除成功","code"=>200];
    }

    /**
     * 删除二级属性
     * @param $id
     * @return array
     * @author wujunjie
     * 2018/12/25-16:23
     */
    private function delAttr($id){
        $res = DbGoods::delAttr($id);
        if (empty($res)){
            return ["msg"=>"删除失败","code"=>3001];
        }
        return ["msg"=>"删除成功","code"=>200];
    }

    /**
     * 外部调用删除属性
     * @param $type
     * @param $id
     * @author wujunjie
     * 2018/12/25-16:23
     */
    public function delSpecAttr($type,$id){
        switch ($type){
            case 1:
                $res = $this->delSpec($id);
                break;
            case 2:
                $res = $this->delAttr($id);
                break;
        }
        return $res;
    }

    /**
     * 根据一级规格id获取二级属性
     * @param $spec_id
     * @author wujunjie
     * 2019/1/7-18:00
     */
    public function getAttr($spec_id){
        //判断传过来的id是否有效
        $where = [["id","=",$spec_id]];
        $field = "spe_name";
        $spec = DbGoods::getOneSpec($where,$field);
        if (empty($spec)){
            return ["msg"=>"数据不存在","code"=>3000];
        }
        $where = [["spec_id","=",$spec_id]];
        $field = "id,attr_name,spec_id";
        $res = DbGoods::getAttrList($where,$field);
        if (empty($res)){
            return ["msg"=>"二级属性获取失败","code"=>3000];
        }
        return ["code"=>200,"attr"=>$res,"spec_name"=>$spec["spe_name"]];
    }

    /**
     * 获取一级规格二级属性
     * @param $cate_id
     * @return array
     * @author wujunjie
     * 2019/1/8-15:25
     */
    public function getSpecAttr($cate_id){
       $where = [["cate_id","=",$cate_id]];
       $field = "id,spe_name";
       $spec = DbGoods::getSpecList($field,$where);
       if (empty($spec)){
           return ["msg"=>"未获取到一级规格","code"=>3000];
       }
       foreach($spec as $k=>$v){
           $where = [["spec_id","=",$v["id"]]];
           $field = "id,attr_name";
           $spec[$k]["attr"] = DbGoods::getAttrList($where,$field);
       }
       return ["code"=>200,"data"=>$spec];
    }
}
