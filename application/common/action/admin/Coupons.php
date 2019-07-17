<?php

namespace app\common\action\admin;

use app\facade\DbCoupon;
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
        $result = DbCoupon::getCouponHd([], 'id,status,title,content,create_time', false, 'id desc', $offset . ',', $pageNum);
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
        $result = DbCoupon::getCoupon([], 'id,price,gs_id,level,title,days,create_time', false, 'id desc', $offset . ',', $pageNum);
        $count  = DbCoupon::countCoupon();
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
            return ['code' => '3003'];//添加失败
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
        if (empty($couponHd)) {//优惠券活动id不存在
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
            return ['code' => '3006'];//修改失败
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
        if (empty($couponHd)) {//优惠券活动id不存在
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
            return ['code' => '3006'];//修改失败
        }
    }

    /**
     * @param $id
     * @return array
     * @author zyr
     */
    public function deleteCouponHd($id) {
        $couponHdRelation = DbCoupon::getCouponHdRelation(['coupon_hd_id' => $id], 'id', true);
        if (!empty($couponHdRelation)) {//活动已绑定优惠券
            return ['code' => '3002'];
        }
        Db::startTrans();
        try {
            DbCoupon::deleteCouponHd($id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//删除失败
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
            return ['code' => '3008'];//修改失败
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
        if (empty($coupon)) {//优惠券id不存在
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
            return ['code' => '3008'];//修改失败
        }
    }

    /**
     * @param $id
     * @return array
     * @author zyr
     */
    public function deleteCoupon($id) {
        $couponHdRelation = DbCoupon::getCouponHdRelation(['coupon_id' => $id], 'id', true);
        if (!empty($couponHdRelation)) {//优惠券已绑定活动
            return ['code' => '3002'];
        }
        Db::startTrans();
        try {
            DbCoupon::deleteCoupon($id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//删除失败
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
        if (empty($coupon)) {//优惠券不存在
            return ['code' => '3003'];
        }
        $couponHd = DbCoupon::getCouponHd(['id' => $couponHdId], 'id', true);
        if (empty($couponHd)) {//优惠券活动不存在
            return ['code' => '3004'];
        }
        $data             = [
            'coupon_id'    => $couponId,
            'coupon_hd_id' => $couponHdId,
        ];
        $couponHdRelation = DbCoupon::getCouponHdRelation($data, 'id', true);
        if (!empty($couponHdRelation)) {//活动已关联
            return ['code' => '3005'];
        }
        Db::startTrans();
        try {
            DbCoupon::addCouponHdRelation($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008'];//修改失败
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
        if (empty($coupon)) {//优惠券不存在
            return ['code' => '3003'];
        }
        $couponHd = DbCoupon::getCouponHd(['id' => $couponHdId], 'id', true);
        if (empty($couponHd)) {//优惠券活动不存在
            return ['code' => '3004'];
        }
        $data             = [
            'coupon_id'    => $couponId,
            'coupon_hd_id' => $couponHdId,
        ];
        $couponHdRelation = DbCoupon::getCouponHdRelation($data, 'id', true);
        if (empty($couponHdRelation)) {//活动未关联
            return ['code' => '3005'];
        }
        Db::startTrans();
        try {
            DbCoupon::deleteCouponHdRelation($couponHdRelation['id']);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008'];//修改失败
        }
    }
}