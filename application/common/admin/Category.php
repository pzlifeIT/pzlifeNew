<?php
namespace app\common\admin;
use app\common\model\GoodsClass;
use app\common\model\GoodsSpec;
use third\PHPTree;
class Category
{
    /**
     * 获取分类列表
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/24-13:48
     */
    public function getCateList(){
        $cate = GoodsClass::where("status",1)->field("id,pid,type_name,tier")->select()->toArray();
//        halt($cate);
        if (empty($cate)){
            return ["msg"=>"分类数据有误","code"=>3000];
        }
        $tree = new PHPTree($cate);
        $tree->setParam("pk","id");
        $tree->setParam("pid","pid");
        $cate_tree = $tree->listTree();
//        halt($cate_tree);
        return ["code"=>200,"data"=>$cate_tree];
    }

    /**
     * 添加分类页面
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/24-13:56
     */
    public function addCatePage(){
        //获取前两级分类
        $data = GoodsClass::where("tier","<=",2)->where("status",1)->field("id,pid,type_name")->select()->toArray();
        if (empty($data)){
            return ["msg"=>"分类数据为空","code"=>3000];
        }
        $tree = new  PHPTree($data);
        $tree->setParam("pk","id");
        $result = $tree->listTree();
        return ["code"=>200,"data"=>$result];
    }

    /**
     * 保存添加的分类
     * @param $pid 父级分类id
     * @param $type_name 分类名称
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/24-14:21
     */
    public function saveAddCate($pid,$type_name){
        $cate = new GoodsClass();
        //如果pid等于0说明是一级分类
        if ($pid == 0){
            $tier = 1;
        }else{
            //如果pid不是0，那就找pid这个分类的pid是不是0
            //找到当前添加的这个分类的父级的pid
            $data = $cate->where("id",$pid)->field("pid")->find()->toArray();
            if ($data["pid"] == 0){//如果pid等于0那就是二级
                $tier = 2;
            }else{
                $tier = 3;
            }
        }
        $result = $cate->save([
            "pid"=>$pid,
            "type_name"=>$type_name,
            "tier"=>$tier,
            "create_time"=>time()
        ]);
        if (empty($result)){
            return ["msg"=>"保存失败","code"=>"3001"];
        }
        return ["msg"=>"保存成功","code"=>200];
    }

    /**
     * 编辑分类页面
     * @param $id 当前分类id
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/24-14:52
     */
    public function editCatePage($id){
        //修改分类，该分类的父级分类只有两个选择，不能是同级的，三级只能选择顶级和二级，二级只能选择顶级和一级
        $result = GoodsClass::where("id",$id)->field("id,pid,type_name,tier")->find()->toArray();
        if (empty($result)){
            return ["msg"=>"该条数据获取失败","code"=>3000];
        }
        //将要修改的数据的等级存起来
        $level = $result["tier"];
        $cate_data = GoodsClass::where("tier","<=",2)->where("status",1)->field("id,type_name,pid,tier")->select()->toArray();
        if (empty($cate_data)){
            return ["msg"=>"分类数据为空","code"=>3000];
        }
        //循环所有的数据,只有高于当前要修改的分类一级，才能被选中
        foreach ($cate_data as $k=>$v){
            if ($v["tier"] == $level - 1 && $v["tier"] != 0){
                $cate_data[$k]["_disable"] = 1;
            }else{
                $cate_data[$k]["_disable"] = 2;
            }
        }
        $tree = new PHPTree($cate_data);
        $tree->setParam("pk","id");
        $res = $tree->listTree();
        return ["code"=>200,"cate_data"=>$result,"cate_list"=>$res];
    }

    /**
     * 保存编辑后的分类
     * @param $id 分类ID
     * @param $pid 父级ID
     * @param $type_name 分类名称
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/24-16:45
     */
    public function saveEditCate($id,$pid,$type_name){
        $cate = new GoodsClass();
        if ($pid == 0){
            $tier = 1;
        }else{
            $res = $cate->where("id",$pid)->field("pid")->find()->toArray();
            if ($res["pid"] == 0){
                $tier = 2;
            }else{
                $tier = 3;
            }
        }
        $res = $cate->save([
            "pid" => $pid,
            "type_name"=>$type_name,
            "create_time"=>time(),
            "tier"=>$tier
        ],["id"=>$id]);
        if (empty($res)){
            return ['msg'=>"保存失败",'code'=>3001];
        }
        return ["msg"=>"保存成功","code"=>200];
    }

    /**
     * 删除分类
     * @param $id需要删除的数据id
     * @return array
     * @author wujunjie
     * 2018/12/24-17:13
     */
    public function delCategory($id){
        //查找该分类是否有子分类,并且没有删除
        $res = GoodsClass::where("pid",$id)->field("id,tier")->find();
        if ($res){
            return ["msg"=>"该分类有子分类,请先删除子分类","code"=>3003];
        }
        //如果是一个三级分类，还要判断该三级分类下有没有一级属性，如果有一级属性也不能删除
        if ($res["tier"] == 3){
            $res = GoodsSpec::where("cate_id",$res["id"])->field("id")->find();
            if ($res){
                return ["msg"=>"请先解除该分类下的属性关系","code"=>3003];
            }
        }
        $res = GoodsClass::destroy($id);
        if (empty($res)){
            return ["msg"=>"删除失败","code"=>3001];
        }
        return ["msg"=>"删除成功","code"=>200];
    }
}