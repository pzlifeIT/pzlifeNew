<?php

namespace app\admin\controller;

use app\admin\AdminController;
use Config;
use Env;
use \upload\Imageupload;

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
        $page = trim($this->request->post('page')) ;
        $pagenum = trim($this->request->post('pagenum'));
        $page = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 1;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['3002'];
        }

        $result = $this->app->suppliers->getSuppliers($page,$pagenum);
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
    public function getSupplierData(){
        $supplierId = trim($this->request->post('supplierId'));
        if (!is_numeric($supplierId)) {
            return ['3002'];
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
     * @apiSuccess (返回) {String} code 200:成功  / 3001:手机号码格式错误 / 3002:提交数据不完整 / 3003:未选择图片 / 3004:新建失败 / 3005:图片上传失败 / 3006:名字不能重复
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /admin/suppliers/addsupplier
     * @author rzc
     */
    public function addSupplier(){
        /* 获取提交参数 */
        $tel = trim($this->request->post('tel'));
        $name = trim($this->request->post('name'));
        $title = trim($this->request->post('title'));
        $desc = trim($this->request->post('desc'));
        $image = $this->request->file('image');

        /* 参数判断 */
        if (!$this->checkMobile($tel)) {
            return ['3001'];
        }
        if (!$name || !$title || !$desc) {
            return ['3002'];
        }
        if (!$image) {
            return ['3003'];
        }
        if ($this->app->suppliers->getSupplierWhereFile('name',$name)) {
            return ['3006'];
        }
        $fileInfo = $image->getInfo();
        
        $upload   = new Imageupload();
        /* 文件名重命名 */
        $filename = $upload->getNewName($fileInfo['name']);
        
        $uploadimage = $upload->uploadFile($fileInfo['tmp_name'], $filename);

        if ($uploadimage) {
            /* 初始化数组 */
            $new_supplier = [];
            $new_supplier['tel'] = $tel;
            $new_supplier['name'] = $name;
            $new_supplier['title'] = $title;
            $new_supplier['desc'] = $desc;
            $new_supplier['image'] = $filename;
            $result = $this->app->suppliers->addSupplier($new_supplier);
            return $result;

        } else {
            return ['3005'];
        }

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
    public function updateSupplier(){
        $id = trim($this->request->post('id'));
        $tel = trim($this->request->post('tel'));
        $name = trim($this->request->post('name'));
        $title = trim($this->request->post('title'));
        $desc = trim($this->request->post('desc'));
        $image = $this->request->file('image');
        /* 参数判断 */
        if (!is_numeric($id)) {
            return ['3006'];
        }
        if (!$this->checkMobile($tel)) {
            return ['3001'];
        }
        if (!$name || !$title || !$desc) {
            return ['3002'];
        }
        if (!$image){
            return ['3003'];
        }
        if ($this->app->suppliers->getSupplierWhereFile('name',$name)) {
            return ['3007'];
        }
        /* 图片上传 */
        $fileInfo = $image->getInfo();
        
        $upload   = new Imageupload();
        /* 文件名重命名 */
        $filename = $upload->getNewName($fileInfo['name']);
        
        $uploadimage = $upload->uploadFile($fileInfo['tmp_name'], $filename);
        /* 上传成功 */
        if ($uploadimage) {
            /* 初始化数组 */
            $new_supplier = [];
            $new_supplier['tel'] = $tel;
            $new_supplier['name'] = $name;
            $new_supplier['title'] = $title;
            $new_supplier['desc'] = $desc;
            $new_supplier['image'] = $filename;
            $result = $this->app->suppliers->updateSupplier($new_supplier,$id);
            return $result;

        } else {
            return ['3005'];
        }
    }

    /**
     * @api              {post} / 弃用或启用供应商
     * @apiDescription   addSupplier
     * @apiGroup         admin_Suppliers
     * @apiName          addSupplier
     * @apiParam (入参) {String} tel 联系方式
     * @apiParam (入参) {String} name 名称
     * @apiParam (入参) {file} image 图片
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} desc 详情
     * @apiSuccess (返回) {String} code 200:成功  / 3001:手机号码格式错误 / 3002:提交数据不完整 / 3003:未选择图片 / 3004:新建失败 / 3005:图片上传失败 / 3006:名字不能重复
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /admin/suppliers/addsupplier
     * @author rzc
     */

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
    public function getSupplierFreights(){
        /* 获取提交参数 */
        $supid = trim($this->request->post('supplierId'));
        /* 判断值 */
        if (!is_numeric($supid)) {
            return ['3002'];
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
    public function getSupplierFreightdetail(){
        $supplierFreightId = trim($this->request->post('supplierFreightId'));
        if (!is_numeric($supplierFreightId)) {
            return ['3002'];
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

     public function addSupplierFreight(){
        return [];
     }


}