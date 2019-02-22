<?php

namespace app\common\action\index;

use app\facade\DbUser;
use cache\Phpredis;
use Env;
use Config;

class CommonIndex {
    protected $redis;
    /**
     * user模块
     */
    protected $redisKey = 'index:user:';
    protected $redisConIdTime = 'index:user:conId:expiration';//conId到期时间的zadd
    protected $redisConIdUid = 'index:user:conId:uid';//conId和uid的hSet

    public function __construct() {
        $this->redis = Phpredis::getConn();
    }

    /**
     * 通过con_id获取uid
     * @param $conId
     * @return int
     * @author zyr
     */
    protected function getUidByConId($conId) {
        $uid             = 0;
        $expireTime      = 2592000;//30天过期
        $subTime         = bcsub(time(), $expireTime, 0);
        $conIdCreatetime = $this->redis->zScore($this->redisConIdTime, $conId);//保存时间
        if ($subTime <= $conIdCreatetime) {//已登录
            $uid = $this->redis->hGet($this->redisConIdUid, $conId);
        }
        if (empty($uid)) {
            $userCon = DbUser::getUserCon([['con_id', '=', $conId], ['update_time', '>=', $subTime]], 'uid', true);
            if (!empty($userCon)) {
                $uid = $userCon['uid'];
            }
        }
        return $uid;
    }

    /**
     * 判断是否登录
     * @param $conId
     * @return array
     * @author zyr
     */
    public function isLogin($conId) {
        $expireTime      = 2592000;//30天过期
        $conIdCreatetime = $this->redis->zScore($this->redisConIdTime, $conId);//保存时间
        if (bcsub(time(), $conIdCreatetime, 0) <= $expireTime) {//已登录
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);//更新时间
            return ['code' => '200'];
        } else {
            if ($conIdCreatetime === false) {//con_id不存在
                if ($this->updateConId($conId) === true) {//查询数据库更新redis
                    return ['code' => '200'];
                }
            }
            $this->redis->zDelete($this->redisConIdTime, $conId);
            $this->redis->hDel($this->redisConIdUid, $conId);
        }
        return ['code' => '5000'];
    }

    /**
     * 更新缓存登录时间
     * @param $conId
     * @return bool
     * @author zyr
     */
    protected function updateConId($conId) {
        $expireTime = 2592000;//30天过期
        $subTime    = bcsub(time(), $expireTime, 0);
        $userCon    = DbUser::getUserCon([['con_id', '=', $conId], ['update_time', '>=', $subTime]], 'id,uid', true);
        if (!empty($userCon)) {
            $this->redis->zAdd($this->redisConIdTime, time(), $conId);//更新时间
            $this->redis->hSet($this->redisConIdUid, $conId, $userCon['uid']);
            if (DbUser::updateUserCon(['con_id' => $conId], $userCon['id'])) {
                return true;
            }
            return false;
        }
        return false;
    }

    protected function resetUserInfo($uid) {
        $user = DbUser::getUser(['id' => $uid]);
        $this->saveUser($uid, $user);
        $saveTime = 600;//保存10分钟
        $this->redis->hMSet($this->redisKey . 'userinfo:' . $uid, $user);
        $this->redis->expireAt($this->redisKey . 'userinfo:' . $uid, bcadd(time(), $saveTime, 0));//设置过期
    }
}