<?php

namespace app\common\action\admin;

use app\common\model\GoodsSubject;
use app\facade\DbGoods;
use app\facade\DbImage;
use think\Db;
use Config;
use third\PHPTree;

class Subject extends CommonIndex
{
    /**
     * 添加商品专题
     * @param $pid
     * @param $status
     * @param $subject
     * @param $image
     * @return array
     * @author zyr
     */
    public function addSubject(int $pid, int $status, $subject, $image, $share_image, int $is_integral_show)
    {
        $tier = 1;
        if (!empty($pid)) { //pid不为0
            $parentSubject = DbGoods::getSubject(['id' => $pid], 'id,pid', true); //获取上级专题
            if (empty($parentSubject)) {
                return ['code' => '3004']; //pid查不到上级专题
            }
            $tier = 2;
            if ($parentSubject['pid'] != 0) {
                $tier = 3;
            }
        }
        $subjectIsset = DbGoods::getSubject(['subject' => $subject, 'tier' => $tier], 'id', true);
        if (!empty($subjectIsset)) {
            return ['code' => '3005']; //专题名已存在
        }
        $data     = [
            "pid"     => $pid,
            "subject" => $subject,
            "tier"    => $tier,
            "status"  => $status,
            "is_integral_show"  => $is_integral_show,
        ];
        $logImage = [];
        if (!empty($image)) {
            $image    = filtraImage(Config::get('qiniu.domain'), $image);
            $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
            if (empty($logImage)) { //图片不存在
                return ['code' => '3006']; //图片没有上传过
            }
        }
        $logShareImage = [];
        if (!empty($share_image)) {
            $share_image = filtraImage(Config::get('qiniu.domain'), $share_image);
            $logShareImage = DbImage::getLogImage($share_image, 2); //判断时候有未完成的图片
            if (empty($logShareImage)) { //图片不存在
                return ['code' => '3006']; //图片没有上传过
            }
        }
        Db::startTrans();
        try {
            $subjectId = DbGoods::addSubject($data);
            if (!empty($logImage)) {
                $addimage['subject_id'] = $subjectId;
                $addimage['image_path'] = $image;
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            }
            if (!empty($logShareImage)) {
                $addimage['share_image_path'] = $share_image;
                DbImage::updateLogImageStatus($logShareImage, 1); //更新状态为已完成
            }
            if (!empty($addimage)) {
                DbGoods::addSubjectImage($addimage); //添加专题图片
            }
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3007"]; //保存失败
        }
    }

