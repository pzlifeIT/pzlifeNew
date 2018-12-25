<?php

namespace app\common\admin;

use app\common\model\GoodsSpec;
use app\common\model\GoodsClass;
use app\common\model\GoodsAttr;
class Spec
{
    /**
     * 获取属性列表
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/25-9:59
     */
    public function getSpecList(){
        //根据一级属性表的cate_id招到三级分类，根据id找到对应的二级属性
        $spec = GoodsSpec::where("delete_time",0)->field("id,cate_id,spe_name")->select()->toArray();
        if (empty($spec)){
            return ["msg"=>"未获取到数据","code"=>3000];
        }
        foreach($spec as $k=>$v){
            $spec[$k]['category'] = GoodsClass::where("id",$v["cate_id"])->where("delete_time",0)->field("id,type_name")->find()->toArray();
            $spec[$k]["attr"] = GoodsAttr::where('spec_id',$v['id'])->where("delete_time",0)->field("id,spec_id,attr_name")->select()->toArray();
        }
        return ["code"=>200,"data"=>$spec];
    }

    /**
     * 添加一级属性页面
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/25-10:38
     */
    public function addSpecPage(){
       //选择分类
        $cate = GoodsClass::where("tier",3)->where("delete_time",0)->field("id,type_name,pid")->select()->toArray();
        if (empty($cate)){
            return ["msg"=>"未获取分类到数据","code"=>3000];
        }
        return ["code"=>200,"cate"=>$cate];
    }

    /**
     * 添加二级属性页面
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/25-10:51
     */
    public function addAttrPage(){
        //可选一级属性
        $spec = GoodsSpec::where("delete_time",0)->field("id,cate_id,spe_name")->select()->toArray();
        if (empty($spec)){
            return ["msg"=>"未获取到规格数据","code"=>3000];
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
        $spec = new GoodsSpec();
        $res = $spec->save([
            "cate_id"=>$cate_id,
            "spe_name"=>$spec_name,
            "create_time"=>time()
        ]);
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
        $attr = new GoodsAttr();
        $res = $attr->save([
            "spec_id"=>$spec_id,
            "attr_name"=>$attr_name,
            "create_time"=>time()
        ]);
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/25-14:27
     */
    public function editSpecPage($id){
        //获取数据
        $data = GoodsSpec::where("id",$id)->field("id,cate_id,spe_name")->find()->toArray();
        if (empty($data)){
            return ["msg"=>"未获取到该条属性数据","code"=>3000];
        }
        $cate = GoodsClass::where("tier",3)->where("status",1)->where("delete_time",0)->field("id,pid,type_name")->select()->toArray();
        if (empty($cate)){
            return ["msg"=>"未获取到分类数据","code"=>3000];
        }
        return ["code"=>200,"spec"=>$data,"cate"=>$cate];
    }

    /**
     * 编辑二级属性页面
     * @param $id
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/25-14:44
     */
    public function editAttrPage($id){
        $data = GoodsAttr::where("id",$id)->field("id,spec_id,attr_name")->find()->toArray();
        if (empty($data)){
            return ["msg"=>"未获取到该条数据","code"=>3000];
        }
        $spec = GoodsSpec::where("delete_time",0)->field("id,cate_id,spe_name")->select()->toArray();
        if (empty($spec)){
            return ["msg"=>"未获取到一级属性数据","code"=>3000];
        }
        return ["code"=>200,"attr"=>$data,"spec"=>$spec];
    }

    /**
     * 保存修改后的一级属性
     * @param $id
     * @param $top_id
     * @param $sa_name
     * @return array
     * @author wujunjie
     * 2018/12/25-15:39
     */
    private function saveEditSpec($id,$top_id,$sa_name){
        $res = (new GoodsSpec())->save([
            "cate_id"=>$top_id,
            "spe_name"=>$sa_name
        ],["id"=>$id]);
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
    private function saveEditAttr($id,$top_id,$sa_name){
        $res = (new GoodsAttr())->save([
            "spec_id"=>$top_id,
            "attr_name"=>$sa_name
        ],["id"=>$id]);
        if (empty($res)){
            return ["msg"=>"保存失败","code"=>3001];
        }
        return ["msg"=>"保存成功","code"=>200];
    }

    /**
     * 外部调用保存修改的属性
     * @param $type
     * @param $id
     * @param $top_id
     * @param $sa_name
     * @return array
     * @author wujunjie
     * 2018/12/25-15:40
     */
    public function saveEditSpecAttr($type,$id,$top_id,$sa_name){
        switch ($type){
            case 1:
               $res = $this->saveEditSpec($id,$top_id,$sa_name);
               break;
            case 2:
                $res = $this->saveEditAttr($id,$top_id,$sa_name);
                break;
        }
        return $res;
    }

    /**
     * 删除一级属性
     * @param $id
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/25-16:23
     */
    private function delSpec($id){
        //删除一级属性的时候需要判断有没有二级属性
        $res = GoodsAttr::where("spec_id",$id)->field("id")->find()->toArray();
        if ($res){
            return ["msg"=>"请先删除二级属性","code"=>3003];
        }
        $res = (new GoodsSpec())->save([
            "delete_time"=>time()
        ],["id"=>$id]);
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
        $res = (new GoodsAttr())->save([
            "delete_time"=>time()
        ],["id"=>$id]);
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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
    }
}
