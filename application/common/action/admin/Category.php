<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbImage;
use think\Db;
use third\PHPTree;
use Config;

class Category {
    public function allCateList(int $status) {
        $where = [];
        if ($status == 3) {
            $where = ['status' => $status];
        }
        $field = "id,pid,tier,type_name,status";
        $cate  = DbGoods::getGoodsClass($field, $where);
        if (empty($cate)) {
            return ['code' => 3000];
        }
        $tree = new PHPTree($cate);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $cate_tree = $tree->listTree();
        return ['code' => '200', 'data' => $cate_tree];
    }

    /**
     * 获取分类列表
     * @param int $type
     * @param int $pid
     * @param int $page
     * @param int $pageNum
     * @return array
     * @author wujunjie
     * 2018/12/24-13:48
     */
    public function getCateList(int $type, int $pid, int $page, int $pageNum) {
        $tier      = 1;//默认一级
        $type_name = '';
        if ($pid !== 0) {
            $res       = DbGoods::getTier($pid);
            $tier      = $res['tier'] + 1;
            $type_name = $res['type_name'];
        }
        $field = "id,status,type_name,create_time";
        if ($type == 3) {
            $where = ["pid" => $pid];
        } else {
            $where = ['pid' => $pid, 'status' => $type];
        }
        $offset = $offset = $pageNum * ($page - 1);;
        $cate  = DbGoods::getGoodsClass($field, $where, $offset, $pageNum);
        $total = DbGoods::getGoodsClassAllNum($where);
        if (empty($cate)) {
            return ["msg" => "分类数据有误", "code" => 3000];
        }
//        $tree = new PHPTree($cate);
//        $tree->setParam("pk", "id");
//        $tree->setParam("pid", "pid");
//        $cate_tree = $tree->listTree();
        return ["code" => 200, 'tier' => $tier, 'type_name' => $type_name, 'total' => $total, "data" => $cate];
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
        $data  = DbGoods::getGoodsClass($field, $where, "select");
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
     * @param $status 1.启用  2.停用
     * @param $image 图片
     * @return array
     * @author wujunjie
     * 2018/12/24-14:21
     */
    public function saveAddCate($pid, $type_name, $status, $image) {
        //保存提交的分类之前需要判断是否已经存在该名称,不能是停用的,删除的
        $where = [["type_name", "=", $type_name]];
        $field = "id,type_name";
        $res   = DbGoods::getOneCate($where, $field);
        if ($res) {
            return ["msg" => "该分类名称已经存在", "code" => 3005];
        }
        //如果pid等于0说明是一级分类
        if ($pid == 0) {
            $tier = 1;
        } else {
            //如果pid不是0，那就找pid这个分类的pid是不是0
            //找到当前添加的这个分类的父级的pid
            $field = "pid";
            $where = [["id", "=", $pid]];
            $data  = DbGoods::getOneCate($where, $field);
            if ($data["pid"] == 0) {//如果pid等于0那就是二级
                $tier = 2;
            } else {
                $tier = 3;
            }
        }
        $data     = [
            "pid"       => $pid,
            "type_name" => $type_name,
            "tier"      => $tier,
            "status"    => $status,
        ];
        $logImage = [];
        $image    = filtraImage(Config::get('qiniu.domain'), $image);
        if ($image) {
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3003'];//图片没有上传过
            }
        }
        Db::startTrans();
        try {
            $newClassId = DbGoods::addCate($data);
            if (!empty($logImage)) {
                DbGoods::addClassImage(['class_id' => $newClassId, 'image_path' => $image]);//添加分类图片
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            Db::commit();
            return ["msg" => "保存成功", "code" => 200];
        } catch (\Exception $e) {
            Db::rollback();
            return ["msg" => "保存失败", "code" => "3001"];
        }
    }

    /**
     * 编辑分类页面
     * @param $id 当前分类id
     * @return array
     * @author wujunjie
     * 2018/12/24-14:52
     */
    public function editCatePage($id) {
        $field  = "id,pid,type_name,tier,status";
        $where  = [["id", "=", $id]];
        $result = DbGoods::getOneCate($where, $field);
        if (empty($result)) {
            return ["msg" => "该条数据获取失败", "code" => 3000];
        }
        //寻找当前分类的父级分类,如果父级分类是0，那就找不到这条数据就是空数组
        if ($result["pid"] != 0) {
//            $cate_data = GoodsClass::where("id", $result["pid"])->where("status", 1)->field("id,type_name,pid,tier")->findOrEmpty()->toArray();
            $field     = "id,type_name,pid,tier";
            $where     = [["id", "=", $result["pid"]], ["status", "=", 1]];
            $cate_data = DbGoods::getOneCate($where, $field);
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
     * @param $type_name 分类名称
     * @param $status
     * @param $image 分类图片
     * @return array
     * @author wujunjie
     * 2018/12/24-16:45
     */
    public function saveEditCate($id, $type_name, $status, $image) {
        //保存提交的分类之前需要判断是否已经存在该名称,删除的
        $cateRes = DbGoods::getOneCate(['id' => $id], 'id');
        if (empty($cateRes)) {
            return ['code' => '3004'];//分类id不存在
        }
        $where = [["type_name", "=", $type_name], ['id', "<>", $id]];
        $field = "id,type_name";
        $res   = DbGoods::getOneCate($where, $field);
        if ($res) {
            return ["msg" => "该分类名称已经存在", "code" => '3005'];
        }
        /* 初始化数组 */
        $oldLogImage = [];
        $logImage    = [];
        if (!empty($image)) {//提交了图片
            $image    = filtraImage(Config::get('qiniu.domain'), $image);
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3006'];//图片没有上传过
            }
            $oldClassImage = DbGoods::getClassImage(['class_id' => $id], 'image_path');
            $oldImage      = $oldClassImage['image_path'];
            $oldImage      = filtraImage(Config::get('qiniu.domain'), $oldImage);
            if (!empty($oldImage)) {//之前有图片
                if (stripos($oldImage, 'http') === false) {//新版本图片
                    $oldLogImage = DbImage::getLogImage($oldImage, 1);//之前在使用的图片日志
                }
            }
//            $data['image'] = $image;
        }
        $data['type_name'] = $type_name;
        $data['status']    = $status;
        Db::startTrans();
        try {
            DbGoods::editCate($data, $id);
            if (!empty($logImage)) {
                DbGoods::updateClassImage(['image_path' => $image], ['class_id' => $id]);//更新分类图片
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            if (!empty($oldLogImage)) {
                DbImage::updateLogImageStatus($oldLogImage, 3);//更新状态为弃用
            }
            Db::commit();
            return ['code' => '200', 'msg' => '更新成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3001', 'msg' => '更新失败'];
        }
    }

    /**
     * 删除分类
     * @param $id需要删除的数据id
     * @return array
     * @author wujunjie
     * 2018/12/24-17:13
     */
//    public function delCategory($id) {
//        //查找该分类是否有子分类,并且没有删除
//        $where = [["pid", "=", $id]];
//        $field = "id,tier";
//        $res   = DbGoods::getOneCate($where, $field);
//        if ($res) {
//            return ["msg" => "该分类有子分类,请先删除子分类", "code" => 3003];
//        }
//        //如果是一个三级分类，还要判断该三级分类下有没有一级属性，如果有一级属性也不能删除
//        //判断当前分类是不是三级分类
//        $where = [["id", "=", $id]];
//        $field = "id,tier";
//        $res   = DbGoods::getOneCate($where, $field);
//        if ($res["tier"] == 3) {
////            $res = GoodsSpec::where("cate_id", $res["id"])->field("id")->find();
//            $where = [["cate_id", "=", $res["id"]]];
//            $field = "id";
//            $res   = DbGoods::getOneSpec($where, $field);
//            if ($res) {
//                return ["msg" => "请先解除该分类下的属性关系", "code" => 3003];
//            }
//        }
//        $res = DbGoods::delCate($id);
//        if (empty($res)) {
//            return ["msg" => "删除失败", "code" => 3001];
//        }
//        return ["msg" => "删除成功", "code" => 200];
//    }

    //停用分类
    private function stop($id) {
        //查找该分类是否有子分类,并且没有停用
        $where = [["pid", "=", $id], ["status", "=", 1]];
        $field = "id,tier";
        $res   = DbGoods::getOneCate($where, $field);
        if ($res) {
            return ["msg" => "该分类有子分类,请先停用子分类", "code" => 3003];
        }
        //如果是一个三级分类，还要判断该三级分类下有没有一级属性，如果有一级属性也不能停用
        //判断该分类是不是三级分类
        $where = [["id", "=", $id]];
        $field = "id,tier";
        $res   = DbGoods::getOneCate($where, $field);
        if ($res["tier"] == 3) {
            $where = [["cate_id", "=", $res["id"]]];
            $field = "id";
            $res   = DbGoods::getOneSpec($where, $field);
            if ($res) {
                return ["msg" => "请先解除该分类下的属性关系", "code" => 3003];
            }
        }
        $data = [
            "status" => 2
        ];
        $res  = DbGoods::editCate($data, $id);
        if (empty($res)) {
            return ["msg" => "停用失败", "code" => 3001];
        }
        return ["msg" => "停用成功", "code" => 200];
    }

    //启用分类
    private function start($id) {
        $data = [
            "status" => 1
        ];
        $res  = DbGoods::editCate($data, $id);
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
     * 2018/12/25-10:38
     */
    public function getThreeCate() {
        //选择分类
//        $cate = GoodsClass::where("tier", 3)->where("status", 1)->field("id,type_name,pid")->select()->toArray();
        $field = "id,type_name,pid";
        $where = [["tier", "=", 3], ["status", "=", 1]];
        $cate  = DbGoods::getGoodsClass($field, $where);
        if (empty($cate)) {
            return ["msg" => "未获取分类到数据", "code" => 3000];
        }
        return ["code" => 200, "cate" => $cate];
    }
}