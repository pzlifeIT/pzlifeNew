<?php

namespace app\common\db\user;

use app\common\model\Admin;

class DbAdmin {

    public function getAdminInfo($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Admin::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * @param $data
     * @return mixed
     * @author zyr
     */
    public function addAdmin($data) {
        $admin = new Admin();
        $admin->save($data);
        return $admin->id;
    }

    public function updatePasswd($newPasswd, $id) {
        $admin = new Admin();
        return $admin->save(['passwd' => $newPasswd], ['id' => $id]);
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