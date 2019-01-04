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
     * @apiSuccess (返回) {String} code 200:成功  / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /admin/upload/uploadfile
     * @author rzc
     */
    public function uploadFile() {
        $image    = $this->request->file('image');
        $fileInfo = $image->getInfo();
        $fileType = explode('/', $fileInfo['type']);
        if ($fileType[0] != 'image') {
            return ['3001'];//上传的不是图片
        }
        if ($fileInfo['size'] > 1024 * 1024 * 2) {
            return ['3002'];//上传图片不能超过2M
        }
        return $this->app->Upload->uploadFile($fileInfo);
    }
}