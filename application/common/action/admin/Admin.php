<?php

namespace app\common\action\admin;

use app\facade\DbAdmin;
use app\facade\DbUser;
use app\facade\DbOrder;
use app\facade\DbImage;
use Config;
use think\Db;
use cache\Phpredis;

class Admin extends CommonIndex {
    private $cmsCipherUserKey = 'adminpass';//用户密码加密key
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
        $getPass   = $this->getPassword($passwd, $this->cmsCipherUserKey);//用户填写的密码
        $adminInfo = DbAdmin::getAdminInfo(['admin_name' => $adminName, 'status' => 1], 'id,passwd', true);
        if (empty($adminInfo)) {
            return ['code' => '3002'];//用户不存在
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
     * @return array
     * @author rzc
     */
    public function getAdminUsers(){
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
    public function adminRemittance($cmsConId,$passwd,$stype,$nick_name,$mobile,$credit,$message){
        $message = $message ?? '';
        $adminId   = $this->getUidByConId($cmsConId);
        $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'id,passwd,status', true);
        if ($adminInfo['passwd'] !== $this->getPassword($passwd, $this->cmsCipherUserKey)) {
            return ['code' => '3001'];
        }
        /* $uid = deUid($uid);
        if (empty($uid)) {
            return ['code' => '3004'];
        } */
        $indexUser = DbUser::getUserInfo(['nick_name'=>$nick_name,'mobile'=>$mobile], 'id,balance,commission,integral', true);
        if (empty($indexUser)) {
            return ['code' => '3004'];
        }
        if ($stype == 1) {
            if ($credit + $indexUser['balance'] <0 ) {
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
    public function auditAdminRemittance($cmsConId,$status,int $id){
        $userRedisKey = Config::get('rediskey.user.redisKey');
        $adminId   = $this->getUidByConId($cmsConId);
        $adminInfo = DbAdmin::getAdminInfo(['id' => $adminId], 'id,stype', true);
        if ($adminInfo['id'] != 1){
            return ['code' => '3002'];
        }
        $remittance = DbAdmin::getAdminRemittance(['id' => $id],'*',true);
        if (empty($remittance)) {
            return ['code' => '3003'];
        }
        if ($remittance['status'] != 1) {
            return ['code' => '3004'];
        }
        $indexUser = DbUser::getUserInfo(['id' => $remittance['uid']], 'id,balance,commission,integral', true);
        if ($status == 2){//审核不通过
            DbAdmin::editRemittance(['audit_admin_id' => $adminId,'status' => 3],$id);
            return ['code' => '200', 'msg' => '审核失败'];
        }
        if ($remittance['stype'] != 3) {
            
            if ($remittance['stype'] == 1) {//商票
                $tradingData = [
                    'uid'          => $remittance['uid'],
                    'trading_type' => 1,
                    'change_type'  => 8,
                    'money'        => $remittance['credit'],
                    'befor_money'  => $indexUser['balance'],
                    'after_money'  => bcadd($indexUser['balance'], $remittance['credit'], 2),
                    'message'      => $remittance['message'],
                ];
            } elseif ($remittance['stype'] == 2) {//佣金
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
                    DbAdmin::editRemittance(['audit_admin_id' => $adminId,'status' => 2],$id);
                    if (!empty($tradingData)) {
                        DbOrder::addLogTrading($tradingData);
                    }
                    
                    if ($remittance['stype'] == 1) {//商票
                        DbUser::modifyBalance($remittance['uid'], $remittance['credit'],'inc');
                    }elseif($remittance['stype'] == 2){//佣金
                        DbUser::modifyCommission($remittance['uid'], $remittance['credit'],'inc');
                    }
                    $this->redis->del($userRedisKey . 'userinfo:' . $remittance['uid']);
                    Db::commit();
                    return ['code' => '200'];
                } catch (\Exception $e) {
                    // print_r($e);die;
                    Db::rollback();
                    return ['code' => '3009'];
                }
        }else{
            $user_integral             = [];
            $user_integral['result_integral'] = $remittance['credit'];
            $user_integral['message']         = $remittance['message'];
            $user_integral['uid']             = $remittance['uid'];
            $user_integral['status']          = 2;
            $user_integral['stype']           = 2;
            
            Db::startTrans();
                try {
                    DbAdmin::editRemittance(['audit_admin_id' => $adminId,'status' => 2],$id);
                    DbUser::modifyIntegral($remittance['uid'], $remittance['credit'],'inc');
                    DbUser::addLogIntegral($user_integral);
                    $this->redis->del($userRedisKey . 'userinfo:' . $remittance['uid']);
                    Db::commit();
                    return ['code' => '200'];
                } catch (\Exception $e) {
                    Db::rollback();
                    print_r($e);die;
                    return ['code' => '3009'];
                }
        }
    }

    /**
     * 获取充值列表
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
    public function getAdminRemittance(int $page,int $pageNum,$initiate_admin_id = 0,$audit_admin_id = 0,$status = 0,$min_credit = 0,$max_credit = 0,$uid = 0,$stype = 0,$start_time = '',$end_time = ''){
        $offset = $pageNum * ($page - 1);
        $where = [];
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
        $result = DbAdmin::getAdminRemittance($where, '*',false,['id'=>'desc'],$offset.','.$pageNum);
        // print_r(count($result));die;
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $total = DbAdmin::getCountAdminRemittance($where);
        return ['code' => '200', 'total' => $total ,'AdminRemittances' => $result];
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
    public function addAdminBank($abbrev,$bank_name,$icon_img = '',$bg_img = '',$status){
        $is_bank_abbrev = DbAdmin::getAdminBank(['abbrev' => $abbrev],'id',true);
        $is_bank_name = DbAdmin::getAdminBank(['bank_name' => $bank_name],'id',true);
        if ($is_bank_abbrev || $is_bank_name) {
            return ['code' => '3004'];
        }
        $icon_img   = $icon_img ?? '';
        $bg_img     = $bg_img ?? '';
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
                $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
                if (empty($logImage)) {//图片不存在
                    return ['code' => '3010'];//图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            if (!empty($admin_bank['bg_img'])) {
                $image    = filtraImage(Config::get('qiniu.domain'), $admin_bank['bg_img']);
                $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
                if (empty($logImage)) {//图片不存在
                    return ['code' => '3010'];//图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            $add = DbAdmin::saveAdminBank($admin_bank);
            Db::commit();
            return ['code' => '200', 'add_id' => $add];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011'];//添加失败
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
    public function editAdminBank(int $id,$abbrev = '',$bank_name = '',$icon_img = '',$bg_img = '',$status = ''){
        if (empty($abbrev) && empty($bank_name) && empty($icon_img) && empty($bg_img) && empty($status)) {
            return ['code' => '3004'];
        }
        $admin_bank = DbAdmin::getAdminBank(['id' => $id],'id',true);
        if (empty($admin_bank)) {
            return ['code' => '3000'];
        }
        if (!empty($abbrev)) {
            $has_abbrev = DbAdmin::getAdminBank([['abbrev' ,'=', $abbrev],['id','<>',$id]],'id',true);
            // print_r($has_abbrev);die;
            if ($has_abbrev) {
                return ['code' => '3005'];
            }
        }
        
        if (!empty($bank_name)) {
            
        // print_r($admin_bank);die;
            $has_bank_name = DbAdmin::getAdminBank([['bank_name','=', $bank_name],['id','<>',$id]],'id',true);
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
        $admin_bank = $this->delDataEmptyKey($admin_bank);
        Db::startTrans();
        try {
            if (!empty($admin_bank['icon_img'])) {
                // $image    = $admin_bank['icon_img'];
                $logImage = DbImage::getLogImage($admin_bank['icon_img'], 2);//判断时候有未完成的图片
                if (empty($logImage)) {//图片不存在
                    return ['code' => '3010'];//图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            if (!empty($admin_bank['bg_img'])) {
                // $image    =  $admin_bank['bg_img'];
                $logImage = DbImage::getLogImage($admin_bank['bg_img'], 2);//判断时候有未完成的图片
                if (empty($logImage)) {//图片不存在
                    return ['code' => '3010'];//图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            $add = DbAdmin::editAdminBank($admin_bank,$id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011'];//添加失败
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
    public function getAdminBank(int $page,int $pageNum,$abbrev = '',$bank_name = '',$status = '',$id = ''){
        $where = [];
        if (!empty($id)) {
            $result = DbAdmin::getAdminBank(['id' => $id], '*', true);
            if (empty($result)) {
                return ['code' => '3000'];
            }
            return ['code' => '200','admin_bank' => $result];
        }
        if (!empty($abbrev)) {
            array_push($where, ['abbrev', '=', $abbrev]);
        }
        if (!empty($bank_name)) {
            array_push($where, ['bank_name', 'LIKE', '%'.$bank_name.'%']);
        }
        if (!empty($status)) {
            array_push($where, ['status', '=', $status]);
        } else {
            array_push($where, ['status', 'IN', '1,2']);
        }
        $offset = $pageNum * ($page - 1);
        $result = DbAdmin::getAdminBank($where, '*', false,['id'=>'desc'],$offset.','.$pageNum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $total = DbAdmin::getAdminBankCount($where);
        return ['code' => '200','total' => $total,'admin_bank' => $result];
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
    public function getLogTransfer($bank_card = '',$abbrev = '',$bank_mobile = '',$user_name = '',$bank_name = '',$min_money = '',$max_money = '',$invoice = '',$status = '',$stype = '',$wtype = '',$start_time = '',$end_time = '',$page = '',$pageNum = '',$id = ''){
        $offset = ($page - 1) * $pageNum;
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $where = [];
        if (!empty($id)){
            array_push($where,['id','=',$id]);
            $result = DbUser::getLogTransfer($where,'*',true);
            if (empty($result)) {
                return ['code' => '3000'];
            }
            return ['code' => '200','log_transfer' => $result];
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
            array_push($where, ['user_name', 'LIKE', '%'.$user_name.'%']);
        }
        if (!empty($bank_name)) {
            array_push($where, ['bank_name', 'LIKE', '%'.$bank_name.'%']);
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
        $result =  DbUser::getLogTransfer($where,'*',false,['id'=>'desc'],$offset.','.$pageNum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200','log_transfer' => $result];
    }

    function delDataEmptyKey($data) {
        foreach ($data as $key => $value) {
            if (!$value) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}