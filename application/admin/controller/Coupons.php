<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Coupons extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
//        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取优惠券活动列表
     * @apiDescription   getCouponHdList
     * @apiGroup         admin_coupons
     * @apiName          getCouponHdList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} [page] 当前页(默认1)
     * @apiParam (入参) {Int} [page_num] 每页条数(默认10)
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSuccess (返回) {Int} total 总记录数
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Int} id 优惠券活动id
     * @apiSuccess (data) {Int} status 状态 1.开启 2.关闭
     * @apiSuccess (data) {String} title 优惠券活动标题
     * @apiSuccess (data) {String} content 优惠券活动内容
     * @apiSuccess (data) {Date} create_time 创建时间
     * @apiSampleRequest /admin/coupons/getcouponhdlist
     * @return array
     * @author zyr
     */
    public function getCouponHdList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('page_num'));
        $page     = $page > 0 ? intval($page) : 1;
        $pageNum  = $pageNum > 0 ? intval($pageNum) : 10;
        $result   = $this->app->coupons->getCouponHdList(intval($page), intval($pageNum));
        $this->apiLog($apiName, [$cmsConId, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取优惠券列表
     * @apiDescription   getCouponList
     * @apiGroup         admin_coupons
     * @apiName          getCouponList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} [page] 当前页(默认1)
     * @apiParam (入参) {Int} [page_num] 每页条数(默认10)
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSuccess (返回) {Int} total 总记录数
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Int} id 优惠券活动id
     * @apiSuccess (data) {Decimal} price 优惠券金额
     * @apiSuccess (data) {Int} gs_id 商品id或专题id
     * @apiSuccess (data) {Int} level 1.单商品优惠券 2.专题优惠券
     * @apiSuccess (data) {String} title 优惠券活动标题
     * @apiSuccess (data) {Int} days 自领取后几天内有效
     * @apiSuccess (data) {Date} create_time 创建时间
     * @apiSampleRequest /admin/coupons/getcouponlist
     * @return array
     * @author zyr
     */
    public function getCouponList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('page_num'));
        $page     = $page > 0 ? intval($page) : 1;
        $pageNum  = $pageNum > 0 ? intval($pageNum) : 10;
        $result   = $this->app->coupons->getCouponList(intval($page), intval($pageNum));
        $this->apiLog($apiName, [$cmsConId, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取某个活动的优惠券列表
     * @apiDescription   getHdCoupon
     * @apiGroup         admin_coupons
     * @apiName          getHdCoupon
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} coupon_hd_id 优惠券活动id
     * @apiParam (入参) {Int} [page] 当前页(默认1)
     * @apiParam (入参) {Int} [page_num] 每页条数(默认10)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:优惠券活动id有误 / 3002:page有误 / 3003:page_num4有误
     * @apiSuccess (返回) {Int} total 优惠券总记录数
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Int} id 优惠券活动id
     * @apiSuccess (data) {String} title 优惠券活动标题
     * @apiSuccess (data) {String} content 优惠券活动内容
     * @apiSuccess (data) {Array} coupons
     * @apiSuccess (coupons) {Int} id 优惠券id
     * @apiSuccess (coupons) {Decimal} price 优惠价格
     * @apiSuccess (coupons) {Int} gs_id 商品id或专题id
     * @apiSuccess (coupons) {Int} level 1.单商品优惠券 2.专题优惠券
     * @apiSuccess (coupons) {String} title 优惠券标题
     * @apiSuccess (coupons) {Int} days 自领取后几天内有效
     * @apiSampleRequest /admin/coupons/gethdcoupon
     * @return array
     * @author zyr
     */
    public function getHdCoupon() {
        $apiName    = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $couponHdId = trim($this->request->post('coupon_hd_id'));
        $page       = trim($this->request->post('page'));
        $pageNum    = trim($this->request->post('page_num'));
        if (!is_numeric($couponHdId)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($page) && !empty($page)) {
            return ["code" => '3002'];
        }
        if (!is_numeric($pageNum) && !empty($pageNum)) {
            return ["code" => '3003'];
        }
        if (intval($couponHdId) <= 0) {
            return ["code" => '3001'];
        }
        $page    = $page > 0 ? intval($page) : 1;
        $pageNum = $pageNum > 0 ? intval($pageNum) : 10;
        $result  = $this->app->coupons->getHdCoupon(intval($couponHdId), intval($page), intval($pageNum));
        $this->apiLog($apiName, [$cmsConId, $couponHdId, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取某个优惠券的活动列表
     * @apiDescription   getHdCouponList
     * @apiGroup         admin_coupons
     * @apiName          getHdCouponList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} coupon_id 优惠券id
     * @apiParam (入参) {Int} [page] 当前页(默认1)
     * @apiParam (入参) {Int} [page_num] 每页条数(默认10)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:优惠券活动id有误 / 3002:page有误 / 3003:page_num4有误
     * @apiSuccess (返回) {Int} total 活动总记录数
     * @apiSuccess (返回) {Array} data
     * @apiSuccess (data) {Int} id 优惠券活动id
     * @apiSuccess (data) {String} title 优惠券活动标题
     * @apiSuccess (data) {String} content 优惠券活动内容
     * @apiSuccess (data) {Array} coupons
     * @apiSuccess (coupons) {Int} id 优惠券id
     * @apiSuccess (coupons) {Decimal} price 优惠价格
     * @apiSuccess (coupons) {Int} gs_id 商品id或专题id
     * @apiSuccess (coupons) {Int} level 1.单商品优惠券 2.专题优惠券
     * @apiSuccess (coupons) {String} title 优惠券标题
     * @apiSuccess (coupons) {Int} days 自领取后几天内有效
     * @apiSampleRequest /admin/coupons/gethdcouponlist
     * @return array
     * @author zyr
     */
    public function getHdCouponList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $couponId = trim($this->request->post('coupon_id'));
        $page     = trim($this->request->post('page'));
        $pageNum  = trim($this->request->post('page_num'));
        if (!is_numeric($couponId)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($page) && !empty($page)) {
            return ["code" => '3002'];
        }
        if (!is_numeric($pageNum) && !empty($pageNum)) {
            return ["code" => '3003'];
        }
        if (intval($couponId) <= 0) {
            return ["code" => '3001'];
        }
        $page    = $page > 0 ? intval($page) : 1;
        $pageNum = $pageNum > 0 ? intval($pageNum) : 10;
        $result  = $this->app->coupons->getHdCouponList(intval($couponId), intval($page), intval($pageNum));
        $this->apiLog($apiName, [$cmsConId, $couponId, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加优惠券活动
     * @apiDescription   addCouponHd
     * @apiGroup         admin_coupons
     * @apiName          addCouponHd
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title
     * @apiParam (入参) {String} content
     * @apiSuccess (返回) {String} code 200:成功 / 3001:title不能未空 / 3003:添加失败
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/coupons/addcouponhd
     * @return array
     * @author zyr
     */
    public function addCouponHd() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $title    = trim($this->request->post('title'));
        $content  = trim($this->request->post('content'));
        if (empty($title)) {
            return ["code" => '3001'];
        }
        $result = $this->app->coupons->addCouponHd($title, $content);
        $this->apiLog($apiName, [$cmsConId, $title, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改优惠券活动
     * @apiDescription   modifyCouponHd
     * @apiGroup         admin_coupons
     * @apiName          modifyCouponHd
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id
     * @apiParam (入参) {String} title
     * @apiParam (入参) {String} content
     * @apiSuccess (返回) {String} code 200:成功 / 3001:title不能未空 / 3002:参数id有误 / 3003:参数status有误 / 3004:优惠券活动id不存在 / 3005:添加失败
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/coupons/modifycouponhd
     * @return array
     * @author zyr
     */
    public function modifyCouponHd() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        $title    = trim($this->request->post('title'));
        $content  = trim($this->request->post('content'));
        if (!is_numeric($id) || intval($id) <= 0) {
            return ["code" => '3002'];
        }
        if (empty($title)) {
            return ["code" => '3001'];
        }
        $result = $this->app->coupons->modifyCouponHd($title, $content, $id);
        $this->apiLog($apiName, [$cmsConId, $id, $title, $content], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改优惠券活动的状态
     * @apiDescription   modifyCouponHdStatus
     * @apiGroup         admin_coupons
     * @apiName          modifyCouponHdStatus
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id
     * @apiParam (入参) {Int} status
     * @apiSuccess (返回) {String} code 200:成功 / 3001:参数status有误 / 3004:优惠券活动id不存在 / 3005:添加失败
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/coupons/modifycouponhdstatus
     * @return array
     * @author zyr
     */
    public function modifyCouponHdStatus() {
        $apiName   = classBasename($this) . '/' . __function__;
        $cmsConId  = trim($this->request->post('cms_con_id'));
        $id        = trim($this->request->post('id'));
        $status    = trim($this->request->post('status'));
        $statusArr = [1, 2];
        if (!in_array($status, $statusArr)) {
            return ["code" => '3001'];
        }
        $result = $this->app->coupons->modifyCouponHdStatus($status, $id);
        $this->apiLog($apiName, [$cmsConId, $id, $status], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除优惠券活动
     * @apiDescription   deleteCouponHd
     * @apiGroup         admin_coupons
     * @apiName          deleteCouponHd
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id 优惠券活动id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:参数id有误 / 3002:优惠券已绑定活动 / 3005:删除失败
     * @apiSampleRequest /admin/coupons/deletecouponhd
     * @return array
     * @author zyr
     */
    public function deleteCouponHd() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        if (!is_numeric($id) || intval($id) <= 0) {
            return ["code" => '3001'];
        }
        $result = $this->app->coupons->deleteCouponHd($id);
        $this->apiLog($apiName, [$cmsConId, $id], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加优惠券
     * @apiDescription   addCoupon
     * @apiGroup         admin_coupons
     * @apiName          addCoupon
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Decimal} price 优惠金额
     * @apiParam (入参) {Int} gs_id 商品id或专题id
     * @apiParam (入参) {Int} level 1.单商品优惠券 2.专题优惠券
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {Int} days 自领取后几天内有效
     * @apiSuccess (返回) {String} code 200:成功 / 3001:优惠券金额有误 / 3002:gs_id参数有误 / 3003:level参数有误 / 3004:标题不能为空 / 3005:days参数有误 / 3008:添加失败
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/coupons/addcoupon
     * @return array
     * @author zyr
     */
    public function addCoupon() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $price    = trim($this->request->post('price'));
        $gsId     = trim($this->request->post('gs_id'));
        $level    = trim($this->request->post('level'));
        $title    = trim($this->request->post('title'));
        $days     = trim($this->request->post('days'));
        $levelArr = [1, 2];
        if (!is_numeric($price) || $price <= 0) {
            return ["code" => '3001'];
        }
        if (!is_numeric($gsId) || intval($gsId) <= 0) {
            return ["code" => '3002'];
        }
        if (!in_array($level, $levelArr)) {
            return ["code" => '3003'];
        }
        if (empty($title)) {
            return ["code" => '3004'];
        }
        if (!is_numeric($days) || intval($days) <= 0) {
            return ["code" => '3005'];
        }
        $result = $this->app->coupons->addCoupon($price, $gsId, $level, $title, $days);
        $this->apiLog($apiName, [$cmsConId, $price, $gsId, $level, $title, $days], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改优惠券
     * @apiDescription   modifyCoupon
     * @apiGroup         admin_coupons
     * @apiName          modifyCoupon
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id 优惠券id
     * @apiParam (入参) {Decimal} price 优惠金额
     * @apiParam (入参) {Int} gs_id 商品id或专题id
     * @apiParam (入参) {Int} level 1.单商品优惠券 2.专题优惠券
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {Int} days 自领取后几天内有效
     * @apiSuccess (返回) {String} code 200:成功 / 3001:优惠券金额有误 / 3002:gs_id参数有误 / 3003:level参数有误 / 3004:标题不能为空 / 3005:days参数有误 / 3006:id参数有误 / 3007:优惠券id不存在 / 3008:添加失败
     * @apiSampleRequest /admin/coupons/modifycoupon
     * @return array
     * @author zyr
     */
    public function modifyCoupon() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        $price    = trim($this->request->post('price'));
        $gsId     = trim($this->request->post('gs_id'));
        $level    = trim($this->request->post('level'));
        $title    = trim($this->request->post('title'));
        $days     = trim($this->request->post('days'));
        $levelArr = [1, 2];
        if (!is_numeric($id) || intval($id) <= 0) {
            return ["code" => '3006'];
        }
        if (!is_numeric($price) || $price <= 0) {
            return ["code" => '3001'];
        }
        if (!is_numeric($gsId) || intval($gsId) <= 0) {
            return ["code" => '3002'];
        }
        if (!in_array($level, $levelArr)) {
            return ["code" => '3003'];
        }
        if (empty($title)) {
            return ["code" => '3004'];
        }
        if (!is_numeric($days) || intval($days) <= 0) {
            return ["code" => '3005'];
        }
        $result = $this->app->coupons->modifyCoupon($price, $gsId, $level, $title, $days, $id);
        $this->apiLog($apiName, [$cmsConId, $id, $price, $gsId, $level, $title, $days], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除优惠券
     * @apiDescription   deleteCoupon
     * @apiGroup         admin_coupons
     * @apiName          deleteCoupon
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} id 优惠券id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:参数id有误 / 3002:优惠券已绑定活动 / 3005:删除失败
     * @apiSampleRequest /admin/coupons/deletecoupon
     * @return array
     * @author zyr
     */
    public function deleteCoupon() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        if (!is_numeric($id) || intval($id) <= 0) {
            return ["code" => '3001'];
        }
        $result = $this->app->coupons->deleteCoupon($id);
        $this->apiLog($apiName, [$cmsConId, $id], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 优惠券活动绑定
     * @apiDescription   bindCouponHd
     * @apiGroup         admin_coupons
     * @apiName          bindCouponHd
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} coupon_hd_id 优惠券活动id
     * @apiParam (入参) {Int} coupon_id 优惠券id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:参数coupon_hd_id有误 / 3002:参数coupon_id有误 / 3003:优惠券不存在 / 3004:优惠券活动不存在 / 3005:活动已关联 / 3008:修改失败
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/coupons/bindcouponhd
     * @return array
     * @author zyr
     */
    public function bindCouponHd() {
        $apiName    = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $couponHdId = trim($this->request->post('coupon_hd_id'));
        $couponId   = trim($this->request->post('coupon_id'));
        if (!is_numeric($couponHdId) || intval($couponHdId) <= 0) {
            return ["code" => '3001'];
        }
        if (!is_numeric($couponId) || intval($couponId) <= 0) {
            return ["code" => '3002'];
        }
        $result = $this->app->coupons->bindCouponHd($couponHdId, $couponId);
        $this->apiLog($apiName, [$cmsConId, $couponHdId, $couponId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 优惠券活动解除绑定
     * @apiDescription   unbindCouponHd
     * @apiGroup         admin_coupons
     * @apiName          unbindCouponHd
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} coupon_hd_id 优惠券活动id
     * @apiParam (入参) {Int} coupon_id 优惠券id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:参数coupon_hd_id有误 / 3002:参数coupon_id有误 / 3003:优惠券不存在 / 3004:优惠券活动不存在 / 3005:活动未关联 / 3008:修改失败
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/coupons/unbindcouponhd
     * @return array
     * @author zyr
     */
    public function unbindCouponHd() {
        $apiName    = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $couponHdId = trim($this->request->post('coupon_hd_id'));
        $couponId   = trim($this->request->post('coupon_id'));
        if (!is_numeric($couponHdId) || intval($couponHdId) <= 0) {
            return ["code" => '3001'];
        }
        if (!is_numeric($couponId) || intval($couponId) <= 0) {
            return ["code" => '3002'];
        }
        $result = $this->app->coupons->unbindCouponHd($couponHdId, $couponId);
        $this->apiLog($apiName, [$cmsConId, $couponHdId, $couponId], $result['code'], $cmsConId);
        return $result;
    }
}