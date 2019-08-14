<?php

namespace app\admin\controller;

use app\admin\AdminController;

class Label extends AdminController {
    protected $beforeActionList = [
        // 'isLogin', //所有方法的前置操作
        'isLogin' => ['except' => 'searchLabel'], //除去login其他方法都进行isLogin前置操作
        // 'three'   => ['only' => 'hello,data'], //只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 给商品添加标签
     * @apiDescription   addLabelToGoods
     * @apiGroup         admin_label
     * @apiName          addLabelToGoods
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} label_name
     * @apiParam (入参) {int} goods_id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:标签名不能未空 / 3002:商品id必须为数字 / 3003:商品不存在 / 3004:标签已关联该商品 / 3005:标签长度不能超过50 /3006:添加失败
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /admin/label/addlabeltogoods
     * @return array
     * @author zyr
     */
    public function addLabelToGoods() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $labelName = trim($this->request->post('label_name')); //标签名称
        $goodsId   = trim($this->request->post('goods_id')); //商品id
        if (empty($labelName)) {
            return ['code' => '3001']; //标签名不能未空
        }
        if (!is_numeric($goodsId)) {
            return ['code' => '3002']; //商品id必须为数字
        }
        $goodsId = intval($goodsId);
        if ($goodsId <= 0) {
            return ['code' => '3002']; //商品id必须为数字
        }
        if (mb_strlen($labelName, 'utf8') > 50) {
            return ['code' => '3005'];//标签长度不能超过50
        }
        $result = $this->app->label->addLabelToGoods($labelName, $goodsId);
        $this->apiLog($apiName, [$cmsConId, $labelName, $goodsId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 搜索标签
     * @apiDescription   searchLabel
     * @apiGroup         admin_label
     * @apiName          searchLabel
     * @apiParam (入参) {String} search_content 搜索的内容
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSuccess (返回) {Array} data 返回消息
     * @apiSuccess (data) {Int} label_id 标签id
     * @apiSuccess (data) {String} label_name 标签名称
     * @apiSampleRequest /admin/label/searchlabel
     * @return array
     * @author zyr
     */
    public function searchLabel() {
        $apiName  = classBasename($this) . '/' . __function__;
        $searchContent = trim($this->request->post('search_content')); //搜索内容
        $result        = $this->app->label->searchLabel(strtolower($searchContent));
        $this->apiLog($apiName, [$searchContent], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 商品标签列表
     * @apiDescription   goodsLabelList
     * @apiGroup         admin_label
     * @apiName          goodsLabelList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} goods_id 商品id
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSuccess (返回) {Array} data 返回消息
     * @apiSuccess (data) {Int} label_id 标签id
     * @apiSuccess (data) {String} label_name 标签名称
     * @apiSampleRequest /admin/label/goodslabellist
     * @return array
     * @author zyr
     */
    public function goodsLabelList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $goodsId  = trim($this->request->post('goods_id')); //商品id
        if (!is_numeric($goodsId)) {
            return ['code' => '3002']; //商品id必须为数字
        }
        $goodsId = intval($goodsId);
        if ($goodsId <= 0) {
            return ['code' => '3002']; //商品id必须为数字
        }
        $result = $this->app->label->goodsLabelList($goodsId);
        $this->apiLog($apiName, [$cmsConId, $goodsId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 删除商品标签
     * @apiDescription   labelDel
     * @apiGroup         admin_label
     * @apiName          labelDel
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} goods_id 商品id
     * @apiParam (入参) {Int} label_id 标签id
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSampleRequest /admin/label/labeldel
     * @return array
     * @author zyr
     */
    public function labelDel() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $goodsId  = trim($this->request->post('goods_id')); //商品id
        $labelId  = trim($this->request->post('label_id')); //标签id
        if (!is_numeric($labelId)) {
            return ['code' => '3001']; //标签id必须为数字
        }
        $labelId = intval($labelId);
        if ($labelId <= 0) {
            return ['code' => '3001']; //标签id必须为数字
        }
        if (!is_numeric($goodsId)) {
            return ['code' => '3002']; //商品id必须为数字
        }
        $goodsId = intval($goodsId);
        if ($goodsId <= 0) {
            return ['code' => '3002']; //商品id必须为数字
        }
        $result = $this->app->label->labelDel($labelId, $goodsId);
        $this->apiLog($apiName, [$cmsConId, $goodsId, $labelId], $result['code'], $cmsConId);
        return $result;
    }
}