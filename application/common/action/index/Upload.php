<?php

namespace app\common\action\index;

use upload\Imageupload;
use Config;
use app\facade\DbImage;

class Upload {
    private $upload;

    public function __construct() {
        $this->upload = new Imageupload();
    }

    public function uploadUserImage($filename) {
        if (empty($filename)) {
            return ['code' => '3001'];
        }
        $imagePath   = Config::get('conf.image_path');
        $image       = $imagePath . $filename;
        $newfilename = date('Ymd') . '/' . $this->upload->getNewName($filename);
        $uploadimage = $this->upload->uploadFile($image, $newfilename);
        if ($uploadimage) {//上传成功
            $result = DbImage::saveLogImage($newfilename, '', 1);
            if ($result) {
                unlink(Config::get('conf.image_path') . $filename);
                return ['code' => '200', 'image_path' => $newfilename];
            } else {
                $this->delImg($newfilename);//删除上传的图片
            }
        }
        return ['code' => '3002'];//上传失败
    }

    /**
     * 批量删除图片
     * @param $filenameArr
     */
    private function delImg($filenameArr) {
        if (!is_array($filenameArr)) {
            $this->upload->deleteImage($filenameArr);//删除上传的图片
        } else {
            foreach ($filenameArr as $v) {
                $this->upload->deleteImage($v);//删除上传的图片
            }
        }
    }

    /**
     * 单个上传图片
     * @param $fileInfo
     * @return array
     */
    public function uploadFile($fileInfo) {
        /* 文件名重命名 */
        $filename    = date('Ymd') . '/' . $this->upload->getNewName($fileInfo['name']);
        $uploadimage = $this->upload->uploadFile($fileInfo['tmp_name'], $filename);
        if ($uploadimage) {//上传成功
            $result = DbImage::saveLogImage($filename);
            if ($result) {
                return ['code' => '200', 'image_path' => Config::get('qiniu.domain') . '/' . $filename];
            } else {
                $this->delImg($filename);//删除上传的图片
            }
        }
        return ['code' => '3003'];//上传失败
    }
}