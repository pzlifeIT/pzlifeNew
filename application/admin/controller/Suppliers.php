<?php

namespace app\admin\controller;

use app\admin\AdminController;
use Env;
use \upload\Imageupload;
use think\Db;

class Suppliers extends AdminController {
    /**
     * @api              {post} / 获取供应商列表
     * @apiDescription   getSuppliers
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplier
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:供应商列表空 / 3002:页码和查询条数只能是数字
     * @apiSuccess (返回) {String} totle 总结果条数
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} id 供应商ID
     * @apiSuccess (data) {String} tel 联系方式
     * @apiSuccess (data) {String} name 名称
     * @apiSuccess (data) {String} image 图片
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} desc 详情
     * @apiSuccess (data) {String} create_time 创建时间
     * @apiSampleRequest /admin/suppliers/getsuppliers
     * @apiParamExample (data) {Array} 返回供应商列表
     * [
     * "code":"200",返回code码
     * "totle":"82",总记录条数
     *  {"id":9,"tel":15502123212,
     *   "name":"喜蓝葡萄酒",
     *   "status":"1",
     *   "image":"","title":"",
     *   "desc":"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费"
     *  },
     * ]
     * @author rzc
     */
    public function getSuppliers() {
        $page    = trim($this->request->post('page'));
        $pagenum = trim($this->request->post('pagenum'));
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }

