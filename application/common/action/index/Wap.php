<?php

namespace app\common\action\index;

//use third\AliSms;
use app\common\action\index\CommonIndex;
use app\facade\DbSup;
use config;
use Env;
use third\Zthy;
use think\Db;
use app\facade\DbUser;


/**
 * H5站接口
 * @package app\common\wap
 */
class Wap extends CommonIndex {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 报名活动详情
     * @param $promote_id
     * @return array
     * @author rzc
     */
    public function getSupPromote($promote_id) {
        $promote = DbSup::getSupPromote(['id' => $promote_id], 'id,title,big_image,share_title,share_image,share_count,bg_image', true);
        if (empty($promote)) {
            return ['code' => '3001'];//推广活动不存在
        }
        return ['code' => '200', 'promote' => $promote];
    }

    /**
     * 报名参加活动
     * @param $promote_id
     * @return array
     * @author rzc
     */
    public function SupPromoteSignUp($conId, $mobile, $nick_name, $promote_id){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $promote = DbSup::getSupPromote(['id' => $promote_id], 'id,share_count', true);
        if (empty($promote)) {
            return ['code' => '3003'];//推广活动不存在
        }
        $promotesignup = DbSup::getSupPromoteSignUp(['promote_id' => $promote_id,'mobile' => $mobile],true);
        if (!empty($promotesignup)) {
            return ['code' => '3005'];
        }
        $data = [
            'uid' => $uid,
            'promote_id' => $promote_id,
            'mobile' => $mobile,
            'nick_name' => $nick_name,
        ];
        DbSup::saveSupPromoteSignUp($data);
        return ['code' => '200'];
    }

    public function getPromoteShareNum($promote_id,$conId){
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $promote = DbSup::getSupPromote(['id' => $promote_id], 'id,share_count', true);
        if (empty($promote)) {
            return ['code' => '3001'];//推广活动不存在
        }
        $has = DbSup::getSupPromoteShareLog(['promote_id' => $promote_id, 'uid' => $uid],'id,share_num',true);
        if (!empty($has)) {
            $share_num = 1;
            $data = [
                'promote_id' => $promote_id,
                'uid' => $uid,
                'share_num' => $share_num,
            ];
            DbSup::saveSupPromoteShareLog($data);
        }else {
            $share_num = $has['share_num'] +1;
            $data = [
                'share_num' => $share_num,
            ];
            DbSup::updateSupPromoteShareLog($data,$has['id']);
        }
       
        $is_share = 2;
        
        if ($share_num < $promote['share_count']) {
            $is_share = 1;
        }
        return ['code' => '200', 'is_share' => $is_share];
    }
}