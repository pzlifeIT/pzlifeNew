<?php

namespace app\admin\controller;

use app\admin\AdminController;
use Config;
use Env;

class Index extends AdminController {

    public function index() {
        $res = Config::get();
        print_r($res);
        die;
        return json_encode($res);
    }

    public function hello($name = '') {
        return 'admin hello ' . $name;
    }

    /**
     * @api              {post} / 省市列表
     * @apiDescription   getProvinceCity
     * @apiGroup         admin_index
     * @apiName          getProvinceCity
     * @apiSuccess (返回) {String} code 200:成功 / 3001:省市区列表有误
     * @apiSampleRequest /index/getProvinceCity
     * @author zyr
     */
    public function getProvinceCity() {
        $result = $this->app->index->getProvinceCity();
        return $result;
    }

    /**
     * @api              {post} / 获取市级列表
     * @apiDescription   getCity
     * @apiGroup         admin_index
     * @apiName          getCity
     * @apiParam (入参) {Number} provinceId 省级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:市列表空 / 3001:省级id不存在 / 3002:省级id只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSampleRequest /index/getCity
     * @author zyr
     */
    public function getCity() {
        $provinceId = trim($this->request->post('provinceId'));
        if (!is_numeric($provinceId)) {
            return ['3002'];
        }
        $result = $this->app->index->getArea($provinceId, 2);
        return $result;
    }

    /**
     * @api              {post} / 获取区级列表
     * @apiDescription   getArea
     * @apiGroup         admin_index
     * @apiName          getArea
     * @apiParam (入参) {Number} cityId 市级id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:区列表空 / 3001:市级id不存在 / 3002:市级id只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSampleRequest /index/getArea
     * @author zyr
     */
    public function getArea() {
        $cityId = trim($this->request->post('cityId'));
        if (!is_numeric($cityId)) {
            return ['3002'];
        }
        $result = $this->app->index->getArea($cityId, 3);
        return $result;
    }
}