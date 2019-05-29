<?php

namespace app\admin\controller;

use app\admin\AdminController;
use think\Controller;

class OfflineActivities extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取线下活动信息
     * @apiDescription   getOfflineActivities
     * @apiGroup         admin_OfflineActivities
     * @apiName          getOfflineActivities
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:手机号格式错误 / 3002:页码和查询条数只能是数字
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/getOfflineActivities
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  },
     * ]
     * @author rzc
     */
    public function getOfflineActivities() {
        $page    = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));
        $result  = $this->app->offlineactivities->getOfflineActivities($page, $pagenum);
        return $result;
    }

    /**
     * @api              {post} / 添加线下活动信息
     * @apiDescription   addOfflineActivities
     * @apiGroup         admin_OfflineActivities
     * @apiName          addOfflineActivities
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:title为空 / 3002:未接收到图片上传信息
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/addOfflineActivities
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  },
     * ]
     * @author rzc
     */
    public function addOfflineActivities() {
        $title      = trim($this->request->post('title'));
        $image_path = trim($this->request->post('image_path'));
        $start_time = trim($this->request->post('start_time'));
        $stop_time  = trim($this->request->post('stop_time'));

        if (empty($title)) {
            return ['code' => 3001];
        }
        if (empty($image_path)) {
            return ['code' => '3002'];
        }
        
    }

    /**
     * @api              {post} / 获取线下活动商品
     * @apiDescription   getOfflineActivitiesGoods
     * @apiGroup         admin_OfflineActivities
     * @apiName          getOfflineActivitiesGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} active_id 线下活动ID
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:手机号格式错误 / 3002:页码和查询条数只能是数字
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/getOfflineActivitiesGoods
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  },
     * ]
     * @author rzc
     */

    public function getOfflineActivitiesGoods() {
        $active_id = trim($this->request->post('active_id'));
        $page      = trim($this->request->post('page'));
        $pagenum   = trim($this->request->post('pagenum'));
        $page      = is_numeric($page) ? $page : 1;
        $pagenum   = is_numeric($pagenum) ? $pagenum : 10;
        $result    = $this->app->offlineactivities->getOfflineActivitiesGoods($page, $pagenum, $active_id);
        return $result;
    }

}
