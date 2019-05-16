<?php

namespace app\common\action\admin;

use app\common\action\notify\Note;
use app\facade\DbAdmin;
use app\facade\DbImage;
use app\facade\DbModelMessage;
use app\facade\DbOrder;
use app\facade\DbShops;
use app\facade\DbUser;
use cache\Phpredis;
use Config;
use Env;
use think\Db;

class Admin extends CommonIndex {
    private $cmsCipherUserKey = 'adminpass'; //用户密码加密key

    private function redisInit() {
        $this->redis = Phpredis::getConn();
//        $this->connect = Db::connect(Config::get('database.db_config'));
    }

    /**
     * @param $adminName
     * @param $passwd
     * @return array
     * @author zyr
     */
    public function login($adminName, $passwd) {
        $getPass   = $this->getPassword($passwd, $this->cmsCipherUserKey); //用户填写的密码
        $adminInfo = DbAdmin::getAdminInfo(['admin_name' => $adminName, 'status' => 1], 'id,passwd', true);
        if (empty($adminInfo)) {
            return ['code' => '3002']; //用户不存在
        }
        if ($adminInfo['passwd'] !== $getPass) {
            return ['code' => '3003']; //密码错误
        }
        $cmsConId = $this->createCmsConId();
        $this->redis->zAdd($this->redisCmsConIdTime, time(), $cmsConId);
        $conUid = $this->redis->hSet($this->redisCmsConIdUid, $cmsConId, $adminInfo['id']);
        if ($conUid === false) {
            return ['code' => '3004']; //登录失败
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
     * @return array
     * @author rzc
     */
    public function getAdminUsers() {
        $adminInfo = DbAdmin::getAdminInfo([], 'admin_name,department,stype,status');
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
            return ['code' => '3005']; //没有操作权限
        }
        if ($stype == 2 && $adminId != 1) {
            return ['code' => '3003']; //只有root账户可以添加超级管理员
        }
        $newAdminInfo = DbAdmin::getAdminInfo(['admin_name' => $adminName], 'id', true);
        if (!empty($newAdminInfo)) {
            return ['code' => '3004']; //该账号已存在
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
            return ['code' => '3006']; //添加失败
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
            return ['code' => '3005']; //修改密码失败
        }
    }

    /**
     * 开通boss
     * @param $cmsConId
     * @param $mobile
     * @param $nickName
     * @param $money
     * @param $message
     * @return array
     * @author zyr
     */
    public function openBoss($cmsConId, $mobile, $nickName, $money, $message) {
        $adminId   = $this->getUidByConId($cmsConId);
        $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'stype', true);
        if ($adminInfo['stype'] != '2') {
            return ['code' => '3005']; //没有操作权限
        }
        $user = DbUser::getUserInfo(['mobile' => $mobile, 'nick_name' => $nickName], 'id,user_identity,commission', true);
        if (empty($user)) {
            return ['code' => '3006']; //用户不存在
        }
        $redisKey = Config::get('rediskey.user.redisUserOpenbossLock');
        if ($this->redis->setNx($redisKey . $user['id'], 1) === false) {
            return ['code' => '3009'];
        }
        if ($user['user_identity'] == 4) {
            return ['code' => '3007']; //该用户已经是boss
        }
        $bossId = $this->getBoss($user['id']);
        if ($bossId == 1) {
            $re = $user['id'];
        } else {
            $re = $bossId . ',' . $user['id'];
        }
        $userRelationList = DbUser::getUserRelation([['relation', 'like', '%,' . $user['id'] . ',%']], 'id,relation');
        $userRelationData = [];
        if (!empty($userRelationList)) {
            foreach ($userRelationList as $url) {
                $url['relation'] = substr($url['relation'], stripos($url['relation'], ',' . $user['id'] . ',') + 1);
                array_push($userRelationData, $url);
            }
        }
        $shopData = [
            'uid'         => $user['id'],
            'shop_right'  => 'all',
            'status'      => 1,
            'create_time' => time(),
        ];
        $tradingDate = [
            'uid'          => $user['id'],
            'trading_type' => 2,
            'change_type'  => 9,
            'money'        => -$money,
            'befor_money'  => $user['commission'],
            'after_money'  => bcsub($user['commission'], $money, 2),
            'message'      => '',
            'create_time'  => time(),
        ];
        $logOpenbossData = [
            'money'    => $money,
            'uid'      => $user['id'],
            'admin_id' => $adminId,
            'status'   => 1,
            'message'  => $message,
        ];
        $pid        = $bossId == 1 ? 0 : $bossId;
        $relationId = $this->getRelation($user['id'])['id'];
        Db::startTrans();
        try {
            if (!empty($userRelationData)) {
                DbUser::updateUserRelation($userRelationData);
            }
            DbUser::updateUserRelation(['is_boss' => 1, 'relation' => $re, 'pid' => $pid], $relationId);
            DbUser::modifyCommission($user['id'], $money, 'dec'); //扣佣金
            DbOrder::addLogTrading($tradingDate); //写佣金明细
            DbShops::addShop($shopData); //添加店铺
            DbUser::updateUser(['user_identity' => 4], $user['id']);
            DbUser::addLogOpenboss($logOpenbossData);
            Db::commit();
            $this->redis->del($redisKey . $user['id']);
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3008']; //开通失败
        }
    }

