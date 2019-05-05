<?php

namespace app\common\action\admin;

use cache\Phpredis;
use Env;
use Config;
use app\facade\DbAdmin;

class CommonIndex {
    protected $redis;
    protected $redisCmsConIdTime;
    protected $redisCmsConIdUid;
    /**
     * user模块
     */
    protected $redisKey;

    public function __construct() {
        $this->redis             = Phpredis::getConn();
        $this->redisCmsConIdTime = Config::get('rediskey.user.redisCmsConIdTime');
        $this->redisCmsConIdUid  = Config::get('rediskey.user.redisCmsConIdUid');
    }

    /**
     * 判断是否登录
     * @param $cmsConId
     * @return array
     * @author zyr
     */
    public function isLogin($cmsConId) {
        if (empty($cmsConId)) {
            return ['code' => '5000'];
        }
        if (strlen($cmsConId) != 32) {
            return ['code' => '5000'];
        }
        $expireTime      = 172800;//2天过期
        $conIdCreatetime = $this->redis->zScore($this->redisCmsConIdTime, $cmsConId);//保存时间
        if (bcsub(time(), $conIdCreatetime, 0) <= $expireTime) {//已登录
            $this->redis->zAdd($this->redisCmsConIdTime, time(), $cmsConId);//更新时间
            $adminId = $this->redis->hGet($this->redisCmsConIdUid, $cmsConId);
            if (empty($adminId)) {
                $this->redis->zDelete($this->redisCmsConIdTime, $cmsConId);
                $this->redis->hDel($this->redisCmsConIdUid, $cmsConId);
                return ['code' => '5000'];
            }
            $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'status', true);
            if (empty($adminInfo)) {
                $this->redis->zDelete($this->redisCmsConIdTime, $cmsConId);
                $this->redis->hDel($this->redisCmsConIdUid, $cmsConId);
                return ['code' => '5000'];
            }
            if ($adminInfo['status'] == '2') {
                return ['code' => '5001'];//账号已停用
            }
            return ['code' => '200'];
        }
        $this->redis->zDelete($this->redisCmsConIdTime, $cmsConId);
        $this->redis->hDel($this->redisCmsConIdUid, $cmsConId);
        return ['code' => '5000'];
    }

    /**
     * 权限验证
     */
    protected function checkPermissions($admin_id) {
//        return true;
    }

    /**
     * 通过cms_con_id获取admin_id
     * @param $cmsConId
     * @return int
     * @author zyr
     */
    protected function getUidByConId($cmsConId) {
//        $adminId         = 0;
//        $expireTime      = 172800;//30天过期
//        $subTime         = bcsub(time(), $expireTime, 0);
//        $conIdCreatetime = $this->redis->zScore($this->redisCmsConIdTime, $cmsConId);//保存时间
//        if ($subTime <= $conIdCreatetime) {//已登录
        $adminId = $this->redis->hGet($this->redisCmsConIdUid, $cmsConId);
//        }
        return $adminId;
    }
}