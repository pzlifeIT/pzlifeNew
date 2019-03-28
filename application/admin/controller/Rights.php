<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\AdminController;

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
            return ['code' => '3001'];//手机号格式错误
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


}
