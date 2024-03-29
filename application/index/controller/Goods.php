<?php
namespace app\index\controller;

use app\index\MyController;

class Goods extends MyController
{
    /**
     * @api              {post} / 分类商品列表
     * @apiDescription   getCategoryGoods
     * @apiGroup         index_Goods
     * @apiName          getCategoryGoods
     * @apiParam (入参) {Number} cate_id 对应商品三级分类id
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} cate_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_market_price 最低市场价
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石再补贴
     * @apiSampleRequest /index/goods/getCategoryGoods
     * @author rzc
     */
    public function getCategoryGoods()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $cate_id = trim($this->request->post('cate_id'));
        $page = trim($this->request->post('page'));
        $page_num = trim($this->request->post('page_num'));
        $goodslist = $this->app->goods->getCategoryGoods($cate_id, $page, $page_num);
        $this->apiLog($apiName, [$cate_id, $page, $page_num], $goodslist['code'], '');
        return $goodslist;
    }

    /**
     * @api              {post} / 商品详情
     * @apiDescription   getGoods
     * @apiGroup         index_Goods
     * @apiName          getGoods
     * @apiParam (入参) {Number} goods_id 对应商品id
     * @apiParam (入参) {Number} source 来源 1.全部 2.pc 3.app 4.微信
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} goods_data 商品数据
     * @apiSuccess (返回) {Array} goods_banner 商品轮播图
     * @apiSuccess (返回) {Array} goods_details 商品详情图
     * @apiSuccess (返回) {Array} goods_spec 商品规格属性
     * @apiSuccess (返回) {Array} goods_sku 商品SKU属性
     * @apiSuccess (goods_data) {String} id 商品ID
     * @apiSuccess (goods_data) {String} supplier_id 供应商ID
     * @apiSuccess (goods_data) {String} cate_id 分类ID
     * @apiSuccess (goods_data) {String} goods_name 商品名称
     * @apiSuccess (goods_data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (goods_data) {String} title 主标题
     * @apiSuccess (goods_data) {String} subtitle 副标题
     * @apiSuccess (goods_data) {String} image 商品标题图
     * @apiSuccess (goods_data) {String} is_integral_sale 是否积分售卖:1,默认不售卖;2,售卖
     * @apiSuccess (goods_banner) {String} goods_id 商品ID
     * @apiSuccess (goods_banner) {String} image_type 图片类型 1.详情图 2.轮播图
     * @apiSuccess (goods_banner) {String} image_path 图片地址
     * @apiSuccess (goods_details) {String} goods_id 商品ID
     * @apiSuccess (goods_details) {String} image_type 图片类型 1.详情图 2.轮播图
     * @apiSuccess (goods_details) {String} image_path 图片地址
     * @apiSuccess (goods_spec) {String} id 类目id
     * @apiSuccess (goods_spec) {String} cate_id 商品三级分类
     * @apiSuccess (goods_spec) {String} spe_name 类目名称
     * @apiSuccess (goods_spec[list]) {String} id 二级规格属性ID
     * @apiSuccess (goods_spec[list]) {String} spec_id 商品一级类目
     * @apiSuccess (goods_spec[list]) {String} attr_name 商品二级类目名称
     * @apiSuccess (goods_sku) {String} id 商品skuid
     * @apiSuccess (goods_sku) {String} goods_id 商品skuid
     * @apiSuccess (goods_sku) {String} stock 库存
     * @apiSuccess (goods_sku) {String} market_price 市场价
     * @apiSuccess (goods_sku) {String} retail_price 零售价
     * @apiSuccess (goods_sku) {String} presell_start_time 预售价开始时间
     * @apiSuccess (goods_sku) {String} presell_end_time 预售价结束时间
     * @apiSuccess (goods_sku) {String} presell_price 预售价
     * @apiSuccess (goods_sku) {String} active_price 活动价
     * @apiSuccess (goods_sku) {String} active_start_time 活动价开始时间
     * @apiSuccess (goods_sku) {String} active_end_time 活动价过期时间
     * @apiSuccess (goods_sku) {String} margin_price 其他运费成本
     * @apiSuccess (goods_sku) {String} brokerage 钻石再补贴
     * @apiSuccess (goods_sku) {String} integral_price 积分售价
     * @apiSuccess (goods_sku) {String} integral_active 积分赠送
     * @apiSuccess (goods_sku) {String} spec sku属性列表
     * @apiSuccess (goods_sku) {String} sku_image 规格详情图
     * @apiSampleRequest /index/goods/getGoods
     * @author rzc
     */
    public function getGoods()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $goods_id = trim($this->request->post('goods_id'));
        $source = trim($this->request->post('source'));
        $result = $this->app->goods->getGoodsinfo($goods_id, intval($source));
        $this->apiLog($apiName, [$goods_id, $source], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 专题商品列表
     * @apiDescription   getSubjectGoods
     * @apiGroup         index_Goods
     * @apiName          getSubjectGoods
     * @apiParam (入参) {Number} subject_id 对应专题三级分类id
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} subject_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_market_price 最低市场价
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石再补贴
     * @apiSampleRequest /index/goods/getSubjectGoods
     * @author rzc
     */
    public function getSubjectGoods()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $subject_id = trim($this->request->post('subject_id'));
        $page = trim($this->request->post('page'));
        $page_num = trim($this->request->post('page_num'));
        if (!is_numeric($subject_id) || empty($subject_id)) {
            return ['code' => '3001'];
        }
        $goodslist = $this->app->goods->getSubjectGoods($subject_id, $page, $page_num);
        $this->apiLog($apiName, [$subject_id, $page, $page_num], $goodslist['code'], '');
        return $goodslist;
    }

    /**
     * @api              {post} / 搜索商品列表
     * @apiDescription   getSearchGoods
     * @apiGroup         index_Goods
     * @apiName          getSearchGoods
     * @apiParam (入参) {String} search 搜索内容
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.page和page_num必须是数字 / 3002.搜索参数不存在
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} subject_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_market_price 最低市场价
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石再补贴
     * @apiSampleRequest /index/goods/getSearchGoods
     * @author rzc
     */
    public function getSearchGoods()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $search = trim($this->request->post('search'));
        $page = trim($this->request->post('page'));
        $page_num = trim($this->request->post('page_num'));
        $page = $page ? $page : 1;
        $page_num = $page_num ? $page_num : 10;
        if (empty($search)) {
            return ['code' => '3002'];
        }
        if (!is_numeric($page) || !is_numeric($page_num)) {
            return ['code' => '3001'];
        }
        $result = $this->app->goods->getSearchGoods($search, $page, $page_num);
        $this->apiLog($apiName, [$search, $page, $page_num], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 标签搜索商品列表
     * @apiDescription   getSearchGoodsByLabel
     * @apiGroup         index_Goods
     * @apiName          getSearchGoodsByLabel
     * @apiParam (入参) {String} label_name 搜索内容
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.page和page_num必须是数字 / 3002.搜索参数不存在
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} subject_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_market_price 最低市场价
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石再补贴
     * @apiSampleRequest /index/goods/getsearchgoodsbylabel
     * @author zyr
     */
    public function getSearchGoodsByLabel()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $labelName = trim($this->request->post('label_name')); //标签id
        $page = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        if (empty($labelName)) {
            return ['code' => '3001']; //搜索内容不能空
        }
        $page = is_numeric($page) ? $page : 1;
        $pageNum = is_numeric($pageNum) ? $pageNum : 10;
        $result = $this->app->goods->getSearchGoodsByLabel(strtolower($labelName), $page, $pageNum);
        $this->apiLog($apiName, [$labelName, $page, $pageNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 搜索标签
     * @apiDescription   searchLabel
     * @apiGroup         index_Goods
     * @apiName          searchLabel
     * @apiParam (入参) {String} search_content 搜索的内容
     * @apiSuccess (返回) {String} code 200:成功
     * @apiSuccess (返回) {String} msg 返回消息
     * @apiSampleRequest /index/goods/searchlabel
     * @return array
     * @author zyr
     */
    public function searchLabel()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $searchContent = trim($this->request->post('search_content')); //搜索内容
        $result = $this->app->goods->searchLabel(strtolower($searchContent));
        $this->apiLog($apiName, [$searchContent], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 商品推荐
     * @apiDescription   goodsRecommend
     * @apiGroup         index_Goods
     * @apiName          goodsRecommend
     * @apiParam (入参) {Int} goods_id 商品id
     * @apiParam (入参) {Int} goods_num 推荐数量 (默认6个)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:商品id为数字 / 3002:商品不存在
     * @apiSuccess (返回) {String} data 返回消息
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石再补贴
     * @apiSampleRequest /index/goods/goodsrecommend
     * @return array
     * @author zyr
     */
    public function goodsRecommend()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $goodsId = trim($this->request->post('goods_id'));
        $goodsNum = trim($this->request->post('goods_num'));
        if (!is_numeric($goodsId)) {
            return ['code' => '3001'];
        }
        if ($goodsId < 1) {
            return ['code' => '3001'];
        }
        $goodsId = intval($goodsId);
        $goodsNum = is_numeric($goodsNum) ? intval($goodsNum) : 6;
        $result = $this->app->goods->goodsRecommend($goodsId, $goodsNum);
        $this->apiLog($apiName, [$goodsId, $goodsNum], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 笛风旅游门票商品测试接口
     * @apiDescription   getDifengTicketScenicList
     * @apiGroup         index_Goods
     * @apiName          getDifengTicketScenicList
     * @apiParam (入参) {String} [key] 查询关键字,城市或者景点名称
     * @apiParam (入参) {Int} page 第几页.页从1开始
     * @apiParam (入参) {Int} pageSize 分页大小
     * @apiSuccess (返回) {String} code 200:成功 / 3001:商品id为数字 / 3002:商品不存在
     * @apiSuccess (返回) {String} data 返回消息
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_retail_price 最低零售价
     * @apiSuccess (data) {String} min_brokerage 最低钻石再补贴
     * @apiSampleRequest /index/goods/getDifengTicketScenicList
     * @return array
     * @author rzc
     */
    public function getDifengTicketScenicList()
    {
        $key = trim($this->request->post('key'));
        $page = trim($this->request->post('key'));
        $pageSize = trim($this->request->post('pageSize'));
        $page = is_numeric($page) ? $page : 1;
        $pageSize = is_numeric($pageSize) ? $pageSize : 10;
        //笛风SecretKey
        $SecretKey = 'aaaaaaaa';
        $apiKey = 'tickettest';

        $request = [];
        if (!empty($key)) {
            $request['key'] = $key;
        }
        if (!empty($page)) {
            $request['page'] = $page;
        }
        if (!empty($pageSize)) {
            $request['pageSize'] = $pageSize;
        }
        $request['apiKey'] = $apiKey;
        $timestamp = date('Y-m-d H:i:s');
        $request['timestamp'] = $timestamp;
        ksort($request);

        $str = '';
        $i = '';
        foreach ($request as $rkey => $value) {
            if ($rkey == 'timestamp') {
                $i = "&amp;";
            }
            $str .= $i . $rkey . '=' . $value;
            $i = '&';
        }
        // echo '请求参数拼接字符串：'.$str.'</br>';
        $sign = $SecretKey . '&' . $str . '&' . $SecretKey; //
        // echo '请求参数拼接SecretKey：'.$sign.'</br>';
        $sign = strtoupper(md5($sign)); //MD5加密并大写转换
        // echo 'sign参数MD5加密并大写转换：'.$sign.'</br>';
        // print_r($sign);die;
        $data = [
            'apiKey' => $apiKey,
            'data' => [
                'key' => $key,
                'page' => $page,
                'pageSize' => $pageSize,
            ],
            'sign' => $sign,
            'timestamp' => $timestamp,
        ];
        // echo "请求报文：";
        // print_r(json_encode($data));
        // echo '</br>'."请求地址：https://doptest-api.dfyoo.com/Ticket/scenicList".'</br>';
        $result = $this->sendRequest2('https://doptest-api.dfyoo.com/Ticket/scenicList', $data);
        // echo "请求结果：";
        // print_r($result);die;
        return json_decode($result, true);
    }

    public function sendRequest2($requestUrl, $data = [])
    {
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
     * @api              {post} / 积分专题商品列表
     * @apiDescription   getIntegralSubjectGoods
     * @apiGroup         index_Goods
     * @apiName          getIntegralSubjectGoods
     * @apiParam (入参) {Number} subject_id 对应专题三级分类id
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} subject_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_market_price 价值
     * @apiSuccess (data) {String} min_integral_price 最低规格兑换所需积分
     * @apiSampleRequest /index/goods/getIntegralSubjectGoods
     * @author rzc
     */
    public function getIntegralSubjectGoods()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $subject_id = trim($this->request->post('subject_id'));
        $page = trim($this->request->post('page'));
        $page_num = trim($this->request->post('page_num'));
        if (!is_numeric($subject_id) || empty($subject_id)) {
            return ['code' => '3001'];
        }
        $goodslist = $this->app->goods->getIntegralSubjectGoods($subject_id, $page, $page_num);
        $this->apiLog($apiName, [$subject_id, $page, $page_num], $goodslist['code'], '');
        return $goodslist;
    }

    /**
     * @api              {post} / 商品详情
     * @apiDescription   getIntegralGoods
     * @apiGroup         index_Goods
     * @apiName          getIntegralGoods
     * @apiParam (入参) {Number} goods_id 对应商品id
     * @apiParam (入参) {Number} source 来源 1.全部 2.pc 3.app 4.微信
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误
     * @apiSuccess (返回) {String} type_name 上级分类的name
     * @apiSuccess (返回) {Array} goods_data 商品数据
     * @apiSuccess (返回) {Array} goods_banner 商品轮播图
     * @apiSuccess (返回) {Array} goods_details 商品详情图
     * @apiSuccess (返回) {Array} goods_spec 商品规格属性
     * @apiSuccess (返回) {Array} goods_sku 商品SKU属性
     * @apiSuccess (goods_data) {String} id 商品ID
     * @apiSuccess (goods_data) {String} supplier_id 供应商ID
     * @apiSuccess (goods_data) {String} cate_id 分类ID
     * @apiSuccess (goods_data) {String} goods_name 商品名称
     * @apiSuccess (goods_data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (goods_data) {String} title 主标题
     * @apiSuccess (goods_data) {String} subtitle 副标题
     * @apiSuccess (goods_data) {String} image 商品标题图
     * @apiSuccess (goods_banner) {String} goods_id 商品ID
     * @apiSuccess (goods_banner) {String} image_type 图片类型 1.详情图 2.轮播图
     * @apiSuccess (goods_banner) {String} image_path 图片地址
     * @apiSuccess (goods_details) {String} goods_id 商品ID
     * @apiSuccess (goods_details) {String} image_type 图片类型 1.详情图 2.轮播图
     * @apiSuccess (goods_details) {String} image_path 图片地址
     * @apiSuccess (goods_spec) {String} id 类目id
     * @apiSuccess (goods_spec) {String} cate_id 商品三级分类
     * @apiSuccess (goods_spec) {String} spe_name 类目名称
     * @apiSuccess (goods_spec[list]) {String} id 二级规格属性ID
     * @apiSuccess (goods_spec[list]) {String} spec_id 商品一级类目
     * @apiSuccess (goods_spec[list]) {String} attr_name 商品二级类目名称
     * @apiSuccess (goods_sku) {String} id 商品skuid
     * @apiSuccess (goods_sku) {String} goods_id 商品skuid
     * @apiSuccess (goods_sku) {String} integral_sale_stock 可兑换库存
     * @apiSuccess (goods_sku) {String} market_price 市场价
     * @apiSuccess (goods_sku) {String} retail_price 零售价
     * @apiSuccess (goods_sku) {String} presell_start_time 预售价开始时间
     * @apiSuccess (goods_sku) {String} presell_end_time 预售价结束时间
     * @apiSuccess (goods_sku) {String} presell_price 预售价
     * @apiSuccess (goods_sku) {String} active_price 活动价
     * @apiSuccess (goods_sku) {String} active_start_time 活动价开始时间
     * @apiSuccess (goods_sku) {String} active_end_time 活动价过期时间
     * @apiSuccess (goods_sku) {String} margin_price 其他运费成本
     * @apiSuccess (goods_sku) {String} integral_price 积分售价
     * @apiSuccess (goods_sku) {String} integral_active 积分赠送
     * @apiSuccess (goods_sku) {String} spec sku属性列表
     * @apiSuccess (goods_sku) {String} sku_image 规格详情图
     * @apiSampleRequest /index/goods/getIntegralGoods
     * @author rzc
     */
    public function getIntegralGoods()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $goods_id = trim($this->request->post('goods_id'));
        $source = trim($this->request->post('source'));
        $result = $this->app->goods->getIntegralGoodsinfo($goods_id, intval($source));
        $this->apiLog($apiName, [$goods_id, $source], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 团购商品列表
     * @apiDescription   getGrouponGoods
     * @apiGroup         index_Goods
     * @apiName          getGrouponGoods
     * @apiParam (入参) {Number} [page] 页码 (默认:1)
     * @apiParam (入参) {Number}  [page_num] 每页显示数量 (默认:10)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.参数必须是数字 / 3002.参数不存在
     * @apiSuccess (返回) {Number} total 总条数
     * @apiSuccess (返回) {Array} data 分类数据
     * @apiSuccess (data) {String} id 商品ID
     * @apiSuccess (data) {String} supplier_id 供应商ID
     * @apiSuccess (data) {String} subject_id 分类ID
     * @apiSuccess (data) {String} goods_name 商品名称
     * @apiSuccess (data) {String} goods_type 商品类型 1.普通(正常发货)商品 2.虚拟商品
     * @apiSuccess (data) {String} title 主标题
     * @apiSuccess (data) {String} subtitle 副标题
     * @apiSuccess (data) {String} image 商品标题图
     * @apiSuccess (data) {String} min_market_price 价值
     * @apiSuccess (data) {String} min_integral_price 最低规格兑换所需积分
     * @apiSampleRequest /index/goods/getGrouponGoods
     * @author rzc
     */
    public function getGrouponGoods()
    {
        $apiName = classBasename($this) . '/' . __function__;
        $page = trim($this->request->post('page'));
        $page_num = trim($this->request->post('page_num'));
        $goodslist = $this->app->goods->getGrouponGoods($page, $page_num);
        $this->apiLog($apiName, [$page, $page_num], $goodslist['code'], '');
        return $goodslist;
    }
}
