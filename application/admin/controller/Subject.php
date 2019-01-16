<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Subject extends AdminController {

    /**
     * @api              {post} / 添加专题
     * @apiDescription   addSubject
     * @apiGroup         admin_subject
     * @apiName          addSubject
     * @apiParam (入参) {Number} pid 父级专题id
     * @apiParam (入参) {String} subject 专题名称
     * @apiParam (入参) {Number} [status] 状态 1启用 / 2停用 (默认1)
     * @apiParam (入参) {String} [image] 图片路径
     * @apiSuccess (返回) {String} code 200:成功 / 3001:状态有误 / 3002:pid只能为数字 / 3003.专题名不能为空 / 3004.pid查不到上级专题 / 3005.专题名已存在 / 3006.图片没有上传过 / 3007:保存失败
     * @apiSampleRequest /admin/subject/addsubject
     * @author zyr
     */
    public function addSubject() {
        $statusArr = [1, 2];//1.启用  2.停用
        $pid       = trim($this->request->post('pid'));
        $subject   = trim($this->request->post('subject'));
        $status    = trim($this->request->post('status'));
        $image     = trim($this->request->post('image'));
        $status    = empty($status) ? 1 : intval($status);//默认添加时为启用
        if (!in_array($status, $statusArr)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($pid)) {
            return ["code" => '3002'];
        }
        if (empty($subject)) {
            return ["code" => '3003'];
        }
        $result = $this->app->subject->addSubject(intval($pid), intval($status), $subject, $image);
        return $result;
    }

    /**
     * @api              {post} / 修改专题
     * @apiDescription   editSubject
     * @apiGroup         admin_subject
     * @apiName          editSubject
     * @apiParam (入参) {Number} id 专题id
     * @apiParam (入参) {String} subject 分类名称
     * @apiParam (入参) {Number} status 状态 1启用 / 2停用
     * @apiParam (入参) {String} [image] 图片路径
     * @apiSuccess (返回) {String} code 200:成功 / 3001:状态有误 / 3002:id只能为数字 / 3004.专题不存在 / 3005.专题名已存在 / 3006.图片没有上传过 /3008:没提交要修改的内容 / 3008:保存失败
     * @apiSampleRequest /admin/subject/editsubject
     * @author zyr
     */
    public function editSubject() {
        $statusArr = [0, 1, 2];//1.启用  2.停用
        $id        = trim($this->request->post('id'));
        $subject   = trim($this->request->post('subject'));
        $status    = trim($this->request->post('status'));
        $image     = trim($this->request->post('image'));
        $status    = empty($status) ? 0 : intval($status);//默认添加时为启用
        if (!in_array($status, $statusArr)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($id)) {
            return ["code" => '3002'];
        }
        $result = $this->app->subject->editSubject(intval($id), intval($status), $subject, $image);
        return $result;
    }
}
