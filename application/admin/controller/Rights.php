<?php

namespace app\admin\controller;

use app\admin\AdminController;
use think\Controller;

class Rights extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 创建合伙人BOSS分享钻石会员机会
     * @apiDescription   creatBossShareDiamondvip
     * @apiGroup         admin_Rights
     * @apiName          creatBossShareDiamondvip
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} mobile 会员手机号
     * @apiParam (入参) {String} linkman 会员姓名
     * @apiParam (入参) {Number} stock 库存
     * @apiParam (入参) {Numeric} coupon_money 被分享用户将获得活动商票
     * @apiParam (入参) {Number} redmoney_status 商票状态 1:直接领取 2:分享激活后获得
     * @apiParam (入参) {Number} type 使用类型 1:分享使用 2:绑定二维码链接
     * @apiSuccess (返回) {String} code 200:成功 / 3001:手机号格式错误 / 3002:stock或者coupon_money或者redmoney_status或者type必须是数字 / 3005:超出金额设置范围
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 用户ID
     * @apiSampleRequest /admin/Rights/creatBossShareDiamondvip
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "msg":"创建成功"
     * ]
     * @author rzc
     */
    public function creatBossShareDiamondvip() {
        $mobile          = trim($this->request->post('mobile'));
        $linkman         = trim($this->request->post('linkman'));
        $stock           = trim($this->request->post('stock'));
        $coupon_money    = trim($this->request->post('coupon_money'));
        $redmoney_status = trim($this->request->post('redmoney_status'));
        $type            = trim($this->request->post('type'));
        if (checkMobile($mobile) === false) {
            return ['code' => '3001']; //手机号格式错误
        }
        if (!is_numeric($stock) || !is_numeric($coupon_money) || !is_numeric($redmoney_status) || !is_numeric($type)) {
            return ['code' => '3002'];
        }
        if ($redmoney_status > 1000000.00) {
            return ['code' => '3005'];
        }
        $result = $this->app->rights->creatBossShareDiamondvip($mobile, $linkman, intval($stock), intval($redmoney_status), intval($type), $coupon_money);
        return $result;
    }

    /**
     * @api              {post} / 获取合伙人BOSS分享钻石会员机会
     * @apiDescription   getBossShareDiamondvip
     * @apiGroup         admin_Rights
     * @apiName          getBossShareDiamondvip
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 每页显示数量
     * @apiSuccess (返回) {String} code 200:成功 / 3001:page或者pagenum必须是数字 / 3002: /
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 用户ID
     * @apiSampleRequest /admin/Rights/getBossShareDiamondvip
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  }
     * ]
     * @author rzc
     */
    public function getBossShareDiamondvip() {
        $page    = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));

        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;
        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => 3001, 'msg' => '参数必须是数字'];
        }

        $result = $this->app->rights->getBossShareDiamondvip($page, $pagenum);
        return $result;
    }

    /**
     * @api              {post} / 审核钻石卡分享机会
     * @apiDescription   passBossShareDiamondvip
     * @apiGroup         admin_Rights
     * @apiName          passBossShareDiamondvip
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 分享钻石会员机会ID
     * @apiParam (入参) {Number} status 分享钻石会员机会ID 1:审核通过 2:不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id和status必须是数字 / 3002: /
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 用户ID
     * @apiSampleRequest /admin/Rights/passBossShareDiamondvip
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  }
     * ]
     * @author rzc
     */
    public function passBossShareDiamondvip() {
        $id     = trim($this->request->post('id'));
        $status = trim($this->request->post('status'));
        if (!is_numeric($id) || !is_numeric($status)) {
            return ['code' => 3001];
        }
        $result = $this->app->rights->passBossShareDiamondvip($id, $status);
        return $result;
    }

    /**
     * @api              {post} / 邀请会员成为Boss列表
     * @apiDescription   getShopApplyList
     * @apiGroup         admin_Rights
     * @apiName          getShopApplyList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [page] 当前页 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiParam (入参) {String} [target_uid] 被邀请人id
     * @apiParam (入参) {String} [target_uname] 被邀请人昵称
     * @apiParam (入参) {String} [target_nickname] 被邀请人姓名
     * @apiParam (入参) {Number} [target_sex] 被邀请人性别 1.男2.女
     * @apiParam (入参) {String} [target_mobile] 被邀请人手机号
     * @apiParam (入参) {String} [target_idcard] 被邀请人身份证号
     * @apiParam (入参) {String} [refe_uid] 邀请人id
     * @apiParam (入参) {String} [refe_uname] 邀请人昵称
     * @apiParam (入参) {Number} [shop_id] 邀请人门店id
     * @apiParam (入参) {Number} [refe_type] 邀请人门店id
     * @apiParam (入参) {Number} [status] 申请进度 1.提交申请  2:财务审核通过 3:经理审核通过 4 审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:page手机号校验失败 / 3002:page_num和page只能为数字 / 3003:target_idcard校验失败 / 3004:status参数必须为数字 / 3005:
     * @apiSuccess (返回) {Number} total 条数
     * @apiSuccess (返回) {Array} data 返回数据
     * @apiSuccess (data) {Number} id id
     * @apiSuccess (data) {String} target_uid 被邀请人id
     * @apiSuccess (data) {String} target_uname 被邀请人昵称
     * @apiSuccess (data) {String} target_nickname 被邀请人姓名
     * @apiSuccess (data) {String} target_sex 被邀请人性别 1.男2.女
     * @apiSuccess (data) {String} target_mobile 被邀请人手机号
     * @apiSuccess (data) {String} target_idcard 被邀请人身份证号
     * @apiSuccess (data) {String} refe_uid 邀请人id
     * @apiSuccess (data) {String} refe_uname 邀请人昵称
     * @apiSuccess (data) {String} shop_id 邀请人门店id
     * @apiSuccess (data) {String} refe_type 被邀请成为店主类型1.创业店主2.boss合伙人
     * @apiSuccess (data) {Number} status 申请进度 1.提交申请  2:财务审核通过 3:经理审核通过 4 审核不通过
     * @apiSampleRequest /admin/Rights/getShopApplyList
     * @author wujunjie
     * 2018/12/26-18:04
     */
    public function getShopApplyList() {
        $page            = trim(input("post.page"));
        $pageNum         = trim(input("post.page_num"));
        $status          = trim(input("post.status"));
        $target_uid      = trim(input("post.target_uid"));
        $target_uname    = trim(input("post.target_uname"));
        $target_nickname = trim(input("post.target_nickname"));
        $target_sex      = trim(input("post.target_sex"));
        $target_mobile   = trim(input("post.target_mobile"));
        $target_idcard   = trim(input("post.target_idcard"));
        $refe_uid        = trim(input("post.refe_uid"));
        $refe_uname      = trim(input("post.refe_uname"));
        $shop_id         = trim(input("post.shop_id"));
        $refe_type       = trim(input("post.refe_type"));
        $status          = trim(input("post.status"));

        $page    = empty($page) ? 1 : $page;
        $pageNum = empty($pageNum) ? 10 : $pageNum;
        // $status     = empty($status) ? 1 : $status;
        $target_uid = deUid($target_uid);
        $refe_uid   = deUid($refe_uid);
        if (!empty($target_mobile)) {
            if (checkIdcard($target_mobile) == false) {
                return ['code' => '3001'];
            }
        }
        if (!is_numeric($page) || !is_numeric($pageNum)) {
            return ['code' => '3002'];
        }
        if (!empty($target_idcard)) {
            if (checkIdcard($target_idcard) == false) {
                return ['code' => '3003'];
            }
        }
        // if (!is_numeric($status)) {
        //     return ['code' => '3004'];
        // }
        $result = $this->app->rights->getShopApplyList($page, $pageNum, $status, $target_uid, $target_uname, $target_nickname, $target_sex, $target_mobile, $target_idcard, $refe_uid, $refe_uname, $shop_id, $refe_type);
        return $result;
    }

    /**
     * @api              {post} / 审核申请开通BOSS
     * @apiDescription   auditShopApply
     * @apiGroup         admin_Rights
     * @apiName          auditShopApply
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 分享钻石会员机会ID
     * @apiParam (入参) {Number} status 申请进度  2:财务审核通过 3:经理审核通过 4 审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id和status必须是数字 / 3002:id为空  / 3003:传入status错误 / 3004:错误的申请状态 / 3005:已审核的无法再次进行相同的审核结果 / 3006:审核失败 / 3007:没有操作权限
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 用户ID
     * @apiSampleRequest /admin/Rights/auditShopApply
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     *
     * ]
     * @author rzc
     */
    public function auditShopApply() {
        $cmsConId = trim($this->request->post('cms_con_id'));
        $id       = trim($this->request->post('id'));
        $status   = trim($this->request->post('status'));
        $message  = trim($this->request->post('message'));
        if (empty($id)) {
            return ['code' => '3002'];
        }
        if (empty($status)) {
            return ['code' => '3003'];
        }
        if (!is_numeric($id) || !is_numeric($status)) {
            return ['code' => '3001'];
        }
        if (!in_array($status, [2, 3, 4])) {
            return ['code' => '3004'];
        }
        $result = $this->app->rights->auditShopApply($id, $status, $message, $cmsConId);
        return $result;

    }

    /**
     * @api              {post} / 兼职网推奖励金列表
     * @apiDescription   getDiamondvipNetPush
     * @apiGroup         admin_Rights
     * @apiName          getDiamondvipNetPush
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 分享钻石会员机会ID
     * @apiParam (入参) {Number} status 申请进度  2:财务审核通过 3:经理审核通过 4 审核不通过
     * @apiSuccess (返回) {String} code 200:成功 / 3001:id和status必须是数字 / 3002:id为空  / 3003:传入status错误 / 3004:错误的申请状态 / 3005:已审核的无法再次进行相同的审核结果 / 3006:审核失败 / 3007:没有操作权限
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 用户ID
     * @apiSampleRequest /admin/Rights/getDiamondvipNetPush
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     *
     * ]
     * @author rzc
     */
    public function getDiamondvipNetPush() {
        $page            = trim(input("post.page"));
        $pageNum         = trim(input("post.page_num"));
        $status          = trim(input("post.status"));
        $page    = empty($page) ? 1 : $page;
        $pageNum = empty($pageNum) ? 10 : $pageNum;
    }
}
