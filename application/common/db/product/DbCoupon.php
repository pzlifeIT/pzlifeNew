<?php

namespace app\common\db\product;

use app\common\model\Coupon;
use app\common\model\Hd;
use app\common\model\HdGoods;
use app\common\model\CouponHd;
use app\common\model\CouponHdRelation;
use app\common\model\UserCoupon;

class DbCoupon {
    public function addCouponHd($data) {
        $couponHd = new CouponHd();
        $couponHd->save($data);
        return $couponHd->id;
    }

    public function updateCouponHd($data, $id) {
        $couponHd = new CouponHd();
        $couponHd->save($data, ['id' => $id]);
    }

    public function deleteCouponHd($id) {
        return CouponHd::destroy($id);
    }

    public function addCoupon($data) {
        $coupon = new Coupon();
        $coupon->save($data);
        return $coupon->id;
    }

    public function updateCoupon($data, $id) {
        $coupon = new Coupon();
        return $coupon->save($data, ['id' => $id]);
    }

    public function addCouponHdRelation($data) {
        $couponHdRelation = new CouponHdRelation();
        $couponHdRelation->save($data);
        return $couponHdRelation->id;
    }

    public function deleteCouponHdRelation($id) {
        return CouponHdRelation::destroy($id, true);
    }

    public function addUserCoupon($data) {
        $userCoupon = new UserCoupon();
        $userCoupon->save($data);
        return $userCoupon->id;
    }

    public function updateUserCoupon($data, $id) {
        $userCoupon = new UserCoupon();
        return $userCoupon->save($data, ['id' => $id]);
    }

    public function deleteCoupon($id) {
        return Coupon::destroy($id);
    }

    public function getCouponByRelation($where, $offset, $pageNum) {
        $result = CouponHd::field('id,title,content')->with(['coupons' => function ($query) use ($offset, $pageNum) {
            $query->field('pz_coupon.id,price,gs_id,level,title,days')->limit($offset, $pageNum);
        }])->where($where)->select()->toArray();
        if (!empty($result)) {
            return $result[0];
        }
        return $result;
    }

    public function getCouponHdByRelation($where, $offset, $pageNum) {
        return Coupon::field('id,price,gs_id,level,title,days')->with(['couponsHd' => function ($query) use ($offset, $pageNum) {
            $query->field('pz_coupon_hd.id,title,content')->limit($offset, $pageNum);
        }])->where($where)->select()->toArray()[0];
    }

    public function __call($name, $arguments) {
        // TODO: Implement __call() method.
        if (strpos($name, 'count') !== false) {
            $name = str_replace('count', '', $name);
            if (!class_exists('\app\\common\\model\\' . $name)) {
                return false;
            }
            $where = empty($arguments[0]) ? [] : $arguments[0];
            return $this->countNum($name, $where);
        }
        $name = str_replace('get', '', $name);
        if (!class_exists('\app\\common\\model\\' . $name)) {
            return false;
        }
        $where   = empty($arguments[0]) ? [] : $arguments[0];
        $field   = empty($arguments[1]) ? '*' : $arguments[1];
        $row     = empty($arguments[2]) ? false : $arguments[2];
        $orderBy = empty($arguments[3]) ? '' : $arguments[3];
        $limit   = empty($arguments[4]) ? '' : $arguments[4];
        return $this->getList($name, $where, $field, $row, $orderBy, $limit);
    }

    private function getList($name, $where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = call_user_func_array(['app\\common\\model\\' . $name, 'field'], [$field]);
        $obj = $obj->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    private function countNum($name, $where) {
        $obj = call_user_func_array(['app\\common\\model\\' . $name, 'where'], [$where]);
        return $obj->count();
    }

    public function getHd( $where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Hd::field($field);
        $obj = $obj->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function getHdCount($where) {
        $obj = Hd::where($where);
        return $obj->count();
    }
    public function saveHd($data){
        $Hd = new Hd;
        $Hd->save($data);
        return $Hd->id;
    }

    public function updateHd($data, $id){
        $Hd = new Hd;
        return $Hd->save($data,['id' => $id]);
    }

    public function saveHdGoods($data){
        $HdGoods = new HdGoods;
        $HdGoods->save($data);
        return $HdGoods->id;
    }
    public function updateHdGoods($data, $id){
        $HdGoods = new HdGoods;
        return $HdGoods->save($data,['id' => $id]);
    }

    public function getHdGoods( $where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = HdGoods::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function countgetHdGoods($where){
        $obj = HdGoods::where($where);
        return $obj->count();
    }

    public function sumgetHdGoods($where,$field){
        $obj = HdGoods::where($where);
        return $obj->sum($field);
    }
}
