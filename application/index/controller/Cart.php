<?php
namespace app\index\controller;

use app\index\MyController;

class Cart extends MyController{
    /**
     * @api              {post} / 获取购物车商品
     * @apiDescription   getUserCart
     * @apiGroup         index_Cart
     * @apiName          getUserCart
     * @apiParam (入参) {Number} paramUid 用户ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在 
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
     * @apiSampleRequest /index/cart/getUserCart
     * @author rzc
     */
    public function getUserCart(){
        $paramUid = trim($this->request->post('paramUid'));
        if (empty($paramUid)) {
            return ['code' => '3002', 'msg' => '缺少参数:uid'];
        }
        if (strlen($paramUid) != 32) {
            return ['code' => 3001];
        }
        // RVYvaEw2Wk1TeXlnUjdlb2RHc3ZEZz09
        // $uid = $this->app->user->deUid($paramUid);
        $result = $this->app->cart->getUserCart($paramUid);
        return $result;

    }

    /**
     * @api              {post} / 添加购物车商品
     * @apiDescription   addUserCart
     * @apiGroup         index_Cart
     * @apiName          addUserCart
     * @apiParam (入参) {Number} paramUid 请求uid
     * @apiParam (入参) {Number} goods_skuid 商品SKU_id
     * @apiParam (入参) {Number} goods_num 数量
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
     * @apiSampleRequest /index/cart/addUserCart
     * @author rzc
     */
    public function addUserCart(){
        $paramUid = trim($this->request->post('paramUid'));
        $goods_skuid = trim($this->request->post('goods_skuid'));
        $goods_num = trim($this->request->post('goods_num'));
        $track_id = trim($this->request->post('track_id'));
        $track_id = $track_id ? $track_id : 1;
        // RVYvaEw2Wk1TeXlnUjdlb2RHc3ZEZz09
        // $uid = $this->app->user->enUid($paramUid);
        // echo $uid;die;
        if (empty($paramUid) || empty($goods_skuid)) {
            return ['code' => '3002', 'msg' => '缺少参数:uid或者商品SKUID'];
        }
        if (!is_numeric($goods_skuid)) {
            return ['code' => '3003','msg' => '商品SKU_ID必须是数字'];
        }
        if (!is_numeric($goods_num)) {
            return ['code' => '3004','msg' => '购买数量必须是数字'];
        }
        if (!is_numeric($track_id)) {
            return ['code' => '3005','msg' => '足迹ID必须是数字'];
        }
        if (strlen($paramUid) != 32) {
            return ['code' => 3001];
        }
        
        $result = $this->app->cart->addCartGoods($paramUid,intval($goods_skuid),intval($goods_num),intval($track_id));
        return $result;
    }

    /**
     * @api              {post} / 修改购物车商品数量
     * @apiDescription   updateUserCart
     * @apiGroup         index_Cart
     * @apiName          updateUserCart
     * @apiParam (入参) {Number} paramUid 请求uid
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
    public function updateUserCart(){
        $paramUid = trim($this->request->post('paramUid'));
        $goods_skuid = trim($this->request->post('goods_skuid'));
        $goods_num = trim($this->request->post('goods_num'));
        $track_id = trim($this->request->post('track_id'));
        $track_id = $track_id ? $track_id : 1;
        // RVYvaEw2Wk1TeXlnUjdlb2RHc3ZEZz09
        // $uid = $this->app->user->enUid($paramUid);
        // echo $uid;die;
        if (empty($paramUid) || empty($goods_skuid)) {
            return ['code' => '3002', 'msg' => '缺少参数:uid或者商品SKUID'];
        }
        if (!is_numeric($goods_skuid)) {
            return ['code' => '3003','msg' => '商品SKU_ID必须是数字'];
        }
        if (!is_numeric($goods_num)) {
            return ['code' => '3004','msg' => '购买数量必须是数字'];
        }
        if (!is_numeric($track_id)) {
            return ['code' => '3005','msg' => '足迹ID必须是数字'];
        }
        if (strlen($paramUid) != 32) {
            return ['code' => 3001];
        }
        $result = $this->app->cart->updateCartGoods($paramUid,intval($goods_skuid),intval($goods_num),intval($track_id));
        return $result;
    }

    /**
     * @api              {post} / 批量删除购物车商品
     * @apiDescription   editUserCart
     * @apiGroup         index_Cart
     * @apiName          editUserCart
     * @apiParam (入参) {Number} paramUid 请求uid
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
    public function editUserCart(){
        $paramUid = trim($this->request->post('paramUid'));
        $del_skuid = trim($this->request->post('del_skuid'));
        $del_shopid = trim($this->request->post('del_shopid'));
        // RVYvaEw2Wk1TeXlnUjdlb2RHc3ZEZz09
        // $uid = $this->app->user->enUid($paramUid);
        // echo $uid;die;
        if (empty($paramUid)) {
            return ['code' => '3002', 'msg' => '缺少参数:uid'];
        }
        if (strlen($paramUid) != 32) {
            return ['code' => 3001];
        }
        $result = $this->app->cart->editUserCart($paramUid,$del_shopid,$del_skuid);
        return $result;
    }
}