    /**
     * 开通boss列表
     * @param $cmsConId
     * @param $mobile
     * @param $nickName
     * @param $page
     * @param $pageNum
     * @return array
     * @author zyr
     */
    public function getOpenBossList($cmsConId, $mobile, $nickName, $page, $pageNum) {
        $adminId  = $this->getUidByConId($cmsConId);
        $allCount = DbUser::getLogOpenbossCount($mobile, $nickName); //总记录数
        $allPage  = ceil(bcdiv($allCount, $pageNum, 5)); //总页数
        $offset   = ($page - 1) * $pageNum;
        $data     = DbUser::getLogOpenboss($offset . ',' . $pageNum, $mobile, $nickName);
        return ['code' => '200', 'data' => $data, 'all_count' => $allCount, 'all_page' => $allPage];
    }

    private function getBoss($uid) {
        if ($uid == 1) {
            return 1;
        }
        $relation = $this->getRelation($uid);
        $bossUid  = explode(',', $relation['relation'])[0];
        if ($uid == $bossUid) {
            return 1;
        }
        $pBossUidCheck = $this->getIdentity($bossUid);
        if ($pBossUidCheck != 4) { //relation第一个关系人不是boss说明是总店下的用户
            return 1;
        }
        return $bossUid;
    }

    private function getRelation($uid) {
        $userRelation = DbUser::getUserRelation(['uid' => $uid], 'id,pid,is_boss,relation', true);
        return $userRelation;
    }

