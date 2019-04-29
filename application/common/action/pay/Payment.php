<?php

namespace app\common\action\pay;

use app\facade\DbOrder;
use app\facade\DbUser;
use pay\wxpay\WxMiniprogramPay;
use cache\Phpredis;
use Config;
use think\Db;
use function Qiniu\json_decode;

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
//            if ($memberOrderRow['create_time'] < date('Y-m-d H:i:s', time() - $orderOutTime)) {
//                return ['code' => '3007'];//订单已过期
//            }
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
            $orderId      = $nomalOrder['id'];
            $uid          = $nomalOrder['uid'];
            $payType      = $nomalOrder['pay_type'];//支付类型 1.所有第三方支付 2.商票
            $thirdPayType = $nomalOrder['third_pay_type'];//第三方支付类型1.支付宝 2.微信 3.银联
            $thirdMoney   = $nomalOrder['third_money'];//第三方支付金额
            $logTypeRow   = DbOrder::getLogPay(['order_id' => $orderId, 'payment' => $payment, 'status' => 1], 'pay_no', true);
            if (!empty($logTypeRow)) {
                return ['code' => '3008'];//第三方支付已付款
            }
            Db::startTrans();
            try {
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
        $payNo      = createOrderNo('wpy');
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
            /* 调用模板消息ID 2019/04/28 */
            $logPayRes    = DbOrder::getLogPay(['pay_no' => $payNo], 'id,order_id,payment', true);
            DbOrder::updateLogPay(['prepay_id' => $result['prepay_id']], $logPayRes['id']);
            return $result;
        }
        return false;
    }

    /**
     * 购买会员订单
     * @param $orderNo
     * @return mixed
     * @author zyr
     */
    private function memberDiamond($orderNo) {
        $memberOrderRow = DbOrder::getMemberOrder(['order_no' => $orderNo], 'id,uid,pay_money,pay_status,pay_type,create_time,from_uid', true);
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
        $nomalOrder = DbOrder::getOrder($field, ['order_no' => $orderNo, 'order_status' => 1], true);
        return $nomalOrder;
    }

    /**
     * 支付回调
     * @param $res
     * @author zyr
     */
    public function wxPayCallback($res) {
        $wxReturn   = $this->xmlToArray($res);
        $notifyData = $wxReturn;
        $sign       = $wxReturn['sign'];//微信返回的签名
        unset($wxReturn['sign']);
        $makeSign = $this->makeSign($wxReturn, Config::get('conf.wx_pay_key'));
        if ($makeSign == $sign) {//验证签名
            $logPayRes    = DbOrder::getLogPay(['pay_no' => $wxReturn['out_trade_no'], 'status' => 2], 'id,order_id,payment,prepay_id', true);
            $data         = [
                'notifydata' => json_encode($notifyData),
                'status'     => 1,
                'pay_time'   => time(),
            ];
            $orderRes     = [];
            $memOrderRes  = [];
            $orderData    = [];
            $memOrderData = [];
            if ($logPayRes['payment'] == 1) {//1.普通订单
                $orderRes  = DbOrder::getOrder('id,uid,create_time,pay_time,order_status,order_no', ['id' => $logPayRes['order_id'], 'order_status' => 1], true);
                $orderData = [
                    'third_order_id' => $wxReturn['transaction_id'],
                    'order_status'   => 4,
                    'pay_time'       => time(),
                    'third_time'     => time(),
                ];
            } else if ($logPayRes['payment'] == 2) {//2.购买会员订单
                $memOrderRes  = DbOrder::getMemberOrder(['id' => $logPayRes['order_id'], 'pay_status' => 1], 'id', true);
                $memOrderData = [
                    'pay_time'   => time(),
                    'pay_status' => 4,
                ];
            }
            if (!empty($orderRes) || !empty($memOrderRes)) {

                Db::startTrans();
                try {
                    DbOrder::updateLogPay($data, $logPayRes['id']);
                    if (!empty($orderData)) {
                        DbOrder::updataOrder($orderData, $orderRes['id']);
                        $redisListKey = Config::get('rediskey.order.redisOrderBonus');
                        $this->redis->rPush($redisListKey, $orderRes['id']);

                        /* 发送模板消息开始 2019/04/28 */
                        $user_wxinfo               = DbUser::getUserWxinfo(['uid' => $orderRes['uid']], 'openid', true);
                        $order                     = DbOrder::getOrderDetail(['uid' => $orderRes['uid'], 'order_no' => $orderRes['order_no']], '*');
                        $data['keyword1'][] = $orderRes['create_time'];
                        $data['keyword2'][] = $orderRes['order_no'];
                        $data['keyword3'][] = '';
                        // $goo
                        // 商品名称
                        foreach ($order as $key => $value) {
                            //    echo $value['sku_json'];die;
                            $data['keyword3'][] .= $value['goods_name'] . $value['goods_price'] . 'X' . $value['goods_num'] . '【' . json_decode($value['sku_json'])[0] . '】 ';
                        }
                        $data['keyword4'][] = '代发货';
                        $data['keyword5'][] = $orderRes['pay_time'];
                
                        $send_data                = [];
                        $send_data['touser']      = $user_wxinfo['openid'];
                        $send_data['template_id'] = 'sTxQPX6BWBAo7In_nr9KbTlV6tEAhINijB2rSjHrKz8';
                        $send_data['page']        = 'order/orderDetail/orderDetail?order_no=' . $orderRes['orderNo'];
                        $send_data['form_id']     = $logPayRes['prepay_id'];
                        $send_data['data']        = $data;
                        // print_r(json_encode($send_data,true));die;
                        $access_token = $this->getWeiXinAccessToken();
                        // echo $access_token;die;
                        $requestUrl = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
                        // print_r(json_encode($send_data,true));die;
                        $this->sendRequest2($requestUrl, $send_data);
                        /* 发送模板消息代码结束 2019/04/28 */
        
                    }
                    if (!empty($memOrderData)) {
                        DbOrder::updateMemberOrder($memOrderData, ['id' => $memOrderRes['id']]);
                        $redisListKey = Config::get('rediskey.order.redisMemberOrder');
                        $this->redis->rPush($redisListKey, $memOrderRes['id']);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    Db::table('pz_log_error')->insert(['title' => '/pay/pay/wxPayCallback', 'data' => $e]);
                }
            } else {//写错误日志(待支付订单不存在)
                echo 'error order';
            }
        } else {//写错误日志(签名错误)
            echo 'error sign';
        }

//        $res = '{"appid":"wx112088ff7b4ab5f3","attach":"255","bank_type":"CFT","cash_fee":"1425","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"0lfvboi6rnpxe2g49ksunp1298e008mu","openid":"o83f0wLtc3Wlx9sv8yyECXv_Enh0","out_trade_no":"PAYSN201807041721287496","result_code":"SUCCESS","return_code":"SUCCESS","sign":"C0B76E319EDEC158036882A56044B2D7","time_end":"20180704172134","total_fee":"1425","trade_type":"JSAPI","transaction_id":"4200000128201807043248657648"}';
//        print_r(json_decode($res, true));

//        "<xml><appid><![CDATA[wxa8c604ce63485956]]></appid><bank_type><![CDATA[CFT]]></bank_type><cash_fee><![CDATA[1]]></cash_fee><fee_type><![CDATA[CNY]]></fee_type><is_subscribe><![CDATA[N]]></is_subscribe><mch_id><![CDATA[1505450311]]></mch_id><nonce_str><![CDATA[aid38or91hq8r4w5ttg4caru18w3v4yq]]></nonce_str><openid><![CDATA[oAuSK5U76yO10U0cJSbzSiRLPXW0]]></openid><out_trade_no><![CDATA[mem19021818075357545052]]></out_trade_no><result_code><![CDATA[SUCCESS]]></result_code><return_code><![CDATA[SUCCESS]]></return_code><sign><![CDATA[789B26EBB62417381005C5FDFCAF59F8]]></sign><time_end><![CDATA[20190218180816]]></time_end><total_fee>1</total_fee><trade_type><![CDATA[JSAPI]]></trade_type><transaction_id><![CDATA[4200000255201902181171403485]]></transaction_id></xml>";
    }


    //xml转换成数组
    private function xmlToArray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val       = json_decode(json_encode($xmlstring), true);
        return $val;
    }

    private function makeSign($params, $key) {
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params);  //参数进行拼接key=value&k=v
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    private function toUrlParams($params) {
        $string = '';
        if (!empty($params)) {
            $array = array();
            foreach ($params as $key => $value) {
                $array[] = $key . '=' . $value;
            }
            $string = implode("&", $array);
        }
        return $string;
    }

    function sendRequest2($requestUrl, $data = []) {
        $curl = curl_init();
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Content-Length:' . strlen($data)]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
}