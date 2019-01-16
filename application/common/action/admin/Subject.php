<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbImage;
use think\Db;
use Config;

class Subject {
    /**
     * 添加商品专题
     * @param $pid
     * @param $status
     * @param $subject
     * @param $image
     * @return array
     * @author zyr
     */
    public function addSubject(int $pid, int $status, $subject, $image) {
        $tier = 1;
        if (!empty($pid)) {//pid不为0
            $parentSubject = DbGoods::getSubject(['id' => $pid], 'id,pid', true);//获取上级专题
            if (empty($parentSubject)) {
                return ['code' => '3004'];//pid查不到上级专题
            }
            $tier = 2;
            if ($parentSubject['pid'] != 0) {
                $tier = 3;
            }
        }
        $subjectIsset = DbGoods::getSubject(['subject' => $subject], 'id', true);
        if (!empty($subjectIsset)) {
            return ['code' => '3005'];//专题名已存在
        }
        $data     = [
            "pid"     => $pid,
            "subject" => $subject,
            "tier"    => $tier,
            "status"  => $status,
        ];
        $logImage = [];
        if (!empty($image)) {
            $image    = filtraImage(Config::get('qiniu.domain'), $image);
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3006'];//图片没有上传过
            }
        }
        Db::startTrans();
        try {
            $subjectId = DbGoods::addSubject($data);
            if (!empty($logImage)) {
                DbGoods::addSubjectImage(['subject_id' => $subjectId, 'image_path' => $image]);//添加分类图片
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3007"];//保存失败
        }
    }

    /**
     * 编辑商品专题
     * @param $id
     * @param $status
     * @param $subject
     * @param $image
     * @return array
     */
    public function editSubject(int $id, int $status, $subject, $image) {
        if (empty($subject) && empty($status) && empty($image)) {
            return ['code' => '3007'];
        }
        $subjectRow = DbGoods::getSubject(['id' => $id], 'id,pid', true);//获取上级专题
        if (empty($subjectRow)) {
            return ['code' => '3004'];//专题不存在
        }
        if (!empty($subject)) {//专题名称不为空
            $subjectIsset = DbGoods::getSubject([['subject', '=', $subject], ['id', '<>', $id]], 'id', true);
            if (!empty($subjectIsset)) {
                return ['code' => '3005'];//专题名已存在
            }
            $data['subject'] = $subject;
        }
        if (!empty($status)) {
            $data['status'] = $status;
        }
        $logImage        = [];
        $oldLogImage     = [];
        $oldSubjectImage = [];
        if (!empty($image)) {
            $image    = filtraImage(Config::get('qiniu.domain'), $image);
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3006'];//图片没有上传过
            }
            $oldSubjectImage = DbGoods::getSubjectImage(['subject_id' => $id], 'id,image_path', true);
            if (!empty($oldSubjectImage)) {//之前有图片
                $oldImage = $oldSubjectImage['image_path'];
                $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);
//                if (stripos($oldImage, 'http') === false) {//新版本图片
                $oldLogImage = DbImage::getLogImage($oldImage, 1);//之前在使用的图片日志
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
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
                if (!empty($oldSubjectImage)) {
                    DbGoods::updateSubjectImage(['image_path' => $image], $oldSubjectImage['id']);//修改专题图片
                } else {
                    DbGoods::addSubjectImage(['subject_id' => $id, 'image_path' => $image]);//添加分类图片
                }
            }
            if (!empty($oldLogImage)) {
                DbImage::updateLogImageStatus($oldLogImage, 3);//更新状态为弃用
            }
            if ($flag === false) {
                Db::rollback();
                return ['code' => '3008'];
            }
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            print_r($e);
            die;
            Db::rollback();
            return ["code" => "3008"];//保存失败
        }
    }
}