<?php

namespace app\common\action\pay;

use app\facade\DbOrder;
use app\facade\DbUser;
use pay\wxpay\WxMiniprogramPay;
use cache\Phpredis;
use Config;

/**
 * 支付
 * @package app\common\action\pay
 */
class Payment {
    private $redis;

    public function __construct() {
        $this->redis = Phpredis::getConn();
    }

    public function payment($orderNo, int $payment, int $platform) {
        $orderOutTime = Config::get('conf.order_out_time');//订单过期时间
        if ($payment == 2) {//购买会员订单
            $payType        = 2; //支付类型 1.支付宝 2.微信 3.银联 4.商票
            $memberOrderRow = $this->memberDiamond($orderNo);
            if (empty($memberOrderRow)) {
                return ['code' => '3000'];//订单号不存在
            }
            if ($memberOrderRow['pay_status'] == 2) {//取消
                return ['code' => '3004'];//订单已取消
            } else if ($memberOrderRow['pay_status'] == 3) {//关闭
                return ['code' => '3005'];//订单已关闭
            } else if ($memberOrderRow['pay_status'] == 4) {//已付款
                return ['code' => '3006'];//订单已付款
            }
            if ($memberOrderRow['create_time'] < date('Y-m-d H:i:s', time() - $orderOutTime)) {
                return ['code' => '3007'];//订单已过期
            }
            $orderId    = $memberOrderRow['id'];
            $payMoney   = $memberOrderRow['pay_money'];//要支付的金额
            $uid        = $memberOrderRow['uid'];
            $payType    = $memberOrderRow['pay_type'];
            $logTypeRow = DbOrder::getLogPay(['order_id' => $orderId, 'payment' => $payment, 'status' => 1], 'pay_no', true);
            if (!empty($logTypeRow)) {
                return ['code' => '3008'];//第三方支付已付款
            }
            if ($payType == 2) {//微信支付
                $parameters = $this->wxpay($uid, $platform, $payment, $payMoney, $orderId);
                if ($parameters === false) {
                    return ['code' => '3010'];//创建支付订单失败
                }
                return ['code' => '200', 'parameters' => $parameters];
            }
        } else if ($payment == 1) {//普通订单
            $nomalOrder = $this->nomalOrder($orderNo);
            if (empty($nomalOrder)) {
                return ['code' => '3000'];//不存在需要支付的订单
            }
            if ($nomalOrder['order_status'] == 2) {//取消
                return ['code' => '3004'];//订单已取消
            } else if ($nomalOrder['order_status'] == 3) {//关闭
                return ['code' => '3005'];//订单已关闭
            } else if ($nomalOrder['order_status'] != 1) {//已付款
                return ['code' => '3006'];//订单已付款
            }
            if ($nomalOrder['create_time'] < date('Y-m-d H:i:s', time() - $orderOutTime)) {
                return ['code' => '3007'];//订单已过期
            }
            $orderId        = $nomalOrder['id'];
            $uid            = $nomalOrder['uid'];
            $payType        = $nomalOrder['pay_type'];//支付类型 1.所有第三方支付 2.商票
            $thirdPayType   = $nomalOrder['third_pay_type'];//第三方支付类型1.支付宝 2.微信 3.银联
            $thirdMoney     = $nomalOrder['third_money'];//第三方支付金额
            $deductionMoney = $nomalOrder['deduction_money'];//商票抵扣金额
            Db::startTrans();
            try {
                if ($payType == 2 && $deductionMoney > 0) {//商票支付
                    DbUser::modifyBalance($uid, $deductionMoney, 'dec');
                }
                if ($thirdPayType == 2) {//微信支付
                    $parameters = $this->wxpay($uid, $platform, $payment, $thirdMoney, $orderId);
                    if ($parameters === false) {
                        Db::rollback();
                        return ['code' => '3010'];//创建支付订单失败
                    }
                    Db::commit();
                    return ['code' => '200', 'parameters' => $parameters];
                }
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3010'];//创建支付订单失败
            }
        }
        return ['code' => '3009'];//支付方式暂不支持
    }

    /**
     * 微信支付
     * @param $uid
     * @param $platform
     * @param $payment
     * @param $payMoney
     * @param $orderId
     * @return array
     * @author zyr
     */
    private function wxpay($uid, $platform, $payment, $payMoney, $orderId) {
        //获取openid
        $openType   = Config::get('conf.platform_conf')[Config::get('app.deploy')];
        $userWxinfo = DbUser::getUserWxinfo(['uid' => $uid, 'platform' => $platform, 'openid_type' => $openType], 'openid', true);
        $openid     = $userWxinfo['openid'];
        $payNo      = createOrderNo('mem');
        $data       = [
            'pay_no'   => $payNo,
            'uid'      => $uid,
            'payment'  => $payment,
            'pay_type' => 2,
            'order_id' => $orderId,
            'money'    => bcmul($payMoney, 100, 0),
        ];
        $addRes     = DbOrder::addLogPay($data);
        if (!empty($addRes)) {
            $wxPay  = new WxMiniprogramPay($openid, $data['pay_no'], $data['money']);
            $result = $wxPay->pay();
            return $result;
        }
        return false;
//        return ['code' => '3010'];//创建支付订单失败
    }

    /**
     * 购买会员订单
     * @param $orderNo
     * @return mixed
     * @author zyr
     */
    private function memberDiamond($orderNo) {
        $memberOrderRow = DbOrder::getMemberOrder(['order_no' => $orderNo], 'id,uid,pay_money,pay_status,pay_type,create_time', true);
        return $memberOrderRow;
    }

    /**
     * 普通商品购买订单
     * @param $orderNo
     * @return mixed
     * @author zyr
     */
    private function nomalOrder($orderNo) {
        $field      = 'id,uid,order_status,pay_money,deduction_money,third_money,pay_type,third_pay_type,create_time';
        $nomalOrder = DbOrder::getUserOrder($field, ['order_no' => $orderNo, 'order_status' => 1], true);
        return $nomalOrder;
    }

    public function wxPayCallback($res) {
        $this->redis->setEx('test', 1800, json_encode($res));
//        $res = '{"appid":"wx112088ff7b4ab5f3","attach":"255","bank_type":"CFT","cash_fee":"1425","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"0lfvboi6rnpxe2g49ksunp1298e008mu","openid":"o83f0wLtc3Wlx9sv8yyECXv_Enh0","out_trade_no":"PAYSN201807041721287496","result_code":"SUCCESS","return_code":"SUCCESS","sign":"C0B76E319EDEC158036882A56044B2D7","time_end":"20180704172134","total_fee":"1425","trade_type":"JSAPI","transaction_id":"4200000128201807043248657648"}';
//        print_r(json_decode($res, true));
    }
}