    /**
     * 获取用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @param $uid
     * @return mixed
     * @author zyr
     */
    private function getIdentity($uid) {
        $user = DbUser::getUserInfo(['id' => $uid], 'user_identity', true);
        if (empty($user)) {
            return false;
        }
        return $user['user_identity'];
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

    /**
     * 商票,佣金,积分手动充值
     * @param $cmsConId 加密的内容
     * @param $passwd
     * @param $stype
     * @param $uid
     * @param $credit
     * @param $message
     * @return string
     * @author rzc
     */
    public function adminRemittance($cmsConId, $passwd, $stype, $nick_name, $mobile, $credit, $message, $admin_message) {
        $message       = $message ?? '';
        $admin_message = $admin_message ?? '';
        $adminId       = $this->getUidByConId($cmsConId);
        $adminInfo     = DbAdmin::getAdminInfo(['id' => $adminId], 'id,passwd,status', true);
        if ($adminInfo['passwd'] !== $this->getPassword($passwd, $this->cmsCipherUserKey)) {
            return ['code' => '3001'];
        }
        /* $uid = deUid($uid);
        if (empty($uid)) {
        return ['code' => '3004'];
        } */
        $indexUser = DbUser::getUserInfo(['nick_name' => $nick_name, 'mobile' => $mobile], 'id,balance,commission,integral', true);
        if (empty($indexUser)) {
            return ['code' => '3004'];
        }
        if ($stype == 1) {
            if ($credit + $indexUser['balance'] < 0) {
                return ['code' => '3006'];
            }
        }
        $add_remittance                      = [];
        $add_remittance['initiate_admin_id'] = $adminId;
        $add_remittance['stype']             = $stype;
        $add_remittance['uid']               = $indexUser['id'];
        $add_remittance['status']            = 1;
        $add_remittance['credit']            = $credit;
        $add_remittance['message']           = $message;
        $add_remittance['admin_message']     = $admin_message;
        DbAdmin::addAdminRemittance($add_remittance);
        return ['code' => '200'];
    }

    /**
     * 商票,佣金,积分手动充值
     * @param $cmsConId 加密的内容
     * @param $status
     * @return string
     * @author rzc
     */
    public function auditAdminRemittance($cmsConId, $status, int $id) {
        $userRedisKey = Config::get('rediskey.user.redisKey');
        $adminId      = $this->getUidByConId($cmsConId);
        $adminInfo    = DbAdmin::getAdminInfo(['id' => $adminId], 'id,stype', true);
        if ($adminInfo['id'] != 1) {
            return ['code' => '3002'];
        }
        $remittance = DbAdmin::getAdminRemittance(['id' => $id], '*', true);
        if (empty($remittance)) {
            return ['code' => '3003'];
        }
        if ($remittance['status'] != 1) {
            return ['code' => '3004'];
        }
        $indexUser = DbUser::getUserInfo(['id' => $remittance['uid']], 'id,balance,commission,integral', true);
        if ($status == 2) { //审核不通过
            DbAdmin::editRemittance(['audit_admin_id' => $adminId, 'status' => 3], $id);
            return ['code' => '200', 'msg' => '审核失败'];
        }
        if ($remittance['stype'] != 3) {

            if ($remittance['stype'] == 1) { //商票
                $tradingData = [
                    'uid'          => $remittance['uid'],
                    'trading_type' => 1,
                    'change_type'  => 8,
                    'money'        => $remittance['credit'],
                    'befor_money'  => $indexUser['balance'],
                    'after_money'  => bcadd($indexUser['balance'], $remittance['credit'], 2),
                    'message'      => $remittance['message'],
                ];
            } elseif ($remittance['stype'] == 2) { //佣金
                $tradingData = [
                    'uid'          => $remittance['uid'],
                    'trading_type' => 2,
                    'change_type'  => 8,
                    'money'        => $remittance['credit'],
                    'befor_money'  => $indexUser['commission'],
                    'after_money'  => bcadd($indexUser['commission'], $remittance['credit'], 2),
                    'message'      => $remittance['message'],
                ];
            }
            Db::startTrans();
            try {
                DbAdmin::editRemittance(['audit_admin_id' => $adminId, 'status' => 2], $id);
                if (!empty($tradingData)) {
                    DbOrder::addLogTrading($tradingData);
                }

                if ($remittance['stype'] == 1) { //商票
                    DbUser::modifyBalance($remittance['uid'], $remittance['credit'], 'inc');
                } elseif ($remittance['stype'] == 2) { //佣金
                    DbUser::modifyCommission($remittance['uid'], $remittance['credit'], 'inc');
                }
                $this->redis->del($userRedisKey . 'userinfo:' . $remittance['uid']);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                // print_r($e);die;
                Db::rollback();
                return ['code' => '3009'];
            }
        } else {
            $user_integral                    = [];
            $user_integral['result_integral'] = $remittance['credit'];
            $user_integral['message']         = $remittance['message'];
            $user_integral['uid']             = $remittance['uid'];
            $user_integral['status']          = 2;
            $user_integral['stype']           = 2;

            Db::startTrans();
            try {
                DbAdmin::editRemittance(['audit_admin_id' => $adminId, 'status' => 2], $id);
                DbUser::modifyIntegral($remittance['uid'], $remittance['credit'], 'inc');
                DbUser::addLogIntegral($user_integral);
                $this->redis->del($userRedisKey . 'userinfo:' . $remittance['uid']);
                Db::commit();
                return ['code' => '200'];
            } catch (\Exception $e) {
                Db::rollback();
                print_r($e);
                die;
                return ['code' => '3009'];
            }
        }
    }

    /**
     * 获取列表
     * @param $page
     * @param $pageNum
     * @param $initiate_admin_id
     * @param $audit_admin_id
     * @param $status
     * @param $min_credit
     * @param $max_credit
     * @param $uid
     * @param $stype
     * @param $start_time
     * @param $end_time
     * @return string
     * @author rzc
     */
    public function getAdminRemittance(int $page, int $pageNum, $initiate_admin_id = 0, $audit_admin_id = 0, $status = 0, $min_credit = 0, $max_credit = 0, $uid = 0, $stype = 0, $start_time = '', $end_time = '') {
        $offset = $pageNum * ($page - 1);
        $where  = [];
        if (!empty($initiate_admin_id)) {
            array_push($where, ['initiate_admin_id', '=', $initiate_admin_id]);
        }
        if (!empty($audit_admin_id)) {
            array_push($where, ['audit_admin_id', '=', $audit_admin_id]);
        }
        if (!empty($status)) {
            array_push($where, ['status', '=', $status]);
        }
        if (!empty($min_credit)) {
            array_push($where, ['credit', '>=', $min_credit]);
        }
        if (!empty($max_credit)) {
            array_push($where, ['credit', '<=', $max_credit]);
        }
        if (!empty($uid)) {
            array_push($where, ['uid', '=', $uid]);
        }
        if (!empty($stype)) {
            array_push($where, ['stype', '=', $stype]);
        }
        if (!empty($start_time)) {
            $start_time = strtotime($start_time);
            array_push($where, ['create_time', '>=', $start_time]);
        }
        if (!empty($end_time)) {
            $end_time = strtotime($end_time);
            array_push($where, ['create_time', '<=', $end_time]);
        }
        // print_r($where);die;
        $result = DbAdmin::getAdminRemittance($where, '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        // print_r(count($result));die;
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $total = DbAdmin::getCountAdminRemittance($where);
        return ['code' => '200', 'total' => $total, 'AdminRemittances' => $result];
    }

    /**
     * 添加支持银行信息
     * @param $abbrev
     * @param $bank_name
     * @param $icon_img
     * @param $bg_img
     * @param $status
     * @return string
     * @author rzc
     */
    public function addAdminBank($abbrev, $bank_name, $icon_img = '', $bg_img = '', $status) {
        $is_bank_abbrev = DbAdmin::getAdminBank(['abbrev' => $abbrev], 'id', true);
        $is_bank_name   = DbAdmin::getAdminBank(['bank_name' => $bank_name], 'id', true);
        if ($is_bank_abbrev || $is_bank_name) {
            return ['code' => '3004'];
        }
        $icon_img                = $icon_img ?? '';
        $bg_img                  = $bg_img ?? '';
        $admin_bank              = [];
        $admin_bank['abbrev']    = $abbrev;
        $admin_bank['bank_name'] = $bank_name;
        $admin_bank['icon_img']  = $icon_img;
        $admin_bank['bg_img']    = $bg_img;
        $admin_bank['status']    = $status;
        Db::startTrans();
        try {
            if (!empty($admin_bank['icon_img'])) {
                $image    = filtraImage(Config::get('qiniu.domain'), $admin_bank['icon_img']);
                $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
                if (empty($logImage)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            }
            if (!empty($admin_bank['bg_img'])) {
                $image    = filtraImage(Config::get('qiniu.domain'), $admin_bank['bg_img']);
                $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
                if (empty($logImage)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            }
            $add = DbAdmin::saveAdminBank($admin_bank);
            Db::commit();
            return ['code' => '200', 'add_id' => $add];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011']; //添加失败
        }
    }

    /**
     * 修改支持银行信息
     * @param $abbrev
     * @param $bank_name
     * @param $icon_img
     * @param $bg_img
     * @param $status
     * @return string
     * @author rzc
     */
    public function editAdminBank(int $id, $abbrev = '', $bank_name = '', $icon_img = '', $bg_img = '', $status = '') {
        if (empty($abbrev) && empty($bank_name) && empty($icon_img) && empty($bg_img) && empty($status)) {
            return ['code' => '3004'];
        }
        $admin_bank = DbAdmin::getAdminBank(['id' => $id], 'id', true);
        if (empty($admin_bank)) {
            return ['code' => '3000'];
        }
        if (!empty($abbrev)) {
            $has_abbrev = DbAdmin::getAdminBank([['abbrev', '=', $abbrev], ['id', '<>', $id]], 'id', true);
            // print_r($has_abbrev);die;
            if ($has_abbrev) {
                return ['code' => '3005'];
            }
        }

        if (!empty($bank_name)) {

            // print_r($admin_bank);die;
            $has_bank_name = DbAdmin::getAdminBank([['bank_name', '=', $bank_name], ['id', '<>', $id]], 'id', true);
            if ($has_bank_name) {
                return ['code' => '3005'];
            }
        }
        if (!empty($icon_img)) {
            $icon_img = filtraImage(Config::get('qiniu.domain'), $icon_img);
        }
        if (!empty($bg_img)) {
            $bg_img = filtraImage(Config::get('qiniu.domain'), $bg_img);
        }
        $admin_bank              = [];
        $admin_bank['abbrev']    = $abbrev;
        $admin_bank['bank_name'] = $bank_name;
        $admin_bank['icon_img']  = $icon_img;
        $admin_bank['bg_img']    = $bg_img;
        $admin_bank['status']    = $status;
        $admin_bank              = $this->delDataEmptyKey($admin_bank);
        Db::startTrans();
        try {
            if (!empty($admin_bank['icon_img'])) {
                // $image    = $admin_bank['icon_img'];
                $logImage = DbImage::getLogImage($admin_bank['icon_img'], 2); //判断时候有未完成的图片
                if (empty($logImage)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            }
            if (!empty($admin_bank['bg_img'])) {
                // $image    =  $admin_bank['bg_img'];
                $logImage = DbImage::getLogImage($admin_bank['bg_img'], 2); //判断时候有未完成的图片
                if (empty($logImage)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            }
            $add = DbAdmin::editAdminBank($admin_bank, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011']; //添加失败
        }
    }
    /**
     * 获取支持银行信息
     * @param $page
     * @param $pageNum
     * @param $abbrev
     * @param $bank_name
     * @param $status
     * @return string
     * @author rzc
     */
    public function getAdminBank(int $page, int $pageNum, $abbrev = '', $bank_name = '', $status = '', $id = '') {
        $where = [];
        if (!empty($id)) {
            $result = DbAdmin::getAdminBank(['id' => $id], '*', true);
            if (empty($result)) {
                return ['code' => '3000'];
            }
            return ['code' => '200', 'admin_bank' => $result];
        }
        if (!empty($abbrev)) {
            array_push($where, ['abbrev', '=', $abbrev]);
        }
        if (!empty($bank_name)) {
            array_push($where, ['bank_name', 'LIKE', '%' . $bank_name . '%']);
        }
        if (!empty($status)) {
            array_push($where, ['status', '=', $status]);
        } else {
            array_push($where, ['status', 'IN', '1,2']);
        }
        $offset = $pageNum * ($page - 1);
        $result = DbAdmin::getAdminBank($where, '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $total = DbAdmin::getAdminBankCount($where);
        return ['code' => '200', 'total' => $total, 'admin_bank' => $result];
    }

    /**
     * 用户提现记录
     * @param $conId
     * @param $bank_card
     * @param $bank_name
     * @param $min_money
     * @param $max_money
     * @param $invoice
     * @param $status
     * @param $wtype
     * @param $stype
     * @param $start_time
     * @param $end_time
     * @param $page
     * @param $pageNum
     * @param $id
     * @return string
     * @author rzc
     */
    public function getLogTransfer($bank_card = '', $abbrev = '', $bank_mobile = '', $user_name = '', $bank_name = '', $min_money = '', $max_money = '', $invoice = '', $status = '', $stype = '', $wtype = '', $start_time = '', $end_time = '', $page = '', $pageNum = '', $id = '') {
        $offset = ($page - 1) * $pageNum;
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $where = [];
        if (!empty($id)) {
            array_push($where, ['id', '=', $id]);
            $result = DbUser::getLogTransfer($where, '*', true);
            if (empty($result)) {
                return ['code' => '3000'];
            }
            return ['code' => '200', 'log_transfer' => $result];
        }
        if (!empty($bank_card)) {
            array_push($where, ['bank_card', '=', $bank_card]);
        }
        if (!empty($abbrev)) {
            array_push($where, ['abbrev', '=', $abbrev]);
        }
        if (!empty($bank_mobile)) {
            array_push($where, ['bank_mobile', '=', $bank_mobile]);
        }
        if (!empty($user_name)) {
            array_push($where, ['user_name', 'LIKE', '%' . $user_name . '%']);
        }
        if (!empty($bank_name)) {
            array_push($where, ['bank_name', 'LIKE', '%' . $bank_name . '%']);
        }
        if (!empty($min_money)) {
            array_push($where, ['money', '>=', $min_money]);
        }
        if (!empty($max_money)) {
            array_push($where, ['money', '<=', $max_money]);
        }
        if (!empty($invoice)) {
            array_push($where, ['invoice', '=', $invoice]);
        }
        if (!empty($status)) {
            array_push($where, ['status', '=', $status]);
        }
        if (!empty($wtype)) {
            array_push($where, ['wtype', '=', $wtype]);
        }
        if (!empty($stype)) {
            array_push($where, ['stype', '=', $stype]);
        }
        if (!empty($start_time)) {
            $start_time = strtotime($start_time);
            array_push($where, ['create_time', '>=', $start_time]);
        }
        if (!empty($end_time)) {
            $end_time = strtotime($end_time);
            array_push($where, ['create_time', '<=', $end_time]);
        }
        $result = DbUser::getLogTransfer($where, '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        foreach ($result as $key => $value) {
            if ($value['stype'] == 1) {
                $result[$key]['real_money']   = bcmul(bcdiv(bcsub(100, $value['proportion'], 2), 100, 2), $value['money'], 2);
                $result[$key]['deduct_money'] = bcmul(bcdiv($value['proportion'], 100, 2), $value['money'], 2);
            }
        }
        $total = DbUser::countLogTransfer($where);
        return ['code' => '200', 'total' => $total, 'log_transfer' => $result];
    }

    /**
     * 审核用户提现
     * @param $conId
     * @param $bank_card
     * @param $bank_mobile
     * @param $user_name
     * @param $status
     * @param $page
     * @param $page_num
     * @param $id
     * @return string
     * @author rzc
     */
    public function checkUserTransfer(int $id, int $status, $message = '') {
        $transfer     = DbUser::getLogTransfer(['id' => $id], '*', true);
        $userRedisKey = Config::get('rediskey.user.redisKey');
        if (empty($transfer)) {
            return ['code' => '3000'];
        }
        if ($transfer['status'] != 1) {
            return ['code' => '3004'];
        }
        if ($transfer['wtype'] == 1) { //提现方式 1 银行
            if ($status == 3) { //审核不通过
                $indexUser = DbUser::getUserInfo(['id' => $transfer['uid']], 'id,commission,bounty,nick_name,mobile', true);
                if ($transfer['stype'] == 2) { //2.佣金提现
                    $tradingData = [
                        'uid'          => $transfer['uid'],
                        'trading_type' => 2,
                        'change_type'  => 10,
                        'money'        => $transfer['money'],
                        'befor_money'  => $indexUser['commission'],
                        'after_money'  => bcadd($indexUser['commission'], $transfer['money'], 2),
                        'message'      => $message,
                    ];
                } elseif ($transfer['stype'] == 4) { //奖励金提现
                    $tradingData = [
                        'uid'          => $transfer['uid'],
                        'trading_type' => 3,
                        'change_type'  => 10,
                        'money'        => $transfer['money'],
                        'befor_money'  => $indexUser['bounty'],
                        'after_money'  => bcadd($indexUser['bounty'], $transfer['money'], 2),
                        'message'      => $message,
                    ];
                }

                // print_r($indexUser);die;
                Db::startTrans();
                try {
                    if ($transfer['stype'] == 2) { //2.佣金提现
                        DbUser::modifyCommission($transfer['uid'], $transfer['money'], 'inc');
                    } elseif ($transfer['stype'] == 4) { //4.奖励金提现
                        DbUser::modifyBounty($transfer['uid'], $transfer['money'], 'inc');
                    }
                    DbUser::editLogTransfer(['status' => $status, 'message' => $message], $id);
                    DbOrder::addLogTrading($tradingData);
                    Db::commit();
                    $this->redis->del($userRedisKey . 'userinfo:' . $transfer['uid']);
                    return ['code' => '200'];
                } catch (\Exception $e) {
                    Db::rollback();
                    exception($e);
                    return ['code' => '3007']; //审核失败
                }
            } elseif ($status == 2) { //审核通过
                Db::startTrans();
                try {
                    DbUser::editLogTransfer(['status' => $status, 'message' => $message], $id);
                    Db::commit();
                    /* 发送短信通知 */
                    if ($transfer['stype'] == 2) { //2.佣金提现
                        $wtype = 7;
                    } elseif ($transfer['stype'] == 4) { //4.奖励金提现
                        $wtype = 8;
                    }
                    $user_identity = DbUser::getUserInfo(['id' => $transfer['uid'], 'user_identity', true])['user_identity'];
                    $message_task  = DbModelMessage::getMessageTask([['wtype', '=', $wtype], ['status', '=', 2], ['type', 'in', '1,' . $user_identity + 1]], 'type,mt_id,trigger_id', true);
                    if (!empty($message_task)) {
                        /* 获取触发器 */
                        $trigger = DbModelMessage::getTrigger(['id' => $message_task['trigger_id'], 'status' => 2], 'start_time,stop_time', true);
                        if (!empty($trigger)) {
                            if (strtotime($trigger['start_time']) < time() && strtotime($trigger['stop_time']) > time()) {
                                /* 获取消息模板 */
                                $message_template = DbModelMessage::getMessageTemplate(['id' => $message_task['mt_id'], 'status' => 2], 'template', true);
                                if (!empty($message_template)) { //模板不为空
                                    $message_template = $message_template['template'];
                                    //匹配模板中需要查询内容
                                    $message_template = str_replace('{{[nick_name]}}', $indexUser['nick_name'], $message_template);
                                    $message_template = str_replace('{{[money]}}', $transfer['money'], $message_template);

                                    $Note = new Note;
                                    $send = $Note->sendSms($indexUser['mobile'], $message_template);
                                    // print_r($send);die;
                                    // $thisorder['linkphone'];
                                }
                            }
                        }
                    }
                    return ['code' => '200'];
                } catch (\Exception $e) {
                    Db::rollback();
                    return ['code' => '3007']; //审核失败
                }
            }
        }

    }

    /**
     * 获取用户提交银行卡
     * @param $conId
     * @param $bank_card
     * @param $bank_mobile
     * @param $user_name
     * @param $status
     * @param $page
     * @param $page_num
     * @param $id
     * @return string
     * @author rzc
     */
    public function getUserBank($id = '', $bank_card = '', $bank_mobile = '', $user_name = '', $status = '', int $page, int $pageNum) {
        $offset = ($page - 1) * $pageNum;
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $where = [];
        if (!empty($id)) {
            array_push($where, ['id', '=', $id]);
            $result = DbUser::getUserBank($where, '*', true);
            if (empty($result)) {
                return ['code' => '3000'];
            }
            return ['code' => '200', 'userbank' => $result];
        }
        if (!empty($bank_card)) {
            array_push($where, ['bank_card', '=', $bank_card]);
        }
        if (!empty($bank_mobile)) {
            array_push($where, ['bank_mobile', '=', $bank_mobile]);
        }
        if (!empty($user_name)) {
            array_push($where, ['user_name', 'LIKE', '%' . $user_name . '%']);
        }
        if (!empty($status)) {
            array_push($where, ['status', '=', $status]);
        }
        $result = DbUser::getUserBank($where, '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $total = DbUser::countUserBank($where);
        return ['code' => '200', 'total' => $total, 'userbank' => $result];
    }

    function delDataEmptyKey($data) {
        foreach ($data as $key => $value) {
            if (!$value) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * 审核用户提交银行卡
     * @param $id
     * @param $status
     * @param $message
     * @param $error_fields
     * @return string
     * @author rzc
     */
    public function checkUserBank($id, $status, $message = '', $error_fields = '') {
        $userbank = DbUser::getUserBank(['id' => $id], '*', true);
        if (empty($userbank)) {
            return ['code' => '3000'];
        }
        if ($userbank['status'] == 2 || $userbank['status'] == 3) {
            return ['code' => '3006'];
        }
        if ($status == 4) {
            if ($userbank['status'] != 1) {
                return ['code' => '3002'];
            }
        }
        if ($status == $userbank['status']) {
            return ['code' => '3002'];
        }
        $check                 = [];
        $check['status']       = $status;
        $check['message']      = $message;
        $check['error_fields'] = $error_fields;
        Db::startTrans();
        try {
            DbUser::editUserBank($check, $id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011']; //添加失败
        }
    }

    /**
     * 获取提现比率
     * @return string
     * @author rzc
     */
    public function getInvoice() {
        // echo ;die;
        $invoice = @file_get_contents(Env::get('root_path') . "invoice.json");
        if ($invoice == false) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'invoice' => json_decode($invoice, true)];

    }

    public function editInvoice($cmsConId, $has_invoice, $no_invoice) {
        $redisManageInvoice     = Config::get('rediskey.manage.redisManageInvoice');
        $invoice                = [];
        $invoice['has_invoice'] = $has_invoice;
        $invoice['no_invoice']  = $no_invoice;
        $invoice                = json_encode($invoice, true);
        file_put_contents(Env::get('root_path') . "invoice.json", $invoice);
        $this->redis->set($redisManageInvoice, $invoice);
        return ['code' => '200', 'invoice' => json_decode($invoice, true)];
        // print_r($invoice);die;
    }
}