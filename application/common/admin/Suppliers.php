<?php

namespace app\common\admin;

use app\common\model\SupplierFreight;
use app\common\model\Supplier;
use third\PHPTree;

class Suppliers {

    /**
     * 供应商列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author rzc
     */
    public function getSuppliers($page,$pagenum) {

        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        
        $result = Supplier::limit($offset,$pagenum)->order('id', 'asc')->select()->toArray();
        $totle = Supplier::count();
        if (empty($result)) {
            return ['code' => '3000'];
        }
       
        return ['code' => '200','totle'=>$totle, 'data' => $result];
    }

    /**
     * 供应商详情
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author rzc
     */
    public function getSupplierData($supplierId){
        $result = Supplier::where('id',$supplierId)->findOrEmpty()->toArray();
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200','data' => $result];
    }

    /**
     * 新增供应商
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author rzc
     */
    public function addSupplier($data){
        $data['create_time'] = time();        
        $add = Supplier::insert($data);
        if ($add) {
            return ['code' => '200','msg'=>'添加成功'];
        } else {
            return ['code' => '3004','msg'=>'添加失败'];
        }
    }

    /**
     * 供应商快递模板列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author rzc
     */
    public function getSupplierFreights($supid){
        $result = SupplierFreight::where('supid',$supid)->select()->toArray();
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => 0,'data'=>$result];
    }
}