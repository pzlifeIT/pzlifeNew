<?php

namespace app\admin\controller;

use app\admin\AdminController;
class Spec extends AdminController
{
    /**
     * @api              {post} / 属性列表包含三级分类，一级规格，二级属性
     * @apiDescription   getSpecList
     * @apiGroup         admin_spec
     * @apiName          getSpecList
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} page_num 每页条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} data 返回数据
     * @apiSuccess (data) {Number} id 一级规格id
     * @apiSuccess (data) {Number} cate_id 三级分类id
     * @apiSuccess (data) {String} spe_name 一级规格名称
     * @apiSuccess (data) {String} category 三级分类名称
     * @apiSuccess (data) {Array} attr 二级属性数据
     * @apiSuccess (attr) {Number} id 二级属性ID
     * @apiSuccess (attr) {Number} spec_id 一级规格ID
     * @apiSuccess (attr) {String} attr_name 二级属性名称
     * @apiSampleRequest /admin/spec/getspeclist
     * @author wujunjie
     * 2018/12/25-10:07
     */
    public function getSpecList(){
        $page = trim(input("post.page"));
        $page = empty($page) ? 1 : intval($page);
        $pageNum = trim(input("post.page_num"));
        $pageNum = empty($pageNum) ? 10 : intval($pageNum);
        if (!is_numeric($page) || !is_numeric($pageNum)){
            return ["msg"=>"参数错误","code"=>3001];
        }
        $spec_data = $this->app->spec->getSpecList($page,$pageNum);
        return $spec_data;
    }



    /**
     * @api              {post} / 获取一级规格
     * @apiDescription   addAttrPage
     * @apiGroup         admin_spec
     * @apiName          addAttrPage
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} spec 可选的一级属性数据
     * @apiSuccess (spec) {Number} id 可选的一级属性id
     * @apiSuccess (spec) {String} spe_name 一级属性名称
     * @apiSuccess (spec) {Number} cate_id 一级属性所属的三级分类id
     * @apiSampleRequest /admin/spec/addattrpage
     * @author wujunjie
     * 2018/12/25-10:52
     */
    public function addAttrPage(){
        $res = $this->app->spec->addAttrPage();
        return $res;
    }

