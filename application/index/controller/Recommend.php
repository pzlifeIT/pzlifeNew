<?php

namespace app\index\controller;

use app\index\MyController;

class Recommend extends MyController {
    protected $beforeActionList = [
//        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'getRecommend'],//除去getFirstCate其他方法都进行isLogin前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 首页显示内容
     * @apiDescription   getRecommend
     * @apiGroup         index_Recommend
     * @apiName          getRecommend
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /index/Recommend/getRecommend
     * @author rzc
     * 2019/3/6
     */
    public function getRecommend() {
        $res = $this->app->recommend->getRecommend();
        return $res;
    }
}
