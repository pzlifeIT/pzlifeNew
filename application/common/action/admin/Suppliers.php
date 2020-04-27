<?php

namespace app\common\action\admin;

use app\common\model\SupplierFreightArea;
use app\facade\DbGoods;
use app\facade\DbProvinces;
use app\facade\DbImage;
use app\facade\DbUser;
use think\Db;
use Config;

class Suppliers extends CommonIndex
{
    private $supCipherUserKey = 'suppass'; //用户密码加密key

    /**
     * 供应商列表
     * @return array
     * @author rzc
     */
    public function getSuppliers($page, $pagenum, $supplierName)
    {
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        $field  = 'id,tel,name,status,image,title,desc';
        $order  = 'id,desc';
        $limit  = $offset . ',' . $pagenum;
        $where  = [['name', 'like', '%' . $supplierName . '%']];
        $result = DbGoods::getSupplier($field, $where, $order, $limit);
        $totle  = DbGoods::getSupplierCount($where);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        return ['code' => '200', 'totle' => $totle, 'data' => $result];
    }

    /**
     * 获取所有供应商
     * @return array
     * @author zyr
     */
    public function getSuppliersAll()
    {
        $field  = 'id,name';
        $where  = ['status' => 1];
        $result = DbGoods::getSupplier($field, $where, 'id');
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 供应商详情
     * @return array
     * @author rzc
     */
    public function getSupplierData($supplierId)
    {
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
    public function addSupplier($tel, $name, $title, $desc, $image)
    {
        $supplier = DbGoods::getSupplierWhereFile('name', $name, 'id');
        if (!empty($supplier)) {
            return ['code' => '3006']; //供应商名字不能重复
        }
        $image = filtraImage(Config::get('qiniu.domain'), $image);
        if (!empty($image)) {
            $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
            if (empty($logImage)) { //图片不存在
                return ['code' => '3005']; //图片没有上传过
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
            DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            Db::commit();
            return ['code' => '200', 'msg' => '添加成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3004', 'msg' => '添加失败'];
        }
    }

    public function editSupplier($id, $tel, $name, $title, $desc, $image)
    {
        $supplierRes = DbGoods::getOneSupplier(['id' => $id], 'image');
        if (empty($supplierRes)) {
            return ['code' => '3006']; //供应商id不存在
        }
        $supplierName = DbGoods::getOneSupplier([['name', '=', $name], ['id', '<>', $id]], 'id');
        if (!empty($supplierName)) {
            return ['code' => '3007']; //供应商名称不能重复
        }
        /* 初始化数组 */
        $oldLogImage  = [];
        $logImage     = [];
        $new_supplier = [];
        $image        = filtraImage(Config::get('qiniu.domain'), $image);
        if (!empty($image)) { //提交了图片
            $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
            if (empty($logImage)) { //图片不存在
                return ['code' => '3005']; //图片没有上传过
            }
            $oldImage = $supplierRes['image'];
            $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);
            if (!empty($oldImage)) { //之前有图片
                if (stripos($oldImage, 'http') === false) { //新版本图片
                    $oldLogImage = DbImage::getLogImage($oldImage, 1); //之前在使用的图片日志
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
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            }
            if (!empty($oldLogImage)) {
                DbImage::updateLogImageStatus($oldLogImage, 3); //更新状态为弃用
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
    public function getSupplierWhereFile($field, $value)
    {
        return DbGoods::getSupplierWhereFile($field, $value);
    }

    /**
     * 查询供应商表中某条数据并且ID不为此ID的值
     * @return array
     * @author rzc
     */
    public function getSupplierWhereFileByID($field, $value, $id)
    {
        return DbGoods::getSupplierWhereFileByID($field, $value, $id);
    }

    /**
     * 修改供应商信息
     * @return array
     * @author rzc
     */
    public function updateSupplier($data, $id)
    {
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
    public function getSupplierFreights($supid)
    {
        $field  = 'id,supid,stype,title,desc';
        $result = DbGoods::getSupplierFreights(['supid' => $supid, 'status' => 1], $field);
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
    public function updateSupplierFreights($status, $supid)
    {
        return DbGoods::updateSupplierFreights($status, $supid);
    }

    /**
     * 获取供应商快递模板详情
     * @return array
     * @author rzc
     */
    public function getSupplierFreight($id)
    {
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
    public function getSupplierFreightdetailList($freight_id, $page, $pagenum)
    {
        $field  = 'id,freight_id,price,after_price,total_price,unit_price';
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
        /* foreach ($result as $key => $value) {

            $parent   = DbProvinces::getAreaOne('area_name,pid', ['id' => $value['area_id']]);
            $pid      = $parent['pid'];
            $areaname = $parent['area_name'];
            do {
                $area     = DbProvinces::getAreaOne('area_name,pid', ['id' => $pid]);
                $pid      = $area['pid'];
                $areaname = $area['area_name'] . $areaname;
            } while ($pid);
            $result[$key]['areaname'] = $areaname;
        } */
        $count = DbGoods::getSupplierFreightdetailCount($freight_id);
        return ['code' => '200', 'totle' => $count, 'data' => $result];
    }

    /**
     * 新建供应商快递模板
     * @param $supplierId
     * @param $stype
     * @param $title
     * @param $desc
     * @return array
     * @author rzc
     */
    public function addSupplierFreight(int $supplierId, int $stype, $title, $desc)
    {
        $supplierfreight          = [];
        $supplierfreight['supid'] = $supplierId;
        $supplierfreight['stype'] = $stype;
        $supplierfreight['title'] = $title;
        $supplierfreight['desc']  = $desc;
        //        DbGoods::addSupplierFreight($data);
        DbGoods::addSupplierFreight($supplierfreight);
        return ['code' => '200'];
    }

    /**
     * 修改供应商快递模板
     * @param $supplier_freight_Id
     * @param $stype
     * @param $title
     * @param $desc
     * @return array
     * @author rzc
     */
    public function updateSupplierFreight(int $supplier_freight_Id, int $stype, $title, $desc)
    {
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
    public function getSupplierFreightdetail($id)
    {
        $field  = 'id,freight_id,price,after_price,total_price,unit_price';
        $result = DbGoods::getSupplierFreightdetailRow($field, $id);
        if (empty($result)) {
            return ['code' => '3000']; /* 不能为空 */
        }
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 添加供应商快递模板运费
     * @param $freight_id
     * @param $price
     * @param $after_price
     * @param $total_price
     * @param $unit_price
     * @return array
     * @author rzc
     */
    public function addSupplierFreightdetail($freight_id, $price, $after_price, $total_price, $unit_price)
    {
        $supplierFreight = DbGoods::getSupplierFreight('id', $freight_id);
        if (empty($supplierFreight)) {
            return ['code' => '3003'];
        }
        /* 查询该运费模板ID是否添加过此区域 */
        $supplier_freight_detail                = [];
        $supplier_freight_detail['freight_id']  = $freight_id;
        $supplier_freight_detail['price']       = $price;
        $supplier_freight_detail['after_price'] = $after_price;
        $supplier_freight_detail['total_price'] = $total_price;
        $supplier_freight_detail['unit_price']  = $unit_price;
        $add                                    = DbGoods::addSupplierFreightdetail($supplier_freight_detail);
        return ['code' => 200, 'id' => $add];
    }

    /**
     * 添加供应商快递模板运费
     * @param $freight_detail_id
     * @param $price
     * @param $after_price
     * @param $total_price
     * @param $unit_price
     * @return array
     * @author zyr
     */
    public function editSupplierFreightdetail($freight_detail_id, $price, $after_price, $total_price, $unit_price)
    {
        $freightDetail = DbGoods::getSupplierFreightdetailRow('id', $freight_detail_id);
        if (empty($freightDetail)) {
            return ['code' => '3003'];
        }
        /* 查询该运费模板ID是否添加过此区域 */
        $supplier_freight_detail                = [];
        $supplier_freight_detail['price']       = $price;
        $supplier_freight_detail['after_price'] = $after_price;
        $supplier_freight_detail['total_price'] = $total_price;
        $supplier_freight_detail['unit_price']  = $unit_price;
        $add                                    = DbGoods::editSupplierFreightdetail($supplier_freight_detail, $freight_detail_id);
        return ['code' => 200, 'id' => $add];
    }

    /**
     * 更新运费模版和市的价格关联
     * @param $cityIdStr
     * @param $freightDetailId
     * @return array
     * @author zyr
     */
    public function updateSupplierFreightArea($cityIdStr, $freightDetailId)
    {
        $cityIdStr  = str_replace(' ', '', $cityIdStr);
        $cityIdList = explode(',', $cityIdStr);
        $cityCount  = DbProvinces::getAreaCount('id', [['id', 'in', $cityIdList], ['level', '=', 2]]);
        if ($cityCount != count($cityIdList)) {
            return ['code' => 3004];
        }
        $where         = [['freight_detail_id', '=', $freightDetailId],];
        $freightArea   = DbGoods::getSupplierFreightArea($where, 'id,city_id');
        $freightAreaId = array_column($freightArea, 'city_id'); //已提交的
        $delList       = array_diff($freightAreaId, $cityIdList); //需要删除的city_id
        $addList       = array_diff($cityIdList, $freightAreaId); //需要添加的city_id
        //        $idList        = array_column($freightArea, 'id');
        $delId = [];
        foreach ($freightArea as $val) {
            if (in_array($val['city_id'], $delList)) {
                array_push($delId, $val['id']);
            }
        }
        Db::startTrans();
        try {
            if (!empty($delList)) {
                SupplierFreightArea::destroy($delId);
            }
            if (!empty($addList)) {
                $addArr = [];
                foreach ($addList as $val) {
                    array_push($addArr, ['city_id' => $val, 'freight_detail_id' => $freightDetailId]);
                }
                DbGoods::addSupplierFreightArea($addArr);
            }
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            Db::rollback();
            return ["code" => "3003"];
        }
    }

    /**
     * 添加供应商管理后台账号(密码默认111111)
     * @param $mobile
     * @param $supName
     * @return array
     * @author zyr
     */
    public function addSupplierAdmin($mobile, $supName, $sup_id = 0)
    {
        $supAdmin = DbGoods::getSupAdmin(['sup_name' => $supName], 'id', true);
        if (!empty($supAdmin)) {
            return ['code' => '3003']; //账号名称已存在
        }
        $user = DbUser::getUserInfo(['mobile' => $mobile], 'id,pid', true);
        if (!empty($user)) {
            return ['code' => '3004',"msg" =>'该手机号已添加过'];
        }
        if ($user['pid'] != 0) {
            return ['code' => '3006',"msg" =>'子账户无法继续添加子账户'];
        }
        $data = [
            // 'uid'        => $user['id'],
            'sup_name'   => $supName,
            'sup_passwd' => getPassword('111111', $this->supCipherUserKey, Config::get('conf.cipher_algo')),
            'mobile'     => $mobile,
        ];
        if ($sup_id) {
            $data['sup_id'] = $sup_id;
        }
        Db::startTrans();
        try {
            DbGoods::addSupAdmin($data);
            Db::commit();
            return ["code" => '200'];
        } catch (\Exception $e) {
            exception($e);
            Db::rollback();
            return ["code" => "3005"];
        }
    }

    public function supplierAdminList($page, $pageNum)
    {
        $offset = $pageNum * ($page - 1);
        $total  = DbGoods::getSupAdminCount([]);
        if ($total < 1) {
            return ['code' => '3000', 'data' => '', 'total' => 0];
        }
        $supAdmin = DbGoods::getSupAdmin(['status' => 1], 'id,sup_name,mobile', false, '', $offset . ',' . $pageNum);
        return ['code' => '200', 'data' => $supAdmin, 'total' => $total];
    }

    public function supplierSonAdminList($page, $pageNum, $sup_id){
        $offset = $pageNum * ($page - 1);
        $total  = DbGoods::getSupAdminCount(['sup_id' => $sup_id]);
        if ($total < 1) {
            return ['code' => '3000', 'data' => '', 'total' => 0];
        }
        $supAdmin = DbGoods::getSupAdmin(['sup_id' => $sup_id], 'id,sup_name,mobile,status', false, '', $offset . ',' . $pageNum);
        return ['code' => '200', 'data' => $supAdmin, 'total' => $total];
    }
}
