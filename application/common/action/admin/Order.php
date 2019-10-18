<?php

namespace app\common\action\admin;

use app\common\action\notify\Note;
use app\facade\DbGoods;
use app\facade\DbModelMessage;
use app\facade\DbOrder;
use app\facade\DbProvinces;
use app\facade\DbShops;
use app\facade\DbUser;
use cache\Phpredis;
use Config;
use think\Db;

class Order extends CommonIndex {
    private $redisDeliverOrderKey    = 'cms:order:deliver:express:';
    private $redisDeliverExpressList = 'cms:order:deliver:list:';

    public function __construct() {
        parent::__construct();
        $this->redisDeliverOrderKey = Config::get('rediskey.order.redisDeliverOrderExpress');
    }

    /**
     * 获取订单列表
     * @param $order_status
     * @param $cityId
     * @return array
     * @author rzc
     */
    public function getOrderList($page, $pagenum, $order_status = '', $order_no = '', $nick_name = '', $supplier_id = 0, $start_time = 0, $end_time = 0) {
        $offset = ($page - 1) * $pagenum;
        if ($offset < 0) {
            return ['code' => 3000];
        }
        $where = [];
        if (!empty($order_status)) {
            array_push($where, ['order_status', '=', $order_status]);
        }
        if (!empty($order_no)) {
            array_push($where, ['order_no', '=',$order_no]);
        }
        if (!empty($nick_name)) {
            $user =  DbUser::getUserInfo([['nick_name', 'like','%'.$nick_name.'%']], 'id', false);
            $uid = [];
            foreach ($user as $key => $value) {
                $uid[] = $value['id'];
            }
            array_push($where, ['uid', 'in',$uid]);
        }
        if ($start_time) {
            array_push($where, ['create_time', '>=',$start_time]);
        }
        if ($end_time) {
            array_push($where, ['create_time', '<=',$end_time]);
        }
        $field     = 'id,uid,order_no,order_status,order_money,deduction_money,pay_money,goods_money,discount_money,pay_type,third_money,third_pay_type';
        if ($supplier_id) {
            $order_ids = DbOrder::getOrderChild('order_id',['supplier_id' => $supplier_id], false, true);
            if (!empty($order_ids)) {
                // $new_order_ids = array_column($order_ids,'order_id');
                array_push($where, ['id', 'in', array_column($order_ids,'order_id')]);
            } else {
                return ['code' => 200, 'totle' => 0, 'order_list' => []];
            }
        }
        // print_r($where);die;
        $orderList = DbOrder::getOrder($field, $where, false,$offset . ',' . $pagenum);
        // dump( Db::getLastSql());die;
        if (empty($orderList)) {
            return ['code' => 3000];
        }
        foreach ($orderList as $key => $value) {
            $user = DbUser::getUserInfo(['id' => $value['uid']], 'nick_name', true);
            if (!empty($user)){
                $orderList[$key]['nick_name'] = $user['nick_name'];
            }
        }
        
        $totle = DbOrder::getOrderCount($where);
        return ['code' => 200, 'totle' => $totle, 'order_list' => $orderList];
    }

