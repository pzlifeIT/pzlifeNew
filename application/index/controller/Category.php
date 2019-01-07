<?php

namespace app\index\controller;

use app\index\MyController;

class Category extends MyController
{
    /**
     * @api              {post} / 一级分类
     * @apiDescription   getFirstCate
     * @apiGroup         index_category
     * @apiName          getFirstCate
     * @apiParam (入参) {Number} [type] 类型 1,启用的 / 2，停用的 / 3，所有的 (默认:1)
     * @apiParam (入参) {Number} [pid] 父级id (默认:0)
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (返回) {Number} tier 当前分类层级
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /admin/category/getFirstCate
     * @author wujunjie
     * 2019/1/7-9:47
     */
    public function getFirstCate(){
        $res = $this->app->category->getFirstCate();
        return $res;
    }

    public function getSecondCate(){
        $res = $this->app->category->getSecondCate();
        return $res;
    }
}
