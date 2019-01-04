<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
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
        $image    = filtraImage(Config::get('qiniu.domain'), $image);
        $logImage = DbImage::getLogImage($image);//判断时候有未完成的图片
        if (empty($logImage)) {//图片不存在
            return ['code' => '3005'];//图片没有上传过
        }
        $supplier = DbGoods::getSupplierWhereFile('name', $name, 'id');
        if (!empty($supplier)) {
            return ['code' => '3006'];//供应商名字不能重复
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
    public function getSupplierFreightdetail($id) {
        $field           = 'id,supid,stype,status,title,desc';
        $supplierfreight = DbGoods::getSupplierFreightdetail($field, $id);
        if (empty($supplierfreight)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'supplierfreight' => $supplierfreight];
    }
}