<?php

namespace app\common\db\product;

use app\common\model\LabelGoodsRelation;
use app\common\model\LabelLibrary;
use think\Db;

class DbLabel {
    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author zyr
     */
    public function getLabelLibrary($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = LabelLibrary::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author zyr
     */
    public function getLabelGoodsRelation($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = LabelGoodsRelation::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    public function getLabelGoodsRelationByGoods($where, $field) {
        array_push($where,['gr.delete_time','=','0']);
        array_push($where,['g.delete_time','=','0']);
        return Db::table('pz_label_goods_relation')
            ->alias('gr')
            ->field($field)
            ->join(['pz_goods' => 'g'], 'gr.goods_id=g.id')
            ->where($where)
//            ->limit($limit)
            ->select();
    }

    /**
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return array
     * @author zyr
     */
    public function getLabelGoodsRelationDistinct($where) {
        return LabelGoodsRelation::distinct(true)->field('goods_id')->where($where)->select()->toArray();

    }

    /**
     * @param $data
     * @return int
     * @author zyr
     */
    public function addLabelLibrary($data) {
        $labelLibrary = new LabelLibrary();
        $labelLibrary->save($data);
        return $labelLibrary->id;
    }

    /**
     * @param $labelLibId
     * @param string $modify
     * @return bool
     * @author zyr
     */
    public function modifyHeat($labelLibId, $modify = 'inc') {
        $labelLibrary           = LabelLibrary::get($labelLibId);
        $labelLibrary->the_heat = [$modify, 1];
        return $labelLibrary->save();
    }

    /**
     * @param $data
     * @return int
     * @author zyr
     */
    public function addLabelGoodsRelation($data) {
        $labelGoodsRelation = new LabelGoodsRelation();
        $labelGoodsRelation->save($data);
        return $labelGoodsRelation->id;
    }

    public function delLabelLibrary($labelLibId) {
        LabelLibrary::destroy($labelLibId);
    }

    public function delLabelGoodsRelation($labelGoodsRelationId) {
        LabelGoodsRelation::destroy($labelGoodsRelationId);
    }

    /**
     * @param $obj
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    private function getResult($obj, $row = false, $orderBy = '', $limit = '') {
        if (!empty($orderBy)) {
            $obj = $obj->order($orderBy);
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
}