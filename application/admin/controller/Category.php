<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Category extends AdminController {
    /**
     * @api              {post} / 分类列表
     * @apiDescription   getCateList
     * @apiGroup         admin_category
     * @apiName          getCateList
     * @apiParam (入参) {Number} [type] 类型 1,启用的 / 2，停用的 / 3，所有的 (默认:1)
     * @apiParam (入参) {Number} [pid] 父级id (默认:0)
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (返回) {Number} tier 当前分类层级
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} type_name 分类名称
     * @apiSampleRequest /admin/category/getcatelist
     * @author wujunjie
     * 2018/12/24-11:43
     */
    public function getCateList() {
        $typeArr = [1, 2, 3];
        $type    = trim(input("post.type"));
        $type    = empty($type) ? 1 : intval($type);
        if (!in_array($type, $typeArr)) {
            return ['code' => 3002];
        }
        $pid = trim(input("post.pid"));
        $pid = empty($pid) ? 0 : intval($pid);
        if (!is_numeric($pid)) {
            return ['code' => 3003];
        }
        $page      = trim(input("post.page")) ?: 1;//页码
        $page      = empty($page) ? 1 : intval($page);
        $pageNum   = trim(input("post.page_num"));
        $pageNum   = empty($pageNum) ? 10 : intval($pageNum);//每页条数
        $cate_date = $this->app->category->getCateList($type, $pid, $page, $pageNum);
        return $cate_date;
    }

    /**
     * @api              {post} / 获取前两级分类
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
    public function addCatePage() {
        $page = $this->app->category->addCatePage();
        return $page;
    }

    /**
     * @api              {post} / 添加分类
     * @apiDescription   saveaddcate
     * @apiGroup         admin_category
     * @apiName          saveaddcate
     * @apiParam (入参) {Number} pid 父级分类id
     * @apiParam (入参) {String} type_name 分类名称
     * @apiParam (入参) {Number} status 状态 1启用 / 2停用
     * @apiSuccess (返回) {String} code 200:成功 / 3001:保存失败 / 3002:参数错误
     * @apiSuccess (返回) {String} msg 提示信息
     * @apiSampleRequest /admin/category/saveaddcate
     * @author wujunjie
     * 2018/12/24-14:32
     */
    public function saveAddCate() {
        $pid       = trim(input("post.pid"));
        $type_name = trim(input("post.type_name"));
        $status    = trim(input("post.status"));
        if (empty($type_name) || empty(is_numeric($status))) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $result = $this->app->category->saveAddCate($pid, $type_name, $status);
        return $result;
    }

    /**
     * @api              {post} / 获取需要编辑的分类数据
     * @apiDescription   editcatepage
     * @apiGroup         admin_category
     * @apiName          editcatepage
     * @apiParam (入参) {Number} id 当前分类id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002:参数错误
     * @apiSuccess (返回) {Array} cate_data 当前修改的数据
     * @apiSuccess (返回) {Array} cate_list 父级分类 为空则是顶级分类
     * @apiSuccess (cate_data) {Number} id 分类id
     * @apiSuccess (cate_data) {Number} pid 分类id
     * @apiSuccess (cate_data) {String} type_name 分类名称
     * @apiSuccess (cate_data) {Number} tier 层级 1 一级 / 2 二级 / 3 三级
     * @apiSuccess (cate_list) {Number} id 分类id
     * @apiSuccess (cate_list) {Number} pid 父级ID
     * @apiSuccess (cate_list) {String} type_name 分类名称
     * @apiSuccess (cate_list) {Number} tier 层级 1 一级/ 2 二级 / 3 三级
     * @apiSampleRequest /admin/category/editcatepage
     * @author wujunjie
     * 2018/12/24-14:56
     */
    public function editCatePage() {
        $id = trim(input("post.id"));
        if (empty(is_numeric($id))) {
            return ["msg" => "参数错误", "code" => 3002];
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
     * @apiParam (入参) {String} type_name 分类名称
     * @apiSuccess (返回) {String} code 200:成功 / 3001:保存失败 / 3002:参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/category/saveeditcate
     * @author wujunjie
     * 2018/12/24-16:56
     */
    public function saveEditCate() {
        $id        = trim(input("post.id"));
        $type_name = trim(input("post.type_name"));
        if (empty(is_numeric($id)) || empty($type_name)) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $result = $this->app->category->saveEditCate($id, $type_name);
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
    public function delCategory() {
        $id = trim(input("post.id"));
        if (empty(is_numeric($id))) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $res = $this->app->category->delCategory($id);
        return $res;
    }

    /**
     * @api              {post} / 停用/启用分类
     * @apiDescription   stopStartCate
     * @apiGroup         admin_category
     * @apiName          stopStartCate
     * @apiParam (入参) {Number} id 当前分类id
     * @apiParam (入参) {Number} type 操作类型 1 启用 /2 停用
     * @apiParam (入参) {String} type_name 分类名称
     * @apiSuccess (返回) {String} code 200:成功 / 3001:停用失败 / 3002:参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/category/stopstartcate
     * @author wujunjie
     * 2018/12/28-9:32
     */
    public function stopStartCate() {
        $id   = trim(input("post.id"));
        $type = trim(input("post.type"));//类型 1启用 2停用
        if (empty(is_numeric($id)) || empty(is_numeric($type))) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $res       = $this->app->category->stopStart($id, $type);
        $type_name = trim(input("post.type_name"));//类型 1启用 2停用
        if (empty(is_numeric($id)) || empty(is_numeric($type)) || empty($type_name)) {
            return ["msg" => "参数错误", "code" => 3002];
        }
        $res = $this->app->category->stopStart($id, $type, $type_name);
        return $res;
    }

    /**
     * @api              {post} / 获取三级分类
     * @apiDescription   addSpecPage
     * @apiGroup         admin_category
     * @apiName          addSpecPage
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} cate 可选的三级分类
     * @apiSuccess (cate) {Number} id 可选的三级分类id
     * @apiSuccess (cate) {String} type_name 三级分类名称
     * @apiSuccess (cate) {Number} pid 父级分类id
     * @apiSampleRequest /admin/spec/getthreecate
     * @author wujunjie
     * 2018/12/25-10:42
     */
    public function getThreeCate() {
        $res = $this->app->category->getThreeCate();
        return $res;
    }
}
