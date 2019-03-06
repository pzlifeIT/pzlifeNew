<?php

namespace app\common\db\other;

use app\common\model\Recommends;

class DbRecommend {

    /**
     * 获取推荐信息
     * @param $field 查询字段
     * @param $where 条件
     * @param $row 单条/多条
     * @param $orderBy 排序字段
     * @param $sc 排序规则
     * @param $limit 查询条数
     * @return array
     */
    public function getRecommends($field, $where,$row = false, $orderBy = '', $sc = '', $limit = '') {
        $obj = Recommends::field($field)->where($where);
        if (!empty($orderBy) && !empty($sc)) {
            $obj = $obj->order($orderBy, $sc);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    public function CountRecommends($where){
        return Recommends::where($where)->count();
    }

    /**
     * 添加一条推荐
     * @param $data 查询字段
     * @return array
     */
    public function addRecommends($data){
        $Recommends  = new Recommends;
        $Recommends->save($data);
        return $Recommends->id;
    }

    /**
     * 修改一条推荐
     * @param $data 查询字段
     * @return array
     */
    public function updateRecommends($data,$id){
        $Recommends  = new Recommends;
        return $Recommends->save($data,['id'=>$id]);
    }
}