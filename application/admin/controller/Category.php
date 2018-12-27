<?php

namespace app\admin\controller;

use app\admin\AdminController;
class Category extends AdminController
{
    /**
     * @api              {post} / 分类列表
     * @apiDescription   getCateList
     * @apiGroup         admin_category
     * @apiName          getCateList
     * @apiParam (入参) {Number} type 类型 1,启用的 / 2，停用的 / 3，所有的
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {Number} id 分类id
     * @apiSuccess (data) {Number}  pid 父级ID / type_name 分类名称 / tier 等级 / _child 子分类数据
     * @apiSuccess (data) {Number}  pid 父级ID
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSuccess (data) {Number} tier 等级 1 一级 / 2 二级 / 3 三级
     * @apiSuccess (data) {Array} _child 子分类
     * @apiSuccess (_child) {Number} id 分类id
     * @apiSuccess (_child) {Number}  pid 父级ID / type_name 分类名称 / tier 等级 / _child 子分类数据
     * @apiSuccess (_child) {Number}  pid 父级ID
     * @apiSuccess (_child) {String} type_name 分类名称
     * @apiSuccess (_child) {Number} tier 等级 1 一级 / 2 二级 / 3 三级
     * @apiSuccess (_child) {Array} _child 子分类
     * @apiSampleRequest /admin/category/getcatelist
     * @author wujunjie
     * 2018/12/24-11:43
     */
    public function getCateList(){
        $type = trim(input("post.type"));
        if (empty(is_numeric($type))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $cate_date = $this->app->category->getCateList($type);
        return $cate_date;
    }

    /**
     * @api              {post} / 添加分类
     * @apiDescription   addCatePage
     * @apiGroup         admin_category
     * @apiName          addCatePage
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {Number} id 分类id
     * @apiSuccess (data) {Number} pid 父级ID
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSuccess (data) {Array}  _child 子分类数据
     * @apiSuccess (_child) {Number} id 分类id
     * @apiSuccess (_child) {Number} pid 父级ID
     * @apiSuccess (_child) {String} type_name 分类名称
     * @apiSampleRequest /admin/category/addcatepage
     * @author wujunjie
     * 2018/12/24-13:58
     */
    public function addCatePage(){
        $page = $this->app->category->addCatePage();
        return $page;
    }

    /**
     * @api              {post} / 提交添加
     * @apiDescription   saveaddcate
     * @apiGroup         admin_category
     * @apiName          saveaddcate
     * @apiParam (入参) {Number} pid 父级分类id
     * @apiParam (入参) {String} type_name 分类名称
     * @apiSuccess (返回) {String} code 200:成功 / 3001:保存失败 / 3002:参数错误
     * @apiSuccess (返回) {String} msg 提示信息
     * @apiSampleRequest /admin/category/saveaddcate
     * @author wujunjie
     * 2018/12/24-14:32
     */
    public function saveAddCate(){
        $pid = trim(input("post.pid"));
        $type_name = trim(input("post.type_name"));
        if (empty(is_numeric($pid)) || empty($type_name)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $result = $this->app->category->saveAddCate($pid,$type_name);
        return $result;
    }

    /**
     * @api              {post} / 编辑分类
     * @apiDescription   editcatepage
     * @apiGroup         admin_category
     * @apiName          editcatepage
     * @apiParam (入参) {Number} id 当前分类id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002:参数错误
     * @apiSuccess (返回) {Array} cate_data 当前修改的数据
     * @apiSuccess (返回) {Array} cate_list 分类列表
     * @apiSuccess (cate_data) {Number} id 分类id
     * @apiSuccess (cate_data) {Number} pid 分类id
     * @apiSuccess (cate_data) {String} type_name 分类名称
     * @apiSuccess (cate_data) {Number} tier 层级 1 一级 / 2 二级 / 3 三级
     * @apiSuccess (cate_list) {Number} id 分类id
     * @apiSuccess (cate_list) {Number} pid 父级ID
     * @apiSuccess (cate_list) {String} type_name 分类名称
     * @apiSuccess (cate_list) {Number} tier 层级 1 一级/ 2 二级 / 3 三级
     * @apiSuccess (cate_list) {Number} _disable 判断分类是否可以作为当前修改的分类的父级 1可选/2不可选
     * @apiSampleRequest /admin/category/editcatepage
     * @author wujunjie
     * 2018/12/24-14:56
     */
    public function editCatePage(){
        $id = trim(input("post.id"));
        if (empty(is_numeric($id))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $result = $this->app->category->editCatePage($id);
        return $result;
    }

    /**
     * @api              {post} / 提交编辑
     * @apiDescription   saveeditcate
     * @apiGroup         admin_category
     * @apiName          saveeditcate
     * @apiParam (入参) {Number} id 当前分类id
     * @apiParam (入参) {Number} pid 当前分类父级id
     * @apiParam (入参) {String} type_name 分类名称
     * @apiSuccess (返回) {String} code 200:成功 / 3001:保存失败 / 3002:参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/category/saveeditcate
     * @author wujunjie
     * 2018/12/24-16:56
     */
    public function saveEditCate(){
        $id = trim(input("post.id"));
        $pid = trim(input("post.pid"));
        $type_name = trim(input("post.type_name"));
        if (empty(is_numeric($id)) || empty($type_name) || !is_numeric($pid)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $result = $this->app->category->saveEditCate($id,$pid,$type_name);
        return $result;
    }

    /**
     * @api              {post} / 删除分类
     * @apiDescription   delcategory
     * @apiGroup         admin_category
     * @apiName          delcategory
     * @apiParam (入参) {Number} id 当前分类id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:删除失败 / 3002:参数错误 / 3003:不能删除
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/category/delcategory
     * @author wujunjie
     * 2018/12/24-17:18
     */
    public function delCategory(){
        $id = trim(input("post.id"));
        if (empty(is_numeric($id))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->category->delCategory($id);
        return $res;
    }
}
