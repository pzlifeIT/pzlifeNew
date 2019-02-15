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
        $orderId        = 0;
        $payMoney       = 0;//要支付的金额
        $uid            = 0;//支付人
        $payType        = 2; //支付类型 1.支付宝 2.微信 3.银联 4.线下
        $orderOutTime   = Config::get('conf.order_out_time');//订单过期时间
        $memberOrderRow = [];
        if ($payment == 2) {//购买会员订单
            $memberOrderRow = $this->memberDiamond($orderNo);
            if (empty($memberOrderRow)) {
                return ['code' => '3000'];//订单号不存在
            }
            if ($memberOrderRow['pay_status'] == 2) {//取消
                return ['code' => '3004'];//订单已取消
            }
            if ($memberOrderRow['pay_status'] == 3) {//关闭
                return ['code' => '3005'];//订单已关闭
            }
            if ($memberOrderRow['pay_status'] == 3) {//已付款
                return ['code' => '3006'];//订单已关闭
            }
//            if ($memberOrderRow['create_time'] < date('Y-m-d H:i:s', time() - $orderOutTime)) {
//                return ['code' => '3007'];//订单已过期
//            }
            $orderId  = $memberOrderRow['id'];
            $payMoney = $memberOrderRow['pay_money'];
            $uid      = $memberOrderRow['uid'];
            $payType  = $memberOrderRow['pay_type'];
        }
        $logTypeRow = DbOrder::getLogPay(['order_id' => $orderId, 'payment' => $payment, 'status' => 1], 'pay_no', true);
        if (!empty($logTypeRow)) {
            return ['code' => '3008'];//订单已支付
        }
        if ($payType == 2) {//微信支付
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
            return $this->wxpay($openid, $data);
        }
    }

    private function wxpay($openid, $data) {
        $addRes = DbOrder::addLogPay($data);
        if (!empty($addRes)) {
            $wxPay  = new WxMiniprogramPay($openid, $data['pay_no'], $data['money']);
            $result = $wxPay->pay();
            return $result;
        }
        return ['code' => '3010'];//创建支付订单失败
    }

    private function memberDiamond($orderNo) {
        $memberOrderRow = DbOrder::getMemberOrder(['order_no' => $orderNo], 'id,uid,pay_money,pay_status,pay_type,create_time', true);
        return $memberOrderRow;
    }

    public function wxPayCallback($res) {
        $this->redis->setEx('test',1800,json_encode($res));
//        $res = '{"appid":"wx112088ff7b4ab5f3","attach":"255","bank_type":"CFT","cash_fee":"1425","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"0lfvboi6rnpxe2g49ksunp1298e008mu","openid":"o83f0wLtc3Wlx9sv8yyECXv_Enh0","out_trade_no":"PAYSN201807041721287496","result_code":"SUCCESS","return_code":"SUCCESS","sign":"C0B76E319EDEC158036882A56044B2D7","time_end":"20180704172134","total_fee":"1425","trade_type":"JSAPI","transaction_id":"4200000128201807043248657648"}';
//        print_r(json_decode($res, true));
    }
}