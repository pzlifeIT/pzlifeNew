<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Audios extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //'isLogin' => ['except' => 'login'], //除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 同步远程音频内容
     * @apiDescription   asyncAudios
     * @apiGroup         admin_audios
     * @apiName          asyncAudios
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 /3001:更新失败
     * @apiSampleRequest /admin/audios/asyncaudios
     * @return array
     * @author zyr
     */
    public function asyncAudios() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $result = $this->app->audios->asyncAudios();
        return $result;
    }

    /**
     * @api              {post} / 音频内容列表
     * @apiDescription   audiosList
     * @apiGroup         admin_audios
     * @apiName          audiosList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} [page] 当前页(默认1)
     * @apiParam (入参) {Int} all 1查询全部
     * @apiParam (入参) {Int} [page_num] 每页条数(默认10)
     * @apiSuccess (返回) {String} code 200:成功 /3001:更新失败
     * @apiSampleRequest /admin/audios/audioslist
     * @return array
     * @author zyr
     */
    public function audiosList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $all    = trim($this->request->post('all'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        $page    = $page > 0 ? intval($page) : 1;
        $pageNum = $pageNum > 0 ? intval($pageNum) : 10;
        $result  = $this->app->audios->audiosList($page, $pageNum, $all);
        $this->apiLog($apiName, [$cmsConId, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改音频试听时间
     * @apiDescription   editAudio
     * @apiGroup         admin_audios
     * @apiName          editAudio
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id
     * @apiParam (入参) {Int} audition_time(秒)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:参数id有误 / 3002:参数audition_time有误 / 3003:更新失败
     * @apiSampleRequest /admin/audios/editaudio
     * @return array
     * @author zyr
     */
    public function editAudio() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id           = trim($this->request->post('id'));
        $auditionTime = trim($this->request->post('audition_time'));
        if (!is_numeric($id) || intval($id) <= 0) {//参数id有误
            return ["code" => '3001'];
        }
        if (!is_numeric($auditionTime) || intval($auditionTime) < 0) {//参数audition_time有误
            return ["code" => '3002'];
        }
        $result = $this->app->audios->editAudio($id, $auditionTime);
        $this->apiLog($apiName, [$cmsConId, $id, $auditionTime], $result['code'], $cmsConId);
        return $result;
    }
}