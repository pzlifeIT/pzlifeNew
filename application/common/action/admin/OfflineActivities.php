<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbImage;
use app\facade\DbOfflineActivities;
use think\Db;
use Config;

class OfflineActivities extends CommonIndex {
    /**
     * 线下活动列表
     * @param $page
     * @param $pagenum
     * @return array
     * @author rzc
     */
    public function getOfflineActivities($page, $pagenum) {
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }

        $result = DbOfflineActivities::getOfflineActivities([], '*', false, ['id' => 'desc'], $offset . ',' . $pagenum);
        if (empty($result)) {
            return ['code' => 3000];
        }
        $total = DbOfflineActivities::countOfflineActivities([]);
        return ['code' => '200', 'total' => $total, 'result' => $result];
    }

    /**
     * 新建线下活动
     * @param $title
     * @param $image_path
     * @param $start_time
     * @param $stop_time
     * @return array
     * @author rzc
     */
    public function addOfflineActivities($title, $image_path, $start_time, $stop_time) {
        $data               = [];
        $data['title']      = $title;
        $data['start_time'] = $start_time;
        $data['stop_time']  = $stop_time;
        Db::startTrans();
        try {
            $image    = filtraImage(Config::get('qiniu.domain'), $image_path);
            $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
            if (empty($logImage)) { //图片不存在
                return ['code' => '3010']; //图片没有上传过
            }
            DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            $data['image_path'] = $image;

            $add = DbOfflineActivities::addOfflineActivities($data);

            if ($add) {
                Db::commit();
                return ['code' => '200', 'add_id' => $add];
            }
            Db::rollback();
            return ['code' => '3011']; //添加失败
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011']; //添加失败
        }
    }

    /**
     * 修改线下活动
     * @param $title
     * @param $image_path
     * @param $start_time
     * @param $stop_time
     * @param $id
     * @return array
     * @author rzc
     */
    public function updateOfflineActivities($title = '', $image_path = '', $start_time = 0, $stop_time = 0, $id) {
        $result = DbOfflineActivities::getOfflineActivities(['id' => $id], '*', true);
        if (empty($result)) {
            return ['code' => 3000];
        }
        if ($start_time) {
            if ($start_time > $result['stop_time']) {
                return ['code' => '3003'];
            }
        }
        if ($stop_time) {
            if ($stop_time < $result['start_time']) {
                return ['code' => '3003'];
            }
        }
        $data = [];
        if ($title) {
            array_push($data, ['title' => $title]);
        }
        if ($image_path) {
            array_push($data, ['image_path' => $image_path]);
        }
        if ($stop_time) {
            array_push($data, ['stop_time' => $stop_time]);
        }
        if ($start_time) {
            array_push($data, ['start_time' => $start_time]);
        }
        Db::startTrans();
        try {

            if (!empty($data['image_path'])) {
                $oldImage = $result['image_path'];

                $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);

                if (!empty($oldImage)) {

                    $oldImage_id = DbImage::getLogImage($oldImage, 1);
                    DbImage::updateLogImageStatus($oldImage_id, 3); //更新状态为弃用

                }
                $image = filtraImage(Config::get('qiniu.domain'), $data['image_path']);

                $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片

                if (empty($logImage)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
                $data['image_path'] = $image;
            }
            DbOfflineActivities::updateOfflineActivities($data, $id);

            Db::commit();
            return ['code' => '200', 'add_id' => $add];

        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011']; //添加失败
        }
    }

    /**
     * 获取线下活动商品
     * @param $page
     * @param $pagenum
     * @param $active_id
     * @return array
     * @author rzc
     */
    public function getOfflineActivitiesGoods($page, $pagenum, $active_id) {
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $result = DbOfflineActivities::getOfflineActivitiesGoods(['active_id' => $active_id], '*', false, ['id' => 'desc'], $offset . ',' . $pagenum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        foreach ($result as $key => $value) {
            $result['goods'] = $this->getGoods($value['goods_id']);
        }
        $total = DbOfflineActivities::countOfflineActivitiesGoods(['active_id' => $active_id]);
        return ['code' => '200', 'total' => $total, 'result' => $result];
    }

    function getGoods($goodsid) {
        /* 返回商品基本信息 （从商品库中直接查询）*/
        $where      = [["id", "=", $goodsid], ["status", "=", 1]];
        $field      = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return [];
        }
        list($goods_spec, $goods_sku) = $this->getGoodsSku($goodsid);
        if ($goods_sku) {
            foreach ($goods_sku as $goods => $sku) {

                $retail_price[$sku['id']]    = $sku['retail_price'];
                $brokerage[$sku['id']]       = $sku['brokerage'];
                $integral_active[$sku['id']] = $sku['integral_active'];
            }
            $goods_data['retail_price']        = min($retail_price);
            $goods_data['min_brokerage']       = $brokerage[array_search(min($retail_price), $retail_price)];
            $goods_data['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
        } else {
            $goods_data['min_brokerage']       = 0;
            $goods_data['min_integral_active'] = 0;
            $goods_data['retail_price']        = 0;
        }
        return $goods_data;
    }

    public function addOfflineActivitiesGoods($active_id, $goods_id) {
        $offlineactivities = DbOfflineActivities::getOfflineActivities(['id' => $id], '*', true);
        if (empty($offlineactivities)) {
            return ['code' => '3000'];
        }
        if ($offlineactivities['stop_time'] < time()) {
            return ['code' => '3001'];
        }
        $goods = DbGoods::getOneGoods([["id", "=", $goodsid], ["status", "=", 1]], 'id');
        if (empty($goods)) {
            return ['code' => '3002'];
        }
        if (DbOfflineActivities::getOfflineActivitiesGoods(['active_id' => $active_id, 'goods_id' => $goods_id],'id')) {
            return ['code' => '3003'];
        }
        $data              = [];
        $data['active_id'] = $active_id;
        $data['goods_id']  = $goods_id;
        DbOfflineActivities::addOfflineActivitiesGoods($data);
        return ['code' => '200'];
    }

    public function updateOfflineActivitiesGoods($active_id, $goods_id, $id) {
        $offlineactivities = DbOfflineActivities::getOfflineActivities(['id' => $id], '*', true);
        if (empty($offlineactivities)) {
            return ['code' => '3000'];
        }
        if ($offlineactivities['stop_time'] < time()) {
            return ['code' => '3001'];
        }
        $goods = DbGoods::getOneGoods([["id", "=", $goodsid], ["status", "=", 1]], 'id');
        if (empty($goods)) {
            return ['code' => '3002'];
        }
        if (DbOfflineActivities::getOfflineActivitiesGoods([['active_id' , '=', $active_id], ['goods_id' , '=', $goods_id], ['id' ,'<>', $id]],'id')) {
            return ['code' => '3003'];
        }
        $data              = [];
        $data['active_id'] = $active_id;
        $data['goods_id']  = $goods_id;
        DbOfflineActivities::updateOfflineActivitiesGoods($data,$id);
        return ['code' => '200'];
    }
}