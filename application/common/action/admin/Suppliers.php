<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbProvinces;
use third\PHPTree;
use Config;

class Suppliers {

    /**
     * 供应商列表
     * @return array
     * @author rzc
     */
    public function getSuppliers($page,$pagenum) {

        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $field = 'id,tel,name,status,image,title,desc';
        $order = 'id,desc';
        $limit = $offset.','.$pagenum;
        $result =DbGoods::getSupplier($field,$order,$limit);
        $totle = DbGoods::getSupplierCount();
        if (empty($result)) {
            return ['code' => '3000'];
        }
       
        return ['code' => '200','totle'=>$totle, 'data' => $result];
    }

    /**
     * 供应商详情
     * @return array
     * @author rzc
     */
    public function getSupplierData($supplierId){
        $field = 'id,tel,name,status,image,title,desc';
        $result = DbGoods::getSupplierData($field,$supplierId);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        if ($result['image']) {
            $result['image']=Config::get('qiniu.domain').'/'.$result['image'];
        }
        return ['code' => '200','data' => $result];
    }

    /**
     * 查询供应商表中某值
     * @return array
     * @author rzc
     */
    public function getSupplierWhereFile($field,$value){
        return DbGoods::getSupplierWhereFile($field,$value);
    }

    /**
     * 查询供应商表中某条数据并且ID不为此ID的值
     * @return array
     * @author rzc
     */
    public function getSupplierWhereFileByID($field,$value,$id){
        return DbGoods::getSupplierWhereFileByID($field,$value,$id);
    }

    /**
     * 新增供应商
     * @return array
     * @author rzc
     */
    public function addSupplier($data){
        $data['create_time'] = time();
       
        $add = DbGoods::addSupplier($data);
        if ($add) {
            return ['code' => '200','msg' => '添加成功'];
        } else {
            return ['code' => '3004','msg' => '添加失败'];
        }
    }

    /**
     * 修改供应商信息
     * @return array
     * @author rzc
     */
    public function updateSupplier($data,$id){ 
        $update = DbGoods::updateSupplier($data,$id);
        if ($update) {
            return ['code'=> '200','msg' => '添加成功'];
        } else {
            return ['code' => '3004','msg' => '添加失败'];
        }
    }

    /**
     * 供应商快递模板列表
     * @return array
     * @author rzc
     */
    public function getSupplierFreights($supid){
        $field = 'id,supid,stype,title,desc';
        echo 123;die;
        $result = DbGoods::getSupplierFreights($field,$supid);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200','data' => $result];
    }

    /**
     * 启用或者停用供应商快递模板
     * @return array
     * @author rzc
     */
    public function updateSupplierFreights($status,$supid){
        return DbGoods::updateSupplierFreights($status,$supid);
    }

    /**
     * 获取供应商快递模板详情
     * @return array
     * @author rzc
     */
    public function getSupplierFreightdetail($id){
        $field = 'id,supid,stype,status,title,desc';
        $supplierfreight = DbGoods::getSupplierFreightdetail($field,$id);
        if (empty($supplierfreight)) {
            return ['code' => '3000'];
        }
        return ['code' => '200','data' => $supplierfreight];
    }

    /**
     * 获取供应商快递模板运费列表
     * @return array
     * @author rzc
     */
    public function  getSupplierFreightdetailList($freight_id,$page,$pagenum){
        $field = 'id,freight_id,area_id,price,after_price,total_price';
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        
        $limit = $offset.','.$pagenum;
        $result = DbGoods::getSupplierFreightdetailList($field,$limit,$freight_id);
        if (empty($result)) {
            return ['code'=> '3000'];
        }
        /* 获取每条数据的上级省市名称 */
        foreach ($result as $key => $value) {
            $parent = DbProvinces::getAreaOne('area_name,pid',['id' => $value['area_id']]);
            $pid = $parent['pid'];
            $areaname = $parent['area_name'];
            do {
                $area = DbProvinces::getAreaOne('area_name,pid',['id'=>$pid]);
                $pid = $area['pid'];
                $areaname = $area['area_name'].$areaname;
            } while ($pid);
            $result[$key]['areaname'] = $areaname;
        }
        $count = DbGoods::getSupplierFreightdetailCount($freight_id);
        return ['code' => '200','totle' => $count,'data' => $result];
    }

    /**
     * 新建供应商快递模板
     * @return array
     * @author rzc
     */
    public function addSupplierFreight($supplierId,$stype,$title,$desc){
        if (!is_numeric($supplierId) || !is_numeric($stype)) {
            return ['code' => '3001']; /* 供应商id和方式必须是数字 */
        }
        if (!$title || !$desc) {
            return ['code' => '3002']; /* 标题和详情不能为空 */
        }
        $supplierfreight = [];
        $supplierfreight['supid'] = $supplierId;
        $supplierfreight['stype'] = $stype;
        $supplierfreight['title'] = $title;
        $supplierfreight['desc'] = $desc;
        DbGoods::addSupplierFreight($data);
        return ['code'=>'200'];
    }
}