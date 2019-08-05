<?php

namespace app\common\action\admin;

use app\facade\DbAudios;
use think\Db;

class Audios extends CommonIndex {
    public function asyncAudios() {
        $audioId       = 2;
        $url           = $this->tingluoSignurl($audioId);
        $data          = sendRequest($url);
        $data          = json_decode($data, true);
        $data          = $data['data'];
        $audioIdList   = array_column($data, 'audio_id');//喜马拉雅获取的最新audio_id
        $audioList     = array_combine($audioIdList, $data);
        $result        = DbAudios::getAudio([['audio_id', 'in', $audioIdList]], 'id,audio_id,album_id,name,article_name,audio');
        $dbAudioIdList = array_column($result, 'audio_id');//已通不过的audio_id
        $dbAudioId     = array_column($result, 'id', 'audio_id');//已通不过的audio_id
        $addData       = [];
        $modifyData    = [];
        foreach ($audioIdList as $v) {
            if (in_array($v, $dbAudioIdList)) {
                $arr = [
                    'id'           => $dbAudioId[$v],
                    'audio_id'     => !empty($audioList[$v]['audio_id']) ? $audioList[$v]['audio_id'] : 0,
                    'album_id'     => !empty($audioList[$v]['album_id']) ? $audioList[$v]['album_id'] : 0,
                    'name'         => !empty($audioList[$v]['name']) ? trim($audioList[$v]['name']) : '',
                    'article_name' => !empty($audioList[$v]['article_name']) ? trim($audioList[$v]['article_name']) : '',
                    'audio'        => !empty($audioList[$v]['xm_file_url']) ? trim($audioList[$v]['xm_file_url']) : '',
                ];
                if (in_array($arr, $result)) {//相同的不用更新
                    continue;
                }
                array_push($modifyData, $arr);
                continue;
            }
            array_push($addData, [
                'audio_id'     => !empty($audioList[$v]['audio_id']) ? $audioList[$v]['audio_id'] : 0,
                'album_id'     => !empty($audioList[$v]['album_id']) ? $audioList[$v]['album_id'] : 0,
                'name'         => !empty($audioList[$v]['name']) ? trim($audioList[$v]['name']) : '',
                'article_name' => !empty($audioList[$v]['article_name']) ? trim($audioList[$v]['article_name']) : '',
                'audio'        => !empty($audioList[$v]['xm_file_url']) ? trim($audioList[$v]['xm_file_url']) : '',
            ]);
        }
        Db::startTrans();
        try {
            if (!empty($addData)) {
                DbAudios::saveAllAudios($addData);
            }
            if (!empty($modifyData)) {
                DbAudios::saveAllAudios($modifyData);
            }
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3001'];//更新失败
        }
    }

    public function audiosList($page, $pageNum) {
        $offset = $pageNum * ($page - 1);
        $result = DbAudios::getAudio([], 'id,audio_id,album_id,name,article_name,audio,audition_time', false, 'id desc', $offset . ',' . $pageNum);
        $count  = DbAudios::countAudio();
        return ['code' => '200', 'data' => $result, 'total' => $count];
    }

    public function editAudio($id, $auditionTime) {
        try {
            DbAudios::updateAudios([
                'audition_time' => $auditionTime,
            ], $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3003'];//更新失败
        }
    }

    private function tingluoSignurl($id) {
        $appid = '8dc8f2b31743fd10ec54d11eac63d2d9';
        $time  = strtotime(date('Y-m-d h:i', strtotime('+2 day')));
        $sign  = strtolower(md5($appid . $id . $time));
        $url   = 'https://ke.itingluo.com/api/tingluo/albums/' . $id . '/audios?appid=' . $appid . '&sign=' . $sign;
        return $url;
    }
}