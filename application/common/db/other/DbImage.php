<?php

namespace app\common\db\other;

use app\common\model\LogImage;

class DbImage {
    private $logImage;

    public function __construct() {
        $this->logImage = new LogImage();
    }

    /**
     * 写入文件上传日志
     * @param $image_path
     * @param string $username
     * @return int
     * @author zyr
     */
    public function saveLogImage($image_path, $username = '') {
        $data = [
            'username'   => $username,
            'image_path' => $image_path,
        ];
        return $this->logImage->save($data);
    }

    /**
     * 批量写入上传日志
     * @param $imagePathList
     * @param string $username
     * @return \think\Collection
     * @throws \Exception
     */
    public function saveLogImageList($imagePathList, $username = '') {
        $data = [];
        foreach ($imagePathList as $val) {
            array_push($data, [
                'username'   => $username,
                'image_path' => $val,
            ]);
        }
        return $this->logImage->saveAll($data);
    }

    /**
     * 查找该图片是否有未完成的
     * @param $name
     * @param $status
     * @return array
     * @author zyr
     */
    public function getLogImage($name, $status) {
        return LogImage::field('id')->where(['image_path' => $name, 'status' => $status])->findOrEmpty()->toArray();
    }

    /**
     * 更新状态
     * @param $id
     * @param $status
     * @return bool
     */
    public function updateLogImageStatus($id, $status) {
        return $this->logImage->save(['status' => $status], $id);
    }

}