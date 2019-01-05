<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbProvinces;
use app\facade\DbImage;
use think\Db;
use Config;

class Suppliers {

    /**
     * 供应商列表
     * @return array
     * @author rzc
     */
    public function getSuppliers($page, $pagenum) {
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $field  = 'id,tel,name,status,image,title,desc';
        $order  = 'id,desc';
        $limit  = $offset . ',' . $pagenum;
        $result = DbGoods::getSupplier($field, $order, $limit);
        $totle  = DbGoods::getSupplierCount();
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'totle' => $totle, 'data' => $result];
    }

    /**
     * 供应商详情
     * @return array
     * @author rzc
     */
    public function getSupplierData($supplierId) {
        $field  = 'id,tel,name,status,image,title,desc';
        $result = DbGoods::getSupplierData($field, $supplierId);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 添加供应商
     * @param $tel
     * @param $name
     * @param $title
     * @param $desc
     * @param $image
     * @return array
     * @author zyr
     */
    public function addSupplier($tel, $name, $title, $desc, $image) {
        $supplier = DbGoods::getSupplierWhereFile('name', $name, 'id');
        if (!empty($supplier)) {
            return ['code' => '3006'];//供应商名字不能重复
        }
        $image = filtraImage(Config::get('qiniu.domain'), $image);
        if (!empty($image)) {
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3005'];//图片没有上传过
            }
        }
        /* 初始化数组 */
        $new_supplier          = [];
        $new_supplier['tel']   = $tel;
        $new_supplier['name']  = $name;
        $new_supplier['title'] = $title;
        $new_supplier['desc']  = $desc;
        $new_supplier['image'] = $image;
        Db::startTrans();
        try {
            DbGoods::addSupplier($new_supplier);
            DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            Db::commit();
            return ['code' => '200', 'msg' => '添加成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3004', 'msg' => '添加失败'];
        }
    }

    public function editSupplier($id, $tel, $name, $title, $desc, $image) {
        $supplierRes = DbGoods::getOneSupplier(['id' => $id], 'image');
        if (empty($supplierRes)) {
            return ['code' => '3006'];//供应商id不存在
        }
        $supplierName = DbGoods::getOneSupplier([['name', '=', $name], ['id', '<>', $id]], 'id');
        if (empty($supplierName)) {
            return ['code' => '3007'];//供应商名称不能重复
        }
        /* 初始化数组 */
        $oldLogImage  = [];
        $logImage     = [];
        $new_supplier = [];
        $image    = filtraImage(Config::get('qiniu.domain'), $image);
        if (!empty($image)) {//提交了图片
            $logImage = DbImage::getLogImage($image, 2);//判断时候有未完成的图片
            if (empty($logImage)) {//图片不存在
                return ['code' => '3005'];//图片没有上传过
            }
            $oldImage = $supplierRes['image'];
            $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);
            if (!empty($oldImage)) {//之前有图片
                if (stripos($oldImage, 'http') === false) {//新版本图片
                    $oldLogImage = DbImage::getLogImage($oldImage, 1);//之前在使用的图片日志
                }
            }
            $new_supplier['image'] = $image;
        }
        $new_supplier['tel']   = $tel;
        $new_supplier['name']  = $name;
        $new_supplier['title'] = $title;
        $new_supplier['desc']  = $desc;
        Db::startTrans();
        try {
            DbGoods::updateSupplier($new_supplier, $id);
            if (!empty($logImage)) {
                DbImage::updateLogImageStatus($logImage, 1);//更新状态为已完成
            }
            if (!empty($oldLogImage)) {
                DbImage::updateLogImageStatus($oldLogImage, 3);//更新状态为弃用
            }
            Db::commit();
            return ['code' => '200', 'msg' => '更新成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3004', 'msg' => '更新失败'];
        }
    }

    /**
     * 查询供应商表中某值
     * @return array
     * @author rzc
     */
    public function getSupplierWhereFile($field, $value) {
        return DbGoods::getSupplierWhereFile($field, $value);
    }

    /**
     * 查询供应商表中某条数据并且ID不为此ID的值
     * @return array
     * @author rzc
     */
    public function getSupplierWhereFileByID($field, $value, $id) {
        return DbGoods::getSupplierWhereFileByID($field, $value, $id);
    }

    /**
     * 修改供应商信息
     * @return array
     * @author rzc
     */
    public function updateSupplier($data, $id) {
        $update = DbGoods::updateSupplier($data, $id);
        if ($update) {
            return ['code' => '200', 'msg' => '添加成功'];
        } else {
            return ['code' => '3004', 'msg' => '添加失败'];
        }
    }

    /**
     * 供应商快递模板列表
     * @return array
     * @author rzc
     */
    public function getSupplierFreights($supid) {
        $field = 'id,supid,stype,title,desc';
        echo 123;
        die;
        $result = DbGoods::getSupplierFreights($field, $supid);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'data' => $result];
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 启用或者停用供应商快递模板
     * @return array
     * @author rzc
     */
    public function updateSupplierFreights($status, $supid) {
        return DbGoods::updateSupplierFreights($status, $supid);
    }

    /**
     * 获取供应商快递模板详情
     * @return array
     * @author rzc
     */
    public function getSupplierFreight($id) {
        $field           = 'id,supid,stype,status,title,desc';
        $supplierfreight = DbGoods::getSupplierFreight($field, $id);
        if (empty($supplierfreight)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'data' => $supplierfreight];
    }

    /**
     * 获取供应商快递模板运费列表
     * @return array
     * @author rzc
     */
    public function getSupplierFreightdetailList($freight_id, $page, $pagenum) {
        $field  = 'id,freight_id,area_id,price,after_price,total_price';
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }

        $limit  = $offset . ',' . $pagenum;
        $result = DbGoods::getSupplierFreightdetailList($field, $limit, $freight_id);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        /* 获取每条数据的上级省市名称 */
        foreach ($result as $key => $value) {
            $parent   = DbProvinces::getAreaOne('area_name,pid', ['id' => $value['area_id']]);
            $pid      = $parent['pid'];
            $areaname = $parent['area_name'];
            do {
                $area     = DbProvinces::getAreaOne('area_name,pid', ['id' => $pid]);
                $pid      = $area['pid'];
                $areaname = $area['area_name'] . $areaname;
            } while ($pid);
            $result[$key]['areaname'] = $areaname;
        }
        $count = DbGoods::getSupplierFreightdetailCount($freight_id);
        return ['code' => '200', 'totle' => $count, 'data' => $result];
    }

    /**
     * 新建供应商快递模板
     * @return array
     * @author rzc
     */
    public function addSupplierFreight($supplierId, $stype, $title, $desc) {
        if (!is_numeric($supplierId) || !is_numeric($stype)) {
            return ['code' => '3001']; /* 供应商id和方式必须是数字 */
        }
        if (!$title || !$desc) {
            return ['code' => '3002']; /* 标题和详情不能为空 */
        }
        $supplierfreight          = [];
        $supplierfreight['supid'] = $supplierId;
        $supplierfreight['stype'] = $stype;
        $supplierfreight['title'] = $title;
        $supplierfreight['desc']  = $desc;
//        DbGoods::addSupplierFreight($data);
        return ['code' => '200'];
        DbGoods::addSupplierFreight($supplierfreight);
        return ['code' => '200'];
    }

    /**
     * 修改供应商快递模板
     * @return array
     * @author rzc
     */
    public function updateSupplierFreight($supplier_freight_Id, $stype, $title, $desc) {
        if (!is_numeric($supplier_freight_Id) || !is_numeric($stype)) {
            return ['code' => '3001']; /* 供应商id和方式必须是数字 */
        }
        if (!$title || !$desc) {
            return ['code' => '3002']; /* 标题和详情不能为空 */
        }
        $supplierfreight          = [];
        $supplierfreight['stype'] = $stype;
        $supplierfreight['title'] = $title;
        $supplierfreight['desc']  = $desc;
        DbGoods::updateSupplierFreight($supplierfreight, $supplier_freight_Id);
        return ['code' => '200'];
    }

    /**
     * 获取供应商快递模板运费详情
     * @return array
     * @author rzc
     */
    public function getSupplierFreightdetail($id) {
        if (!is_numeric($id)) {
            return ['code' => '3001']; /* 供应商id和方式必须是数字 */
        }
        // $result = DbGoods::getSupplierFreightdetail($id);
        if (empty($result)) {
            return ['code' => '3000']; /* 不能为空 */
        }
        return ['code' => '200', $result];
    }
}