<?php

namespace app\common\action\index;

use app\facade\DbRights;
use app\facade\DbShops;
use app\facade\DbUser;
use Config;
use think\Db;
use function Qiniu\json_decode;

class WechatTweets extends CommonIndex {

    /**
     * 获取微信公众号文章信息
     * @param $page
     * @param $pagenum
     * @return array
     * @author rzc
     */

    public function getWeChatGraphicMaterialList($page, $pagenum) {
        $offset = ($page - 1) * $pagenum;
        if ($offset < 0) {
            return ['code' => '200', 'WeChatList' => []];
        }
        $redisBatchgetMaterial = Config::get('redisKey.weixin.redisBatchgetMaterial');
        $WeChatList = $this->redis->ZRANGE($redisBatchgetMaterial,$offset,$pagenum);
        if (empty($WeChatList)) {
            return ['code' => '200', 'WeChatList' => []];
        }
        foreach ($WeChatList as $key => $value) {
           $WeChatList[$key] = json_decode($value);
        }
        return ['code' => '200', 'WeChatList' => $WeChatList];
    }




}
