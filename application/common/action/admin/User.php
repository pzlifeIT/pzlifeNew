<?php
namespace app\common\action\admin;

use app\facade\DbUser;
use think\Db;

class User{
    /**
     * 会员列表
     * @return array
     * @author rzc
     */
    public function getUsers($page,$pagenum){
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $filed = '*';
        $order  = 'id';
        $limit  = $offset . ',' . $pagenum;
        $result = DbUser::getUsers($filed, $order, $limit);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $totle = DbUser::getUsersCount();
        return ['code' => '200','totle' => $totle,'result' =>$result];
    }

}