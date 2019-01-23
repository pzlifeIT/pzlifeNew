<?php

namespace app\index\controller;

use app\index\MyController;

class Category extends MyController {
    protected $beforeActionList = [
//        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'getFirstCate'],//除去getFirstCate其他方法都进行isLogin前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 分类
     * @apiDescription   getFirstCate
     * @apiGroup         index_category
     * @apiName          getFirstCate
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /index/category/getFirstCate
     * @author wujunjie
     * 2019/1/7-9:47
     */
    public function getFirstCate() {
        $res = $this->app->category->getFirstCate();
        return $res;
    }
}
