<?php

namespace app\index\controller;

use app\index\MyController;

class Upload extends MyController {
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
}