    /**
     * 编辑商品专题
     * @param $id
     * @param $status
     * @param $subject
     * @param $image
     * @param $orderBy
     * @return array
     */
    public function editSubject(int $id, int $status = 0, $subject = '', $image = '', $orderBy = 0, $share_image = '', int $is_integral_show = 0)
    {
        if (empty($subject) && empty($status) && empty($image) && empty($orderBy)) {
            return ['code' => '3007'];
        }
        $subjectRow = DbGoods::getSubject(['id' => $id], 'id,pid,tier', true); //获取上级专题
        if (empty($subjectRow)) {
            return ['code' => '3004']; //专题不存在
        }
        if (!empty($subject)) { //专题名称不为空
            $subjectIsset = DbGoods::getSubject([['subject', '=', $subject], ['id', '<>', $id], ['tier', '=', $subjectRow['tier']]], 'id', true);
            if (!empty($subjectIsset)) {
                return ['code' => '3005']; //专题名已存在
            }
            $data['subject'] = $subject;
        }
        if (!empty($status)) {
            $data['status'] = $status;
        }
        if (!empty($orderBy)) {
            $data['order_by'] = $orderBy;
        }
        if (!empty($is_integral_show)) {
            $data['is_integral_show'] = $is_integral_show;
        }
        $logImage        = [];
        $oldLogImage     = [];
        $oldSubjectImage = [];
        if (!empty($image)) {
            $image    = filtraImage(Config::get('qiniu.domain'), $image);
            $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
            if (empty($logImage)) { //图片不存在
                return ['code' => '3006']; //图片没有上传过
            }
            $oldSubjectImage = DbGoods::getSubjectImage(['subject_id' => $id], 'id,image_path', true);
            if (!empty($oldSubjectImage)) { //之前有图片
                $oldImage = $oldSubjectImage['image_path'];
                $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);
                //                if (stripos($oldImage, 'http') === false) {//新版本图片
                $oldLogImage = DbImage::getLogImage($oldImage, 1); //之前在使用的图片日志
                //                }
            }
        }
        $logShareImage        = [];
        $oldLogShareImage     = [];
        $oldSubjectShareImage = [];
        if (!empty($share_image)) {
            $share_image    = filtraImage(Config::get('qiniu.domain'), $share_image);
            $logShareImage = DbImage::getLogImage($share_image, 2); //判断时候有未完成的图片
            if (empty($logShareImage)) { //图片不存在
                return ['code' => '3006']; //图片没有上传过
            }
            $oldSubjectShareImage = DbGoods::getSubjectImage(['subject_id' => $id], 'id,image_path,share_image_path', true);
            if (!empty($oldSubjectShareImage)) { //之前有图片
                $oldShareImage = $oldSubjectShareImage['image_path'];
                $oldShareImage = filtraImage(Config::get('qiniu.domain'), $oldShareImage);
                //                if (stripos($oldImage, 'http') === false) {//新版本图片
                $oldLogShareImage = DbImage::getLogImage($oldShareImage, 1); //之前在使用的图片日志
                //                }
            }
        }
        Db::startTrans();
        try {
            $flag = false;
            if (!empty($data)) {
                $flag = true;
                DbGoods::updateSubject($data, $id);
            }
            if (!empty($logImage)) {
                $flag = true;
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
                if (!empty($oldSubjectImage)) {
                    DbGoods::updateSubjectImage(['image_path' => $image], $oldSubjectImage['id']); //修改专题图片
                } else {
                    DbGoods::addSubjectImage(['subject_id' => $id, 'image_path' => $image]); //添加分类图片
                }
            }
            if (!empty($oldLogImage)) {
                DbImage::updateLogImageStatus($oldLogImage, 3); //更新状态为弃用
            }
            if (!empty($logShareImage)) {
                $flag = true;
                DbImage::updateLogImageStatus($logShareImage, 1); //更新状态为已完成
                if (!empty($oldSubjectShareImage)) {
                    DbGoods::updateSubjectImage(['share_image_path' => $share_image], $oldSubjectShareImage['id']); //修改专题图片
                } else {
                    DbGoods::addSubjectImage(['subject_id' => $id, 'share_image_path' => $share_image]); //添加分类图片
                }
            }
            if (!empty($oldLogShareImage)) {
                DbImage::updateLogImageStatus($oldLogShareImage, 3); //更新状态为弃用
            }
            if ($flag === false) {
                Db::rollback();
                return ['code' => '3008'];
            }
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ["code" => "3008"]; //保存失败
        }
    }

    /**
     * 所有专题
     * @param $stype
     * @return array
     * @author zyr
     */
    public function getAllSubject(int $stype)
    {
        $where       = [];
        $field       = 'id,pid,subject,status,tier,order_by,is_integral_show';
        $selectImage = true;
        if ($stype == 2) {
            $field       = 'id,pid,subject';
            $where[]     = [['tier', '<>', 3]];
            $selectImage = false;
        }
        $subjectList = DbGoods::getSubject($where, $field, false, $selectImage);
        if (empty($subjectList)) {
            return ['code' => '3000'];
        }
        foreach ($subjectList as $k => $val) {
            if (!isset($val['goods_subject_image'])) {
                break;
            }
            $subjectImage = $val['goods_subject_image'][0]['image_path'] ?? '';
            $subjectShareImage = $val['goods_subject_image'][0]['share_image_path'] ?? '';
            unset($subjectList[$k]['goods_subject_image']);
            $subjectList[$k]['subject_image'] = $subjectImage;
            $subjectList[$k]['subject_share_image'] = $subjectShareImage;
        }
        $tree = new PHPTree($subjectList);
        $tree->setParam("pk", "id");
        $tree->setParam("pid", "pid");
        $subject_tree = $tree->listTree();
        return ['code' => '200', 'data' => $subject_tree];
    }

    /**
     * 建立商品和专题关系
     * @param int $goodsId
     * @param int $subjectId
     * @return array
     * @author zyr
     */
    public function subjectGoodsAssoc(int $goodsId, int $subjectId)
    {
        $goodsRow = DbGoods::getOneGoods(['id' => $goodsId], 'id');
        if (empty($goodsRow)) {
            return ['code' => '3003']; //商品不存在
        }
        $subjectRow = DbGoods::getSubject(['id' => $subjectId, 'tier' => 3], 'id', true);
        if (empty($subjectRow)) {
            return ['code' => '3004']; //专题不存在
        }
        $subjectRelation = DbGoods::getSubjectRelation(['goods_id' => $goodsId, 'subject_id' => $subjectId], 'id', true);
        if (!empty($subjectRelation)) {
            return ['code' => '3005']; //已经关联
        }
        $data = [
            'goods_id'   => $goodsId,
            'subject_id' => $subjectId,
        ];
        $id   = DbGoods::addSubjectRelation($data);
        if ($id) {
            return ['code' => '200'];
        }
        return ['code' => '3006'];
    }

    /**
     * 获取商品专题
     * @param $goodsId
     * @param $stype
     * @return array
     * @author zyr
     */
    public function getGoodsSubject($goodsId, $stype)
    {
        $goodsRow = DbGoods::getOneGoods(['id' => $goodsId], 'id');
        if (empty($goodsRow)) {
            return ['code' => '3003']; //商品不存在
        }
        $subjectRelationList = DbGoods::getSubjectRelation([['goods_id', '=', $goodsId]], 'subject_id');
        if (empty($subjectRelationList) && $stype === 1) {
            return ['code' => '3000'];
        }
        $where = [['tier', '=', 3]];
        if ($stype === 2) {
            if (!empty($subjectRelationList)) {
                $subjectIdList = array_column($subjectRelationList, 'subject_id');
                array_push($where, ['id', 'not in', $subjectIdList]);
            }
            $subjectList3 = DbGoods::getSubject($where, 'id,pid,subject'); //三级
            if (empty($subjectList3)) {
                return ['code' => '3000'];
            }
            $subjectList2 = DbGoods::getSubject([['id', 'in', array_unique(array_column($subjectList3, 'pid'))]], 'id,pid,subject'); //二级
            $subjectList1 = DbGoods::getSubject([['id', 'in', array_unique(array_column($subjectList2, 'pid'))]], 'id,pid,subject'); //一级
            $subjectList  = array_merge($subjectList1, $subjectList2, $subjectList3);
            $tree         = new PHPTree($subjectList);
            $tree->setParam("pk", "id");
            $tree->setParam("pid", "pid");
            $subjectList = $tree->listTree();
        } else {
            $subjectIdList = array_column($subjectRelationList, 'subject_id');
            array_push($where, ['id', 'in', $subjectIdList]);
            $subjectList3 = DbGoods::getSubject($where, 'id,pid,subject'); //三级
            if (empty($subjectList3)) {
                return ['code' => '3000'];
            }
            $subjectList2 = DbGoods::getSubject([['id', 'in', array_unique(array_column($subjectList3, 'pid'))]], 'id,pid,subject'); //二级
            $subjectList1 = DbGoods::getSubject([['id', 'in', array_unique(array_column($subjectList2, 'pid'))]], 'id,pid,subject'); //一级
            $subjectList  = [];
            foreach ($subjectList3 as $s3) {
                foreach ($subjectList2 as $s2) {
                    if ($s3['pid'] == $s2['id']) {
                        $s3['subject_tier2'] = $s2['subject'];
                        foreach ($subjectList1 as $s1) {
                            if ($s2['pid'] == $s1['id']) {
                                $s3['subject_tier1'] = $s1['subject'];
                            }
                        }
                    }
                }
                array_push($subjectList, $s3);
            }
        }

        return ['code' => '200', 'data' => $subjectList];
    }

    public function getSubjectDetail(int $subjectId)
    {
        $where       = ['id' => $subjectId];
        $field       = 'id,pid,subject,status,tier,order_by,is_integral_show';
        $subjectList = DbGoods::getSubject($where, $field, true, true);
        // print_r($subjectList);die;
        if (empty($subjectList)) {
            return ['code' => '3000'];
        }
        $subjectList['subject_image'] = $subjectList['goods_subject_image'][0]['image_path'] ?? '';
        $subjectList['subject_share_image'] = $subjectList['goods_subject_image'][0]['share_image_path'] ?? '';
        unset($subjectList['goods_subject_image']);
        return ['code' => '200', 'data' => $subjectList];
    }

    /**
     * 删除专题
     * @param int $subjectId
     * @return array
     * @author zyr
     */
    public function delGoodsSubject(int $subjectId)
    {
        $getSubject = DbGoods::getSubject(['id' => $subjectId], 'id', true);
        if (empty($getSubject)) {
            return ['code' => '3002']; //专题不存在
        }
        $subjectRelationId  = [];
        $getSubjectRelation = DbGoods::getSubjectRelation(['subject_id' => $subjectId], 'id');
        if (!empty($getSubjectRelation)) {
            $subjectRelationId = array_column($getSubjectRelation, 'id');
        }
        Db::startTrans();
        try {
            $res1 = true;
            if (!empty($subjectRelationId)) {
                $res1 = DbGoods::delSubjectRelation($subjectRelationId);
            }
            $res2 = DbGoods::delSubject($subjectId);
            if ($res1 && $res2) {
                Db::commit();
                return ["code" => '200'];
            }
            Db::rollback();
            return ['code' => '3003'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3003"]; //保存失败
        }
    }

    /**
     * 取消专题商品的关联
     * @param int $goodsId
     * @param int $subjectId
     * @return array
     * @author zyr
     */
    public function delGoodsSubjectAssoc(int $goodsId, int $subjectId)
    {
        $subjectRelation = DbGoods::getSubjectRelation(['goods_id' => $goodsId, 'subject_id' => $subjectId], 'id', true);
        if (empty($subjectRelation)) {
            return ['code' => '3003']; //没有关联无法删除
        }
        $subjectRelationId = $subjectRelation['id'];
        $res               = DbGoods::delSubjectRelation($subjectRelationId);
        if ($res) {
            return ['code' => '200'];
        }
        return ['code' => '3004'];
    }
}
