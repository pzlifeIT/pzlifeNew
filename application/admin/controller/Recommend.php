<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\AdminController;
class Recommend extends AdminController
{
    /**
     * @api              {post} / 获取推荐信息(倒序排序)【未完成】
     * @apiDescription   getRecommend
     * @apiGroup         admin_Recommend
     * @apiName          getRecommend
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3002:页码和查询条数只能是数字
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 用户ID
     * @apiSuccess (data) {String} user_type 用户类型1.普通账户2.总店账户
     * @apiSuccess (data) {String} user_identity 用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiSuccess (data) {String} sex 用户性别 1.男 2.女 3.未确认
     * @apiSuccess (data) {String} nick_name 微信昵称
     * @apiSuccess (data) {String} true_name 真实姓名
     * @apiSuccess (data) {String} brithday 生日
     * @apiSuccess (data) {String} avatar 微信头像
     * @apiSuccess (data) {String} mobile 手机号
     * @apiSuccess (data) {String} email email
     * @apiSampleRequest /admin/Recommend/getRecommend
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  },
     * ]
     * @author rzc
     */
    public function getRecommend(){
        $result = $this->app->recommend->getRecommend();
        return $result;
    }

    /**
     * @api              {post} / 添加推荐
     * @apiDescription   addRecommend
     * @apiGroup         admin_Recommend
     * @apiName          addRecommend
     * @apiParam (入参) {Number} model_id 模板类型 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} image_path 图片路径
     * @apiParam (入参) {Number} parent_id 关联上级ID
     * @apiParam (入参) {Number} jump_type 跳转类型: 1:专题 2:商品 3:路径
     * @apiParam (入参) {String} jump_content 跳转内容
     * @apiParam (入参) {String} show_type 展示类型: 1:图片 2:商品
     * @apiParam (入参) {String} show_data 展示商品ID
     * @apiParam (入参) {String} show_days 展示每周天数(1:周一，2:周二...,7:周日)
     * @apiParam (入参) {Number} tier 层级
     * @apiParam (入参) {Number} model_order 模板排序
     * @apiParam (入参) {Number} model_son_order 模板子内容排序
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:model_id只能是数字 / 3002:无效的model_id / 3003:title,jump_type,jump_content参数不完整 / 3004:请上传图片 / 3005:未设置显示自然日或者未获取到parent_id / 3006:请设置展示商品 / 3007:未获取到parent_id / 3008:非法参数 / 3009:超出添加数量
     * @apiSampleRequest /admin/Recommend/addRecommend
     * @apiParamExample (data) {Array} 返回
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function addRecommend(){
        $model_id        = trim($this->request->post('model_id'));
        $title           = trim($this->request->post('title'));
        $image_path      = trim($this->request->post('image_path'));
        $parent_id       = trim($this->request->post('parent_id'));
        $jump_type       = trim($this->request->post('jump_type'));
        $jump_content    = trim($this->request->post('jump_content'));
        $show_type       = trim($this->request->post('show_type'));
        $show_data       = trim($this->request->post('show_data'));
        $show_days       = trim($this->request->post('show_days'));
        $model_order     = trim($this->request->post('model_order'));
        $model_son_order = trim($this->request->post('model_son_order'));
        $tier            = trim($this->request->post('tier'));
        $model_order     = $model_order ? $model_order : 0;
        $model_son_order = $model_son_order ? $model_son_order : 0;
        $parent_id       = $parent_id ? $parent_id : 0;
        $tier            = $tier ? $tier : 1;
        $show_type       = $show_type ? $show_type : 1;
        if (!is_numeric($model_id)) {
            return ['code' => '3001'];
        }
        $model_arr = [1,2,3,4,5,6,7,8,9,10];
        if (!in_array($model_id,$model_arr)) {
            return ['code' => '3002'];
        }
        if ($model_id == 1 || $model_id == 2 || $model_id == 3 ) {
            if ($tier == 2  ) {
                if (!$title  || !$jump_type || !$jump_content){
                    return ['code' => '3003'];
                }
                if (!$image_path || !$parent_id) {
                    return ['code' => '3004'];
                }
                
            }
        }elseif ($model_id == 5) {
            if (!$title || !$image_path || !$jump_type || !$jump_content){
                return ['code' => '3003'];
            }
            if ($tier == 2 ){
                if ( !$show_days || !$parent_id) {
                    return ['code' => '3005'];
                }
            }
        }elseif ($model_id == 6 || $model_id == 4 || $model_id == 7 || $model_id == 8 || $model_id == 9) {
            if ($tier == 1){
                if (!$title  || !$jump_type || !$jump_content){
                    return ['code' => '3003'];
                }
                if ($model_id == 7 || $model_id == 8 ) {
                    if (!$image_path){
                        return ['code' => '3004'];
                    }
                }
            }elseif ($tier == 2){
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if ($show_type == 1){
                    if (!$title  || !$jump_type || !$jump_content || !$image_path ){
                        return ['code' => '3003'];
                    }
                }elseif($show_type == 2){
                    if (!is_numeric($show_data)){
                        return ['code' => '3006'];
                    }
                    if (!$title  || !$jump_type || !$jump_content){
                        return ['code' => '3003'];
                    }
                }
            }
        }elseif ($model_id == 10) {
            if ($tier == 1){

            }elseif ($tier == 2) {
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if (!$title  || !$jump_type || !$jump_content || !$image_path ){
                    return ['code' => '3003'];
                }
            }elseif ($tier == 3) {
                if ($show_type!=2){
                    return ['code' => '3008'];
                }
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if (!is_numeric($show_data)){
                    return ['code' => '3006'];
                }
            }
        }

        $data = [];
        $data['model_id'] = $model_id;
        $data['title'] = $title;
        $data['image_path'] = $image_path;
        $data['parent_id'] = $parent_id;
        $data['jump_type'] = $jump_type;
        $data['jump_content'] = $jump_content;
        $data['show_type'] = $show_type;
        $data['show_data'] = $show_data;
        $data['show_days'] = $show_days;
        $data['tier'] = $tier;
        $data['model_order'] = $model_order;
        $data['model_son_order'] = $model_son_order;
        $result = $this->app->recommend->addRecommend($data);
        return $result;
    }

    /**
     * @api              {post} / 获取推荐ID
     * @apiDescription   getRecommendId
     * @apiGroup         admin_Recommend
     * @apiName          getRecommendId
     * @apiParam (入参) {Number} model_id 模板类型 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiParam (入参) {Number} tier 层级
     * @apiSuccess (返回) {String} code 200:成功 / 3000:查询结果为空 / 3001:model_id和tier只能是数字 
     * @apiSampleRequest /admin/Recommend/getRecommendId
     * @apiParamExample (data) {Array} 返回
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function getRecommendId(){
        $model_id = trim($this->request->post('model_id'));
        $tier = trim($this->request->post('tier'));
        if (!is_numeric($tier) || !is_numeric($model_id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->recommend->getRecommendId($model_id,$tier);
        return $result;
    }
}
