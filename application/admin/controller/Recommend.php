<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\AdminController;

class Recommend extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
//        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取推荐信息
     * @apiDescription   getRecommend
     * @apiGroup         admin_Recommend
     * @apiName          getRecommend
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:推荐列表空
     * @apiSuccess (返回) {object_array} recommends_ids 主模块id
     * @apiSuccess (data) {object_array} recommends 结果
     * @apiSuccess (recommends) {String} id 主键ID
     * @apiSuccess (recommends) {String} model_id 模板id 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiSuccess (recommends) {String} title 标题
     * @apiSuccess (recommends) {String} image_path 图片路径
     * @apiSuccess (recommends) {String} jump_type 跳转类型: 1:专题 2:商品 3:路径
     * @apiSuccess (recommends) {String} jump_content 跳转内容
     * @apiSuccess (recommends) {String} model_order 模板排序
     * @apiSuccess (recommends) {String} is_show 模块是否显示,1:显示,2:不显示
     * @apiSuccess (recommends[son]) {String} id 主键ID
     * @apiSuccess (recommends[son]) {String} model_id 模板id 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiSuccess (recommends[son]) {String} title 标题
     * @apiSuccess (recommends[son]) {String} image_path 图片路径
     * @apiSuccess (recommends[son]) {String} parent_id 关联上级ID
     * @apiSuccess (recommends[son]) {String} jump_type 跳转类型: 1:专题 2:商品 3:路径
     * @apiSuccess (recommends[son]) {String} jump_content 跳转内容
     * @apiSuccess (recommends[son]) {String} show_type 展示类型: 1:图片 2:商品
     * @apiSuccess (recommends[son]) {String} show_data 展示商品ID
     * @apiSuccess (recommends[son]) {String} show_days 展示每周天数
     * @apiSuccess (recommends[son]) {String} tier 层级
     * @apiSuccess (recommends[son]) {String} is_show 模块是否显示,1:显示,2:不显示
     * @apiSuccess (recommends[son]) {String} model_order 模板排序
     * @apiSuccess (recommends[son]) {String} goods_id 商品ID
     * @apiSuccess (recommends[son]) {String} supplier_id 商品供应商ID
     * @apiSuccess (recommends[son]) {String} cate_id 商品分类ID
     * @apiSuccess (recommends[son]) {String} goods_name 商品名称
     * @apiSuccess (recommends[son]) {String} goods_title 商品标题
     * @apiSuccess (recommends[son]) {String} goods_subtitle 商品副标题
     * @apiSuccess (recommends[son]) {String} goods_image 商品图片
     * @apiSuccess (recommends[son]) {String} goods_status 商品状态
     * @apiSuccess (recommends[son]) {String} goods_min_brokerage 商品最小钻石返利
     * @apiSuccess (recommends[son]) {String} goods_min_integral_active 商品最小赠送积分
     * @apiSuccess (recommends[son][third]) {String} id 主键ID
     * @apiSuccess (recommends[son][third]) {String} model_id 模板id 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiSuccess (recommends[son][third]) {String} title 标题
     * @apiSuccess (recommends[son][third]) {String} image_path 图片路径
     * @apiSuccess (recommends[son][third]) {String} parent_id 关联上级ID
     * @apiSuccess (recommends[son][third]) {String} jump_type 跳转类型: 1:专题 2:商品 3:路径
     * @apiSuccess (recommends[son][third]) {String} jump_content 跳转内容
     * @apiSuccess (recommends[son][third]) {String} show_type 展示类型: 1:图片 2:商品
     * @apiSuccess (recommends[son][third]) {String} show_data 展示商品ID
     * @apiSuccess (recommends[son][third]) {String} show_days 展示每周天数
     * @apiSuccess (recommends[son][third]) {String} tier 层级
     * @apiSuccess (recommends[son][third]) {String} is_show 模块是否显示,1:显示,2:不显示
     * @apiSuccess (recommends[son][third]) {String} model_order 模板排序
     * @apiSuccess (recommends[son][third]) {String} goods_id 商品ID
     * @apiSuccess (recommends[son][third]) {String} supplier_id 商品供应商ID
     * @apiSuccess (recommends[son][third]) {String} cate_id 商品分类ID
     * @apiSuccess (recommends[son][third]) {String} goods_name 商品名称
     * @apiSuccess (recommends[son][third]) {String} goods_title 商品标题
     * @apiSuccess (recommends[son][third]) {String} goods_subtitle 商品副标题
     * @apiSuccess (recommends[son][third]) {String} goods_image 商品图片
     * @apiSuccess (recommends[son][third]) {String} goods_status 商品状态
     * @apiSuccess (recommends[son][third]) {String} goods_retail_price 商品零售价
     * @apiSuccess (recommends[son][third]) {String} goods_min_brokerage 商品最小钻石返利
     * @apiSuccess (recommends[son][third]) {String} goods_min_integral_active 商品最小赠送积分
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
    public function getRecommend() {
        $result = $this->app->recommend->getRecommend();
        return $result;
    }

    /**
     * @api              {post} / 添加推荐
     * @apiDescription   addRecommend
     * @apiGroup         admin_Recommend
     * @apiName          addRecommend
     * @apiParam (入参) {String} cms_con_id
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
     * @apiParam (入参) {Number} is_show 主模块内容是否显示
     * @apiParam (入参) {Number} model_order 模板排序
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:model_id只能是数字 / 3002:无效的model_id / 3003:title,jump_type,jump_content参数不完整 / 3004:请上传图片(模板类型为3，或者未传入上级ID) / 3005:未设置显示自然日或者未获取到parent_id / 3006:请设置展示商品 / 3007:未获取到parent_id / 3008:非法参数 / 3009:超出添加数量 / 3010:图片没有上传过 / 3011:添加失败 /3012:不存在的关联上级内容 / 3013:添加内容模板ID与父级模板ID不一致
     * @apiSampleRequest /admin/Recommend/addRecommend
     * @apiParamExample (data) {Array} 返回
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function addRecommend() {
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
        $is_show         = trim($this->request->post('is_show'));
        $model_order     = $model_order ? $model_order : 0;
        $parent_id       = $parent_id ? $parent_id : 0;
        $tier            = $tier ? $tier : 1;
        $show_type       = $show_type ? $show_type : 1;
        $is_show         = $is_show ? $is_show : 2;
        if (!is_numeric($model_id)) {
            return ['code' => '3001'];
        }
        $model_arr = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        if (!in_array($model_id, $model_arr)) {
            return ['code' => '3002'];
        }

        if ($model_id == 1 || $model_id == 2 || $model_id == 3) {
            if ($tier == 2) {
                if (!$title || !$jump_type || !$jump_content) {
                    return ['code' => '3003'];
                }
                if (!$image_path || !$parent_id) {
                    return ['code' => '3004'];
                }

            }
        } elseif ($model_id == 5) {

            if ($tier == 2) {
                if (!$title || !$image_path || !$jump_type || !$jump_content) {
                    return ['code' => '3003'];
                }
                if (!$show_days || !$parent_id) {
                    return ['code' => '3005'];
                }
            }
        } elseif ($model_id == 6 || $model_id == 4 || $model_id == 7 || $model_id == 8 || $model_id == 9) {
            if ($tier == 1) {
                if (!$title || !$jump_type || !$jump_content) {
                    return ['code' => '3003'];
                }
                if ($model_id == 7 || $model_id == 8) {
                    if (!$image_path) {
                        return ['code' => '3004'];
                    }
                }
            } elseif ($tier == 2) {
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if ($show_type == 1) {
                    if (!$title || !$jump_type || !$jump_content || !$image_path) {
                        return ['code' => '3003'];
                    }
                } elseif ($show_type == 2) {
                    if (!is_numeric($show_data)) {
                        return ['code' => '3006'];
                    }
                    if (!$title || !$jump_type || !$jump_content) {
                        return ['code' => '3003'];
                    }
                }
            }
        } elseif ($model_id == 10) {
            if ($tier == 1) {

            } elseif ($tier == 2) {
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if (!$title || !$jump_type || !$jump_content || !$image_path) {
                    return ['code' => '3003'];
                }
            } elseif ($tier == 3) {
                if ($show_type != 2) {
                    return ['code' => '3008'];
                }
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if (!is_numeric($show_data)) {
                    return ['code' => '3006'];
                }
            }
        }

        $data                    = [];
        $data['model_id']        = $model_id;
        $data['title']           = $title;
        $data['image_path']      = $image_path;
        $data['parent_id']       = $parent_id;
        $data['jump_type']       = $jump_type;
        $data['jump_content']    = $jump_content;
        $data['show_type']       = $show_type;
        $data['show_data']       = $show_data;
        $data['show_days']       = $show_days;
        $data['tier']            = $tier;
        $data['model_order']     = $model_order;
        $data['model_son_order'] = $model_son_order;
        $data['is_show']         = $is_show;
        if ($tier != 1) {
            unset($data['is_show']);
        }
        $result = $this->app->recommend->addRecommend($data);
        return $result;
    }

    /**
     * @api              {post} / 获取推荐ID
     * @apiDescription   getRecommendId
     * @apiGroup         admin_Recommend
     * @apiName          getRecommendId
     * @apiParam (入参) {String} cms_con_id
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
    public function getRecommendId() {
        $model_id = trim($this->request->post('model_id'));
        $tier     = trim($this->request->post('tier'));
        if (!is_numeric($tier) || !is_numeric($model_id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->recommend->getRecommendId($model_id, $tier);
        return $result;
    }

    /**
     * @api              {post} / 获取推荐详情
     * @apiDescription   getRecommendInfo
     * @apiGroup         admin_Recommend
     * @apiName          getRecommendInfo
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 推荐ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:查询结果为空 / 3001:id只能是数字
     * @apiSuccess (recommends_info) {String} id
     * @apiSuccess (recommends_info) {String} model_id 模板id 1:轮播banner 2:图标tips 3:专题模块推荐 4:新品上市 5:每周推荐 6:爆款推荐 7:应季推荐 8:时令推荐 9:买主推荐 10:专题商品推荐
     * @apiSuccess (recommends_info) {String} title 标题
     * @apiSuccess (recommends_info) {String} image_path 图片路径
     * @apiSuccess (recommends_info) {String} parent_id 关联上级ID
     * @apiSuccess (recommends_info) {String} jump_type 跳转类型: 1:专题 2:商品 3:路径
     * @apiSuccess (recommends_info) {String} jump_content 跳转内容
     * @apiSuccess (recommends_info) {String} show_type 展示类型: 1:图片 2:商品
     * @apiSuccess (recommends_info) {String} show_data 展示商品ID
     * @apiSuccess (recommends_info) {String} show_days 展示每周天数
     * @apiSuccess (recommends_info) {String} tier 层级
     * @apiSuccess (recommends_info) {String} model_order 模板排序
     * @apiSampleRequest /admin/Recommend/getRecommendInfo
     * @apiParamExample (data) {Array} 返回
     * [
     * "code":"200",返回code码
     * "recommends_info":{
     * "id": 1,
     * "model_id": 1,
     * "title": "banner",
     * "image_path": "",
     * "parent_id": 0,
     * "jump_type": 1,
     * "jump_content": "",
     * "show_type": 1,
     * "show_data": 0,
     * "show_days": 1,
     * "tier": 1,
     * "model_order": 0,
     * "model_son_order": 0,
     * "create_time": "2019-03-05 19:20:02",
     * "update_time": "2019-03-05 19:20:02",
     * "delete_time": null
     * }
     * ]
     * @author rzc
     */
    public function getRecommendInfo() {
        $id = trim($this->request->post('id'));
        if (!$id || !is_numeric($id)) {
            return ['code' => 3001];
        }
        $result = $this->app->recommend->getRecommendInfo($id);
        return $result;
    }

    /**
     * @api              {post} / 修改推荐
     * @apiDescription   updateRecommend
     * @apiGroup         admin_Recommend
     * @apiName          updateRecommend
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 修改内容对应ID
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
     * @apiSuccess (返回) {String} code 200:成功 / 3000:查询结果为空，无法更改 / 3001:model_id和id只能是数字 / 3002:无效的model_id / 3003:title,jump_type,jump_content参数不完整 / 3004:请上传图片 / 3005:未设置显示自然日或者未获取到parent_id / 3006:请设置展示商品 / 3007:未获取到parent_id / 3008:非法参数 / 3010:图片没有上传过 / 3011:修改失败
     * @apiSampleRequest /admin/Recommend/updateRecommend
     * @apiParamExample (data) {Array} 返回
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function updateRecommend() {
        $id              = trim($this->request->post('id'));
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
        $is_show         = trim($this->request->post('is_show'));
        $model_order     = $model_order ? $model_order : 0;
        $parent_id       = $parent_id ? $parent_id : 0;
        $tier            = $tier ? $tier : 1;
        $show_type       = $show_type ? $show_type : 1;
        $is_show         = $is_show ? $is_show : 2;
        if (!is_numeric($model_id) && !is_numeric($id)) {
            return ['code' => '3001'];
        }
        $model_arr = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        if (!in_array($model_id, $model_arr)) {
            return ['code' => '3002'];
        }


        if ($model_id == 1 || $model_id == 2 || $model_id == 3) {
            if ($tier == 2) {
                if (!$title || !$jump_type || !$jump_content) {
                    return ['code' => '3003'];
                }
                if (!$parent_id) {
                    return ['code' => '3004'];
                }

            }

        } elseif ($model_id == 5) {
            if (!$title || !$jump_type || !$jump_content) {
                return ['code' => '3003'];
            }
            if ($tier == 2) {
                if (!$show_days || !$parent_id) {
                    return ['code' => '3005'];
                }
            }
        } elseif ($model_id == 6 || $model_id == 4 || $model_id == 7 || $model_id == 8 || $model_id == 9) {
            if ($tier == 1) {
                if (!$title || !$jump_type || !$jump_content) {
                    return ['code' => '3003'];
                }

            } elseif ($tier == 2) {
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if ($show_type == 1) {
                    if (!$title || !$jump_type || !$jump_content) {
                        return ['code' => '3003'];
                    }
                } elseif ($show_type == 2) {
                    if (!is_numeric($show_data)) {
                        return ['code' => '3006'];
                    }
                    if (!$title || !$jump_type || !$jump_content) {
                        return ['code' => '3003'];
                    }
                }
            }
        } elseif ($model_id == 10) {
            if ($tier == 1) {

            } elseif ($tier == 2) {
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if (!$title || !$jump_type || !$jump_content) {
                    return ['code' => '3003'];
                }
            } elseif ($tier == 3) {
                if ($show_type != 2) {
                    return ['code' => '3008'];
                }
                if (!$parent_id) {
                    return ['code' => '3007'];
                }
                if (!is_numeric($show_data)) {
                    return ['code' => '3006'];
                }
            }
        }

        $data                    = [];
        $data['model_id']        = $model_id;
        $data['title']           = $title;
        $data['image_path']      = $image_path;
        $data['jump_type']       = $jump_type;
        $data['jump_content']    = $jump_content;
        $data['show_type']       = $show_type;
        $data['show_data']       = $show_data;
        $data['show_days']       = $show_days;
        $data['model_order']     = $model_order;
        $data['model_son_order'] = $model_son_order;
        $data['is_show']         = $is_show;
        if ($tier != 1) {
            unset($data['is_show']);
        }
        $result = $this->app->recommend->saveRecommend($data, $id);
        return $result;
    }

    /**
     * @api              {post} / 删除推荐
     * @apiDescription   delRecommend
     * @apiGroup         admin_Recommend
     * @apiName          delRecommend
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 删除内容对应ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:查询结果为空，无法更改 / 3002:请先删除下级推荐
     * @apiSampleRequest /admin/Recommend/delRecommend
     * @apiParamExample (data) {Array} 返回
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function delRecommend() {
        $id = trim($this->request->post('id'));
        if (!is_numeric($id)) {
            return ['code' => '3001'];
        }
        $result = $this->app->recommend->delRecommend($id);
        return $result;
    }
}
