<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\AdminController;
class Spec extends AdminController
{
    /**
     * @api              {post} / 属性列表
     * @apiDescription   getSpecList
     * @apiGroup         admin_spec
     * @apiName          getSpecList
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} attr 二级属性 / category 该分类所属的三级分类
     * @apiSuccess (attr) {Array} id 二级属性ID / spec_id 一级规格id / attr_name 二级属性名称
     * @apiSuccess (category) {Array} id 分类ID / type_name 分类名称
     * @apiSampleRequest /spec/getspeclist
     * @author wujunjie
     * 2018/12/25-10:07
     */
    public function getSpecList(){
        $spec_data = $this->app->spec->getSpecList();
        return $spec_data;
    }

    /**
     * @api              {post} / 添加一级属性页面
     * @apiDescription   addSpecPage
     * @apiGroup         admin_spec
     * @apiName          addSpecPage
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} cate 可选的三级分类
     * @apiSuccess (cate) {Array} id 可选的三级分类id / type_name 三级分类名称 / pid 父级分类id
     * @apiSampleRequest /spec/getspeclist
     * @author wujunjie
     * 2018/12/25-10:42
     */
    public function addSpecPage(){
        $res = $this->app->spec->addSpecPage();
        return $res;
    }

    /**
     * @api              {post} / 添加二级属性页面
     * @apiDescription   addAttrPage
     * @apiGroup         admin_spec
     * @apiName          addAttrPage
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据
     * @apiSuccess (返回) {Array} spec 可选的一级属性
     * @apiSuccess (spec) {Array} id 可选的一级属性id / spe_name 一级属性名称/ cate_id 一级属性所属的三级分类id
     * @apiSampleRequest /spec/addattrpage
     * @author wujunjie
     * 2018/12/25-10:52
     */
    public function addAttrPage(){
        $res = $this->app->spec->addAttrPage();
        return $res;
    }

    /**
     * @api              {post} / 保存添加的属性
     * @apiDescription   saveSpecAttr
     * @apiGroup         admin_spec
     * @apiName          saveSpecAttr
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 /3002 参数错误
     * @apiParam (入参) {Number} top_id 上级id（type为1时是三级分类id/type为2时是一级属性id）
     * @apiParam (入参) {String} sa_name 添加的属性名称（一级属性名称/二级属性名称）
     * @apiParam (入参) {Number} type 保存类型 1是添加一级属性，2是添加二级属性
     * @apiSampleRequest /spec/savespecattr
     * @author wujunjie
     * 2018/12/25-11:34
     */
    public function saveSpecAttr(){
        $top_id = trim(input("post.top_id"));
        $name = trim(input("post.sa_name"));
        $type = trim(input("post.type"));
        if (empty(is_numeric($top_id)) || empty($name) || empty(is_numeric($type))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->saveSpecAttr($type,$top_id,$name);
        return $res;
    }

    /**
     * @api              {post} / 编辑一级属性页面
     * @apiDescription   editSpecPage
     * @apiGroup         admin_spec
     * @apiName          editSpecPage
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 /3002 参数错误
     * @apiSuccess (返回) {Array}  spec 当前需要求改的数据 / cate 可选三级分类数据
     * @apiSuccess (返回) {spec}  id 一级规格id / cate_id 可选三级分类id / spe_name 规格名称
     * @apiSuccess (返回) {cate}  id 分类ID / pid 父级ID / type_name 分类名称
     * @apiParam (入参) {Number} id 需要修改的数据的id
     * @apiSampleRequest /spec/editspecpage
     * @author wujunjie
     * 2018/12/25-14:32
     */
    public function editSpecPage(){
        $id = trim(input("post.id"));
        if (empty(is_numeric($id))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->editSpecPage($id);
        return $res;
    }

    /**
     * @api              {post} / 编辑二级属性页面
     * @apiDescription   editAttrPage
     * @apiGroup         admin_spec
     * @apiName          editAttrPage
     * @apiSuccess (返回) {String} code 200:成功 / 3000：未获取到数据 /3002 参数错误
     * @apiSuccess (返回) {Array}  attr 当前需要求改的数据 / spec 可选一级属性数据
     * @apiSuccess (返回) {attr}  id 二级属性id / spec_id  一级属性id /attr_name 二级属性名称
     * @apiSuccess (返回) {spec}  id 一级属性id / cate_id 三级分类id /spe_name 一级属性名称
     * @apiParam (入参) {Number} id 需要修改的数据的id
     * @apiSampleRequest /spec/editattrpage
     * @author wujunjie
     * 2018/12/25-14:51
     */
    public function editAttrPage(){
        $id = trim(input("post.id"));
        if (empty(is_numeric($id))){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->editAttrPage($id);
        return $res;
    }

    /**
     * @api              {post} / 保存修改的属性
     * @apiDescription   saveEditSpecAttr
     * @apiGroup         admin_spec
     * @apiName          saveEditSpecAttr
     * @apiSuccess (返回) {String} code 200:成功 / 3001 保存失败 /3002 参数错误
     * @apiParam (入参) {Number} top_id 上级id（type为1时是三级分类id/type为2时是一级属性id）
     * @apiParam (入参) {Number} id 当前属性id
     * @apiParam (入参) {String} sa_name 修改的属性名称（一级属性名称/二级属性名称）
     * @apiParam (入参) {Number} type 保存类型 1是保存一级属性，2是保存二级属性
     * @apiSampleRequest /spec/savespecattr
     * @author wujunjie
     * 2018/12/25-15:47
     */
    public function saveEditSpecAttr(){
        $id = trim(input("post.id"));
        $top_id = trim(input("post.top_id"));
        $sa_name = trim(input("post.sa_name"));
        $type = trim(input("post.type"));
        if (empty(is_numeric($id)) || empty(is_numeric($top_id)) || empty(is_numeric($type)) || empty($sa_name)){
            return ["msg"=>"参数错误","code"=>3002];
        }
        $res = $this->app->spec->saveEditSpecAttr($type,$id,$top_id,$sa_name);
        return $res;
    }

    /**
     * @api              {post} / 删除属性
     * @apiDescription   editAttrPage
     * @apiGroup         admin_spec
     * @apiName          editAttrPage
     * @apiSuccess (返回) {String} code 200:成功 / 3003：无法删除 /3002 参数错误
     * @apiParam (入参) {Number} id 需要修改的数据的id
     * @apiParam (入参) {Number} type 删除类型 1删除一级属性 2删除二级属性
     * @apiSampleRequest /spec/editattrpage
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
}
