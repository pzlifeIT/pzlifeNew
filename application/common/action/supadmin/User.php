<?php

namespace app\common\action\supadmin;

use app\facade\DbGoods;
use app\facade\DbImage;
use app\facade\DbSup;
use Config;
use think\Db;

class User extends CommonIndex {
    private $supCipherUserKey = 'suppass'; //用户密码加密key

    /**
     * 登录
     * @param $supName
     * @param $passwd
     * @return array
     * @author zyr
     */
    public function login($supName, $passwd) {
        $getPass  = getPassword($passwd, $this->supCipherUserKey, Config::get('conf.cipher_algo')); //用户填写的密码
        $supAdmin = DbGoods::getSupAdmin(['sup_name' => $supName, 'status' => 1], 'id,sup_passwd', true);
        if (empty($supAdmin)) {
            return ['code' => '3002']; //用户不存在
        }
        if ($supAdmin['sup_passwd'] !== $getPass) {
            return ['code' => '3003']; //密码错误
        }
        $supConId = $this->createSupConId();
        $this->redis->zAdd($this->redisSupConIdTime, time(), $supConId);
        $conUid = $this->redis->hSet($this->redisSupConIdUid, $supConId, $supAdmin['id']);
        if ($conUid === false) {
            return ['code' => '3004']; //登录失败
        }
        return ['code' => '200', 'sup_con_id' => $supConId];
    }

    /**
     * 添加推广
     * @param $title
     * @param $bigImage
     * @param $shareTitle
     * @param $shareImage
     * @param $shareCount
     * @param $bgImage
     * @return array
     * @author zyr
     */
    public function addPromote($title, $bigImage, $shareTitle, $shareImage, $shareCount, $bgImage) {
        $newBigImage   = filtraImage(Config::get('qiniu.domain'), $bigImage);
        $newShareImage = filtraImage(Config::get('qiniu.domain'), $shareImage);
        $newBgImage    = filtraImage(Config::get('qiniu.domain'), $bgImage);
        $logBigImage   = DbImage::getLogImage($newBigImage, 2);//判断时候有未完成的图片
        $logShareImage = DbImage::getLogImage($newShareImage, 2);//判断时候有未完成的图片
        $logBgImage    = DbImage::getLogImage($newBgImage, 2);//判断时候有未完成的图片
        if (empty($logBigImage)) {//图片不存在
            return ['code' => '3006'];//big_image图片没有上传过
        }
        if (empty($logShareImage)) {//图片不存在
            return ['code' => '3007'];//share_image图片没有上传过
        }
        if (empty($logBgImage)) {//图片不存在
            return ['code' => '3008'];//bg_image图片没有上传过
        }
        $data = [
            'title'       => $title,
            'big_image'   => $bigImage,
            'share_title' => $shareTitle,
            'share_image' => $shareImage,
            'share_count' => $shareCount,
            'bg_image'    => $bgImage,
        ];
        Db::startTrans();
        try {
            DbSup::addSupPromote($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3010'];//修改失败
        }
    }

    /**
     * 编辑推广
     * @param $id
     * @param $title
     * @param $bigImage
     * @param $shareTitle
     * @param $shareImage
     * @param $shareCount
     * @param $bgImage
     * @return array
     * @author zyr
     */
    public function editPromote($id, $title, $bigImage, $shareTitle, $shareImage, $shareCount, $bgImage) {
        $promote = DbSup::getSupPromote(['id' => $id], 'id', true);
        if (empty($promote)) {
            return ['code' => '3009'];//推广活动不存在
        }
        $data = [
            'title'       => $title,
            'share_title' => $shareTitle,
            'share_count' => $shareCount,
        ];
        if (!empty($bigImage)) {
            $newBigImage = filtraImage(Config::get('qiniu.domain'), $bigImage);
            $logBigImage = DbImage::getLogImage($newBigImage, 2);//判断时候有未完成的图片
            if (empty($logBigImage)) {//图片不存在
                return ['code' => '3006'];//big_image图片没有上传过
            }
            $data['big_image'] = $bigImage;
        }
        if (!empty($shareImage)) {
            $newShareImage = filtraImage(Config::get('qiniu.domain'), $shareImage);
            $logShareImage = DbImage::getLogImage($newShareImage, 2);//判断时候有未完成的图片
            if (empty($logShareImage)) {//图片不存在
                return ['code' => '3007'];//share_image图片没有上传过
            }
            $data['share_image'] = $shareImage;
        }
        if (!empty($bgImage)) {
            $newBgImage = filtraImage(Config::get('qiniu.domain'), $bgImage);
            $logBgImage = DbImage::getLogImage($newBgImage, 2);//判断时候有未完成的图片
            if (empty($logBgImage)) {//图片不存在
                return ['code' => '3008'];//bg_image图片没有上传过
            }
            $data['bg_image'] = $bgImage;
        }
        Db::startTrans();
        try {
            DbSup::editSupPromote($data, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3010'];//修改失败
        }
    }

    /**
     * 推广活动列表
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getPromoteList($page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        $field  = 'title,big_image,share_title,share_image,share_count,bg_image';
        $where  = [];
        $count  = DbSup::getSupPromoteCount([]);
        if ($count <= 0) {
            return ['code' => '3000', 'data' => [], 'totle' => 0];
        }
        $promoteData = DbSup::getSupPromote($where, $field, false, '', $offset . ',' . $pageNum);
        return ['code' => '200', 'data' => $promoteData, 'total' => $count];
    }

    private function createSupConId() {
        $supConId = uniqid(date('ymdHis'));
        $supConId = hash_hmac('ripemd128', $supConId, 'sup');
        return $supConId;
    }
}