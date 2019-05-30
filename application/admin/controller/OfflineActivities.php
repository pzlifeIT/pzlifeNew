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
     * @apiParam (入参) {Number} id 查询ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:线下活动列表空 / 3001:手机号格式错误 / 3002:页码和查询条数只能是数字
     * @apiSuccess (返回) {String} total 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/getOfflineActivities
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "total":"82",总记录条数
     *
     * ]
     * @author rzc
     */
    public function getOfflineActivities() {
        $page    = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));
        $id      = trim($this->request->post('id'));
        $result  = $this->app->offlineactivities->getOfflineActivities($page, $pagenum, $id);
        return $result;
    }

    /**
     * @api              {post} / 添加线下活动信息
     * @apiDescription   addOfflineActivities
     * @apiGroup         admin_OfflineActivities
     * @apiName          addOfflineActivities
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} image_path 图片
     * @apiParam (入参) {String} start_time 开始时间
     * @apiParam (入参) {String} stop_time 结束时间
     * @apiSuccess (返回) {String} code 200:成功 /  3001:title为空 / 3002:时间格式错误 / 3003:结束时间不能小于开始时间 / 3004:未接收到图片上传信息
     * @apiSuccess (返回) {String} total 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/addOfflineActivities
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "total":"82",总记录条数
     *
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
            return ['code' => '3004'];
        }
        $preg = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)?$/';
        if (empty($start_time)) {
            $start_time = time();
        } else {
            if (preg_match($preg, $start_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $start_time = strtotime($start_time);
        }
        if (empty($stop_time)) {
            return ['code' => '3002'];
        } else {
            if (preg_match($preg, $stop_time, $parts2)) {
                if (checkdate($parts2[2], $parts2[3], $parts2[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $stop_time = strtotime($stop_time);
        }
        if ($stop_time < $start_time) {
            return ['code' => '3003'];
        }
        $result = $this->app->offlineactivities->addOfflineActivities($title, $image_path, $start_time, $stop_time);
        return $result;
    }

    /**
     * @api              {post} / 修改线下活动信息
     * @apiDescription   updateOfflineActivities
     * @apiGroup         admin_OfflineActivities
     * @apiName          updateOfflineActivities
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} image_path 图片
     * @apiParam (入参) {String} start_time 开始时间
     * @apiParam (入参) {String} stop_time 结束时间
     * @apiParam (入参) {String} id id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:活动不存在 / 3001:title为空 / 3002:时间格式错误 / 3003:结束时间不能小于开始时间 / 3004:未接收到图片上传信息
     * @apiSuccess (返回) {String} total 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/updateOfflineActivities
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "total":"82",总记录条数
     *
     * ]
     * @author rzc
     */
    public function updateOfflineActivities() {
        $title      = trim($this->request->post('title'));
        $image_path = trim($this->request->post('image_path'));
        $start_time = trim($this->request->post('start_time'));
        $stop_time  = trim($this->request->post('stop_time'));
        $id         = trim($this->request->post('id'));

        $preg = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)?$/';
        if (!empty($start_time)) {
            if (preg_match($preg, $start_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $start_time = strtotime($start_time);
        }
        if (!empty($stop_time)) {
            if (preg_match($preg, $stop_time, $parts2)) {
                if (checkdate($parts2[2], $parts2[3], $parts2[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $stop_time = strtotime($stop_time);
        }

        $result = $this->app->offlineactivities->updateOfflineActivities($title, $image_path, $start_time, $stop_time, $id);
        return $result;
    }

    /**
     * @api              {post} / 获取线下活动商品
     * @apiDescription   getOfflineActivitiesGoods
     * @apiGroup         admin_OfflineActivities
     * @apiName          getOfflineActivitiesGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} active_id 线下活动ID
     * @apiParam (入参) {String} id ID
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:商品列表空 / 3001:手机号格式错误 / 3002:页码和查询条数只能是数字
     * @apiSuccess (返回) {String} total 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/getOfflineActivitiesGoods
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "total":"82",总记录条数
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
        $id        = trim($this->request->post('id'));
        $page      = is_numeric($page) ? $page : 1;
        $pagenum   = is_numeric($pagenum) ? $pagenum : 10;
        $result    = $this->app->offlineactivities->getOfflineActivitiesGoods($page, $pagenum, $active_id, $id);
        return $result;
    }

    /**
     * @api              {post} / 添加线下活动商品
     * @apiDescription   addOfflineActivitiesGoods
     * @apiGroup         admin_OfflineActivities
     * @apiName          addOfflineActivitiesGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} active_id 线下活动ID
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:该活动不存在 / 3001:该活动已过期 / 3002:商品已下架或者不存在
     * @apiSuccess (返回) {String} total 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/addOfflineActivitiesGoods
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     *
     * ]
     * @author rzc
     */
    public function addOfflineActivitiesGoods() {
        $active_id = trim($this->request->post('active_id'));
        $goods_id  = trim($this->request->post('goods_id'));
        $result    = $this->app->offlineactivities->addOfflineActivitiesGoods($active_id, $goods_id);
        return $result;
    }

    /**
     * @api              {post} / 修改线下活动商品
     * @apiDescription   updateOfflineActivitiesGoods
     * @apiGroup         admin_OfflineActivities
     * @apiName          updateOfflineActivitiesGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} active_id 线下活动ID
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} id 商品id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:该活动不存在 / 3001:该活动已过期 / 3002:商品已下架或者不存在 / 3003:参数错误
     * @apiSuccess (返回) {String} total 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/updateOfflineActivitiesGoods
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     *
     * ]
     * @author rzc
     */
    public function updateOfflineActivitiesGoods() {
        $active_id = trim($this->request->post('active_id'));
        $goods_id  = trim($this->request->post('goods_id'));
        $id        = trim($this->request->post('id'));
        if (!is_numeric($active_id) || !is_numeric($goods_id) || !is_numeric($id)) {
            return ['code' => '3004'];
        }
        $result = $this->app->offlineactivities->updateOfflineActivitiesGoods($active_id, $goods_id, $id);
        return $result;
    }

    /**
     * @api              {get} / 重新生成活动页二维码
     * @apiDescription   resetOfflineActivitiesQrcode
     * @apiGroup         admin_OfflineActivities
     * @apiName          resetOfflineActivitiesQrcode
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} id 线下活动ID
     * @apiParam (入参) {String} [uid] 活动绑定会员ID
     * @apiSuccess (返回) {String} code 200:成功 3001:con_id长度只能是28位 / 3002:缺少参数id / 3003:scene不能为空 / 3004:获取access_token失败 / 3005:未获取到access_token / 3006:生成二维码识别 / 3007:scene最大长度32 / 3008:page不能为空 / 3009:微信错误 / 3011:上传失败 / 3012 该会员不存在
     * @apiSuccess (返回) {String} total 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSampleRequest /admin/OfflineActivities/resetOfflineActivitiesQrcode
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     *
     * ]
     * @author rzc
     */
    public function resetOfflineActivitiesQrcode(){
        $id  = trim($this->request->get('id'));
        $uid  = trim($this->request->get('uid'));
        if (empty($id)) {
           return ['code' => 3002];
        }
        if (empty($uid)) {
            return ['code' =>3012];
        }
        $result = $this->app->offlineactivities->resetOfflineActivitiesQrcode( $id, $uid);
        return $result;
    }
}
