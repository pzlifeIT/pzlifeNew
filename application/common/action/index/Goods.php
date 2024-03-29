<?php

namespace app\common\action\index;

use app\facade\DbAudios;
use app\facade\DbCoupon;
use app\facade\DbGoods;
use app\facade\DbLabel;
use Config;

class Goods extends CommonIndex
{
    private $transformRedisKey;
    private $labelLibraryRedisKey;
    private $labelLibraryHeatRedisKey;

    public function __construct()
    {
        parent::__construct();
        $this->redisGoodsDetail = Config::get('rediskey.index.redisGoodsDetail');
        $this->transformRedisKey = Config::get('rediskey.label.redisLabelTransform');
        $this->labelLibraryRedisKey = Config::get('rediskey.label.redisLabelLibrary');
        $this->labelLibraryHeatRedisKey = Config::get('rediskey.label.redisLabelLibraryHeat');
    }

    /**
     * 分类商品列表
     * @param $cate_id
     * @param $page
     * @param $page_num
     * @return array
     * @author rzc
     */
    public function getCategoryGoods($cate_id, $page, $page_num)
    {
        $page = $page ? $page : 1;
        $page_num = $page_num ? 10 : 10;
        if (!$cate_id) {
            return ['code' => 3002, 'msg' => '参数不存在'];
        }
        if (!is_numeric($cate_id) || !is_numeric($page) || !is_numeric($page_num)) {
            return ['code' => 3001, 'msg' => '参数必须是数字'];
        }
        $field = 'id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image';
        $offect = ($page - 1) * $page_num;
        if ($offect < 0) {
            return ['code' => '3000'];
        }
        $limit = $offect . ',' . $page_num;
        $order = 'id';
        $where = ['status' => 1, 'cate_id' => $cate_id];
        $result = DbGoods::getGoods($field, $limit, $order, $where);
        if (empty($result)) {
            return ['code' => 200, 'data' => $result];
        }
        /* 获取每条商品的SKU,后期列表开放加入购物车释放 */
        foreach ($result as $key => $value) {
            /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            $result[$key]['spec'] = $goods_spec;
            $result[$key]['goods_sku'] = $goods_sku; */
            $where = ['goods_id' => $value['id']];
            $field = 'market_price';
            $result[$key]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            $field = 'retail_price';
            $result[$key]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            list($goods_spec, $goods_sku) = $this->getGoodsSku($value['id']);
            foreach ($goods_sku as $goods => $sku) {
                $retail_price[$sku['id']] = $sku['retail_price'];
                $brokerage[$sku['id']] = $sku['brokerage'];
            }
            $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
        }
        return ['code' => 200, 'data' => $result];
    }

    /**
     * 商品详情
     * @param $goods_id
     * @param $source
     * @return array
     * @author rzc
     */
    public function getGoodsinfo($goods_id, $source)
    {
        /* 判断参数 注：goods_id为商品库ID */
        if (!is_numeric($goods_id)) {
            return ['code' => 3001, 'msg' => '参数必须是数字'];
        }
        /* 判断来源 */
        $source_type = [1, 2, 3, 4];
        if (!in_array($source, $source_type)) {
            return ['code' => 3002, 'msg' => '非法来源'];
        }
        /* 返回商品基本信息 （从商品库中直接查询）*/
        $where = [["id", "=", $goods_id], ["status", "=", 1]];
        $field = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,share_image,status,is_integral_sale";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return ['code' => 3000, 'msg' => '商品不存在'];
        }
        $redisGoodsDetailKey = $this->redisGoodsDetail . $goods_id . ':' . $source;
        /* 查询商品轮播图 */
        $where = [["goods_id", "=", $goods_id], ["image_type", "=", 2], ["source_type", "IN", "1," . $source]];
        $field = "goods_id,source_type,image_type,image_path,order_by";
        $goods_banner = DbGoods::getOneGoodsImage($where, $field, 'order_by asc,id asc');