    /**
     * 获取订单详情
     * @param $order_status
     * @param $cityId
     * @return array
     * @author rzc
     */
    public function getOrderInfo($id) {
        $order_info = DbOrder::getOrder('*', [['id', '=', $id]], true);
        if (!$order_info) {
            return ['code' => 3001, 'msg' => '该订单不存在'];
        }
        if ($order_info['province_id']) {
            $order_info['province_name'] = DbProvinces::getAreaOne('*', ['id' => $order_info['province_id']])['area_name'];
        }
        if ($order_info['city_id']) {
            $order_info['city_name'] = DbProvinces::getAreaOne('*', ['id' => $order_info['city_id'], 'level' => 2])['area_name'];
        }
        if ($order_info['area_id']) {
            $order_info['area_name'] = DbProvinces::getAreaOne('*', ['id' => $order_info['area_id']])['area_name'];
        }

        $order_child     = DbOrder::getOrderChild('*', ['order_id' => $order_info['id']]);
        $express_money   = 0;
        $order_goods_ids = [];
        foreach ($order_child as $order => $child) {
            $order_goods = DbOrder::getOrderGoods('*', ['order_child_id' => $child['id']]);
            foreach ($order_goods as $og => $goods) {
                $order_goods[$og]['sku_json'] = json_decode($goods['sku_json']);
                $order_goods_ids[]            = $goods['id'];
            }
            $order_child[$order]['order_goods'] = $order_goods;
            $express_money += $child['express_money'];
        }
        $order_info['express_money'] = $express_money;
        $order_pack                  = [];
        foreach ($order_child as $order => $child) {
            $order_goods     = DbOrder::getOrderGoods('goods_id,goods_name,order_child_id,sku_id,sup_id,goods_type,goods_price,margin_price,integral,goods_num,sku_json', ['order_child_id' => $child['id']], false, true);
            $order_goods_num = DbOrder::getOrderGoods('sku_id,COUNT(goods_num) as goods_num', ['order_child_id' => $child['id']], 'sku_id');
            foreach ($order_goods as $og => $goods) {
                foreach ($order_goods_num as $ogn => $goods_num) {
                    if ($goods_num['sku_id'] == $goods['sku_id']) {
                        $order_goods[$og]['goods_num'] = $goods_num['goods_num'];
                        $order_goods[$og]['sku_image'] = DbGoods::getOneGoodsSku(['id' => $goods['sku_id']], 'sku_image', true)['sku_image'];
                        $order_goods[$og]['sku_json']  = json_decode($order_goods[$og]['sku_json'], true);
                    }
                }
            }
            $order_pack[$order]['order_goods'] = $order_goods;

            // dump( Db::getLastSql());die;
        }
        // print_r($order_pack);die;
        $has_deliver_goods = [];
        $has_order_express = DbOrder::getOrderExpress('order_goods_id,express_no,express_key,express_name', [['order_goods_id', 'IN', $order_goods_ids]]);
        if ($has_order_express) {
            $has_order_goods_id = [];
            foreach ($has_order_express as $has => $express) {
                $has_order_goods_id[]             = $express['order_goods_id'];
                $goods                            = DbOrder::getOrderGoods('id,goods_name,sku_json,sku_id', [['id', '=', $express['order_goods_id']]], false, false, true);
                $goods['sku_image']               = DbGoods::getOneGoodsSku(['id' => $goods['sku_id']], 'sku_image', true)['sku_image'];
                $goods['express']                 = $express;
                $has_deliver_goods[$has]['goods'] = $goods;
            }
            $no_order_goods_id = array_diff($order_goods_ids, $has_order_goods_id);

            $no_deliver_goods = DbOrder::getOrderGoods('id,goods_name,sku_json', [['id', 'IN', $no_order_goods_id]]);
            // $has_deliver_goods = DbOrder::getOrderGoods('id,goods_name,sku_json',[['id','IN',$has_order_goods_id]]);

        } else {
            $no_deliver_goods = DbOrder::getOrderGoods('id,goods_name,sku_json', [['id', 'IN', $order_goods_ids]]);
        }
        $no_deliver_goods_num = count($no_deliver_goods);
        // print_r($has_deliver_goods);die;
        // print_r($express_money);die;
        $order_sheet = $this->getOrderSheet($order_info['order_no'])['fromList'];
        return ['code' => 200, 'order_info' => $order_info, 'order_pack' => $order_pack, 'order_child' => $order_child, 'no_deliver_goods' => $no_deliver_goods, 'has_deliver_goods' => $has_deliver_goods, 'no_deliver_goods_num' => $no_deliver_goods_num, 'fromList' => $order_sheet];
    }

