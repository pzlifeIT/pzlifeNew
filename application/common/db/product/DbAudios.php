<?php

namespace app\common\db\product;

use app\common\db\Db;
use app\common\model\Audio;
use app\common\model\AudioSku;
use app\common\model\AudioSkuRelation;
use app\common\model\UserAudio;

class DbAudios extends Db {
    public function saveAllAudios($data) {
        $audio = new Audio;
        $audio->saveAll($data);
    }

    public function updateAudios($data, $id) {
        $audio=new Audio;
        $audio->save($data,['id'=>$id]);
    }

    public function countAudio($where = []){
        return Audio::where($where)->count();
    }

    public function saveAudioSku($data){
        $audioSku = new AudioSku();
        $audioSku->save($data);
        return $audioSku->id;
    }

    public function saveAllAudioSkuRelation($data){
        $audioSkuRelation = new AudioSkuRelation;
        $audioSkuRelation->saveAll($data);
    }

    public function getAudioSkuRelation($where, $field, $row = false, $orderBy = '', $limit = ''){
        $obj = AudioSkuRelation::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function delAudioSkuRelation($ids){
        return AudioSkuRelation::destroy($ids);
    }

    public function getAudiosSku($where, $field, $row = false, $orderBy = '', $limit = ''){
        $obj = AudioSku::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 获取一条商品规格某字段最值
     * @param $where
     * @param $field
     * @return array
     * @author rzc
     */
    public function getOneAudioSkuMost($where, $most, $field) {
        /* 最小 */
        if ($most == 1) {
            return AudioSku::where($where)->min($field);
        } elseif ($most == 2) {
            return AudioSku::where($where)->max($field);
        }

    }

    public function updateAudiosSku($data, $id){
        $audioSku = new AudioSku;
        return $audioSku->save($data, ['id' => $id]);
    }

    public function getUserAudio($where, $field, $row = false, $orderBy = '', $limit = ''){
        $obj = UserAudio::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function updateUserAudio($data, $id){
        $UserAudio = new UserAudio;
        return $UserAudio->save($data, ['id' => $id]);
    }

    public function addUserAudio($data){
        $UserAudio = new UserAudio;
        $UserAudio->saveAll($data);
    }

    public function getUserAudioRelation($where, $field, $row = false, $orderBy = '', $limit = ''){
        $obj = UserAudio::field($field)->with([
            'audio'      => function ($query) {
                $query->field('id,name,audio,audition_time,audio_length,audio_length_text')->where(['delete_time' => 0]);
            },
        ])->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }
}