<?php
namespace app\common\action\admin;

use app\facade\DbRecommend;
use app\facade\DbGoods;
use app\facade\DbImage;
use think\Db;
use Config;
use cache\Phpredis;

class Recommend{
   
    function delDataEmptyKey($data) {
        foreach ($data as $key => $value) {
            if (!$value) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    function SetRedis($data = []){
        $this->redis = Phpredis::getConn();
        $redisListKey = Config::get('redisKey.index.redisIndexShow');
        $this->redis->set($redisListKey,json_encode($data));
    }
    /**
     * 添加新记录
     * @param $data
     * @return array
     * @author rzc
     */
    public function saveRecommend($data,$id = 0){
        $data = $this->delDataEmptyKey($data);
        if ($id){//更新操作
            $recommend_info = DbRecommend::getRecommends('*',['id' => $id],true);
            if (empty($recommend_info)) {
                return ['code' => '3000'];
            }
            Db::startTrans();
            try {
                
                $oldImage = $recommend_info['image_path'];
                $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);
                if (!empty($oldImage)) {
                    DbImage::updateLogImageStatus($oldImage, 3);//更新状态为已完成
                }
                if (!empty($data['image_path'])) {
                    $image    = filtraImage(Config::get('qiniu.domain'), $data['image_path']);
                    $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
                    if (empty($logImage)) {//图片不存在
                        return ['code' => '3010'];//图片没有上传过
                    }
                    DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
                }
                $updateRecommends = DbRecommend::updateRecommends($data,$id);
                $has_recommends = $this->getRecommendOrderBy();
                if ($has_recommends['recommends']) {
                    $this->SetRedis($has_recommends['recommends']);
                }
                if ($updateRecommends) {
                    Db::commit();
                    return ['code' => '200','id'=>$id];
                }
                Db::rollback();
                return ['code' => '3011'];//修改失败
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3011'];//修改失败
            }
        }else{//添加操作
            // print_r($data);die;
        if (!empty($data['parent_id'])) {
            $parent_info = DbRecommend::getRecommends('*',['id' => $data['parent_id']],true);
            if (empty($parent_info)) {
                return ['code' => '3012'];
            }
            if ($parent_info['model_id'] != $data['model_id']) {
                return ['code' => '3013'];
            }
        }
            if ($data['tier'] > 1) {
                $has_parent = DbRecommend::getRecommends('id',['tier'=>$data['tier']-1,'model_id'=>$data['model_id']]);
                if (empty($has_parent)) {
                    return ['code' => '3012'];//添加上级为空
                }
                if ($data['model_id']<8 && $data['model_id'] > 1) {
                    $model_num = [
                        2 => 8,
                        3 => 2,
                        4 => 4,
                        5 => 7,
                        6 => 8,
                        7 => 9,
                    ];
                    $has_num = DbRecommend::CountRecommends(['model_id'=>$data['model_id'],'tier'=>2]);
                    if ($has_num+1 > $model_num[$data['model_id']]) {
                        return ['code' => '3009'];//超出限定添加数量
                    }
                   
                }elseif ($data['model_id'] == 10) {
                    if ($data['tier'] == 3){
                        $has_num = DbRecommend::CountRecommends(['model_id'=>$data['model_id'],'tier'=>3]);
                        if ($has_num+1 > 5) {
                            return ['code' => '3009'];//超出限定添加数量
                        }
                    }
                }
                
            }else{
                // print_r(DbRecommend::getRecommends('id',['model_id'=>$data['model_id'],'tier'=>1]));die;
                if (DbRecommend::getRecommends('id',['model_id'=>$data['model_id'],'tier'=>1])) {
                    return ['code' => '3009'];//超出限定添加数量
                }
            }
            Db::startTrans();
            try {
                
                if (!empty($data['image_path'])) {
                    $image    = filtraImage(Config::get('qiniu.domain'), $data['image_path']);
                    $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
                    if (empty($logImage)) {//图片不存在
                        return ['code' => '3010'];//图片没有上传过
                    }
                    DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
                }
                $add = DbRecommend::addRecommends($data);
                $has_recommends = $this->getRecommendOrderBy();
                if ($has_recommends['recommends']) {
                    $this->SetRedis($has_recommends['recommends']);
                }
                if ($add) {
                    Db::commit();
                    return ['code' => '200','add_id'=>$add];
                }
                Db::rollback();
                return ['code' => '3011'];//修改失败
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3011'];//修改失败
            }
        }
  
    
    }
    
    /**
     * 查询推荐内容(已排序,倒序)
     * @return array
     * @author rzc
     */
    public function getRecommendOrderBy(){
        
        $recommends = [];
        $recommends = DbRecommend::getRecommends('id,model_id,title,image_path,jump_type,jump_content,model_order,is_show',['tier'=>1],false,'model_order','desc');
        if ($recommends) {
            foreach ($recommends as $key => $value) {
                $recommends_son = DbRecommend::getRecommends('*',['tier'=>2,'parent_id' => $value['id']],false,'model_order','desc');
                if ($recommends_son) {
                    foreach ($recommends_son as $recommend => $son) {
                        if ($son['show_type'] == 2 && $son['show_data']) {
                            $goods_data = $this->getGoods($son['show_data']);
                            if ($goods_data){
                                $recommends_son[$recommend]['goods_id'] = $goods_data['id'];
                                $recommends_son[$recommend]['supplier_id'] = $goods_data['supplier_id'];
                                $recommends_son[$recommend]['cate_id'] = $goods_data['cate_id'];
                                $recommends_son[$recommend]['goods_name'] = $goods_data['goods_name'];
                                $recommends_son[$recommend]['goods_title'] = $goods_data['title'];
                                $recommends_son[$recommend]['goods_subtitle'] = $goods_data['subtitle'];
                                $recommends_son[$recommend]['goods_image'] = $goods_data['image'];
                                $recommends_son[$recommend]['goods_status'] = $goods_data['status'];
                                $recommends_son[$recommend]['goods_retail_price'] = $goods_data['retail_price'];
                                $recommends_son[$recommend]['goods_min_brokerage'] = $goods_data['min_brokerage'];
                                $recommends_son[$recommend]['goods_min_integral_active'] = $goods_data['min_integral_active'];
                            }
                        }
                        if ($value['model_id'] == 10){
                            $third = [];
                            $third = DbRecommend::getRecommends('*',['tier'=>3,'parent_id' => $son['id']],false,'model_order','desc');
                            if ($third) {
                                foreach ($third as $thi => $rd) {
                                    if ($rd['show_type'] == 2 && $rd['show_data']) {
                                        $goods_data = $this->getGoods($rd['show_data']);
                                        if ($goods_data){
                                            $third[$thi]['goods_id'] = $goods_data['id'];
                                            $third[$thi]['supplier_id'] = $goods_data['supplier_id'];
                                            $third[$thi]['cate_id'] = $goods_data['cate_id'];
                                            $third[$thi]['goods_name'] = $goods_data['goods_name'];
                                            $third[$thi]['goods_title'] = $goods_data['title'];
                                            $third[$thi]['goods_subtitle'] = $goods_data['subtitle'];
                                            $third[$thi]['goods_image'] = $goods_data['image'];
                                            $third[$thi]['goods_status'] = $goods_data['status'];
                                            $third[$thi]['goods_retail_price'] = $goods_data['retail_price'];
                                            $third[$thi]['goods_min_brokerage'] = $goods_data['min_brokerage'];
                                            $third[$thi]['goods_min_integral_active'] = $goods_data['min_integral_active'];
                                        }
                                    }
                                }
                            }
                            $recommends_son[$recommend]['third'] = $third;

                        }
                    }
                    
                   
                }
                $recommends[$key]['son'] = $recommends_son;
               
            }
            // print_r($redisListKey);die;
            
            return ['code' => '200','recommends' => $recommends];
        }else{
            return ['code' => '3000'];
        }
    }

    public function getRecommend(){
        
        $recommends = [];
        $recommends_ids = [];
        $recommends = DbRecommend::getRecommends('id,model_id,title,image_path,jump_type,jump_content,model_order,is_show',['tier'=>1],false,'model_id','asc');
        if (!empty($recommends)) {
            foreach ($recommends as $key => $value) {
                $recommends_son = DbRecommend::getRecommends('*',['tier'=>2,'parent_id' => $value['id']],false,'id','asc');
                if (!empty($recommends_son)) {
                    foreach ($recommends_son as $recommend => $son) {
                        if ($son['show_type'] == 2 && $son['show_data']) {
                            $goods_data = $this->getGoods($son['show_data']);
                            if ($goods_data){
                                $recommends_son[$recommend]['goods_id'] = $goods_data['id'];
                                $recommends_son[$recommend]['supplier_id'] = $goods_data['supplier_id'];
                                $recommends_son[$recommend]['cate_id'] = $goods_data['cate_id'];
                                $recommends_son[$recommend]['goods_name'] = $goods_data['goods_name'];
                                $recommends_son[$recommend]['goods_title'] = $goods_data['title'];
                                $recommends_son[$recommend]['goods_subtitle'] = $goods_data['subtitle'];
                                $recommends_son[$recommend]['goods_image'] = $goods_data['image'];
                                $recommends_son[$recommend]['goods_status'] = $goods_data['status'];
                                $recommends_son[$recommend]['goods_retail_price'] = $goods_data['retail_price'];
                                $recommends_son[$recommend]['goods_min_brokerage'] = $goods_data['min_brokerage'];
                                $recommends_son[$recommend]['goods_min_integral_active'] = $goods_data['min_integral_active'];
                            }
                        }
                        if ($value['model_id'] == 10){
                            $third = [];
                            $third = DbRecommend::getRecommends('*',['tier'=>3,'parent_id' => $son['id']],false,'id','asc');
                            if (!empty($third)) {
                                foreach ($third as $thi => $rd) {
                                    if ($rd['show_type'] == 2 && $rd['show_data']) {
                                        $goods_data = $this->getGoods($rd['show_data']);
                                        if ($goods_data){
                                            $third[$thi]['goods_id'] = $goods_data['id'];
                                            $third[$thi]['supplier_id'] = $goods_data['supplier_id'];
                                            $third[$thi]['cate_id'] = $goods_data['cate_id'];
                                            $third[$thi]['goods_name'] = $goods_data['goods_name'];
                                            $third[$thi]['goods_title'] = $goods_data['title'];
                                            $third[$thi]['goods_subtitle'] = $goods_data['subtitle'];
                                            $third[$thi]['goods_image'] = $goods_data['image'];
                                            $third[$thi]['goods_status'] = $goods_data['status'];
                                            $third[$thi]['goods_retail_price'] = $goods_data['retail_price'];
                                            $third[$thi]['goods_min_brokerage'] = $goods_data['min_brokerage'];
                                            $third[$thi]['goods_min_integral_active'] = $goods_data['min_integral_active'];
                                        }
                                    }
                                }
                            }
                            $recommends_son[$recommend]['third'] = $third;
                        }
                       
                    }
                    
                   
                }else{
                    $recommends_son = [];
                }
                $recommends[$key]['son'] = $recommends_son;
                $recommends_ids[] = $value['id'];
            }
            // print_r($redisListKey);die;
            
            return ['code' => '200','recommends_ids'=>$recommends_ids,'recommends' => $recommends];
        }else{
            return ['code' => '3000'];
        }
    }

    /**
     * 查询推荐模板下的id集合 
     * @param $model_id
     * @param $tier
     * @return array
     * @author rzc
     */
    public function getRecommendId($model_id,$tier){
        if ($tier = 1) {
            $recommends = DbRecommend::getRecommends('id',['tier'=>$tier,'model_id' => $model_id],true);
            if ($recommends) {
                // $recommends_id = $recommends['id'];
                return ['code' => '200','recommends_id' => $recommends];
            }else{
                return ['code' => '3000'];
            }
        }else{
            $recommends = DbRecommend::getRecommends('id,title',['tier'=>$tier,'model_id' => $model_id],false);
            if ($recommends) {
                $recommends_id = [];
                foreach ($recommends as $key => $value) {
                    $recommends_id[] = $value['id'];
                }
                if ($model_id == 10) {
                    return ['code' => '200','recommends_id' => $recommends];
                }
                return ['code' => '200','recommends_id' => $recommends_id];
            }else{
                return ['code' => '3000'];
            }
        }
    }

    /**
     * 查询推荐详情
     * @param $model_id
     * @param $tier
     * @return array
     * @author rzc
     */
    public function getRecommendInfo($id){
        $recommends = DbRecommend::getRecommends('*',['id' => $id],true);
            if ($recommends) {
                $recommends_son = DbRecommend::getRecommends('*',['tier'=>2,'parent_id' => $recommends['id']],false,'id','asc');
                if (empty($recommends_son)) {
                    $recommends_son = [];
                }else{
                    if ($recommends['model_id'] == 10) {
                        foreach ($recommends_son as $rec => $son) {
                            $third = DbRecommend::getRecommends('*',['tier'=>3,'parent_id' => $son['id']],false,'id','asc');
                            if (empty($third)) {
                                $third = [];
                            }
                            $recommends_son[$rec]['third'] = $third;
                        }
                    }
                }
                
                $recommends['son'] = $recommends_son;
                // $recommends_id = $recommends['id'];
                return ['code' => '200','recommends_info' => $recommends];
            }else{
                return ['code' => '3000'];
            }
    }

    function getGoods($goodsid){
        /* 返回商品基本信息 （从商品库中直接查询）*/
        $where = [["id", "=", $goodsid], ["status", "=", 1]];
        $field = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return [];
        }
        list($goods_spec,$goods_sku) = $this->getGoodsSku($goodsid);
        if ($goods_sku) {
            foreach ($goods_sku as $goods => $sku) {
                
                $retail_price[$sku['id']] = $sku['retail_price'];
                $brokerage[$sku['id']] = $sku['brokerage'];
                $integral_active[$sku['id']] = $sku['integral_active'];
            }
            $goods_data['retail_price'] = min($retail_price);
            $goods_data['min_brokerage'] = $brokerage[array_search(min($retail_price),$retail_price)];
            $goods_data['min_integral_active'] = $integral_active[array_search(min($retail_price),$retail_price)];
        }else{
            $goods_data['min_brokerage'] = 0;
            $goods_data['min_integral_active'] = 0;
            $goods_data['retail_price'] = 0;
        }
        return $goods_data;
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
        // $where = [["goods_id", "=", $goods_id],["status", "=",1],['retail_price','<>', 0]];
        $where = [["goods_id", "=", $goods_id],["status", "=",1]];
        $goods_sku = DbGoods::getOneGoodsSku($where, $field);
        /* brokerage：佣金；计算公式：(商品售价-商品进价-其它运费成本)*0.9*(钻石返利：0.75) */
        /* integral_active：积分；计算公式：(商品售价-商品进价-其它运费成本)*2 */
        foreach ($goods_sku as $goods => $sku) {
            $goods_sku[$goods]['brokerage'] = bcmul( bcmul(bcsub(bcsub($sku['retail_price'],$sku['cost_price'],4),$sku['margin_price'],2),0.9,2),0.75,2);
            $goods_sku[$goods]['integral_active'] = bcmul(bcsub(bcsub($sku['retail_price'],$sku['cost_price'],4),$sku['margin_price'],2),2,2);
            $sku_json = DbGoods::getAttrList( [['id', 'in', $sku['spec']]], 'attr_name');
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
     * 删除推荐
     * @param $id
     * @return array
     * @author rzc
     */
    public function delRecommend($id){
        $recommends = DbRecommend::getRecommends('id',['id' => $id],true);
        if (empty($recommends)) {
            return ['code' => '3000'];
        }
        $recommends_son = DbRecommend::getRecommends('id',['parent_id' => $id],false);
        if ($recommends_son) {
            return ['code' => '3002'];
        }
        DbRecommend::delRecommend($id);
        $has_recommends = $this->getRecommendOrderBy();
        if ($has_recommends['recommends']) {
            $this->SetRedis($has_recommends['recommends']);
        }
        return ['code' =>'200'];
    }
}