    /**
     * 订单发货
     * @param $order_goods_id
     * @param $express_no
     * @param $express_key
     * @param $express_name
     * @return array
     * @author rzc
     */
    public function deliverOrderGoods($order_goods_id, $express_no, $express_key, $express_name) {

        // print_r($this->redisDeliverOrderKey);die;
        $this->redis      = Phpredis::getConn();
        $order_express_id = DbOrder::getOrderExpress('id', ['order_goods_id' => $order_goods_id], false, false, true);
        // dump( Db::getLastSql());die;
        if ($order_express_id) {
            return ['code' => '3005', 'msg' => '已添加的订单商品物流分配关系'];
        }
        $had_express = DbOrder::getOrderExpress('order_goods_id', ['express_key' => $express_key, 'express_no' => $express_no]);
        if (!empty($had_express)) {
            $had_order_goods = [];
            foreach ($had_express as $had => $d_express) {
                $had_order_goods[] = $d_express['order_goods_id'];
            }

            $had_child    = DbOrder::getOrderGoods('order_child_id', [['id', 'in', $had_order_goods]]);
            $had_child_id = [];

            if (!empty($had_child)) {
                foreach ($had_child as $had => $child) {
                    $had_child_id[] = $child['order_child_id'];
                }

                if (!empty($had_child_id)) {
                    $had_order = DbOrder::getOrderChild('order_id', [['id', 'in', $had_child_id]]);
                    // print_r($had_child_id);die;
                    $had_order_id = [];
                    if (!empty($had_order)) {
                        foreach ($had_order as $has => $order) {
                            $had_order_id[] = $order['order_id'];
                        }
                        $had_order_user = DbOrder::getOrder('uid', [['id', 'in', $had_order_id]]);
                        $had_uid        = [];
                        if ($had_order_user) {
                            foreach ($had_order_user as $key => $user) {
                                $had_uid[] = $user['uid'];
                            }
                            $had_uid = array_unique($had_uid);
                            if (count($had_uid) > 1) {
                                return ['code' => '3007', 'msg' => '不同用户订单不能使用同一物流公司物流单号发货'];
                            }
                        }
                    }

                }

            }

        }
        // print_r(array_unique($had_uid));die;

        $order_child_id = DbOrder::getOrderGoods('order_child_id', ['id' => $order_goods_id], false, false, true)['order_child_id'];

        if (!$order_child_id) {
            return ['code' => '3003', 'msg' => '不存在的order_goods_id'];
        }
        $order_id = DbOrder::getOrderChild('order_id', ['id' => $order_child_id], true)['order_id'];

        $thisorder = DbOrder::getOrder('order_no,linkphone,linkman,order_status,uid,order_money,third_time', ['id' => $order_id], true);
        if (!empty($had_uid)) {
            if (!in_array($thisorder['uid'], $had_uid)) {
                return ['code' => '3007', 'msg' => '不同用户订单不能使用同一物流公司物流单号发货'];
            }
        }

        if ($thisorder['order_status'] != 4) {
            return ['code' => '3004', 'msg' => '非待发货订单无法发货'];
        }

        $order_childs = DbOrder::getOrderChild('id', ['order_id' => $order_id]);
        $new_chileds  = [];
        foreach ($order_childs as $order => $value) {
            $new_chileds[] = $value['id'];
        }

        $order_goods_data = DbOrder::getOrderGoods('id,goods_name,sku_json,sku_id', [['order_child_id', 'IN', $new_chileds]]);
        $order_goods_ids  = [];
        foreach ($order_goods_data as $key => $value) {
            $order_goods_ids[] = $value['id'];
        }

        $order_express                   = [];
        $order_express['order_goods_id'] = $order_goods_id;
        $order_express['express_no']     = $express_no;
        $order_express['express_key']    = $express_key;
        $order_express['express_name']   = $express_name;
        $order_express['send_time']      = time();

        $add_order_express = DbOrder::addOrderExpress($order_express);

        $key = $express_key . '&' . $express_no;
        $this->redis->set($this->redisDeliverOrderKey . $key, '');
        $this->redis->expire($this->redisDeliverOrderKey . $key, 2592000);
        $this->redis->rPush($this->redisDeliverExpressList, $key);
        // $this->redis->expire($this->redisDeliverOrderKey.$order_id, 20);
        /* 查出已添加的订单商品物流单分配信息 */
        $has_order_express = DbOrder::getOrderExpress('order_goods_id', [['order_goods_id', 'IN', $order_goods_ids]]);
        if ($has_order_express) {
            $has_order_goods_id = [];
            foreach ($has_order_express as $has => $express) {
                $has_order_goods_id[] = $express['order_goods_id'];
            }
            $no_order_goods_id = array_diff($order_goods_ids, $has_order_goods_id);
            if (!$no_order_goods_id) {
                DbOrder::updataOrder(['order_status' => 5, 'send_time' => time()], $order_id);
                /* 发送模板消息开始 2019/04/28 */
                $has_order_express = DbOrder::getOrderExpress('express_no,express_key,express_name', [['order_goods_id', 'IN', $order_goods_ids]], false, true);
                $logPayRes         = DbOrder::getLogPay(['order_id' => $order_id, 'payment' => 1], 'uid,prepay_id', true);
                if (!empty($logPayRes)) {
                    $access_token = $this->getWeiXinAccessToken();
                    $user_wxinfo  = DbUser::getUserWxinfo(['uid' => $logPayRes['uid']], 'openid', true);
                    $skuids              = [];
                    $sku_num             = [];
                    $sku_name            = [];
                    $data['keyword1']['value']        = $thisorder['order_no'];
                    $data['keyword1']['color'] = '#157efb';
                    #快递单号
                    $express_word = '';
                    foreach ($has_order_express as $order => $express) {
                        $express_word = $express_word . '【' . $express['express_name'] . '】' . $express['express_no'];
                    }
                    $data['keyword2']['value']        = $express_word;
                    $data['keyword2']['color'] = '#333';
                    $keyword3                  = '';
                    foreach ($order_goods_data as $key => $value) {
                        if (empty($skuids)) {
                            $skuids[]                           = $value['sku_id'];
                            $sku_num[$value['sku_id']]  = 1;
                            $sku_name[$value['sku_id']] = $value['goods_name'];
                        } else {
                            if (in_array($value['sku_id'], $skuids)) {
                                $sku_num[$value['sku_id']] = $sku_num[$value['sku_id']] + 1;
                            } else {
                                $skuids[]                           = $value['sku_id'];
                                $sku_num[$value['sku_id']]  = 1;
                                $sku_name[$value['sku_id']] = $value['goods_name'];
                            }
                        }
                        
                    }
                    foreach ($skuids as $key => $skuid) {
                        $keyword3 = $keyword3 . '商品' . $sku_name[$skuid] . ' 数量' . $sku_num[$skuid];
                    }
                    $data['keyword3']['value']        = $keyword3;
                    $data['keyword3']['color'] = '#333';
                    // $goo
                    // 商品名称

                    $data['keyword4']['color'] = '#333';
                    $data['keyword4']['value']        = $thisorder['order_money'];
                    $data['keyword5']['color'] = '#333';
                    $data['keyword5']['value']        = $thisorder['third_time'];

                    $send_data                = [];
                    $send_data['touser']      = $user_wxinfo['openid'];
                    $send_data['template_id'] = Config::get('conf.deliver_goods_template_id');
                    $send_data['page']        = 'pages/order/orderDetail/orderDetail?orderno=' . $thisorder['order_no'];
                    $send_data['form_id']     = $logPayRes['prepay_id'];
                    $send_data['data']        = $data;
                    // print_r(json_encode($send_data,true));die;
                    // echo $access_token;die;
                    $requestUrl = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
                    // print_r(json_encode($send_data,true));die;
                    $result = $this->sendRequest2($requestUrl, $send_data);
                    // Db::table('pz_log_error')->insert(['title' => '/pay/pay/wxPayCallback', 'data' => $result]);
                } else {
/* 发送模板消息代码结束 2019/04/28 */
                    /* 短信模板发送短信 */
                    $user_identity = DbUser::getUserInfo(['id' => $thisorder['uid']], 'user_identity', true);
                    $user_identity = $user_identity['user_identity'] + 1;
                    $m_type        = '1,' . $user_identity;
                    $message_task  = DbModelMessage::getMessageTask([['wtype', '=', 1], ['status', '=', 2], ['type', 'in', $m_type]], 'type,mt_id,trigger_id', true);
                    if (!empty($message_task)) {
                        /* 获取触发器 */
                        $trigger = DbModelMessage::getTrigger(['id' => $message_task['trigger_id'], 'status' => 2], 'start_time,stop_time', true);
                        if (!empty($trigger)) {
                            if (strtotime($trigger['start_time']) < time() && strtotime($trigger['stop_time']) > time()) {
                                /* 获取消息模板 */
                                $message_template = DbModelMessage::getMessageTemplate(['id' => $message_task['mt_id'], 'status' => 2], 'template', true);
                                if (!empty($message_template)) { //模板不为空
                                    $message_template = $message_template['template'];

                                    //模板中订单号替换
                                    $tem_orderNo      = '订单号' . $thisorder['order_no'];
                                    $message_template = str_replace('{{[order_no]}}', $tem_orderNo, $message_template);

                                    //商品发货信息替换

                                    $tem_delivergoods = '';
                                    foreach ($has_order_express as $order => $express) {
                                        $where = [
                                            'express_no'   => $express['express_no'],
                                            'express_key'  => $express['express_key'],
                                            'express_name' => $express['express_name'],
                                        ];
                                        $has_express_goodsid = DbOrder::getOrderExpress('order_goods_id', $where);
                                        $skuids              = [];
                                        $sku_num             = [];
                                        $sku_name            = [];
                                        foreach ($has_express_goodsid as $has_express => $goods) {
                                            $express_goods = DbOrder::getOrderGoods('goods_name,sku_json,sku_id', [['id', '=', $goods['order_goods_id']]], false, false, true);
                                            // $express_goods['sku_json'] = json_decode($express_goods['sku_json'], true);
                                            if (empty($skuids)) {
                                                $skuids[]                           = $express_goods['sku_id'];
                                                $sku_num[$express_goods['sku_id']]  = 1;
                                                $sku_name[$express_goods['sku_id']] = $express_goods['goods_name'];
                                            } else {
                                                if (in_array($express_goods['sku_id'], $skuids)) {
                                                    $sku_num[$express_goods['sku_id']] = $sku_num[$express_goods['sku_id']] + 1;
                                                } else {
                                                    $skuids[]                           = $express_goods['sku_id'];
                                                    $sku_num[$express_goods['sku_id']]  = 1;
                                                    $sku_name[$express_goods['sku_id']] = $express_goods['goods_name'];
                                                }
                                            }
                                        }
                                        $deliver_goods_text = '';
                                        foreach ($skuids as $key => $skuid) {
                                            $deliver_goods_text = $deliver_goods_text . '商品' . $sku_name[$skuid] . ' 数量' . $sku_num[$skuid];
                                        }
                                        $tem_delivergoods = $tem_delivergoods . ' 物流公司' . $express['express_name'] . ' 运单号' . $express['express_no'] . $deliver_goods_text . ' ';
                                    }
                                    $message_template = str_replace('{{[delivergoods]}}', $tem_delivergoods, $message_template);
                                    $message_template = str_replace('{{[nick_name]}}', $thisorder['linkman'], $message_template);
                                    $message_template = str_replace('{{[money]}}', '￥'.$thisorder['order_money'], $message_template);

                                    $Note = new Note;
                                    $send = $Note->sendSms($thisorder['linkphone'], $message_template);
                                    // print_r($send);die;
                                    // $thisorder['linkphone'];
                                }
                            }
                        }
                    }
                }

                $no_deliver_goods = [];
            } else {
                $no_deliver_goods = DbOrder::getOrderGoods('id,goods_name,sku_json', [['id', 'IN', $no_order_goods_id]]);
            }

        } else {
            $no_deliver_goods = $order_goods_data;
        }
        if ($add_order_express) {
            return ['code' => 200, 'msg' => '添加成功', 'no_deliver_goods' => $no_deliver_goods];
        } else {
            return ['code' => '3006', 'msg' => '添加失败', 'no_deliver_goods' => $no_deliver_goods];
        }

    }