        $result = $this->app->suppliers->getSuppliers($page, $pagenum);
        return $result;
    }

    /**
     * @api              {post} / 获取供应商详情
     * @apiDescription   getSupplierData
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplierData
     * @apiParam (入参) {Number} supplierId 供应商ID
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3002:供应商ID只能是数字
     * @apiSuccess (data) {Array} data 结果
     * @apiSuccess (data) {String} id 供应商ID
     * @apiSuccess (data) {String} tel 联系方式
     * @apiSuccess (data) {String} name 名称
     * @apiSuccess (data) {String} image 图片
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} desc 详情
     * @apiSuccess (data) {String} create_time 创建时间
     * @apiSampleRequest /admin/suppliers/getsupplierdata
     * @author rzc
     */
    public function getSupplierData() {
        $supplierId = trim($this->request->post('supplierId'));
        if (!is_numeric($supplierId)) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->getSupplierData($supplierId);
        return $result;
    }

    /**
     * @api              {post} / 新建供应商
     * @apiDescription   addSupplier
     * @apiGroup         admin_Suppliers
     * @apiName          addSupplier
     * @apiParam (入参) {String} tel 联系方式
     * @apiParam (入参) {String} name 名称
     * @apiParam (入参) {file} image 图片
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} desc 详情
     * @apiSuccess (返回) {String} code 200:成功  / 3001:手机号码格式错误 / 3002:提交数据不完整 / 3003:未选择图片 / 3004:添加失败 / 3005:图片没有上传过 / 3006:供应商名字不能重复
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /admin/suppliers/addsupplier
     * @author rzc
     */
    public function addSupplier() {
        /* 获取提交参数 */
        $tel   = trim($this->request->post('tel'));
        $name  = trim($this->request->post('name'));
        $title = trim($this->request->post('title'));
        $desc  = trim($this->request->post('desc'));
        $image = trim($this->request->post('image'));

        /* 参数判断 */
        if (!$this->checkMobile($tel)) {
            return ['code' => '3001'];
        }
        if (!$name || !$title || !$desc) {
            return ['code' => '3002'];
        }
        if (!$image) {
            return ['code' => '3003'];
        }
        $result = $this->app->suppliers->addSupplier($tel, $name, $title, $desc, $image);
        return $result;
    }

    /**
     * @api              {post} / 修改供应商
     * @apiDescription   updateSupplier
     * @apiGroup         admin_Suppliers
     * @apiName          updateSupplier
     * @apiParam (入参) {Number} id 供应商ID
     * @apiParam (入参) {String} tel 联系方式
     * @apiParam (入参) {String} name 名称
     * @apiParam (入参) {file} image 图片
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} desc 详情
     * @apiSuccess (返回) {String} code 200:成功  / 3001:手机号码格式错误 / 3002:提交数据不完整 / 3003:未选择图片 / 3004:新建失败 / 3005:图片上传失败 / 3006:供应商ID必须是数字 / 3007:名字不能重复
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /admin/suppliers/updateSupplier
     * @author rzc
     */
    public function updateSupplier() {
        $id    = trim($this->request->post('id'));
        $tel   = trim($this->request->post('tel'));
        $name  = trim($this->request->post('name'));
        $title = trim($this->request->post('title'));
        $desc  = trim($this->request->post('desc'));
        $image = $this->request->file('image');
        /* 参数判断 */
        if (!is_numeric($id)) {
            return ['code' => '3006'];
        }
        if (!$this->checkMobile($tel)) {
            return ['code' => '3001'];
        }
        if (!$name || !$title || !$desc) {
            return ['code' => '3002'];
        }

        /* 判断是否存在 */
        if ($this->app->suppliers->getSupplierWhereFileByID('name', $name, $id)) {
            return ['code' => '3007'];
        }
        /* 有图片执行图片上传 */
        if ($image) {
            /* 图片上传 */
            $fileInfo = $image->getInfo();

            $upload = new Imageupload();
            /* 文件名重命名 */
            $filename = $upload->getNewName($fileInfo['name']);

            $uploadimage = $upload->uploadFile($fileInfo['tmp_name'], 'supplier/' . $filename);
            if ($uploadimage) {
                /* 初始化数组 */
                $new_supplier          = [];
                $new_supplier['tel']   = $tel;
                $new_supplier['name']  = $name;
                $new_supplier['title'] = $title;
                $new_supplier['desc']  = $desc;
                $new_supplier['image'] = $filename;
                $result                = $this->app->suppliers->updateSupplier($new_supplier, $id);
                return $result;
            } else {
                return ['code' => '3005'];
            }
        } /* 没有图片，修改其他 */
        else {
            $new_supplier          = [];
            $new_supplier['tel']   = $tel;
            $new_supplier['name']  = $name;
            $new_supplier['title'] = $title;
            $new_supplier['desc']  = $desc;
            $result                = $this->app->suppliers->updateSupplier($new_supplier, $id);
            return $result;
        }
    }

    /**
     * @api              {post} / 停用或启用供应商
     * @apiDescription   issetSupplier
     * @apiGroup         admin_Suppliers
     * @apiName          issetSupplier
     * @apiParam (入参) {Number} status 状态 1：启用 / 2：弃用
     * @apiParam (入参) {Number} id 供应商ID
     * @apiSuccess (返回) {String} code 200:成功  / 3001:状态参数和ID必须是数字 / 3002:已启用供应商无法再次启用 / 3003:已停用供应商无法再次停用 / 3004:操作失败
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /admin/suppliers/issetSupplier
     * @author rzc
     */
    public function issetSupplier() {
        /* 参数处理及判断 */
        $status = trim($this->request->post('status'));
        $id     = trim($this->request->post('id'));
        if (!is_numeric($status) || !is_numeric($id)) {
            return ['code' => '3001'];
        }
        $supplier = $this->app->suppliers->getSupplierWhereFile('id', $id);
        /* 已启用供应商 */
        if ($status == 1 && $status == $supplier['status']) {
            return ['code' => '3002'];
        } /* 已停用供应商 */
        elseif ($status == 2 && $status == $supplier['status']) {
            return ['code' => '3003'];
        }
        /* 启动事务 */
        Db::startTrans();
        try {
            $this->app->suppliers->updateSupplier(['status' => $status], $id);
            $this->app->suppliers->updateSupplierFreights($status, $id);
            /* 提交事务 */
            Db::commit();
            return ['code' => '200', 'msg' => '操作成功'];
        } catch (\Exception $e) {
            /* 回滚事务 */
            Db::rollback();
            return ['code' => '3004', 'msg' => '操作失败'];
        }

    }

    /**
     * @api              {post} / 获取供应商快递模板
     * @apiDescription   getSupplierFreights
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplierFreights
     * @apiParam (入参) {Number} supplierId 供应商ID
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3002:供应商ID只能是数字
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} supid 供货商id
     * @apiSuccess (data) {String} pz_supplier_freight 计价方式1.件数 2.重量 3.体积
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} desc 详情
     * @apiSampleRequest /admin/suppliers/getsupplierfreights
     * @author rzc
     */
    public function getSupplierFreights() {
        /* 获取提交参数 */
        $supid = trim($this->request->post('supplierId'));
        /* 判断值 */
        if (!is_numeric($supid)) {
            return ['code' => '3002'];
        }
        /* 获取返回结果 */
        $result = $this->app->suppliers->getSupplierFreights($supid);
        return $result;
    }

    /**
     * @api              {post} / 获取供应商快递模板详情
     * @apiDescription   getSupplierFreightdetail
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplierFreightdetail
     * @apiParam (入参) {Number} supplierFreightId 供应商快递模板ID
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3002:供应商快递模板ID只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSampleRequest /admin/suppliers/getsupplierfreightdetail
     * @author rzc
     */
    public function getSupplierFreightdetail() {
        $supplierFreightId = trim($this->request->post('supplierFreightId'));
        if (!is_numeric($supplierFreightId)) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->getSupplierFreightdetail($supplierFreightId);
        return $result;
    }

    /**
     * @api              {post} / 新建供应商快递模板
     * @apiDescription   addSupplierFreight
     * @apiGroup         admin_Suppliers
     * @apiName          addSupplierFreight
     * @apiParam (入参) {Number} supplierId 供应商快递模板ID
     * @apiParam (入参) {Number} stype 计价方式1.件数 2.重量 3.体积
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} desc 详情
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3002:供应商ID只能是数字
     * @apiSuccess (返回) {String} data 结果
     * @apiSampleRequest /admin/suppliers/addsupplierfreight
     * @author rzc
     */

    public function addSupplierFreight() {
        return [];
    }


}