<?php

namespace app\common\action\admin;

use app\common\model\GoodsClass;
use app\common\model\GoodsSpec;
use app\facade\DbGoods;
use third\PHPTree;

class Category {
    /**
     * 获取分类列表
     * @return array
     * @author wujunjie
     * 2018/12/24-13:48
     */
    public function getCateList($type) {
        $field = "id,pid,type_name,tier,status";
        if ($type == 3) {
            $cate = DbGoods::getGoodsClassAll($field);
        }else{
            $cate = DbGoods::getGoodsClassByStatus($field, $type);
        }
        if (empty($cate)) {
            return ["msg" => "分类数据有误", "code" => 3000];
        }
        $tree = new PHPTree($cate);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $cate_tree = $tree->listTree();
        return ["code" => 200, "data" => $cate_tree];
    }

    /**
     * 添加分类页面
     * @return array
     * @author wujunjie
     * 2018/12/24-13:56
     */
    public function addCatePage() {
        $field = "id,pid,type_name,tier";
        //获取前两级分类
//        $data = GoodsClass::where("tier", "<=", 2)->where("status", 1)->field("id,pid,type_name,tier")->select()->toArray();
        $where = [['tier', '<=', 2], ['status', '=', 1],];
        $data  = DbGoods::getGoodsClass($field, $where);
        if (empty($data)) {
            return ["msg" => "分类数据为空", "code" => 3000];
        }
        $tree = new PHPTree($data);
        $tree->setParam("pk", "id");
        $result = $tree->listTree();
        return ["code" => 200, "data" => $result];
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
    public function saveAddCate($pid, $type_name, $status) {
        $cate = new GoodsClass();
        //如果pid等于0说明是一级分类
        if ($pid == 0) {
            $tier = 1;
        } else {
            //如果pid不是0，那就找pid这个分类的pid是不是0
            //找到当前添加的这个分类的父级的pid
            $data = $cate->where("id", $pid)->field("pid")->find()->toArray();
            if ($data["pid"] == 0) {//如果pid等于0那就是二级
                $tier = 2;
            } else {
                $tier = 3;
            }
        }
        $result = $cate->save([
            "pid"         => $pid,
            "type_name"   => $type_name,
            "tier"        => $tier,
            "status"      => $status,
            "create_time" => time()
        ]);
        if (empty($result)) {
            return ["msg" => "保存失败", "code" => "3001"];
        }
        return ["msg" => "保存成功", "code" => 200];
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
    public function editCatePage($id) {
        $result = GoodsClass::where("id", $id)->field("id,pid,type_name,tier,status")->find()->toArray();
        if (empty($result)) {
            return ["msg" => "该条数据获取失败", "code" => 3000];
        }
        //寻找当前分类的父级分类,如果父级分类是0，那就找不到这条数据就是空数组
        if ($result["pid"] != 0) {
            $cate_data = GoodsClass::where("id", $result["pid"])->where("status", 1)->field("id,type_name,pid,tier")->findOrEmpty()->toArray();
            if (empty($cate_data)) {
                return ["msg" => "未获取到数据", "code" => 3000];
            }
        } else {
            $cate_data = [];
        }

        return ["code" => 200, "cate_data" => $result, "cate_list" => $cate_data];
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
    public function saveEditCate($id, $type_name) {
        $cate = new GoodsClass();
        $res  = $cate->save([
            "type_name" => $type_name
        ], ["id" => $id]);
        if (empty($res)) {
            return ['msg' => "保存失败", 'code' => 3001];
        }
        return ["msg" => "保存成功", "code" => 200];
    }

    /**
     * 删除分类
     * @param $id需要删除的数据id
     * @return array
     * @author wujunjie
     * 2018/12/24-17:13
     */
    public function delCategory($id) {
        //查找该分类是否有子分类,并且没有删除
        $res = GoodsClass::where("pid", $id)->field("id,tier")->find();
        if ($res) {
            return ["msg" => "该分类有子分类,请先删除子分类", "code" => 3003];
        }
        //如果是一个三级分类，还要判断该三级分类下有没有一级属性，如果有一级属性也不能删除
        if ($res["tier"] == 3) {
            $res = GoodsSpec::where("cate_id", $res["id"])->field("id")->find();
            if ($res) {
                return ["msg" => "请先解除该分类下的属性关系", "code" => 3003];
            }
        }
        $res = GoodsClass::destroy($id);
        if (empty($res)) {
            return ["msg" => "删除失败", "code" => 3001];
        }
        return ["msg" => "删除成功", "code" => 200];
    }

    //停用分类
    private function stop($id) {
        //查找该分类是否有子分类,并且没有停用
        $res = GoodsClass::where("pid", $id)->where("status", 1)->field("id,tier")->find();
        if ($res) {
            return ["msg" => "该分类有子分类,请先停用子分类", "code" => 3003];
        }
        //如果是一个三级分类，还要判断该三级分类下有没有一级属性，如果有一级属性也不能停用
        if ($res["tier"] == 3) {
            $res = GoodsSpec::where("cate_id", $res["id"])->field("id")->find();
            if ($res) {
                return ["msg" => "请先解除该分类下的属性关系", "code" => 3003];
            }
        }
        $res = (new GoodsClass())->save([
            "status" => 2
        ], ["id" => $id]);
        if (empty($res)) {
            return ["msg" => "停用失败", "code" => 3001];
        }
        return ["msg" => "停用成功", "code" => 200];
    }

    //启用分类
    private function start($id) {
        $res = (new GoodsClass())->save([
            "status" => 1
        ], ["id" => $id]);
        if (empty($res)) {
            return ["msg" => "启用失败", "code" => 3001];
        }
        return ["msg" => "启用成功", "code" => 200];
    }

    /**
     * 外部调用启用/停用分类
     * @param $id
     * @param $type
     * @return bool
     * @author wujunjie
     * 2018/12/28-9:29
     */
    public function stopStart($id, $type) {
        switch ($type) {
            case 1:
                $res = $this->start($id);
                break;
            case 2:
                $res = $this->stop($id);
        }
        return $res;
    }

    /**
     * 展示三级分类
     * @return array
     * @author wujunjie
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 2018/12/25-10:38
     */
    public function getThreeCate() {
        //选择分类
        $cate = GoodsClass::where("tier", 3)->where("status", 1)->field("id,type_name,pid")->select()->toArray();
        if (empty($cate)) {
            return ["msg" => "未获取分类到数据", "code" => 3000];
        }
        return ["code" => 200, "cate" => $cate];
    }
}