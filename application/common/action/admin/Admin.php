<?php

namespace app\common\action\admin;

use app\facade\DbAdmin;
use Config;
use think\Db;

class Admin extends CommonIndex {
    private $cmsCipherUserKey = 'adminpass';//用户密码加密key

    /**
     * @param $adminName
     * @param $passwd
     * @return array
     * @author zyr
     */
    public function login($adminName, $passwd) {
        $getPass   = $this->getPassword($passwd, $this->cmsCipherUserKey);//用户填写的密码
        $adminInfo = DbAdmin::getAdminInfo(['admin_name' => $adminName, 'status' => 1], 'id,passwd', true);
        if (empty($adminInfo)) {
            return ['code' => '3002'];//用户已存在
        }
        if ($adminInfo['passwd'] !== $getPass) {
            return ['code' => '3003'];//密码错误
        }
        $cmsConId = $this->createCmsConId();
        $this->redis->zAdd($this->redisCmsConIdTime, time(), $cmsConId);
        $conUid = $this->redis->hSet($this->redisCmsConIdUid, $cmsConId, $adminInfo['id']);
        if ($conUid === false) {
            return ['code' => '3004'];//登录失败
        }
        return ['code' => '200', 'cms_con_id' => $cmsConId];
    }

    /**
     * @param $cmsConId
     * @return array
     * @author zyr
     */
    public function getAdminInfo($cmsConId) {
        $adminId   = $this->getUidByConId($cmsConId);
        $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'admin_name,stype', true);
        return ['code' => '200', 'data' => $adminInfo];
    }

    /**
     * @param $cmsConId
     * @param $adminName
     * @param $passwd
     * @param $stype
     * @return array
     * @author zyr
     */
    public function addAdmin($cmsConId, $adminName, $passwd, $stype) {
        $adminId   = $this->getUidByConId($cmsConId);
        $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'stype,status', true);
        if ($adminInfo['stype'] != '2') {
            return ['code' => '3005'];//没有操作权限
        }
        if ($stype == 2 && $adminId != 1) {
            return ['code' => '3003'];//只有root账户可以添加超级管理员
        }
        $newAdminInfo = DbAdmin::getAdminInfo(['admin_name' => $adminName], 'id', true);
        if (!empty($newAdminInfo)) {
            return ['code' => '3004'];//该账号已存在
        }
        Db::startTrans();
        try {
            DbAdmin::addAdmin([
                'admin_name' => $adminName,
                'passwd'     => $this->getPassword($passwd, $this->cmsCipherUserKey),
                'stype'      => $stype,
            ]);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3006'];//添加失败
        }
    }

    /**
     * @param $cmsConId
     * @param $passwd
     * @param $newPasswd
     * @return array
     * @author zyr
     */
    public function midifyPasswd($cmsConId, $passwd, $newPasswd) {
        $adminId   = $this->getUidByConId($cmsConId);
        $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'id,passwd,status', true);
        if ($adminInfo['passwd'] !== $this->getPassword($passwd, $this->cmsCipherUserKey)) {
            return ['code' => '3001'];
        }
        Db::startTrans();
        try {
            DbAdmin::updatePasswd($this->getPassword($newPasswd, $this->cmsCipherUserKey), $adminId);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//修改密码失败
        }
    }

    /**
     * 创建唯一conId
     * @author zyr
     */
    private function createCmsConId() {
        $cmsConId = uniqid(date('ymdHis'));
        $cmsConId = hash_hmac('ripemd128', $cmsConId, 'admin');
        return $cmsConId;
    }

    /**
     * @param $str 加密的内容
     * @param $key
     * @return string
     * @author zyr
     */
    private function getPassword($str, $key) {
        $algo   = Config::get('conf.cipher_algo');
        $md5    = hash_hmac('md5', $str, $key);
        $key2   = strrev($key);
        $result = hash_hmac($algo, $md5, $key2);
        return $result;
    }
}