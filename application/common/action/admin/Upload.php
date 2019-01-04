<?php

namespace app\common\action\admin;

use Config;
use upload\Imageupload;
use app\facade\DbImage;

class Upload {
    public function uploadFile($fileInfo) {
        $upload = new Imageupload();
        /* 文件名重命名 */
        $filename    = date('Ymd') . '/' . $upload->getNewName($fileInfo['name']);
        $uploadimage = $upload->uploadFile($fileInfo['tmp_name'], $filename);
        if ($uploadimage) {//上传成功
            $result = DbImage::saveLogImage($filename);
            if ($result) {
                return ['code' => '200', 'image_path' => Config::get('qiniu.domain') . '/' . $filename];
            } else {
                $upload->deleteImage($filename);//删除上传的图片
            }
        }
        return ['code' => '3003'];//上传失败
    }
}