<?php

namespace app\common\db\other;

use app\common\model\SupPromote;
use app\common\model\SupPromoteSignUp;
use app\common\model\SupPromoteShareLog;
use app\common\model\PromoteImage;

class DbSup {
    public function __construct() {
    }

    public function getSupPromoteCount($where) {
        return SupPromote::where($where)->count();
    }

    /**
     * 获取列表
     * @param $where
     * @param $field
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author zyr
     */
    public function getSupPromote($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = SupPromote::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 添加
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addSupPromote($data) {
        $supPromote = new SupPromote();
        $supPromote->save($data);
        return $supPromote->id;
    }

    /**
     * 编辑
     * @param $data
     * @param $id
     * @author zyr
     */
    public function editSupPromote($data, $id) {
        $supPromote = new SupPromote();
        $supPromote->save($data, ['id' => $id]);
    }

    /**
     * 获取报名列表
     * @param $where
     * @param $field
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author rzc
     */
    public function getSupPromoteSignUp($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = SupPromoteSignUp::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function getSupPromoteSignUpCount($where) {
        return SupPromoteSignUp::where($where)->count();
    }

    public function saveSupPromoteSignUp($data){
        $SupPromoteSignUp = new SupPromoteSignUp;
        $SupPromoteSignUp->save($data);
        return $SupPromoteSignUp->id;
    }

    /**
     * 获取分享日志
     * @param $where
     * @param $field
     * @param bool $row
     * @param string $orderBy
     * @param string $limit
     * @return mixed
     * @author rzc
     */
    public function getSupPromoteShareLog($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = SupPromoteShareLog::field($field)->where($where);
        return getResult($obj, $row, $orderBy, $limit);
    }

    public function saveSupPromoteShareLog($data){
        $SupPromoteShareLog = new SupPromoteShareLog;
        $SupPromoteShareLog->save($data);
        return $SupPromoteShareLog->id;
    }

    public function updateSupPromoteShareLog($data,$id){
        $SupPromoteShareLog = new SupPromoteShareLog;
        return $SupPromoteShareLog->save($data,['id' => $id]);
    }
    
    /**
     * 批量添加图片
     * @param $data
     * @return bool
     */
    public function addPromoteImageList($data) {
        $PromoteImage = new PromoteImage();
        return $PromoteImage->saveAll($data);
    }

    /**
     * 获取一个商品的图片
     * @param $where
     * @param $field
     * @param $orderBy
     * @return array
     * @author rzc
     * 2019/1/2-16:26
     */
    public function getOnePromoteImage($where, $field, $orderBy = '') {
        return PromoteImage::where($where)->field($field)->order($orderBy)->select()->toArray();
    }

   /**
     * 删除商品图
     * @param $id
     * @return bool
     * @author wujunjie
     * 2019/1/8-10:09
     */
    public function delPromoteImage($id) {
        return PromoteImage::destroy($id);
    }

    /**
     * 更新商品图片
     * @param $data
     * @param $id
     * @author zyr
     */
    public function updatePromoteImage($data, $id) {
        $goodsImage = new PromoteImage();
        $goodsImage->save($data, ['id' => $id]);
    }
}