<?php

namespace app\index\controller;

use app\index\MyController;

class Cart extends MyController {

    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        // 'isLogin' => ['except' => 'login,quickLogin,register,resetPassword,sendVercode,loginUserByOpenid'],//除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取购物车商品
     * @apiDescription   getUserCart
     * @apiGroup         index_Cart
     * @apiName          getUserCart
     * @apiParam (入参) {Number} con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} valid 有效商品
     * @apiSuccess (valid) {String} id 商品ID
     * @apiSuccess (valid) {String} supplier_id 供应商ID
     * @apiSuccess (valid) {String} cate_id 分类ID
     * @apiSuccess (valid) {String} goods_name 商品名称
     * @apiSuccess (valid) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (valid) {String} title 主标题
     * @apiSuccess (valid) {String} subtitle 副标题
     * @apiSuccess (valid) {String} image 商品标题图
     * @apiSuccess (返回) {Array} failure 失效效商品
     * @apiSuccess (failure) {String} id 商品ID
     * @apiSuccess (failure) {String} supplier_id 供应商ID
     * @apiSuccess (failure) {String} cate_id 分类ID
     * @apiSuccess (failure) {String} goods_name 商品名称
     * @apiSuccess (failure) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (failure) {String} title 主标题
     * @apiSuccess (failure) {String} subtitle 副标题
     * @apiSuccess (failure) {String} image 商品标题图
     * @apiSampleRequest /index/cart/getUserCart
     * @author rzc
     */
    public function getUserCart() {
        $conId = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        // RVYvaEw2Wk1TeXlnUjdlb2RHc3ZEZz09
        // $uid = $this->app->user->deUid($paramUid);
        $result = $this->app->cart->getUserCart($conId);
        return $result;

    }

    /**
     * @api              {post} / 添加购物车商品
     * @apiDescription   addUserCart
     * @apiGroup         index_Cart
     * @apiName          addUserCart
     * @apiParam (入参) {Number} con_id 请求uid
     * @apiParam (入参) {Number} goods_skuid 商品SKU_id
     * @apiParam (入参) {Number} goods_num 数量
     * @apiParam (入参) {Number} parent_id 分享者id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:uid长度只能是32位 / 3002:缺少参数
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} cate_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSampleRequest /index/cart/addUserCart
     * @author rzc
     */
    public function addUserCart() {
        $goods_skuid = trim($this->request->post('goods_skuid'));
        $goods_num   = trim($this->request->post('goods_num'));
        $parent_id    = trim($this->request->post('parent_id'));
        // $track_id    = $track_id ? $track_id : 1;
        $conId       = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        // 5a3f0e0196fdebb4737c0851849c2005

        if (empty($goods_skuid)) {
            return ['code' => '3009', 'msg' => '缺少参数:uid或者商品SKUID'];
        }
        if (!is_numeric($goods_skuid)) {
            return ['code' => '3003', 'msg' => '商品SKU_ID必须是数字'];
        }
        if (!is_numeric($goods_num) || $goods_num < 1) {
            return ['code' => '3004', 'msg' => '购买数量必须是数字'];
        }
        $parent_id = empty(deUid($parent_id)) ? 1 : deUid($parent_id);
        $result = $this->app->cart->addCartGoods($conId, intval($goods_skuid), intval($goods_num), $parent_id);
        return $result;
    }

    /**
     * @api              {post} / 修改购物车商品数量
     * @apiDescription   updateUserCart
     * @apiGroup         index_Cart
     * @apiName          updateUserCart
     * @apiParam (入参) {Number} con_id 请求uid
     * @apiParam (入参) {Number} goods_skuid 商品SKU_id
     * @apiParam (入参) {Number} goods_num 数量(数量为0则为删除此商品)
     * @apiParam (入参) {Number} track_id 店铺ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:uid长度只能是32位 / 3002:缺少参数
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} cate_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSampleRequest /index/cart/updateUserCart
     * @author rzc
     */
    public function updateUserCart() {
        $goods_skuid = trim($this->request->post('goods_skuid'));
        $goods_num   = trim($this->request->post('goods_num'));
        $track_id    = trim($this->request->post('track_id'));
        $track_id    = $track_id ? $track_id : 1;
        $conId       = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        // RVYvaEw2Wk1TeXlnUjdlb2RHc3ZEZz09
        // $uid = $this->app->user->enUid($paramUid);
        // echo $uid;die;
        if (empty($goods_skuid)) {
            return ['code' => '3009', 'msg' => '缺少参数:商品SKUID'];
        }
        if (!is_numeric($goods_skuid)) {
            return ['code' => '3003', 'msg' => '商品SKU_ID必须是数字'];
        }
        if (!is_numeric($goods_num)) {
            return ['code' => '3004', 'msg' => '购买数量必须是数字'];
        }
        if (!is_numeric($track_id)) {
            return ['code' => '3005', 'msg' => '足迹ID必须是数字'];
        }
        $result = $this->app->cart->updateCartGoods($conId, intval($goods_skuid), intval($goods_num), intval($track_id));
        return $result;
    }

    /**
     * @api              {post} / 批量删除购物车商品
     * @apiDescription   editUserCart
     * @apiGroup         index_Cart
     * @apiName          editUserCart
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} del_skuid 删除商品skuid,多条用','拼接
     * @apiParam (入参) {String} del_shopid 删除商品店铺ID,多条用','拼接
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:uid长度只能是32位 / 3002:缺少参数
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} cate_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSampleRequest /index/cart/editUserCart
     * @author rzc
     */
    public function editUserCart() {
        $del_skuid  = trim($this->request->post('del_skuid'));
        $del_shopid = trim($this->request->post('del_shopid'));
        // RVYvaEw2Wk1TeXlnUjdlb2RHc3ZEZz09
        // $uid = $this->app->user->enUid($paramUid);
        // echo $uid;die;
        $conId = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->cart->editUserCart($conId, $del_shopid, $del_skuid);
        return $result;
    }

    /**
     * @api              {post} / 查询购物车商品数量
     * @apiDescription   getUserCartNum
     * @apiGroup         index_Cart
     * @apiName          getUserCartNum
     * @apiParam (入参) {String} con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001:uid长度只能是32位 / 3002:缺少参数
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSampleRequest /index/cart/getUserCartNum
     * @author rzc
     */
    public function getUserCartNum(){
        $conId = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->cart->getUserCartNum($conId);
        return $result;
    }
}
