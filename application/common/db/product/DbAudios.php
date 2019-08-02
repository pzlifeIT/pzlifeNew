<?php

namespace app\common\db\product;

use app\common\db\Db;
use app\common\model\Audio;
use app\common\model\AudioSku;
use app\common\model\AudioSkuRelation;

class DbAudios extends Db {
    public function saveAllAudios($data) {
        $audio = new Audio;
        $audio->saveAll($data);
    }

    public function updateAudios($data, $id) {
        $audio=new Audio;
        $audio->save($data,['id'=>$id]);
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
}