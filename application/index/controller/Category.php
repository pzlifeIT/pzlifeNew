<?php

namespace app\index\controller;

use app\index\MyController;

class Category extends MyController {
    protected $beforeActionList = [
//        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'getFirstCate,getGoodsSubject,getSubjectDetail'],//除去getFirstCate其他方法都进行isLogin前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 分类
     * @apiDescription   getFirstCate
     * @apiGroup         index_category
     * @apiName          getFirstCate
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /index/category/getFirstCate
     * @author wujunjie
     * 2019/1/7-9:47
     */
    public function getFirstCate() {
        $apiName = classBasename($this) . '/' . __function__;
        $res     = $this->app->category->getFirstCate();
        $this->apiLog($apiName, [], $res['code'], '');
        return $res;
    }

    /**
     * @api              {post} / 专题
     * @apiDescription   getGoodsSubject
     * @apiGroup         index_category
     * @apiName          getGoodsSubject
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /index/category/getGoodsSubject
     * @author rzc
     * 2019/1/7-9:47
     */
    public function getGoodsSubject() {
        $apiName = classBasename($this) . '/' . __function__;
        $res     = $this->app->category->getGoodsSubject();
        $this->apiLog($apiName, [], $res['code'], '');
        return $res;
    }

    /**
     * @api              {post} / 获取专题详情
     * @apiDescription   getSubjectDetail
     * @apiGroup         index_category
     * @apiName          getSubjectDetail
     * @apiParam (入参) {Number} subject_id 专题id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} subject 专题名称
     * @apiSuccess (data) {Number} status 1.启用 2.停用
     * @apiSuccess (data) {Number} tier 层级
     * @apiSuccess (data) {String} subject_image 专题图片
     * @apiSuccess (data) {String} subject_share_image 专题分享图片
     * @apiSampleRequest /index/category/getSubjectDetail
     * @author rzc
     * 2019/1/7-9:47
     */
    public function getSubjectDetail(){
        $subjectId = trim($this->request->post('subject_id'));
        if (!is_numeric($subjectId)) {
            return ["code" => '3001'];
        }
        $result = $this->app->category->getSubjectDetail(intval($subjectId));
        return $result;
    }
}
