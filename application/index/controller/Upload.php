<?php

namespace app\index\controller;

use app\index\MyController;

class Upload extends MyController {

    protected $beforeActionList = [
           'isLogin',//所有方法的前置操作
    // 'isLogin' => ['except' => ''], //除去getFirstCate其他方法都进行second前置操作
    //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 上传单个本地图片
     * @apiDescription   uploadUserImage
     * @apiGroup         index_upload
     * @apiName          uploadUserImage
     * @apiParam (入参) {String} filename 图片
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传文件不能为空 / 3002:上传失败
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /index/upload/uploaduserimage
     * @author zyr
     */
    public function uploadUserImage() {
        $apiName  = classBasename($this) . '/' . __function__;
        $conId    = trim($this->request->post('con_id'));
        $filename = $this->request->post('filename');
        if (empty($filename)) {
            return ['code' => '3001'];
        }
        $result = $this->app->upload->uploadUserImage($filename);
        $this->apiLog($apiName, [$conId, $filename], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 上传单个图片
     * @apiDescription   uploadFile
     * @apiGroup         index_upload
     * @apiName          uploadFilee
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {file} image 图片
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /index/upload/uploadfile
     * @author zyr
     */
    public function uploadFile() {
        $apiName  = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        $image    = $this->request->file('image');
        if (empty($image)) {
            return ['code' => '3004'];
        }
        $fileInfo = $image->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        if ($fileType[0] != 'image') {
            return ['3001'];//上传的不是图片
        }
        if ($fileInfo['size'] > 1024 * 1024 * 2) {
            return ['3002'];//上传图片不能超过2M
        }
        $result = $this->app->upload->uploadFile($fileInfo);
        $this->apiLog($apiName, [$conId, $image], $result['code'], $conId);
        return $result;
    }
}