    /**
     * 修改订单发货信息
     * @param $order_goods_id
     * @param $express_no
     * @param $express_key
     * @param $express_name
     * @return array
     * @author rzc
     */
    public function updateDeliverOrderGoods($order_goods_id, $express_no, $express_key, $express_name) {
        $this->redis   = Phpredis::getConn();
        $order_express = DbOrder::getOrderExpress('id', ['order_goods_id' => $order_goods_id], false, false, true);
        $order_child_id = DbOrder::getOrderGoods('order_child_id', ['id' => $order_goods_id], false, false, true)['order_child_id'];

        if (!$order_child_id) {
            return ['code' => '3003', 'msg' => '不存在的order_goods_id'];
        }
        // dump( Db::getLastSql());die;
        if (!$order_express) {
            return ['code' => 3005, 'msg' => '未添加的订单商品物流分配关系，无法修改'];
        }
        $had_express = DbOrder::getOrderExpress('order_goods_id', ['express_key' => $express_key, 'express_no' => $express_no]);
        if (!empty($had_express)) {
            $had_order_goods = [];
            foreach ($had_express as $had => $d_express) {
                $had_order_goods[] = $d_express['order_goods_id'];
            }

            $had_child    = DbOrder::getOrderGoods('order_child_id', [['id', 'in', $had_order_goods]]);
            $had_child_id = [];

            if (!empty($had_child)) {
                foreach ($had_child as $had => $child) {
                    $had_child_id[] = $child['order_child_id'];
                }

                if (!empty($had_child_id)) {
                    $had_order = DbOrder::getOrderChild('order_id', [['id', 'in', $had_child_id]]);
                    // print_r($had_child_id);die;
                    $had_order_id = [];
                    if (!empty($had_order)) {
                        foreach ($had_order as $has => $order) {
                            $had_order_id[] = $order['order_id'];
                        }
                        $had_order_user = DbOrder::getOrder('uid', [['id', 'in', $had_order_id]]);
                        $had_uid        = [];
                        if ($had_order_user) {
                            foreach ($had_order_user as $key => $user) {
                                $had_uid[] = $user['uid'];
                            }
                            $had_uid = array_unique($had_uid);
                            if (count($had_uid) > 1) {
                                return ['code' => '3007', 'msg' => '不同用户订单不能使用同一物流公司物流单号发货'];
                            }
                        }
                    }

                }

            }

        }
        $order_child_id = DbOrder::getOrderGoods('order_child_id', ['id' => $order_goods_id], false, false, true)['order_child_id'];

        $order_id = DbOrder::getOrderChild('order_id', ['id' => $order_child_id], true)['order_id'];

        $order_status = DbOrder::getOrder('order_status', ['id' => $order_id], true)['order_status'];

        if ($order_status = 4 || $order_status = 5) {
            $update_order_express = [];

            $update_order_express['express_no']   = $express_no;
            $update_order_express['express_key']  = $express_key;
            $update_order_express['express_name'] = $express_name;
            DbOrder::updateOrderExpress($update_order_express, $order_express['id']);
            $key = $express_key . '&' . $express_no;
            $this->redis->set($this->redisDeliverOrderKey . $key, '');
            $this->redis->expire($this->redisDeliverOrderKey . $key, 2592000);
            $this->redis->rPush($this->redisDeliverExpressList, $key);
            return ['code' => 200];
        } else {
            return ['code' => 3004, 'msg' => '非待发货订单无法发货或已发货订单无法变更'];
        }

    }

