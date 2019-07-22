<?php

namespace app\common\action\admin;

use app\facade\DbCoupon;
use app\facade\DbGoods;
use app\facade\DbImage;
use think\Db;

class Coupons extends CommonIndex {
    public function __construct() {
        parent::__construct();
    }

    /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getCouponHdList($page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        $result = DbCoupon::getCouponHd([], 'id,status,title,content,create_time', false, 'id desc', $offset . ',' . $pageNum);
        $count  = DbCoupon::countCouponHd();
        return ['code' => '200', 'data' => $result, 'total' => $count];
    }

    /**
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getCouponList($page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        $result = DbCoupon::getCoupon([], 'id,price,gs_id,level,title,days,create_time', false, 'id desc', $offset . ',' . $pageNum);
        foreach ($result as &$r) {
            $r['name'] = '';
            if ($r['level'] == 1) {
                $goods = DbGoods::getOneGoods(['id' => $r['gs_id']], 'goods_name');
                if (!empty($goods)) {
                    $r['name'] = $goods['goods_name'];
                }
            } else if ($r['level'] == 2) {
                $subject = DbGoods::getSubject(['id' => $r['gs_id']], 'subject', true);
                if (!empty($subject['subject'])) {
                    $r['name'] = $subject['subject'];
                }
            }
        }
        unset($r);
        $count = DbCoupon::countCoupon();
        return ['code' => '200', 'data' => $result, 'total' => $count];
    }

    /**
     * @param $couponHdId
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getHdCoupon($couponHdId, $page, $pageNum) {
        $offset            = $pageNum * ($page - 1);
        $result            = DbCoupon::getCouponByRelation(['id' => $couponHdId], $offset, $pageNum);
        $count             = DbCoupon::countCouponHdRelation([['coupon_hd_id', '=', $couponHdId]]);
        $result['coupons'] = array_map(function ($var) {
            unset($var['pivot']);
            return $var;
        }, $result['coupons']);
        return ['code' => '200', 'data' => $result, 'total' => $count];
    }

    /**
     * @param $couponId
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getHdCouponList($couponId, $page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        $result = DbCoupon::getCouponHdByRelation(['id' => $couponId], $offset, $pageNum);
        if (empty($result)) {
            return ['code' => '200', 'data' => $result, 'total' => 0];
        }
        $count                = DbCoupon::countCouponHdRelation([['coupon_id', '=', $couponId]]);
        $result['coupons_hd'] = array_map(function ($var) {
            unset($var['pivot']);
            return $var;
        }, $result['coupons_hd']);
        return ['code' => '200', 'data' => $result, 'total' => $count];
    }

    /**
     * @param $title
     * @param $content
     * @return array
     * @author zyr
     */
    public function addCouponHd($title, $content) {
        $data = [
            'title'   => $title,
            'content' => $content,
        ];
        Db::startTrans();
        try {
            DbCoupon::addCouponHd($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3003']; //添加失败
        }
    }

    /**
     * @param $title
     * @param $content
     * @param $id
     * @return array
     * @author zyr
     */
    public function modifyCouponHd($title, $content, $id) {
        $couponHd = DbCoupon::getCouponHd(['id' => $id], 'id', true);
        if (empty($couponHd)) { //优惠券活动id不存在
            return ['code' => '3004'];
        }
        $data = [
            'title'   => $title,
            'content' => $content,
        ];
        Db::startTrans();
        try {
            DbCoupon::updateCouponHd($data, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3006']; //修改失败
        }
    }

    /**
     * @param $status
     * @param $id
     * @return array
     * @author zyr
     */
    public function modifyCouponHdStatus($status, $id) {
        $couponHd = DbCoupon::getCouponHd(['id' => $id], 'id,status', true);
        if (empty($couponHd)) { //优惠券活动id不存在
            return ['code' => '3004'];
        }
        if ($couponHd['status'] == $status) {
            return ['code' => '200'];
        }
        $data = [
            'status' => $status,
        ];
        Db::startTrans();
        try {
            DbCoupon::updateCouponHd($data, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3006']; //修改失败
        }
    }

    /**
     * @param $id
     * @return array
     * @author zyr
     */
    public function deleteCouponHd($id) {
        $couponHdRelation = DbCoupon::getCouponHdRelation(['coupon_hd_id' => $id], 'id', true);
        if (!empty($couponHdRelation)) { //活动已绑定优惠券
            return ['code' => '3002'];
        }
        Db::startTrans();
        try {
            DbCoupon::deleteCouponHd($id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005']; //删除失败
        }
    }

    /**
     * @param $price
     * @param $gsId
     * @param $level
     * @param $title
     * @param $days
     * @return array
     * @author zyr
     */
    public function addCoupon($price, $gsId, $level, $title, $days) {
        $data = [
            'price' => $price,
            'gs_id' => $gsId,
            'level' => $level,
            'title' => $title,
            'days'  => $days,
        ];
        Db::startTrans();
        try {
            DbCoupon::addCoupon($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008']; //修改失败
        }
    }

    /**
     * @param $price
     * @param $gsId
     * @param $level
     * @param $title
     * @param $days
     * @param $id
     * @return array
     * @author zyr
     */
    public function modifyCoupon($price, $gsId, $level, $title, $days, $id) {
        $coupon = DbCoupon::getCoupon(['id' => $id], 'id', true);
        if (empty($coupon)) { //优惠券id不存在
            return ['code' => '3007'];
        }
        $data = [
            'price' => $price,
            'gs_id' => $gsId,
            'level' => $level,
            'title' => $title,
            'days'  => $days,
        ];
        Db::startTrans();
        try {
            DbCoupon::updateCoupon($data, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008']; //修改失败
        }
    }

    /**
     * @param $id
     * @return array
     * @author zyr
     */
    public function deleteCoupon($id) {
        $couponHdRelation = DbCoupon::getCouponHdRelation(['coupon_id' => $id], 'id', true);
        if (!empty($couponHdRelation)) { //优惠券已绑定活动
            return ['code' => '3002'];
        }
        Db::startTrans();
        try {
            DbCoupon::deleteCoupon($id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005']; //删除失败
        }
    }

    /**
     * @param $couponHdId
     * @param $couponId
     * @return array
     * @author zyr
     */
    public function bindCouponHd($couponHdId, $couponId) {
        $coupon = DbCoupon::getCoupon(['id' => $couponId], 'id', true);
        if (empty($coupon)) { //优惠券不存在
            return ['code' => '3003'];
        }
        $couponHd = DbCoupon::getCouponHd(['id' => $couponHdId], 'id', true);
        if (empty($couponHd)) { //优惠券活动不存在
            return ['code' => '3004'];
        }
        $data = [
            'coupon_id'    => $couponId,
            'coupon_hd_id' => $couponHdId,
        ];
        $couponHdRelation = DbCoupon::getCouponHdRelation($data, 'id', true);
        if (!empty($couponHdRelation)) { //活动已关联
            return ['code' => '3005'];
        }
        Db::startTrans();
        try {
            DbCoupon::addCouponHdRelation($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008']; //修改失败
        }
    }

    /**
     * @param $couponHdId
     * @param $couponId
     * @return array
     * @author zyr
     */
    public function unbindCouponHd($couponHdId, $couponId) {
        $coupon = DbCoupon::getCoupon(['id' => $couponId], 'id', true);
        if (empty($coupon)) { //优惠券不存在
            return ['code' => '3003'];
        }
        $couponHd = DbCoupon::getCouponHd(['id' => $couponHdId], 'id', true);
        if (empty($couponHd)) { //优惠券活动不存在
            return ['code' => '3004'];
        }
        $data = [
            'coupon_id'    => $couponId,
            'coupon_hd_id' => $couponHdId,
        ];
        $couponHdRelation = DbCoupon::getCouponHdRelation($data, 'id', true);
        if (empty($couponHdRelation)) { //活动未关联
            return ['code' => '3005'];
        }
        Db::startTrans();
        try {
            DbCoupon::deleteCouponHdRelation($couponHdRelation['id']);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008']; //修改失败
        }
    }

    /**
     * @param $page
     * @param $pageNum
     * @param $id
     * @return array
     * @author rzc
     */
    public function getHd(int $page, int $pageNum, $id = 0) {
        if (!empty($id)) {
            $result = DbCoupon::getHd( ['id' => $id], '*', true);
            return ['code' => '200', 'luckydraw' => $result];
        }
        $offset = ($page - 1) * $pageNum;
        // $DbCoupon = new DbCoupon('Hd',[[], '*', false, ['id' => 'desc'], $offset . ',' . $pageNum]);
        $result = DbCoupon::getHd( [], '*', false,'', $offset . ',' . $pageNum);
        if (!$result) {
            $result = [];
        }
        $count =DbCoupon::getHdCount([]);
        if (!$count) {
            $count = 0;
        }
        return ['code' => '200', 'total' => $count, 'luckydraw' => $result];
    }

    /**
     * @param $title
     * @param $start_time
     * @param $end_time
     * @return array
     * @author rzc
     */
    public function saveHd($title, $start_time, $end_time) {
        $has_Hd = DbCoupon::getHd( ['status' => 2], '*', true);
        if (!empty($has_Hd)) {
            return ['code' => '3002'];
        }
        $data = [];
        $data = [
            'title'      => $title,
            'status'     => 1,
            'start_time' => $start_time,
            'end_time'   => $end_time,
        ];
        DbCoupon::saveHd($data);
        return ['code' => '200'];
    }

    /**
     * @param $title
     * @param $status
     * @param $start_time
     * @param $end_time
     * @param $id
     * @return array
     * @author rzc
     */
    public function updateHd($id, $title = '', $status = 0, $start_time = 0, $end_time = 0) {
        $data = [];
        if ($status == '2') {
            $has_Hd = DbCoupon::getHd([['status', '=', 2], ['id', '<>', $id]], '*', true);
            if (!empty($has_Hd)) {
                return ['code' => '3002'];
            }
        }

        if (!empty($title)) {
            array_push($data, ['title' => $title]);
        }
        if ($status) {
            array_push($data, ['status' => $status]);
        }
        if ($start_time) {
            array_push($data, ['start_time' => $start_time]);
        }
        if ($end_time) {
            array_push($data, ['end_time' => $end_time]);
        }
        DbCoupon::saveHd($data, $id);
        return ['code' => '200'];
    }

    /**
     * @param $hd_id
     * @return array
     * @author rzc
     */
    public function getHdGoods($hd_id,$id = 0) {
        if ($id) {
            if (!is_numeric($id)) {
                return ['code' => '3000'];
            }
            $result = DbCoupon::getHdGoods( ['id' => $id], '*', true);
        } else {
            $result = DbCoupon::getHdGoods( ['hd_id' => $hd_id], '*', false);
        }
        return ['code' => '200', 'HdGoods' => $result];
    }

    /**
     * @param $hd_id
     * @param $image
     * @param $kind
     * @param $relevance
     * @param $debris
     * @param $title
     * @param $probability
     * @return array
     * @author rzc
     */
    public function addHdGoods($hd_id, $image, $kind, $relevance, $debris, $title, $probability) {
        $num = DbCoupon::countgetHdGoods( ['hd_id' => $hd_id]);
        if ($num > 7) {
            return ['code' => '3008'];
        }
        $data = [];
        $data = [
            'image'       => $image,
            'kind'        => $kind,
            'relevance'   => $relevance,
            'debris'      => $debris,
            'title'       => $title,
            'probability' => $probability,
        ];
        Db::startTrans();
        try {

            if (!empty($data['image'])) {
                $oldImage = $data['image'];

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
                $data['image'] = $image;
            }

            $id = DbCoupon::saveHdGoods($data);
            if ($id) {
                Db::commit();
                return ['code' => '200', 'id' => $id];
            }
            Db::rollback();
            return ['code' => '3011']; //修改失败
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011']; //修改失败
        }
    }

    public function saveHdGoods($id, $image = '', $kind = '', $relevance = '', $debris = '', $title = '', $probability = '') {
        $HdGoods = DbCoupon::getList('HdGoods', ['id' => $id]);
        if (!$HdGoods) {
            return ['code' => '3000'];
        }
        $data = [];
        if (!empty($image)) {
            array_push($data, ['image' => $image]);
        }
        if (!empty($kind)) {
            array_push($data, ['kind' => $kind]);
        }
        if (!empty($relevance)) {
            array_push($data, ['relevance' => $relevance]);
        }
        if (!empty($debris)) {
            array_push($data, ['debris' => $debris]);
        }
        if (!empty($title)) {
            array_push($data, ['title' => $title]);
        }
        if (!empty($probability)) {
            array_push($data, ['probability' => $probability]);
        }
        Db::startTrans();
        try {

            if (!empty($data['image'])) {
                $oldImage = $data['image'];

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
                $data['image'] = $image;
            }

            $id = DbCoupon::updateHdGoods($data,$id);
            if ($id) {
                Db::commit();
                return ['code' => '200', 'id' => $id];
            }
            Db::rollback();
            return ['code' => '3011']; //修改失败
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011']; //修改失败
        }
    }
}