<?php

namespace app\common\db\other;

use app\common\model\SupPromote;
use app\common\model\SupPromoteSignUp;
use app\common\model\SupPromoteShareLog;

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
}