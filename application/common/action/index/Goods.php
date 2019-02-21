<?php
namespace app\common\action\index;

use app\facade\DbGoods;
use third\PHPTree;

class Goods
{

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
        $page = $page ? 1 : 1;
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

        if(empty($result)){
            return ['code' => 200, 'data' => $result];
        }

        /* 获取每条商品的SKU,后期列表开放加入购物车释放 */
        foreach ($result as $key => $value) {
           /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            $result[$key]['spec'] = $goods_spec;
            $result[$key]['goods_sku'] = $goods_sku; */
            $where = ['goods_id'=>$value['id']];
            $field = 'market_price';
            $result[$key]['min_market_price'] =DbGoods:: getOneSkuMost($where, 1, $field);
            $field = 'retail_price';
            $result[$key]['min_retail_price'] =DbGoods:: getOneSkuMost($where, 1, $field);
            list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            foreach ($goods_sku as $goods => $sku) {
                $retail_price[$sku['id']] = $sku['retail_price'];
                $brokerage[$sku['id']] = $sku['brokerage'];
            }
            $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price),$retail_price)];
            
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
        $field = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return ['code' => 3000, 'msg' => '商品不存在'];
        }
        $goods_data['supplier_desc'] = DbGoods::getSupplierData('desc', $goods_data['supplier_id'])['desc'];
        $where = ['goods_id' => $goods_id,'status' => 1];
        $field = 'market_price';
        $goods_data['min_market_price'] = DbGoods:: getOneSkuMost($where, 1, $field);
        $goods_data['max_market_price'] = DbGoods:: getOneSkuMost($where, 2, $field);
        $field = 'retail_price';
        $goods_data['min_retail_price'] = DbGoods:: getOneSkuMost($where, 1, $field);
        $goods_data['max_retail_price'] = DbGoods:: getOneSkuMost($where, 2, $field);

       

        /* 查询商品轮播图 */
        $where = [["goods_id", "=", $goods_id], ["image_type", "=", 2], ["source_type", "IN", "1," . $source]];
        $field = "goods_id,source_type,image_type,image_path";
        $goods_banner = DbGoods::getOneGoodsImage($where, $field);

        /* 查询商品详情图 */
        $where = [["goods_id", "=", $goods_id], ["image_type", "=", 1], ["source_type", "IN", "1," . $source]];
        $field = "goods_id,source_type,image_type,image_path";
        $goods_details = DbGoods::getOneGoodsImage($where, $field);

        /* 商品对应规格及SKU价格 */

        list($goods_spec, $goods_sku) = $this->getGoodsSku($goods_id);
        $integral_active = [];
        foreach ($goods_sku as $key => $value) {
            $integral_active[] = $value['integral_active'];
        }
        $goods_data['max_integral_active'] = max($integral_active);
        $goods_data['min_integral_active'] = min($integral_active);
        // $goods_sku = $goods_sku;

        return [
            'code' => 200,
            'goods_data' => $goods_data,
            'goods_banner' => $goods_banner,
            'goods_details' => $goods_details,
            'goods_spec' => $goods_spec,
            'goods_sku' => $goods_sku,
        ];
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
                $attr_where = [['id', 'in', $attr_where],['spec_id','=',$value['spec_id']]];
                
                $result['list'] = DbGoods::getAttrList($attr_where, $attr_field);
                
                $goods_spec[] = $result;
            }
         
        }
         
        
        $field = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,cost_price,integral_price,spec,sku_image';
        $where = [["goods_id", "=", $goods_id],["status", "=",1],['stock','<>',0]];
        $goods_sku = DbGoods::getOneGoodsSku($where, $field);
        /* brokerage：佣金；计算公式：(商品售价-商品进价-其它运费成本)*0.9*(钻石返利：0.75) */
        /* integral_active：积分；计算公式：(商品售价-商品进价-其它运费成本)*2 */
        foreach ($goods_sku as $goods => $sku) {
            $goods_sku[$goods]['brokerage'] = bcmul(bcmul(bcsub($sku['retail_price'],$sku['cost_price'],$sku['margin_price']),0.9),0.75,2);
            $goods_sku[$goods]['integral_active'] = bcmul(bcsub($sku['retail_price'],$sku['cost_price'],$sku['margin_price']),2,2);
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
        $page = $page ? 1 : 1;
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
        $where = ['id'=>$subject_id];
        $subject = DbGoods::getSubject($where, $field, true);
        if ($subject['tier'] !=3) {
            return ['code' => 3003,'msg'=>'传入专题ID有误'];
        }
        // getSubjectRelation($where, $field, $row = false,$limit = false)

        $field = 'goods_id';
        $where = ['subject_id'=>$subject_id];
        $goodslist = DbGoods::getSubjectRelation($where, $field, false,$limit);
        foreach ($goodslist as $goods => $list) {
           $goodsid[]=$list['goods_id'];
        }

        if(empty($goodslist)){
            return ['code' => 200, 'data' => []];
        }
        
        /* 获取专题商品关联关系 */

        $field = 'id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image';
        $order = 'id';
        // $where = ['status' => 1, 'cate_id' => $cate_id];
        
        $where = [['status','=', 1], ['id' ,'IN', $goodsid]];
        $result = DbGoods::getGoods($field, $limit, $order, $where);
        
        if(empty($result)){
            return ['code' => 200, 'data' => []];
        }
        // print_r($result);die;
        /* 获取每条商品的SKU,后期列表开放加入购物车释放 */
        foreach ($result as $key => $value) {
           /*  list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
            $result[$key]['spec'] = $goods_spec;
            $result[$key]['goods_sku'] = $goods_sku; */
            $where = [['goods_id', '=', $value['id']],['status', '=' , 1],['stock','<>',0]];
           
            $field = 'market_price';
            $result[$key]['min_market_price'] =DbGoods:: getOneSkuMost($where, 1, $field);
            $field = 'retail_price';
            $result[$key]['min_retail_price'] =DbGoods:: getOneSkuMost($where, 1, $field);
            // print_r($value['id']);die;
            list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
           
            foreach ($goods_sku as $goods => $sku) {
                $retail_price[$sku['id']] = $sku['retail_price'];
                $brokerage[$sku['id']] = $sku['brokerage'];
                $integral_active[$sku['id']] = $sku['integral_active'];
            }
            $result[$key]['min_brokerage'] = $brokerage[array_search(min($retail_price),$retail_price)];
            $result[$key]['min_integral_active'] = $integral_active[array_search(min($retail_price),$retail_price)];
            
        }
        return ['code' => 200, 'data' => $result];
    }

}
