<?php

namespace app\common\db\user;

use app\common\model\Admin;
use app\common\model\AdminRemittance;
use app\common\model\AdminBank;
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

    /**
     * 获取充值记录条数
     * @param $where
     * @return mixed
     * @author rzc
     */
    public function getCountAdminRemittance($where){
        return AdminRemittance::where($where)->count();
    }

    /**
     * 获取支持银行
     * @param $where
     * @param $field
     * @param $row
     * @param $orderBy
     * @param $limit
     * @return mixed
     * @author rzc
     */
    public function getAdminBank($where, $field, $row = false, $orderBy = '', $limit = '',$whereOr = false) {
        $obj = AdminBank::field($field);
        if ($whereOr === true) {
            $obj = $obj->whereOr($where);
        }else{
            $obj = $obj->where($where);
        }
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 获取记录条数
     * @param $where
     * @return number
     * @author rzc
     */
    public function getAdminBankCount($where){
        return AdminBank::where($where)->count();
    }

    /**
     * 添加支持银行
     * @param $data
     * @return id
     * @author rzc
     */
    public function saveAdminBank($data){
        $AdminBank = new AdminBank;
        $AdminBank->save($data);
        return $AdminBank->id;
    }

    /**
     * 修改支持银行
     * @param $data
     * @return id
     * @author rzc
     */
    public function editAdminBank($data,$id){
        $AdminBank = new AdminBank;
        return $AdminBank->save($data,['id'=>$id]);
    }
}