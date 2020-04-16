<?php

namespace app\common\action\index;

//use third\AliSms;
use app\common\action\index\CommonIndex;
use app\facade\DbSup;
use app\facade\DbAdmin;
use config;
use Env;
use think\Db;
use third\Zthy;

/**
 * H5站接口
 * @package app\common\wap
 */
class Wap extends CommonIndex
{

    public function __construct()
    {
        parent::__construct();
        $this->redisAccessTokenTencent = Config::get('redisKey.weixin.redisAccessTokenTencent');
        $this->redisTicketTencent      = Config::get('redisKey.weixin.redisTicketTencent');
    }

    /**
     * 验证提交的验证码是否正确
     * @param $stype
     * @param $mobile
     * @param $vercode
     * @return bool
     * @author zyr
     */
    private function checkVercode($stype, $mobile, $vercode)
    {
        $redisKey  = $this->redisKey . 'vercode:' . $mobile . ':' . $stype;
        $redisCode = $this->redis->get($redisKey); //服务器保存的验证码
        if ($redisCode == $vercode) {
            return true;
        }
        return false;
    }

    private $JSAPI_TICKET;

    /**
     * 报名活动详情
     * @param $promote_id
     * @return array
     * @author rzc
     */
    public function getSupPromote($promote_id)
    {
        $promote = DbSup::getSupPromote(['id' => $promote_id], 'id,title,big_image,share_title,share_image,share_count,bg_image', true);
        if (empty($promote)) {
            return ['code' => '3001']; //推广活动不存在
        }
        $banner = DbSup::getOnePromoteImage(['promote_id' => $promote_id, 'image_type' => 2], 'image_path', ['order_by' => 'desc']);
        $detail = DbSup::getOnePromoteImage(['promote_id' => $promote_id, 'image_type' => 1], 'image_path', ['order_by' => 'desc']);

        return ['code' => '200', 'promote' => $promote, 'banner' => $banner, 'detail' => $detail];
    }

    /**
     * 报名参加活动
     * @param $promote_id
     * @return array
     * @author rzc
     */
    public function SupPromoteSignUp($conId, $mobile, $nick_name, $promote_id, $sex, $age, $signinfo,  $study_name, $study_mobile)
    {
        // $stype = 5;
        // if ($this->checkVercode($stype, $mobile, $vercode) === false) {
        //     return ['code' => '3008']; //验证码错误
        // }
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $promote = DbSup::getSupPromote(['id' => $promote_id], 'id,share_count', true);
        if (empty($promote)) {
            return ['code' => '3003']; //推广活动不存在
        }
        $promotesignup = DbSup::getSupPromoteSignUp(['promote_id' => $promote_id, 'study_name' => $study_name, 'study_mobile' => $study_mobile, 'uid' => $uid], true);
        if (!empty($promotesignup)) {
            return ['code' => '3005'];
        }
        $data = [
            'uid'        => $uid,
            'promote_id' => $promote_id,
            'mobile'     => $mobile,
            'nick_name'  => $nick_name,
            'sex'        => $sex,
            'age'        => $age,
            'signinfo'   => $signinfo,
            'study_name'   => $study_name,
            'study_mobile'   => $study_mobile,
        ];
        DbSup::saveSupPromoteSignUp($data);
        // $this->redis->del($this->redisKey . 'vercode:' . $mobile . ':' . $stype); //成功后删除验证码
        return ['code' => '200'];
    }

    public function getPromoteShareNum($promote_id, $conId)
    {
        $uid = $this->getUidByConId($conId);
        if (empty($uid)) {
            return ['code' => '3002'];
        }
        $promote = DbSup::getSupPromote(['id' => $promote_id], 'id,share_count', true);
        if (empty($promote)) {
            return ['code' => '3001']; //推广活动不存在
        }
        $has = DbSup::getSupPromoteShareLog(['promote_id' => $promote_id, 'uid' => $uid], 'id,share_num', true);

        if (empty($has)) {
            $share_num = 1;
            $data      = [
                'promote_id' => $promote_id,
                'uid'        => $uid,
                'share_num'  => $share_num,
            ];
            DbSup::saveSupPromoteShareLog($data);
        } else {
            $share_num = $has['share_num'] + 1;
            $data      = [
                'share_num' => $share_num,
            ];
            DbSup::updateSupPromoteShareLog($data, $has['id']);
        }

        $is_share = 2;

        if ($share_num < $promote['share_count']) {
            $is_share = 1;
        }
        return ['code' => '200', 'is_share' => $is_share];
    }

    public function getJsapiTicket($durl)
    {
        $jsapiTicket = $this->getWXJsapiTicket();
        $timestamp   = time();
        $nonceStr    = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序

        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$durl";

        $signature = sha1($string);

        $signPackage = [
            "appId"     => (string) Env::get('weixin.weixin_appid'),
            "nonceStr"  => (string) $nonceStr,
            "timestamp" => (string) $timestamp,
            "url"       => (string) $durl,
            "signature" => (string) $signature,
        ];
        return ['code' => 200, 'signPackage' => $signPackage];
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str   = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    //获取微信jsapi_ticket
    public function getWXJsapiTicket()
    {
        $access_token = $this->redis->get($this->redisAccessTokenTencent);

        if (empty($access_token)) {
            $access_token = $this->getWeiXinAccessTokenTencent();
        }
        if (($jsapi_ticket = $this->redis->get($this->redisTicketTencent)) === false) {
            $jsapi_ticket = $this->getTicketTencent($access_token);
            // return ['code' => '3002', 'msg' => '微信获取jsapi_ticket失败'];
        }

        return $jsapi_ticket;
    }

    public function samplingReport($card_number, $passwd, $mobile, $from_id = '')
    {
        $card = DbAdmin::getSamplingCard(['card_number' => $card_number, 'passwd' => $passwd], '*', true);
        if (empty($card)) {
            return ['code' => '3001', 'msg' => '该卡不存在'];
        }
        if (DbAdmin::getSamplingReport(['card_number' => $card_number], '*', true)) {
            return ['code' => '3002', 'msg' => '该卡已被领取'];
        }
        if (checkMobile($mobile) == false) {
            return ['code' => '3003', 'msg' => '手机号格式错误'];
        }
        switch ($card['type']) {
            case '1':
                $goods_id = 2056;
                break;

            default:
                $goods_id = 2155;
                break;
        }
        $data = [];
        $data = [
            'card_number' => $card_number,
            'type' => $card['type'],
            'goods_id' => $goods_id,
            'mobile' => $mobile,
            'from_id' => $from_id,
        ];
        Db::startTrans();
        try {
            DbAdmin::addSamplingReport($data);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005', 'Errormsg' => 'add false']; //添加失败
        }
    }

    public function getsamplingReport($mobile)
    {
        $result = DbAdmin::getSamplingReport(['mobile' => $mobile, 'status' => 1], '*', false);
        return ['code' => '200', 'data' => $result];
    }

    public function getBloodSampling($province_id, $city_id, $area_id)
    {
        $where = [];
        if (empty($province_id) || empty($city_id) || empty($area_id)) {
        }
        array_push($where, ['province_id', '=', $province_id]);
        array_push($where, ['city_id', '=', $city_id]);
        array_push($where, ['area_id', '=', $area_id]);
        $result = DbAdmin::getBloodSamplingAddress($where, '*', false);
        $total = DbAdmin::countBloodSamplingAddress($where);
        return ['code' => '200', 'total' => $total, 'result' => $result];
    }
}
