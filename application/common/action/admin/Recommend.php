<?php
namespace app\common\action\admin;

use app\facade\DbRecommend;
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
        
        if (!empty($id)){//更新操作
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
                    return ['code' => '200'];
                }
                Db::rollback();
                return ['code' => '3011'];//修改失败
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3011'];//修改失败
            }
        }else{//添加操作
            if ($data['tier'] > 1) {
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
                    return ['code' => '200'];
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
        $recommends = DbRecommend::getRecommends('id,model_id,title,image_path,jump_type,jump_content,model_order',['tier'=>1],false,'model_order','desc');
        if ($recommends) {
            foreach ($recommends as $key => $value) {
                $recommends_son = DbRecommend::getRecommends('*',['tier'=>2,'parent_id' => $value['id']],false,'model_order','desc');
                if ($recommends_son) {
                    if ($value['model_id'] == 10){
                        foreach ($recommends_son as $recommend => $son) {
                            $third = [];
                            $third = DbRecommend::getRecommends('*',['tier'=>3,'parent_id' => $son['id']],false,'model_order','desc');
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
        $recommends = DbRecommend::getRecommends('id,model_id,title,image_path,jump_type,jump_content,model_order',['tier'=>1],false,'model_id','asc');
        if ($recommends) {
            foreach ($recommends as $key => $value) {
                $recommends_son = DbRecommend::getRecommends('*',['tier'=>2,'parent_id' => $value['id']],false,'id','asc');
                if ($recommends_son) {
                    if ($value['model_id'] == 10){
                        foreach ($recommends_son as $recommend => $son) {
                            $third = [];
                            $third = DbRecommend::getRecommends('*',['tier'=>3,'parent_id' => $son['id']],false,'id','asc');
                            $recommends_son[$recommend]['third'] = $third;
                        }
                    }
                   
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
            $recommends = DbRecommend::getRecommends('id',['tier'=>$tier,'model_id' => $model_id],false);
            if ($recommends) {
                $recommends_id = [];
                foreach ($recommends as $key => $value) {
                    $recommends_id[] = $value['id'];
                }
                return ['code' => '200','recommends_id' => $recommends_id];
            }else{
                return ['code' => '3000'];
            }
        }
    }

    public function getRecommendInfo($id){
        $recommends = DbRecommend::getRecommends('*',['id' => $id],true);
            if ($recommends) {
                // $recommends_id = $recommends['id'];
                return ['code' => '200','recommends_info' => $recommends];
            }else{
                return ['code' => '3000'];
            }
    }
    
}