    /**
     * @api              {post} / 添加属性/规格
     * @apiDescription   saveSpecAttr
     * @apiGroup         admin_spec
     * @apiName          saveSpecAttr
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 /3002 参数错误
     * @apiParam (入参) {Number} top_id 上级id（type为1时是三级分类id/type为2时是一级属性id）
     * @apiParam (入参) {String} sa_name 添加的属性名称（一级属性名称/二级属性名称）
     * @apiParam (入参) {Number} type 添加保存类型 1是添加一级属性，2是添加二级属性
     * @apiSampleRequest /admin/spec/savespecattr
     * @author wujunjie
     * 2018/12/25-11:34
     */
    public function saveSpecAttr(){
        $top_id = trim(input("post.top_id"));
        $name = trim(input("post.sa_name"));
        $type = trim(input("post.type"));
        if ($top_id == 0){
            return ["msg"=>"top_id不能为0","code"=>3002];
        }
        if (empty(is_numeric($top_id)) || empty($name) || empty(is_numeric($type))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->saveSpecAttr($type,$top_id,$name);
        return $res;
    }

    /**
     * @api              {post} / 获取需要编辑的规格/属性数据
     * @apiDescription   editSpecPage
     * @apiGroup         admin_spec
     * @apiName          editSpecPage
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 /3002 参数错误
     * @apiSuccess (返回) {Array}  spec(type为2时是attr数据) 当前需要求改的数据 / cate(type为2时是spec数据) 关联数据
     * @apiSuccess (spec(attr)) {Number}  id 规格/属性id
     * @apiSuccess (spec(attr)) {Number}  cate_id(spec_id) 上级id type为1时是cate_id，type为2时是spec_id
     * @apiSuccess (spec(attr)) {String}  spe_name(attr_name) 规格/属性名称 type为1时是spe_name，type为2时是attr_name
     * @apiSuccess (cate(spec)) {Number}  id 分类/一级规格id type为1时是三级分类id type为2时是一级规格id
     * @apiSuccess (cate(spec)) {String}  type_name(spe_name) 分类/规格名称 type为1时是type_name 三级分类名称 type为2时是spe_name规格名称
     * @apiParam (入参) {Number} id 需要修改的数据的id
     * @apiParam (入参) {Number} type 类型 1 一级规格数据 / 2 二级属性数据
     * @apiSampleRequest /admin/spec/getEditData
     * @author wujunjie
     * 2018/12/25-14:32
     */
    public function getEditData(){
        $id = trim(input("post.id"));
        $type = trim(input("post.type"));
        if (empty(is_numeric($id)) || empty(is_numeric($type))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->getEditData($id,$type);
        return $res;
    }

    /**
     * @api              {post} / 修改属性/规格
     * @apiDescription   saveEditSpecAttr
     * @apiGroup         admin_spec
     * @apiName          saveEditSpecAttr
     * @apiSuccess (返回) {String} code 200:成功 / 3001 保存失败 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiParam (入参) {Number} id 当前属性id
     * @apiParam (入参) {String} sa_name 修改的属性名称（一级属性名称/二级属性名称）
     * @apiParam (入参) {Number} type 提交类型 1是提交保存一级属性，2是提交保存二级属性
     * @apiSampleRequest /admin/spec/saveEditSpecAttr
     * @author wujunjie
     * 2018/12/25-15:47
     */
    public function saveEditSpecAttr(){
        $id = trim(input("post.id"));
        $sa_name = trim(input("post.sa_name"));
        $type = trim(input("post.type"));
        if (empty(is_numeric($id)) || empty(is_numeric($type)) || empty($sa_name)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->saveEditSpecAttr($type,$id,$sa_name);
        return $res;
    }

    /**
     * @api              {post} / 删除
     * @apiDescription   delSpecAttr
     * @apiGroup         admin_spec
     * @apiName          delSpecAttr
     * @apiSuccess (返回) {String} code 200:成功 / 3003：无法删除 /3002 参数错误
     * @apiSuccess (返回) {String}  msg 返回消息
     * @apiParam (入参) {Number} id 需要修改的数据的id
     * @apiParam (入参) {Number} type 删除类型 1删除一级属性 2删除二级属性
     * @apiSampleRequest /admin/spec/delspecattr
     * @author wujunjie
     * 2018/12/25-16:25
     */
    public function delSpecAttr(){
        $id = trim(input("post.id"));
        $type = trim(input("post.type"));
        if (empty(is_numeric($id)) || empty(is_numeric($type))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->delSpecAttr($type,$id);
        return $res;
    }

    /**
     * @api              {post} / 获取二级属性
     * @apiDescription   getAttr
     * @apiGroup         admin_spec
     * @apiName          getAttr
     * @apiSuccess (返回) {String} code 200:成功 / 3001 保存失败 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSuccess (返回) {Array} data 二级属性
     * @apiSuccess (data) {string} spec_name 一级规格名
     * @apiParam (入参) {Number} spec_id 一级规格id
     * @apiSampleRequest /admin/spec/getAttr
     * @author wujunjie
     * 2019/1/7-18:11
     */
    public function getAttr(){
        $id = trim(input("post.spec_id"));
        if (empty(is_numeric($id))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->getAttr($id);
        return $res;
    }

    /**
     * @api              {post} / 获取一级规格和二级属性
     * @apiDescription   getSpecAttr
     * @apiGroup         admin_spec
     * @apiName          getSpecAttr
     * @apiSuccess (返回) {String} code 200:成功 / 3001 保存失败 /3002 参数错误
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSuccess (返回) {Array} data 数据
     * @apiParam (入参) {Number} cate_id 三级分类id
     * @apiSampleRequest /admin/spec/getSpecAttr
     * @author wujunjie
     * 2019/1/8-15:25
     */
    public function getSpecAttr(){
        $id = trim(input("post.cate_id"));
        if (!is_numeric($id)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->getSpecAttr($id);
        return $res;
    }
}
