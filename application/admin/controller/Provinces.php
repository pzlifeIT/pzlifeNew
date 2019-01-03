<?php

namespace app\admin\controller;

use app\admin\AdminController;
use Config;
use Env;

class Provinces extends AdminController {
    /**
     * @api              {post} / 省市列表
     * @apiDescription   getProvinceCity
     * @apiGroup         admin_provinces
     * @apiName          getProvinceCity
     * @apiSuccess (返回) {String} code 200:成功 / 3001:省市区列表有误
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /admin/provinces/getProvinceCity
     * @author zyr
     */
    public function getProvinceCity() {
        $result = $this->app->provinces->getProvinceCity();
//        $this->addLog($result['code'],__function__);//接口请求日志
        return $result;
    }

    /**
     * @api              {post} / 获取市级列表
     * @apiDescription   getCity
     * @apiGroup         admin_provinces
     * @apiName          getCity
     * @apiParam (入参) {Number} provinceId 省级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:市列表空 / 3001:省级id不存在 / 3002:省级id只能是数字
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /admin/provinces/getCity
     * @author zyr
     */
    public function getCity() {
        $provinceId = trim($this->request->post('provinceId'));
        if (!is_numeric($provinceId)) {
            return ['3002'];
        }
        $provinceId = intval($provinceId);
        $result     = $this->app->provinces->getArea($provinceId, 2);
        return $result;
    }

    /**
     * @api              {post} / 获取区级列表
     * @apiDescription   getArea
     * @apiGroup         admin_provinces
     * @apiName          getArea
     * @apiParam (入参) {Number} cityId 市级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:区列表空 / 3001:市级id不存在 / 3002:市级id只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /admin/provinces/getArea
     * @author zyr
     */
    public function getArea() {
        $cityId = trim($this->request->post('cityId'));
        if (!is_numeric($cityId)) {
            return ['3002'];
        }
        $cityId = intval($cityId);
        $result = $this->app->provinces->getArea($cityId, 3);
        return $result;
    }

    /**
     * 写入api日志
     * @param $code 接口返回code
     * @param $func 调用的接口方法
     * @param string $name
     */
    private function addLog($code,$func, $name = '') {
        $this->app->adminLog->apiRequestLog($this->classBasename(__class__) . '/' . $func, $code, $this->controllerBaseName(__FILE__), $name);
    }
}