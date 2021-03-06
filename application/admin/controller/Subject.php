<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Subject extends AdminController
{
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 添加专题
     * @apiDescription   addSubject
     * @apiGroup         admin_subject
     * @apiName          addSubject
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} pid 父级专题id
     * @apiParam (入参) {String} subject 专题名称
     * @apiParam (入参) {Number} [status] 状态 1启用 / 2停用 (默认1)
     * @apiParam (入参) {Number} [is_integral_show] 是否显示在积分售卖区域:1,默认显示;2,显示
     * @apiParam (入参) {String} [image] 图片路径
     * @apiParam (入参) {String} [share_image] 分享图片路径
     * @apiSuccess (返回) {String} code 200:成功 / 3001:状态有误 / 3002:pid只能为数字 / 3003.专题名不能为空 / 3004.pid查不到上级专题 / 3005.专题名已存在 / 3006.图片没有上传过 / 3007:保存失败 / 3008:积分显示状态有误
     * @apiSampleRequest /admin/subject/addsubject
     * @author zyr
     */
    public function addSubject()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $statusArr = [1, 2]; //1.启用  2.停用
        $is_integral_showArr = [1, 2]; //1.启用  2.停用
        $pid       = trim($this->request->post('pid'));
        $subject   = trim($this->request->post('subject'));
        $status    = trim($this->request->post('status'));
        $is_integral_show    = trim($this->request->post('is_integral_show'));
        $image     = trim($this->request->post('image'));
        $share_image     = trim($this->request->post('share_image'));
        $status    = empty($status) ? 1 : intval($status); //默认添加时为启用
        $is_integral_show    = empty($is_integral_show) ? 1 : intval($is_integral_show); //默认添加时为启用
        if (!in_array($status, $statusArr)) {
            return ["code" => '3001'];
        }
        if (!in_array($is_integral_show, $is_integral_showArr)) {
            return ["code" => '3008'];
        }
        if (!is_numeric($pid)) {
            return ["code" => '3002'];
        }
        if (empty($subject)) {
            return ["code" => '3003'];
        }
        $result = $this->app->subject->addSubject(intval($pid), intval($status), $subject, $image, $share_image, $is_integral_show);
        $this->apiLog($apiName, [$cmsConId, $pid, $subject, $status, $image, $share_image], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改专题
     * @apiDescription   editSubject
     * @apiGroup         admin_subject
     * @apiName          editSubject
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 专题id
     * @apiParam (入参) {String} subject 分类名称
     * @apiParam (入参) {Number} status 状态 1启用 / 2停用
     * @apiParam (入参) {String} [image] 图片路径
     * @apiParam (入参) {String} [share_image] 分享图片路径
     * @apiParam (入参) {Number} [order_by] 排序
     * @apiParam (入参) {Number} [is_integral_show] 是否显示在积分售卖区域:1,默认显示;2,显示
     * @apiSuccess (返回) {String} code 200:成功 / 3001:状态有误 / 3002:id只能为数字 /3003:排序只能是数字 / 3004.专题不存在 / 3005.专题名已存在 / 3006.图片没有上传过 /3008:没提交要修改的内容 / 3008:保存失败
     * @apiSampleRequest /admin/subject/editsubject
     * @author zyr
     */
    public function editSubject()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $statusArr = [0, 1, 2]; //1.启用  2.停用
        $id        = trim($this->request->post('id'));
        $subject   = trim($this->request->post('subject'));
        $status    = trim($this->request->post('status'));
        $is_integral_show    = trim($this->request->post('is_integral_show'));
        $image     = trim($this->request->post('image'));
        $image     = trim($this->request->post('image'));
        $share_image     = trim($this->request->post('share_image'));
        $orderBy   = trim($this->request->post('order_by'));
        $status    = empty($status) ? 0 : $status;
        $orderBy   = empty($orderBy) ? 0 : $orderBy;
        if (!in_array($status, $statusArr)) {
            return ["code" => '3001'];
        }
        if (!in_array($is_integral_show, [1, 2]) && !empty($is_integral_show)) {
            return ["code" => '3008'];
        }
        if (!is_numeric($id)) {
            return ["code" => '3002'];
        }
        if (!is_numeric($orderBy)) {
            return ["code" => '3003'];
        }
        $result = $this->app->subject->editSubject(intval($id), intval($status), $subject, $image, intval($orderBy), $share_image, intval($is_integral_show));
        $this->apiLog($apiName, [$cmsConId, $id, $subject, $status, $image, $orderBy, $share_image], $result['code'], $cmsConId);
        return $result;
    }


    /**
     * @api              {post} / 所有专题
     * @apiDescription   getAllSubject
     * @apiGroup         admin_subject
     * @apiName          getAllSubject
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) stype 1:所有 2.一二级  默认:1
     * @apiSuccess (返回) {String} code 200:成功 / 3000:没有数据 / 3001:stype参数有误
     * @apiSuccess (data) {String} subject 专题名称
     * @apiSuccess (data) {Number} status 1.启用 2.停用
     * @apiSuccess (data) {Number} tier 层级
     * @apiSuccess (data) {String} subject_image 专题图片
     * @apiSuccess (data) {String} subject_share_image 专题分享图片
     * @apiSuccess (data) {Number} is_integral_show 是否显示在积分售卖区域:1,默认显示;2,显示
     * @apiSampleRequest /admin/subject/getallsubject
     * @author zyr
     */
    public function getAllSubject()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $stypeArr = [1, 2];
        $stype    = trim($this->request->post('stype'));
        $stype    = empty($stype) ? 1 : $stype;
        if (!in_array($stype, $stypeArr)) {
            return ['code' => '3001']; //stype参数有误
        }
        $result = $this->app->subject->getAllSubject(intval($stype));
        $this->apiLog($apiName, [$cmsConId, $stype], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 建立商品专题关系
     * @apiDescription   subjectGoodsAssoc
     * @apiGroup         admin_subject
     * @apiName          subjectGoodsAssoc
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {String} subject_id 专题id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:商品id必须是数字 / 3002:专题id必须是数字 /3003:商品不存在 / 3004.专题不存在 / 3005.已经关联 / 3006:保存失败
     * @apiSampleRequest /admin/subject/subjectgoodsassoc
     * @author zyr
     */
    public function subjectGoodsAssoc()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $goodsId   = trim($this->request->post('goods_id'));
        $subjectId = trim($this->request->post('subject_id'));
        if (!is_numeric($goodsId)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($subjectId)) {
            return ["code" => '3002'];
        }
        $result = $this->app->subject->subjectGoodsAssoc(intval($goodsId), intval($subjectId));
        $this->apiLog($apiName, [$cmsConId, $goodsId, $subjectId], $result['code'], $cmsConId);
        return $result;
    }


    /**
     * @api              {post} / 获取商品专题
     * @apiDescription   getGoodsSubject
     * @apiGroup         admin_subject
     * @apiName          getGoodsSubject
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} stype 获取类型 1.获取已选专题 2.获取可选专题
     * @apiSuccess (返回) {String} code 200:成功 /3000:数据为空 / 3001:商品id必须数字 / 3002:类型错误 / 3003:商品不存在
     * @apiSampleRequest /admin/subject/getgoodssubject
     * @author zyr
     */
    public function getGoodsSubject()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $stypeArr = [1, 2];
        $goodsId  = trim($this->request->post('goods_id'));
        $stype    = trim($this->request->post('stype'));
        if (!is_numeric($goodsId)) {
            return ["code" => '3001'];
        }
        if (!in_array($stype, $stypeArr)) {
            return ["code" => '3002'];
        }
        $result = $this->app->subject->getGoodsSubject(intval($goodsId), intval($stype));
        $this->apiLog($apiName, [$cmsConId, $goodsId, $stype], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取专题详情
     * @apiDescription   getSubjectDetail
     * @apiGroup         admin_subject
     * @apiName          getSubjectDetail
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} subject_id 专题id
     * @apiSuccess (返回) {String} code 200:成功 /3000:数据为空 / 3001:id必须数字
     * @apiSuccess (data) {String} subject 专题名称
     * @apiSuccess (data) {Number} status 1.启用 2.停用
     * @apiSuccess (data) {Number} tier 层级
     * @apiSuccess (data) {Number} is_integral_show 是否显示在积分售卖区域:1,默认显示;2,显示
     * @apiSuccess (data) {String} subject_image 专题图片
     * @apiSuccess (data) {String} subject_share_image 专题分享图片
     * @apiSampleRequest /admin/subject/getsubjectdetail
     * @author zyr
     */
    public function getSubjectDetail()
    {
        $apiName   = classBasename($this) . '/' . __function__;
        $cmsConId  = trim($this->request->post('cms_con_id')); //操作管理员
        $subjectId = trim($this->request->post('subject_id'));
        if (!is_numeric($subjectId)) {
            return ["code" => '3001'];
        }
        $result = $this->app->subject->getSubjectDetail(intval($subjectId));
        $this->apiLog($apiName, [$cmsConId, $subjectId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除专题
     * @apiDescription   delGoodsSubject
     * @apiGroup         admin_subject
     * @apiName          delGoodsSubject
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} subject_id 专题id
     * @apiSuccess (返回) {String} code 200:成功 /3000:数据为空 / 3001:商品id必须数字 / 3002:类型错误 / 3003:商品不存在
     * @apiSampleRequest /admin/subject/delgoodssubject
     * @author zyr
     */
    public function delGoodsSubject()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $subjectId = trim($this->request->post('subject_id'));
        if (!is_numeric($subjectId)) {
            return ["code" => '3001'];
        }
        $result = $this->app->subject->delGoodsSubject(intval($subjectId));
        $this->apiLog($apiName, [$cmsConId, $subjectId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 取消专题商品的关联
     * @apiDescription   delGoodsSubjectAssoc
     * @apiGroup         admin_subject
     * @apiName          delGoodsSubjectAssoc
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} goods_id 商品id
     * @apiParam (入参) {Number} subject_id 专题id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:商品id必须数字 / 3002:专题id必须数字 / 3003:商品和专题没有关联 / 3004:取消失败
     * @apiSampleRequest /admin/subject/delgoodssubjectassoc
     * @author zyr
     */
    public function delGoodsSubjectAssoc()
    {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $goodsId   = trim($this->request->post('goods_id'));
        $subjectId = trim($this->request->post('subject_id'));
        if (!is_numeric($goodsId)) {
            return ["code" => '3001'];
        }
        if (!is_numeric($subjectId)) {
            return ["code" => '3002'];
        }
        $result = $this->app->subject->delGoodsSubjectAssoc(intval($goodsId), intval($subjectId));
        $this->apiLog($apiName, [$cmsConId, $goodsId, $subjectId], $result['code'], $cmsConId);
        return $result;
    }
}
