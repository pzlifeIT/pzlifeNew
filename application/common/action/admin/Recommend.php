<?php
namespace app\common\action\admin;

use app\facade\DbRecommend;
use think\Db;

class Recommend{

    function delDataEmptyKey($data) {
        foreach ($data as $key => $value) {
            if (!$value) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * 添加新记录
     * @return array
     * @author rzc
     */
    public function addRecommend($data){
        $data = $this->delDataEmptyKey($data);
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
        DbRecommend::addRecommends($data);
        /* 写入首页缓存 */
        return ['code' => 200];
        
    }
    
    /**
     * 查询推荐内容
     * @return array
     * @author rzc
     */
    public function getRecommend(){
        $recommends = [];
        $recommends = DbRecommend::getRecommends('id,model_id,title,image_path,jump_type,jump_content,model_order',['tier'=>1],false,'model_order','desc');
        if ($recommends) {
            foreach ($recommends as $key => $value) {
                $recommends_son = DbRecommend::getRecommends('*',['tier'=>2,'parent_id' => $value['id']],false,'model_order','desc');
                print_r($recommends_son);
            }
        }else{
            return ['code' => '3000'];
        }
    }

    /**
     * 查询推荐id 
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
}