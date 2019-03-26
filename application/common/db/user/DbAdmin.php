<?php

namespace app\common\db\user;

use app\common\model\Admin;
use app\common\model\AdminRemittance;
use app\common\model\User;

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

    /**
     * 添加充值记录
     * @param $data
     * @return mixed
     * @author rzc
     */
    public function addAdminRemittance($data){
        $AdminRemittance = new AdminRemittance;
        $AdminRemittance->save($data);
        return $AdminRemittance->id;
    }

    /**
     * 修改充值记录
     * @param $data
     * @param $id
     * @return mixed
     * @author rzc
     */
    public function editRemittance($data,$id){
        $AdminRemittance = new AdminRemittance;
        return $AdminRemittance->save($data,['id' => $id]);
    }

    /**
     * 获取充值记录
     * @param $data
     * @param $id
     * @return mixed
     * @author rzc
     */
    public function getAdminRemittance($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = AdminRemittance::field($field)->with([
            'initiateadmin'=>function($query){
                $query->field('id,admin_name,department,stype,status');
             },'auditadmin'=>function($query){
                $query->field('id,admin_name,department,stype,status');
             },'user'=>function($query){
                $query->field('id,nick_name,user_identity,mobile');
             }
        ])->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }
}