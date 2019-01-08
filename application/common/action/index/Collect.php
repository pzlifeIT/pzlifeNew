<?php
namespace app\common\action\index;

use app\facade\DbGoods;
use app\facade\DbUser;

class Collect{

    /**
     * 获取收藏的商品
     * @param $user_id
     * @return array
     * @author wujunjie
     * 2019/1/8-11:23
     */
    public function collectGoods($user_id){
        //判断传过来的数据是否有效
        $where = [["id","=",$user_id]];
        $res = DbUser::getUser($where);
        if (empty($res)){
            return ['msg'=>"数据不存在","code"=>3000];
        }
        //根据收藏表中的用户id对应的商品id找到商品的基本数据
        $where = [["user_id","=",$user_id]];
        $field = "id,user_id,goods_id";
//        "select collect.id,collect.user_id,collect.goods_id,goods.goods_name,goods.title from collect inner join goods on (collect.goods_id =goods.id ) where ('user_id' = '5')";
        $res = DbGoods::getCollectGoods($where,$field);
        if (empty($res)){
            return ['msg'=>''];
        }
    }
}