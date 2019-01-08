<?php
namespace app\common\action\index;

use app\facade\DbGoods;
use third\PHPTree;

class Goods
{

    /**
     * 商品列表
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

        /* 获取每条商品的SKU,后期列表开放加入购物车释放 */
        /* foreach ($result as $key => $value) {
        list($goods_spec,$goods_sku) = $this->getGoodsSku($value['id']);
        $result[$key]['spec'] = $goods_spec;
        $result[$key]['goods_sku'] = $goods_sku;
        } */
        return ['code' => 200, 'data' => $result];
    }

    /**
     * 商品列表
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
            $field = 'id,cate_id,spe_name';
            foreach ($goods_first_spec as $key => $value) {
                $where = ['id', '=', $value['spec_id']];
                $result = DbGoods::getOneSpec($where, $field);
                $goods_attr_field = 'attr_id';
                $goods_attr_where = ['id', '=', $goods_id];
                $goods_first_attr = DbGoods::getOneGoodsSpec($goods_attr_where, $goods_attr_field);
                $attr_field = 'id,spec_id,attr_name';
                $attr_where = ['id', 'in', join(',', $goods_first_attr)];
                $result['list'] = DbGoods::getAttrList($where, $field);
                $goods_spec[] = $result;
            }

        }
        $field = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,integral_price,integral_active,spec,sku_image';
        $where = [["goods_id", "=", $goods_id]];
        $goods_sku = DbGoods::getOneGoodsSku($where, $field);
        return [$goods_spec, $goods_sku];
    }

}