    public function getMemberOrders(int $page, int $pagenum) {
        $offset = ($page - 1) * $pagenum;
        if ($offset < 0) {
            return ['code' => 3000];
        }
        $result = DbOrder::getMemberOrders(['pay_status' => 4], 'order_no,from_uid,uid,actype,user_type,pay_money,pay_type,pay_time,create_time', false, 'id', 'desc', $offset . ',' . $pagenum);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $total = DbOrder::countMemberOrder(['pay_status' => 4]);
        return ['code' => '200', 'total' => $total, 'memberOrderList' => $result];

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

    /**
     * 修改订单发货信息
     * @param $cmsConId
     * @param $keyword
     * @return array
     * @author rzc
     */
    public function searchKeywordOrders($cmsConId, $keyword){
        $adminId = $this->getUidByConId($cmsConId);
        if (!$this->redis->hget(Config::get('rediskey.cms.redisCmsSearchKeyword') . $adminId,$keyword)) {
            return ['code' => '3002'];
        }
        $order_num = DbOrder::getOrderGoodsGroup($keyword);
        $all_goods_num = 0;
        $all_goods_price = 0;
        $all_order_num = count($order_num);
        if (!empty($order_num)) {
            foreach ($order_num as $order => $value) {
                $all_goods_num = bcadd($all_goods_num, $value['goods_num']);
                $all_goods_price = bcadd($all_goods_price, $value['goods_price'], 2);
            }
        }
        return ['code' => '200', 'order_num' => $all_order_num, 'all_goods_num' => $all_goods_num, 'all_goods_price' => $all_goods_price];
    }
    public function getOrderSheet($orderNo, $goods_id = 0){
        $where = ['order_no' => $orderNo];
        $row = false;
        if (!empty($goods_id)) {
            $where = ['order_no' => $orderNo, 'goods_id' => $goods_id];
            $row = true;
        }
        $result = DbOrder::getOrderGoodsSheet($where, 'id,order_no,goods_id,from,status',$row);
        if (!empty($result)) {
            
            if (!empty($goods_id)){
                $from = json_decode($result['from'],true);
                unset($result['from']);
                foreach ($from as $key => $value) {
                    $options = DbGoods::getSheetOption(['name' => $key],'name,title,type',true);
                    $options['value'] = $value;
                    $result['from'][] = $options;
                }
            }else {
                foreach ($result as $key => $value) {
                    $from = json_decode($value['from'],true);
                    unset($result[$key]['from']);
                    foreach ($from as $fr => $om) {
                        $options = DbGoods::getSheetOption(['name' => $fr],'name,title,type',true);
                        $options['value'] = $om;
                        $result[$key]['from'][] = $options;
                    }
                }
            }
        }
        return ['code' => '200', 'fromList' => $result];
    }

    public function updateOrderSheet($id, $from = [], $status = 1){
        $result = DbOrder::getOrderGoodsSheet(['id' => $id], 'id',true);
        if (empty($result)) {
            return ['code' => '3001'];
        }
        if ($status == 2) {
            $data['status'] = 2;
        }
        if (!empty($from)) {
            $data['from'] = json_encode($from);
        }
        Db::startTrans();
        try {
            DbOrder::saveOrderGoodsSheet($data,$id);
            Db::commit();
            return ['code' => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005'];//领取失败
        }
    }
    
    public function exportDeliveryOrder($sup_id, $order_type, $order_status, $page, $pagenum){
        $where = [];
        if ($sup_id) {
            array_push($where,['sup_id', '=', $sup_id]);
        }
        array_push($where,['order_type', '=', $order_type]);
        array_push($where,['order_status', '=', $order_status]);
        $offset = ($page - 1) * $pagenum;
        $result = DbOrder::getDeliveryOrderDetail($where, 'order_no,linkman,linkphone,province_id,city_id,area_id,address,message,supplier_id,supplier_name,goods_name,goods_num,sku_json', $offset.','.$pagenum, ['sup_id' => 'asc']);
        foreach ($result as $key => $value) {
            $result[$key]['address'] = '';
            if ($value['province_id']) {
                $value['province_name'] = DbProvinces::getAreaOne('*', ['id' => $value['province_id']])['area_name'];
                $result[$key]['address'] .= $value['province_name'];
            }
            if ($value['city_id']) {
                $value['city_name'] = DbProvinces::getAreaOne('*', ['id' => $value['city_id'], 'level' => 2])['area_name'];
                $result[$key]['address'] .= $value['city_name'];
            }
            if ($value['area_id']) {
                $value['area_name'] = DbProvinces::getAreaOne('*', ['id' => $value['area_id']])['area_name'];
                $result[$key]['address'] .= $value['area_name'];
            }
            $result[$key]['sku_json'] = join(',',json_decode($value['sku_json'],true));
            unset($result[$key]['province_id']);
            unset($result[$key]['city_id']);
            unset($result[$key]['area_id']);
            $result[$key]['address'] .= $value['address'];
        }
        return ['code' => '200', 'result' => $result];
    }
}