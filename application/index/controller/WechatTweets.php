<?php
namespace app\index\controller;
//
use app\index\MyController;

//
class WechatTweets extends MyController {

    protected $beforeActionList = [
        //        'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => ''], //除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取微信图文素材列表
     * @apiDescription   getWeChatGraphicMaterialList
     * @apiGroup         index_wechattweets
     * @apiName          getWeChatGraphicMaterialList
     * @apiParam (入参) {String} con_id
     * @apiParam (入参) {String} [page] 默认第一页开始
     * @apiParam (入参) {String} [pageNum] 每页显示结果
     * @apiSuccess (返回) {String} code 200:成功  3001:con_id长度只能是32位 / 3002:conId有误 / 3003:page和pagenum必须是数字 / 4001:获取微信认证KEY失败
     * @apiSuccess (返回) {Array} WeChatList 微信图文列表
     * @apiSuccess (WeChatList) {String} title 文章标题
     * @apiSuccess (WeChatList) {String} author 作者
     * @apiSuccess (WeChatList) {String} url 文章链接
     * @apiSuccess (WeChatList) {String} thumb_url 图片链接
     * @apiSuccess (WeChatList) {String} create_time 创建时间
     * @apiSuccess (WeChatList) {String} update_time 修改时间
     * @apiSampleRequest /index/wechattweets/getWeChatGraphicMaterialList
     * @return array
     * @author rzc
     */
    public function getWeChatGraphicMaterialList() {
        $page    = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pageNum'));
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;
        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3003'];
        }
        $result = $this->app->wechattweets->getWeChatGraphicMaterialList($page, $pagenum);
        return $result;
    }
}