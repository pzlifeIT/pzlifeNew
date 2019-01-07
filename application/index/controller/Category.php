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
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /index/category/getFirstCate
     * @author wujunjie
     * 2019/1/7-9:47
     */
    public function getFirstCate(){
        $res = $this->app->category->getFirstCate();
        return $res;
    }

    public function getSecondCate(){
        $id = trim(input("post.id"));
        if (empty(is_numeric($id))){
            return ["msg"=>"参数错误","code"=>200];
        }
        $res = $this->app->category->getSecondCate($id);
        return $res;
    }
}
