<?php

namespace app\common\action\admin;

use Config;
use upload\Imageupload;
use app\facade\DbImage;

class Upload {
    private $upload;

    public function __construct() {
        $this->upload = new Imageupload();
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

    /**
     * 批量上传图片
     * @param $list
     * @return array
     */
    public function uploadMultiFile($list) {
        $filenameArr = [];
        $flag        = true;
        foreach ($list as $val) {
            $filename    = date('Ymd') . '/' . $this->upload->getNewName($val['name']);
            $uploadimage = $this->upload->uploadFile($val['tmp_name'], $filename);
            if (!$uploadimage) {//上传失败
                $flag = false;
                break;
            }
            array_push($filenameArr, $filename);
        }
        if (!empty($filenameArr) && $flag === false) {//批量上传失败需要将已上传的文件删除
            $this->delImg($filenameArr);
        }
        if ($flag) {
            try {
                DbImage::saveLogImageList($filenameArr);//全部上传成功后写如日志/**/
            } catch (\Exception $e) {
//                $this->delImg($filenameArr);
                return ['code' => '3003'];//上传失败
            }
            array_walk($filenameArr, function (&$value, $key) {//加上域名前缀
                $value = Config::get('qiniu.domain') . '/' . $value;
            });
            return ['code' => '200', 'data'=>$filenameArr];
        } else {
            return ['code' => '3003'];//上传失败
        }
    }

    /**
     * 批量删除图片
     * @param $filenameArr
     */
    private function delImg($filenameArr) {
        if (!is_array($filenameArr)) {
            $this->upload->deleteImage($$filenameArr);//删除上传的图片
        } else {
            foreach ($filenameArr as $v) {
                $this->upload->deleteImage($v);//删除上传的图片
            }
        }
    }
}