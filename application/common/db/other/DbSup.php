<?php

namespace app\common\db\other;

use app\common\model\SupAdmin;
use app\common\model\SupPromote;

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

    public function updatePasswd($newPasswd, $id) {
        $supAdmin = new SupAdmin();
        return $supAdmin->save(['sup_passwd' => $newPasswd], ['id' => $id]);
    }
}