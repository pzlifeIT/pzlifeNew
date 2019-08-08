<?php
namespace app\index\controller;
//
use app\index\MyController;

//
class Audio extends MyController {

    protected $beforeActionList = [
               'isLogin',//所有方法的前置操作
        // 'isLogin' => ['except' => 'getWeChatGraphicMaterialList'], //除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取用户全部音频列表
     * @apiDescription   getUserAudioList
     * @apiGroup         index_audio
     * @apiName          getUserAudioList
     * @apiParam (入参) {String} con_id 默认第一页开始
     * @apiParam (入参) {String} [page] 默认第一页开始
     * @apiParam (入参) {String} [pageNum] 每页显示结果
     * @apiSuccess (返回) {String} code 200:成功  3001:con_id长度只能是32位 / 3002:conId有误 / 3003:page和pagenum必须是数字 / 4001:获取微信认证KEY失败
     * @apiSuccess (返回) {Array} audioList 已购买音频
     * @apiSuccess (audioList) {String} id 记录ID
     * @apiSuccess (audioList) {String} audio_id 音频ID
     * @apiSuccess (audioList) {String} end_time 【视听】结束时间
     * @apiSuccess (audioList) {String} create_time 购买时间
     * @apiSuccess (audioList) {String} update_time 更新时间
     * @apiSuccess (audioList[audio]) {String} id 音频ID
     * @apiSuccess (audioList[audio]) {String} name 音频名称
     * @apiSuccess (audioList[audio]) {String} audio 音频地址
     * @apiSuccess (audioList[audio]) {String} audition_time 音频【试听】时间
     * @apiSampleRequest /index/audio/getUserAudioList
     * @return array
     * @author rzc
     */
    public function getUserAudioList() {
        $conId   = trim($this->request->post('con_id'));
        $page    = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pageNum'));
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;
        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3003'];
        }
        $result = $this->app->audio->getUserAudioList($conId, $page, $pagenum);
        return $result;
    }

    /**
     * @api              {post} / 查询用户是否有音频【视听】资格
     * @apiDescription   checkUserAudio
     * @apiGroup         index_audio
     * @apiName          checkUserAudio
     * @apiParam (入参) {String} con_id 默认第一页开始
     * @apiParam (入参) {String} audio_id 
     * @apiSuccess (返回) {String} code 200:成功  3001:音频ID错误
     * @apiSuccess (返回) {String} checked 1有视听资格 2无视听资格
     * @apiSampleRequest /index/audio/checkUserAudio
     * @return array
     * @author rzc
     */
    public function checkUserAudio(){
        $conId   = trim($this->request->post('con_id'));
        $audio_id    = trim($this->request->post('audio_id'));
        if (!is_numeric($audio_id) || intval($audio_id)<1) {
            return ['code' => '3001'];
        }
        $result = $this->app->audio->checkUserAudio($conId, $audio_id);
        return $result;
    }
}