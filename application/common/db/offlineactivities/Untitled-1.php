    public function getExpressLog($ShipperCode, $LogisticCode) {
        $customer = '389C6F5CB8C771CC620DCC88932229F3';
        $key      = 'jrsaVPbM2682';
        $param    = array(
            'com'      => $ShipperCode, //快递公司编码
            'num'      => $LogisticCode, //快递单号
            'phone'    => '', //手机号
            'from'     => '', //出发地城市
            'to'       => '', //目的地城市
            'resultv2' => '1', //开启行政区域解析
        );

        $post_data             = array();
        $post_data["customer"] = $customer;
        $post_data["param"]    = json_encode($param);
        $sign                  = md5($post_data["param"] . $key . $post_data["customer"]);
        $post_data["sign"]     = strtoupper($sign);

        $url = 'http://poll.kuaidi100.com/poll/query.do'; //实时查询请求地址

        $params = "";
        foreach ($post_data as $k => $v) {
            $params .= "$k=" . urlencode($v) . "&"; //默认UTF-8编码格式
        }
        $post_data = substr($params, 0, -1);
        
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);
	    $data = str_replace("\"", '"', $result );
        // $data = json_decode($data, true);
        return $data;
    }

    $preg = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)?$/';
        if (empty($start_time)) {
            $start_time = time();
        } else {
            if (preg_match($preg, $start_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $start_time = strtotime($start_time);
        }
        if (empty($stop_time)) {
            return ['code' => '3002'];
        } else {
            if (preg_match($preg, $stop_time, $parts2)) {
                if (checkdate($parts2[2], $parts2[3], $parts2[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $stop_time = strtotime($stop_time);
        }
        if ($stop_time < $start_time + 900) {
            return ['code' => '3003'];
        }

        /**
     * 获取微信access_token
     * @return array
     * @author rzc
     */
    private function getWeiXinAccessToken() {
        $access_token = $this->redis->get($this->redisAccessToken);
        if (empty($access_token)) {
            $appid = Config::get('conf.weixin_miniprogram_appid');
            // $appid         = 'wx1771b2e93c87e22c';
            $secret = Config::get('conf.weixin_miniprogram_appsecret');
            // $secret        = '1566dc764f46b71b33085ba098f58317';
            $requestUrl       = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $requsest_subject = json_decode(sendRequest($requestUrl), true);
            $access_token     = $requsest_subject['access_token'];
            if (!$access_token) {
                return false;
            }
            $this->redis->set($this->redisAccessToken, $access_token);
            $this->redis->expire($this->redisAccessToken, 6600);
        }

        return $access_token;
    }


    {
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
    }


        /**
     * @api              {get} / 线下活动商品订单生成取货二维码
     * @apiDescription   createOrderQrCode
     * @apiGroup         index_OfflineActivities
     * @apiName          createOrderQrCode
     * @apiParam (入参) {Number} con_id
     * @apiParam (入参) {String} order_no 订单号
     * @apiSuccess (返回) {String} code 200:成功 
     * @apiSuccess (返回) {String} order_no 订单号
     * @apiSampleRequest /index/OfflineActivities/createOrderQrCode
     * @author rzc
     */
    public function createOrderQrCode(){
        $order_no = trim($this->request->post('order_no'));
    }