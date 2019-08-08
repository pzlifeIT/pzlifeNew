<?php

namespace app\common\action\index;

use app\facade\DbRights;
use app\facade\DbShops;
use app\facade\DbAudios;
use Config;
use think\Db;
use function Qiniu\json_decode;

class Audio extends CommonIndex {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取用户全部音频列表
     * @param $conId
     * @param $page
     * @param $pagenum
     * @return array
     * @author rzc
     */

    public function getUserAudioList($conId, $page, $pagenum) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        $offset = ($page - 1) * $pagenum;
        if ($offset < 0) {
            return ['code' => '200', 'WeChatList' => []];
        }
        $audio = DbAudios::getUserAudioRelation(['uid' => $uid],'id,audio_id,end_time,create_time,update_time',false,['id' => 'desc'],$offset.','.$pagenum);
        foreach ($audio as $key => $value) {
            $audio[$key]['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
        }
        return ['code' => '200', 'audioList' => $audio];
    }

    /**
     * 查询用户是否有音频【视听】资格
     * @param $conId
     * @param $audio_id
     * @return array
     * @author rzc
     */
    public function checkUserAudio($conId, $audio_id) {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3003'];
        }
        if ($this->redis->get(Config::get('rediskey.audio.redisAudioVisual') . $uid.':'.$audio_id)){
            return ['code' => '200', 'checked' => 1];
        }
        return ['code' => '200', 'checked' => 2];
    }


}