        /* 查询商品详情图 */
        $where = [["goods_id", "=", $goods_id], ["image_type", "=", 1], ["source_type", "IN", "1," . $source]];
        $field = "goods_id,source_type,image_type,image_path,order_by";
        $goods_details = DbGoods::getOneGoodsImage($where, $field, 'order_by asc,id asc');
        $goods_sku = [];
        $goods_spec = [];
        if ($goods_data['goods_type'] == 1) {
            $goods_data['goods_name'] = htmlspecialchars_decode($goods_data['goods_name']);
            $goods_data['supplier_desc'] = DbGoods::getSupplierData('desc', $goods_data['supplier_id'])['desc'];
            if ($this->redis->exists($redisGoodsDetailKey)) {
                $goodsInfo = json_decode($this->redis->get($redisGoodsDetailKey), true);
                $goodsInfo['goods_data']['supplier_desc'] = $goods_data['supplier_desc'];
                $goodsInfo['goods_data']['goods_name'] = htmlspecialchars_decode($goodsInfo['goods_data']['goods_name']);
                $result = array_merge(['code' => '200'], $goodsInfo);
                return $result;
            }
            $where = ['goods_id' => $goods_id, 'status' => 1];
            $field = 'market_price';
            $goods_data['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            $goods_data['max_market_price'] = DbGoods::getOneSkuMost($where, 2, $field);
            $field = 'retail_price';
            $goods_data['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            $goods_data['max_retail_price'] = DbGoods::getOneSkuMost($where, 2, $field);

            /* 商品对应规格及SKU价格 */
            list($goods_spec, $goods_sku) = $this->getGoodsSku($goods_id);
            $integral_active = [];
            $brokerage = [];
            // print_r($goods_sku);die;
            foreach ($goods_sku as $key => $value) {
                $integral_active[] = $value['integral_active'];
                $brokerage[] = $value['brokerage'];
            }
            $min_integral_active = [0];
            $min_brokerage = [0];
            $goods_data['max_integral_active'] = max($integral_active);
            if (empty(array_diff($integral_active, $min_integral_active))) {
                $goods_data['min_integral_active'] = 0;
            } else {
                $goods_data['min_integral_active'] = min(array_diff($integral_active, $min_integral_active));
                // $goods_sku = $goods_sku;
            }
            $goods_data['max_brokerage'] = max($brokerage);
            $goods_data['min_brokerage'] = min(array_diff($brokerage, $min_brokerage));
        } else if ($goods_data['goods_type'] == 2) {
            $goods_sku = DbGoods::getAudioSkuRelation([['goods_id', '=', $goods_id]]);
            $integral_active = [];
            $brokerage = [];
            $market_price = [];
            $retail_price = [];
            $min_integral_active = [0];
            $min_brokerage = [0];
            $min_market_price = [0];
            $min_retail_price = [0];

            foreach ($goods_sku as &$v) {
                $v['end_time'] = $v['end_time'] / 3600;
                foreach ($v['audios'] as $key => $value) {
                    $v['audios'][$key]['id'] = $value['pivot']['audio_pri_id'];
                }
                $v['audios'] = array_map(function ($var) {
                    unset($var['pivot']);
                    return $var;
                }, $v['audios']);
                $v['brokerage'] = bcmul(getDistrProfits($v['retail_price'], $v['cost_price'], 0), 0.75, 2);
                $v['integral_active'] = bcmul(bcsub(bcsub($v['retail_price'], $v['cost_price'], 4), 0, 2), 2, 0);
                $integral_active[] = $v['integral_active'];
                $brokerage[] = $v['brokerage'];
                $market_price[] = $v['market_price'];
                $retail_price[] = $v['retail_price'];
            }
            unset($v);
            $goods_data['max_brokerage'] = max($brokerage);
            $goods_data['min_brokerage'] = min(array_diff($brokerage, $min_brokerage));
            $goods_data['max_integral_active'] = max($integral_active);
            if (empty(array_diff($integral_active, $min_integral_active))) {
                $goods_data['min_integral_active'] = 0;
            } else {
                $goods_data['min_integral_active'] = min(array_diff($integral_active, $min_integral_active));
                // $goods_sku = $goods_sku;
            }

            $goods_data['min_market_price'] = min(array_diff($market_price, $min_market_price));
            $goods_data['max_market_price'] = max($market_price);
            $goods_data['min_retail_price'] = min(array_diff($retail_price, $min_retail_price));
            $goods_data['max_retail_price'] = max($retail_price);

        }

        // 获取商品优惠券
        $goods_coupon = DbCoupon::getCoupon(['level' => 1, 'gs_id' => $goods_id], 'id,price,gs_id,level,title,days,create_time,time_type,start_time,end_time', false, 'id desc');
        $new_coupon = [];
        if (!empty($goods_coupon)) {
            foreach ($goods_coupon as $gc => $coupon) {
                if ($coupon['time_type'] == 2) {
                    if (time() > strtotime($coupon['start_time']) && time() < strtotime($coupon['end_time'])) {
                        array_push($new_coupon, $coupon);
                    }
                } else {
                    array_push($new_coupon, $coupon);
                }
            }
        }

        $goodsInfo = [
            'goods_data' => $goods_data,
            'goods_banner' => $goods_banner,
            'goods_details' => $goods_details,
            'goods_spec' => $goods_spec,
            'goods_sku' => $goods_sku,
            'goods_coupon' => $new_coupon,
        ];
        $this->redis->setEx($redisGoodsDetailKey, 86400, json_encode($goodsInfo));
        $goodsInfo['goods_data']['goods_name'] = htmlspecialchars_decode($goodsInfo['goods_data']['goods_name']);
        $result = array_merge(['code' => '200'], $goodsInfo);
        return $result;
    }

    /**
     * 获取商品SKU及规格名称等
     * @param $goods_id
     * @param $source
     * @return array
     * @author rzc
     */
    public function getGoodsSku($goods_id)
    {
        $field = 'goods_id,spec_id';
        $where = [["goods_id", "=", $goods_id]];
        $goods_first_spec = DbGoods::getOneGoodsSpec($where, $field, 1);
        $goods_spec = [];
        if ($goods_first_spec) {
            $field = 'id,spe_name';
            foreach ($goods_first_spec as $key => $value) {
                $where = ['id' => $value['spec_id']];
                $result = DbGoods::getOneSpec($where, $field);

                $goods_attr_field = 'attr_id';
                $goods_attr_where = ['goods_id' => $goods_id, 'spec_id' => $value['spec_id']];
                $goods_first_attr = DbGoods::getOneGoodsSpec($goods_attr_where, $goods_attr_field);
                $attr_where = [];
                foreach ($goods_first_attr as $goods => $attr) {
                    $attr_where[] = $attr['attr_id'];
                }
                $attr_field = 'id,spec_id,attr_name';
                $attr_where = [['id', 'in', $attr_where], ['spec_id', '=', $value['spec_id']]];
                $result['list'] = DbGoods::getAttrList($attr_where, $attr_field);
                $goods_spec[] = $result;
            }
        }
        $field = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,cost_price,integral_price,spec,sku_image';
        // $where = [["goods_id", "=", $goods_id],["status", "=",1],['retail_price','<>', 0]];
        $where = [["goods_id", "=", $goods_id], ["status", "=", 1]];
        $goods_sku = DbGoods::getOneGoodsSku($where, $field);
        /* brokerage：佣金；计算公式：(商品售价-商品进价-其它运费成本-售价*0.006)*0.9*(钻石再补贴：0.75) */
        /* integral_active：积分；计算公式：(商品售价-商品进价-其它运费成本)*2 */
        foreach ($goods_sku as $goods => $sku) {
            $goods_sku[$goods]['brokerage'] = bcmul(getDistrProfits($sku['retail_price'], $sku['cost_price'], $sku['margin_price']), 0.75, 2);
            $goods_sku[$goods]['integral_active'] = bcmul(bcsub(bcsub($sku['retail_price'], $sku['cost_price'], 4), $sku['margin_price'], 2), 2, 0);
            $sku_json = DbGoods::getAttrList([['id', 'in', $sku['spec']]], 'attr_name');
            $sku_name = [];
            if ($sku_json) {
                foreach ($sku_json as $sj => $json) {
                    $sku_name[] = $json['attr_name'];
                }
            }
            $goods_sku[$goods]['sku_name'] = $sku_name;
        }
        return [$goods_spec, $goods_sku];
    }

    /**
     * 专题商品列表
     * @param $cate_id
     * @param $page
     * @param $page_num
     * @return array
     * @author rzc
     */
    public function getSubjectGoods($subject_id, $page, $page_num)
    {
        $page = $page ? $page : 1;
        $page_num = $page_num ? 10 : 10;
        if (!$subject_id) {
            return ['code' => 3002, 'msg' => '参数不存在'];
        }
        if (!is_numeric($subject_id) || !is_numeric($page) || !is_numeric($page_num)) {
            return ['code' => 3001, 'msg' => '参数必须是数字'];
        }
        $offect = ($page - 1) * $page_num;
        if ($offect < 0) {
            return ['code' => '3000'];
        }
        $limit = $offect . ',' . $page_num;
        $field = 'subject,tier,id';
        $where = ['id' => $subject_id];
        $subject = DbGoods::getSubject($where, $field, true);
        // echo Db::getLastSql();die;
        // print_r($subject);die;
        if (empty($subject)) {
            return ['code' => '3000'];
        }
        if ($subject['tier'] != 3 || empty($subject)) {
            return ['code' => 3003, 'msg' => '传入专题ID有误'];
        }
        // getSubjectRelation($where, $field, $row = false,$limit = false)
        $field = 'goods_id';
        $where = ['subject_id' => $subject_id];
        $goodslist = DbGoods::getSubjectRelation($where, $field, false);
        //    echo Db::getLastSql();die;
        foreach ($goodslist as $goods => $list) {
            $goodsid[] = $list['goods_id'];
        }
        if (empty($goodslist)) {
            return ['code' => 200, 'data' => []];
        }
        /* 获取专题商品关联关系 */
        $field = 'id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image';
        $order = 'id';
        // $where = ['status' => 1, 'cate_id' => $cate_id];

        $where = [['status', '=', 1], ['id', 'IN', $goodsid]];
        $result = DbGoods::getGoods($field, $limit, $order, $where);
        // echo Db::getLastSql();die;
        // print_r($result);die;
        if (empty($result)) {
            return ['code' => 200, 'data' => []];
        }

        // print_r($result);die;
        /* 获取每条商品的SKU,后期列表开放加入购物车释放 */
        foreach ($result as $key => $value) {
            /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            $result[$key]['spec'] = $goods_spec;
            $result[$key]['goods_sku'] = $goods_sku; */
            $result[$key]['goods_name'] = htmlspecialchars_decode($value['goods_name']);

            $brokerage = [];
            $integral_active = [];
            // print_r($value['id']);die;
            if ($value['goods_type'] == 1) {
                $where = [['goods_id', '=', $value['id']], ['status', '=', 1], ['stock', '<>', 0]];
                $field = 'market_price';
                $result[$key]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                $field = 'retail_price';
                $result[$key]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                $retail_price = [];
                list($goods_spec, $goods_sku) = $this->getGoodsSku($value['id']);

                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']] = $sku['retail_price'];
                        $brokerage[$sku['id']] = $sku['brokerage'];
                        $integral_active[$sku['id']] = $sku['integral_active'];
                    }
                    $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
                    $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
                    unset($retail_price);
                } else {
                    $result[$key]['min_brokerage'] = 0;
                    $result[$key]['min_integral_active'] = 0;
                }
            } else if ($value['goods_type'] == 2) {
                $goods_sku = DbGoods::getAudioSkuRelation([['goods_id', '=', $value['id']]]);
                $where = ['goods_id' => $value['id']];
                $field = 'market_price';
                $result[$key]['min_market_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                $field = 'retail_price';
                $result[$key]['min_retail_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']] = $sku['retail_price'];
                        $brokerage[$sku['id']] = bcmul(getDistrProfits($sku['retail_price'], $sku['cost_price'], 0), 0.75, 2);
                        $integral_active[$sku['id']] = bcmul(bcsub(bcsub($sku['retail_price'], $sku['cost_price'], 4), 0, 2), 2, 0);
                    }
                    // print_r($brokerage);
                    // print_r(array_search(min($retail_price), $retail_price));
                    $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
                    $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
                    // echo $value['id'];die;
                    unset($retail_price);
                } else {
                    $result[$key]['min_brokerage'] = 0;
                    $result[$key]['min_integral_active'] = 0;
                }
            }

        }
        return ['code' => 200, 'data' => $result];
    }

    /**
     * 搜索商品列表
     * @param $search
     * @param $page
     * @param $page_num
     * @return array
     * @author rzc
     */
    public function getSearchGoods($search, $page, $page_num)
    {
        $offset = ($page - 1) * $page_num;
        if ($offset < 0) {
            return ['code' => 200, 'goods_data' => []];
        }
        $result = DbGoods::getGoodsList('*', [['goods_name', 'LIKE', '%' . $search . '%'], ['status', '=', 1]], $offset, $page_num);
        // print_r($goods_data);die;
        if (empty($result)) {
            return ['code' => 200, 'goods_data' => []];
        }
        foreach ($result as $key => $value) {
            /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            $result[$key]['spec'] = $goods_spec;
            $result[$key]['goods_sku'] = $goods_sku; */
            $where = ['goods_id' => $value['id'], 'status' => 1];
            $field = 'market_price';
            $result[$key]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            $field = 'retail_price';
            $result[$key]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            //  echo Db::getLastSQl();die;
            list($goods_spec, $goods_sku) = $this->getGoodsSku($value['id']);
            $retail_price = [];
            $brokerage = [];
            foreach ($goods_sku as $goods => $sku) {
                $retail_price[$sku['id']] = $sku['retail_price'];
                $brokerage[$sku['id']] = $sku['brokerage'];
            }
            $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
        }
        return ['code' => 200, 'goods_data' => $result];
    }

    public function getSearchGoodsByLabel($labelName, $page, $pageNum)
    {
        $labelIdList = $this->getLabelScan($labelName);
        $goodsIdList = DbLabel::getLabelGoodsRelationDistinct([['label_lib_id', 'in', $labelIdList]]);
        $goodsIdList = array_column($goodsIdList, 'goods_id');
        $offset = ($page - 1) * $pageNum;
        $result = DbGoods::getGoodsList('*', [['id', 'in', $goodsIdList], ['status', '=', 1]], $offset, $pageNum);
        // print_r($goods_data);die;
        if (empty($result)) {
            return ['code' => 200, 'goods_data' => []];
        }
        foreach ($result as $key => $value) {
            $result[$key]['goods_name'] = htmlspecialchars_decode($value['goods_name']);
            if ($value['goods_type'] == 1) {

                $where = ['goods_id' => $value['id'], 'status' => 1];
                $field = 'market_price';
                $result[$key]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                $field = 'retail_price';
                $result[$key]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                //  echo Db::getLastSQl();die;
                list($goods_spec, $goods_sku) = $this->getGoodsSku($value['id']);
                $retail_price = [];
                $brokerage = [];
                foreach ($goods_sku as $goods => $sku) {
                    $retail_price[$sku['id']] = $sku['retail_price'];
                    $brokerage[$sku['id']] = $sku['brokerage'];
                }
                $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
            } else if ($value['goods_type'] == 2) {
                $where = ['goods_id' => $value['id']];
                $field = 'market_price';
                $result[$key]['min_market_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                $field = 'retail_price';
                $result[$key]['min_retail_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                $goods_sku = DbGoods::getAudioSkuRelation([['goods_id', '=', $value['id']]]);
                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']] = $sku['retail_price'];
                        $brokerage[$sku['id']] = bcmul(getDistrProfits($sku['retail_price'], $sku['cost_price'], 0), 0.75, 2);
                        $integral_active[$sku['id']] = bcmul(bcsub(bcsub($sku['retail_price'], $sku['cost_price'], 4), 0, 2), 2, 0);
                    }
                    $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
                    $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];

                } else {
                    $result[$key]['min_brokerage'] = 0;
                    $result[$key]['min_integral_active'] = 0;
                }
            }

        }
        return ['code' => 200, 'goods_data' => $result];
    }

    public function searchLabel($searchContent)
    {
        $data = $this->getLabelScan($searchContent);
        if (empty($data)) {
            return ['code' => '3000'];
        }
        $data = array_unique($data);
        $heat = $this->redis->zRevRange($this->labelLibraryHeatRedisKey, 0, -1);
        $result = [];
        foreach ($heat as $v) {
            if (in_array($v, $data)) {
                array_push($result, $v);
                if (count($result) >= 10) {
                    break;
                }
            }
        }
        $result = $this->getLabelLibrary($result);
        return ['code' => '200', 'data' => $this->labelProcess($result)];
    }

    /**
     * 商品推荐
     * @param $goodsId
     * @param $goodsNum
     * @return array
     * @author zyr
     */
    public function goodsRecommend($goodsId, $goodsNum)
    {
        $goods = DbGoods::getOneGoods([
            ['id', '=', $goodsId],
            ['status', '=', 1],
        ], 'id,supplier_id');
        if (empty($goods)) {
            return ['code' => '3002'];
        }
        $goodsLabel = DbLabel::getLabelGoodsRelation([
            ['goods_id', '=', $goodsId],
        ], 'label_lib_id');
        $goodsLabel = array_unique(array_column($goodsLabel, 'label_lib_id')); //当前商品的标签id列表

        $goodsList = DbGoods::getGoodsList2([
            ['id', '<>', $goodsId],
            ['status', '=', 1],
            ['supplier_id', '=', $goods['supplier_id']],
        ], 'id');
        $supGoodsId = array_column($goodsList, 'id');

        $goodsIdRes = [];
        if (!empty($goodsList)) {
            $goodsLabelRelation = DbLabel::getLabelGoodsRelationByGoods([
                ['gr.goods_id', 'in', $supGoodsId],
                ['gr.label_lib_id', 'in', $goodsLabel],
                ['g.status', '=', 1],
            ], 'gr.goods_id,gr.label_lib_id');
            $goodsHeat = [];
            foreach ($goodsLabelRelation as $glr) {
                $heat = $this->redis->zScore($this->labelLibraryHeatRedisKey, $glr['label_lib_id']);
                $goodsHeat[$glr['goods_id']] = isset($goodsHeat[$glr['goods_id']]) ? bcadd($goodsHeat[$glr['goods_id']], $heat, 0) : $heat;
            }
            $hasLabel = array_unique(array_column($goodsLabelRelation, 'goods_id'));
            foreach ($supGoodsId as $sgi) {
                if (!in_array($sgi, $hasLabel)) {
                    $goodsHeat[$sgi] = 1;
                }
            }
            ksort($goodsHeat);
            asort($goodsHeat);
            $goodsIdRes = array_keys($goodsHeat);
            $goodsIdRes = array_slice($goodsIdRes, 0, 6);
        }
        if (count($goodsIdRes) < $goodsNum) { //供应商下不够就查相同标签的
            $goodsLabelRelation2 = DbLabel::getLabelGoodsRelationByGoods([
                ['gr.label_lib_id', 'in', $goodsLabel],
                ['gr.goods_id', 'not in', $supGoodsId],
                ['gr.goods_id', '<>', $goodsId],
                ['g.status', '=', 1],
            ], 'gr.goods_id,gr.label_lib_id');
//            print_r($goodsLabelRelation2); die;
            $goodsHeat2 = [];
            foreach ($goodsLabelRelation2 as $glr2) {
                $heat2 = $this->redis->zScore($this->labelLibraryHeatRedisKey, $glr2['label_lib_id']);
                $goodsHeat2[$glr2['goods_id']] = isset($goodsHeat2[$glr2['goods_id']]) ? bcadd($goodsHeat2[$glr2['goods_id']], $heat2, 0) : $heat2;
            }
            krsort($goodsHeat2);
            arsort($goodsHeat2);
            $goodsIdRes2 = array_keys($goodsHeat2);
            $goodsIdRes2 = array_slice($goodsIdRes2, 0, bcsub($goodsNum, count($goodsIdRes), 0));
            $goodsIdRes = array_merge($goodsIdRes, $goodsIdRes2);
        }
        if (empty($goodsIdRes)) {
            return ['code' => 200, 'data' => []];
        }
        $field = 'id,goods_name,subtitle,image,goods_type';
        $where = [['status', '=', 1], ['id', 'IN', $goodsIdRes]];
        $result = DbGoods::getGoodsList2($where, $field);
        if (empty($result)) {
            return ['code' => 200, 'data' => []];
        }
        foreach ($result as $key => $value) {
            $result[$key]['goods_name'] = htmlspecialchars_decode($value['goods_name']);
            $retail_price = [];
            $brokerage = [];
            $integral_active = [];
            if ($value['goods_type'] == 1) {
                $where = [['goods_id', '=', $value['id']], ['status', '=', 1], ['stock', '<>', 0]];
                $field = 'market_price';
                $field = 'retail_price';
                $result[$key]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                list($goods_spec, $goods_sku) = $this->getGoodsSku($value['id']);
                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']] = $sku['retail_price'];
                        $brokerage[$sku['id']] = $sku['brokerage'];
                        $integral_active[$sku['id']] = $sku['integral_active'];
                    }
                    $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
                } else {
                    $result[$key]['min_brokerage'] = 0;
                    $result[$key]['min_integral_active'] = 0;
                }
            } else if ($value['goods_type'] == 2) {
                $field = 'retail_price';
                $where = ['goods_id' => $value['id']];
                $result[$key]['min_retail_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                $goods_sku = DbGoods::getAudioSkuRelation([['goods_id', '=', $value['id']]]);
                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']] = $sku['retail_price'];
                        $brokerage[$sku['id']] = bcmul(getDistrProfits($sku['retail_price'], $sku['cost_price'], 0), 0.75, 2);
                        $integral_active[$sku['id']] = bcmul(bcsub(bcsub($sku['retail_price'], $sku['cost_price'], 4), 0, 2), 2, 0);
                    }
                    $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
                    $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];

                } else {
                    $result[$key]['min_brokerage'] = 0;
                    $result[$key]['min_integral_active'] = 0;
                }
            }
        }
        array_multisort($goodsIdRes, SORT_ASC, $result);
        return ['code' => '200', 'data' => $result];
    }

    private function labelProcess($result)
    {
        $data = [];
        foreach ($result as $k => $v) {
            $arr = ['label_id' => $k, 'label_name' => htmlspecialchars_decode($v)];
            array_push($data, $arr);
        }
        return $data;
    }

    /**
     * 通过标签库的id列表获取标签列表
     * @param $labeLibIdList array
     * @return array
     * @author zyr
     */
    private function getLabelLibrary($labeLibIdList)
    {
        return $this->redis->hMGet($this->labelLibraryRedisKey, $labeLibIdList);
    }

    /**
     * 标签库模糊查询
     * @param $searchContent
     * @return array
     * @author zyr
     */
    private function getLabelScan($searchContent)
    {
        $data = [];
        $iterator = null;
        while (true) {
            $keys = $this->redis->hScan($this->transformRedisKey, $iterator, $searchContent . '*');
            if ($keys === false) { //迭代结束，未找到匹配pattern的key
                break;
            }
            foreach ($keys as $key) {
                $data = array_merge($data, json_decode($key, true));
            }
        }
        if (empty($data)) {
            return [];
        }
        $data = array_unique($data);
        return $data;
    }

    public function getIntegralSubjectGoods($subject_id, $page, $page_num)
    {
        $page = $page ? $page : 1;
        $page_num = $page_num ? 10 : 10;
        if (!$subject_id) {
            return ['code' => 3002, 'msg' => '参数不存在'];
        }
        if (!is_numeric($subject_id) || !is_numeric($page) || !is_numeric($page_num)) {
            return ['code' => 3001, 'msg' => '参数必须是数字'];
        }
        $offect = ($page - 1) * $page_num;
        if ($offect < 0) {
            return ['code' => '3000'];
        }
        $limit = $offect . ',' . $page_num;
        $field = 'subject,tier,id';
        $where = ['id' => $subject_id, 'is_integral_show' => 2];
        $subject = DbGoods::getSubject($where, $field, true);
        // echo Db::getLastSql();die;
        // print_r($subject);die;
        if (empty($subject)) {
            return ['code' => '3000'];
        }
        if ($subject['tier'] != 3 || empty($subject)) {
            return ['code' => 3003, 'msg' => '传入专题ID有误'];
        }
        $field = 'goods_id';
        $where = ['subject_id' => $subject_id];
        $goodslist = DbGoods::getSubjectRelation($where, $field, false);
        //    echo Db::getLastSql();die;
        foreach ($goodslist as $goods => $list) {
            $goodsid[] = $list['goods_id'];
        }
        if (empty($goodslist)) {
            return ['code' => 200, 'data' => []];
        }
        /* 获取专题商品关联关系 */
        $field = 'id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image';
        $order = 'id';
        // $where = ['status' => 1, 'cate_id' => $cate_id];

        $where = [['status', '=', 1], ['id', 'IN', $goodsid], ['is_integral_sale', '=', '2']];
        $result = DbGoods::getGoods($field, $limit, $order, $where);
        // echo Db::getLastSql();die;
        // print_r($result);die;
        if (empty($result)) {
            return ['code' => 200, 'data' => []];
        }

        // print_r($result);die;
        /* 获取每条商品的SKU,后期列表开放加入购物车释放 */
        foreach ($result as $key => $value) {
            /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            $result[$key]['spec'] = $goods_spec;
            $result[$key]['goods_sku'] = $goods_sku; */
            $result[$key]['goods_name'] = htmlspecialchars_decode($value['goods_name']);

            $brokerage = [];
            $integral_active = [];
            // print_r($value['id']);die;
            if ($value['goods_type'] == 1) {
                $where = [['goods_id', '=', $value['id']], ['status', '=', 1], ['stock', '<>', 0]];
                $field = 'market_price';
                $result[$key]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                $field = 'integral_price';
                $result[$key]['min_integral_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            } else if ($value['goods_type'] == 2) {
                $goods_sku = DbGoods::getAudioSkuRelation([['goods_id', '=', $value['id']]]);
                $where = ['goods_id' => $value['id']];
                $field = 'market_price';
                $result[$key]['min_market_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                $field = 'integral_price';
                $result[$key]['min_integral_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
            }
        }
        return ['code' => 200, 'data' => $result];
    }

    public function getIntegralGoodsinfo($goods_id, $source)
    {
        /* 判断参数 注：goods_id为商品库ID */
        if (!is_numeric($goods_id)) {
            return ['code' => 3001, 'msg' => '参数必须是数字'];
        }
        /* 判断来源 */
        $source_type = [1, 2, 3, 4];
        if (!in_array($source, $source_type)) {
            return ['code' => 3002, 'msg' => '非法来源'];
        }
        /* 返回商品基本信息 （从商品库中直接查询）*/
        $where = [["id", "=", $goods_id], ["status", "=", 1], ['is_integral_sale', '=', 2]];
        $field = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,share_image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return ['code' => 3000, 'msg' => '商品不存在'];
        }
        $redisGoodsDetailKey = $this->redisGoodsDetail . $goods_id . ':' . $source;
        /* 查询商品轮播图 */
        $where = [["goods_id", "=", $goods_id], ["image_type", "=", 2], ["source_type", "IN", "1," . $source]];
        $field = "goods_id,source_type,image_type,image_path,order_by";
        $goods_banner = DbGoods::getOneGoodsImage($where, $field, 'order_by asc,id asc');

        /* 查询商品详情图 */
        $where = [["goods_id", "=", $goods_id], ["image_type", "=", 1], ["source_type", "IN", "1," . $source]];
        $field = "goods_id,source_type,image_type,image_path,order_by";
        $goods_details = DbGoods::getOneGoodsImage($where, $field, 'order_by asc,id asc');
        $goods_sku = [];
        $goods_spec = [];
        if ($goods_data['goods_type'] == 1) {
            $goods_data['goods_name'] = htmlspecialchars_decode($goods_data['goods_name']);
            $goods_data['supplier_desc'] = DbGoods::getSupplierData('desc', $goods_data['supplier_id'])['desc'];
            if ($this->redis->exists($redisGoodsDetailKey)) {
                $goodsInfo = json_decode($this->redis->get($redisGoodsDetailKey), true);
                $goodsInfo['goods_data']['supplier_desc'] = $goods_data['supplier_desc'];
                $goodsInfo['goods_data']['goods_name'] = htmlspecialchars_decode($goodsInfo['goods_data']['goods_name']);
                $result = array_merge(['code' => '200'], $goodsInfo);
                return $result;
            }
            $where = ['goods_id' => $goods_id, 'status' => 1];
            $field = 'market_price';
            $goods_data['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            $goods_data['max_market_price'] = DbGoods::getOneSkuMost($where, 2, $field);
            $field = 'integral_price';
            $goods_data['min_integral_price'] = DbGoods::getOneSkuMost($where, 1, $field);
            $goods_data['max_integral_price'] = DbGoods::getOneSkuMost($where, 2, $field);

            /* 商品对应规格及SKU价格 */
            list($goods_spec, $goods_sku) = $this->getGoodsSku($goods_id);
            // $integral_active = [];
            // $brokerage       = [];
            // // print_r($goods_sku);die;
            // foreach ($goods_sku as $key => $value) {
            //     $integral_active[] = $value['integral_active'];
            //     $brokerage[]       = $value['brokerage'];
            // }
            // $min_integral_active               = [0];
            // $min_brokerage                     = [0];
            // $goods_data['max_integral_active'] = max($integral_active);
            // if (empty(array_diff($integral_active, $min_integral_active))) {
            //     $goods_data['min_integral_active'] = 0;
            // } else {
            //     $goods_data['min_integral_active'] = min(array_diff($integral_active, $min_integral_active));
            //     // $goods_sku = $goods_sku;
            // }
            // $goods_data['max_brokerage'] = max($brokerage);
            // $goods_data['min_brokerage'] = min(array_diff($brokerage, $min_brokerage));
        } else if ($goods_data['goods_type'] == 2) {
            $goods_sku = DbGoods::getAudioSkuRelation([['goods_id', '=', $goods_id]]);
            $integral_active = [];
            $brokerage = [];
            $market_price = [];
            $retail_price = [];
            $integral_price = [];
            $min_integral_active = [0];
            $min_brokerage = [0];
            $min_market_price = [0];
            $min_retail_price = [0];
            $min_integral_price = [0];

            foreach ($goods_sku as &$v) {
                $v['end_time'] = $v['end_time'] / 3600;
                foreach ($v['audios'] as $key => $value) {
                    $v['audios'][$key]['id'] = $value['pivot']['audio_pri_id'];
                }
                $v['audios'] = array_map(function ($var) {
                    unset($var['pivot']);
                    return $var;
                }, $v['audios']);
                // $v['brokerage']       = bcmul(getDistrProfits($v['retail_price'], $v['cost_price'], 0), 0.75, 2);
                // $v['integral_active'] = bcmul(bcsub(bcsub($v['retail_price'], $v['cost_price'], 4), 0, 2), 2, 0);
                $integral_active[] = $v['integral_active'];
                $brokerage[] = $v['brokerage'];
                $market_price[] = $v['market_price'];
                $retail_price[] = $v['retail_price'];
                $integral_price[] = $v['integral_price'];
            }
            unset($v);
            // $goods_data['max_brokerage'] = max($brokerage);
            // $goods_data['min_brokerage'] = min(array_diff($brokerage, $min_brokerage));
            // $goods_data['max_integral_active'] = max($integral_active);
            /*  if (empty(array_diff($integral_active, $min_integral_active))) {
            $goods_data['min_integral_active'] = 0;
            } else {
            $goods_data['min_integral_active'] = min(array_diff($integral_active, $min_integral_active));
            // $goods_sku = $goods_sku;
            } */

            $goods_data['min_market_price'] = min(array_diff($market_price, $min_market_price));
            $goods_data['max_market_price'] = max($market_price);
            $goods_data['min_integral_price'] = min(array_diff($integral_price, $min_integral_price));
            $goods_data['max_integral_price'] = max($integral_price);
            // $goods_data['min_retail_price'] = min(array_diff($retail_price, $min_retail_price));
            // $goods_data['max_retail_price'] = max($retail_price);

        }

        // 获取商品优惠券
        /*    $goods_coupon = DbCoupon::getCoupon(['level' => 1, 'gs_id' => $goods_id], 'id,price,gs_id,level,title,days,create_time,time_type,start_time,end_time', false, 'id desc');
        $new_coupon = [];
        if (!empty($goods_coupon)) {
        foreach ($goods_coupon as $gc => $coupon) {
        if ($coupon['time_type'] == 2){
        if (time() > strtotime($coupon['start_time']) && time() < strtotime($coupon['end_time'])){
        array_push($new_coupon,$coupon);
        }
        } else {
        array_push($new_coupon,$coupon);
        }
        }
        } */

        $goodsInfo = [
            'goods_data' => $goods_data,
            'goods_banner' => $goods_banner,
            'goods_details' => $goods_details,
            'goods_spec' => $goods_spec,
            'goods_sku' => $goods_sku,
            // 'goods_coupon'  => $new_coupon
        ];
        $this->redis->setEx($redisGoodsDetailKey, 86400, json_encode($goodsInfo));
        $goodsInfo['goods_data']['goods_name'] = htmlspecialchars_decode($goodsInfo['goods_data']['goods_name']);
        $result = array_merge(['code' => '200'], $goodsInfo);
        return $result;

    }

    public function getGrouponGoods($page, $pageNum)
    {
        $offset = $pageNum * ($page - 1);
        $result = DbGoods::getGrouponGoods([], 'goods_id', false);
        $goodsid = [];
        foreach ($result as $key => $value) {
            $goodsid[] = $value['goods_id'];
        }
        $limit = $offset . ',' . $pageNum;
        $where = [['status', '=', 1], ['id', 'IN', $goodsid]];
        $field = 'id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image';
        $order = 'id';
        $result = DbGoods::getGoods($field, $limit, $order, $where);
        // echo Db::getLastSql();die;
        // print_r($result);die;
        if (empty($result)) {
            return ['code' => 200, 'data' => []];
        }

        // print_r($result);die;
        /* 获取每条商品的SKU,后期列表开放加入购物车释放 */
        foreach ($result as $key => $value) {
            /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            $result[$key]['spec'] = $goods_spec;
            $result[$key]['goods_sku'] = $goods_sku; */
            $result[$key]['goods_name'] = htmlspecialchars_decode($value['goods_name']);

            $brokerage = [];
            $integral_active = [];
            // print_r($value['id']);die;
            if ($value['goods_type'] == 1) {
                $where = [['goods_id', '=', $value['id']], ['status', '=', 1], ['stock', '<>', 0]];
                $field = 'market_price';
                $result[$key]['min_market_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                $field = 'retail_price';
                $result[$key]['min_retail_price'] = DbGoods::getOneSkuMost($where, 1, $field);
                $retail_price = [];
                list($goods_spec, $goods_sku) = $this->getGoodsSku($value['id']);

                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']] = $sku['retail_price'];
                        $brokerage[$sku['id']] = $sku['brokerage'];
                        $integral_active[$sku['id']] = $sku['integral_active'];
                    }
                    $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
                    $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
                    unset($retail_price);
                } else {
                    $result[$key]['min_brokerage'] = 0;
                    $result[$key]['min_integral_active'] = 0;
                }
            } else if ($value['goods_type'] == 2) {
                $goods_sku = DbGoods::getAudioSkuRelation([['goods_id', '=', $value['id']]]);
                $where = ['goods_id' => $value['id']];
                $field = 'market_price';
                $result[$key]['min_market_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                $field = 'retail_price';
                $result[$key]['min_retail_price'] = DbAudios::getOneAudioSkuMost($where, 1, $field);
                if ($goods_sku) {
                    foreach ($goods_sku as $goods => $sku) {

                        $retail_price[$sku['id']] = $sku['retail_price'];
                        $brokerage[$sku['id']] = bcmul(getDistrProfits($sku['retail_price'], $sku['cost_price'], 0), 0.75, 2);
                        $integral_active[$sku['id']] = bcmul(bcsub(bcsub($sku['retail_price'], $sku['cost_price'], 4), 0, 2), 2, 0);
                    }
                    // print_r($brokerage);
                    // print_r(array_search(min($retail_price), $retail_price));
                    $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price), $retail_price)];
                    $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
                    // echo $value['id'];die;
                    unset($retail_price);
                } else {
                    $result[$key]['min_brokerage'] = 0;
                    $result[$key]['min_integral_active'] = 0;
                }
            }

        }
        return ['code' => 200, 'data' => $result];
    }
}
