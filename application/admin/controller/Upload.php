<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Upload extends AdminController {
    /**
     * @api              {post} / 上传单个图片
     * @apiDescription   uploadFile
     * @apiGroup         admin_upload
     * @apiName          uploadFilee
     * @apiParam (入参) {file} image 图片
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /admin/upload/uploadfile
     * @author zyr
     */
    public function uploadFile() {
        $image = $this->request->file('image');
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
        return $this->app->upload->uploadFile($fileInfo);
    }

    /**
     * @api              {post} / 上传多个图片
     * @apiDescription   uploadMultiFile
     * @apiGroup         admin_upload
     * @apiName          uploadMultiFile
     * @apiParam (入参) {file} images 图片集
     * @apiSuccess (返回) {String} code 200:成功 / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空 / 3004:上传文件不能为空
     * @apiSuccess (data) {Array} data 上传后的图片路径
     * @apiSampleRequest /admin/upload/uploadmultifile
     * @author zyr
     */
    public function uploadMultiFile() {
        $images = $this->request->file('images');
        if (empty($images)) {
            return ['code' => '3004'];
        }
        if (count($images) > 5) {
            return ['code' => '3005'];
        }
        $list = [];
        foreach ($images as $val) {
            $fileInfo = $val->getInfo();
            $fileType = explode('/', $fileInfo['type']);
            if ($fileType[0] != 'image') {
                return ['3001'];//上传的不是图片
            }
            if ($fileInfo['size'] > 1024 * 1024 * 2) {
                return ['3002'];//上传图片不能超过2M
            }
            array_push($list, $fileInfo);
        }
        return $this->app->upload->uploadMultiFile($list);

    }
}