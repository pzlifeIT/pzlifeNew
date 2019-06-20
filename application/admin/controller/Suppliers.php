<?php

namespace app\admin\controller;

use app\admin\AdminController;
use Env;
use think\Db;

class Suppliers extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
//        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
//        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 获取供应商列表
     * @apiDescription   getSuppliers
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplier
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 查询条数
     * @apiParam (入参) {String} supplierName 供应商名称模糊查询
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
        $apiName      = classBasename($this) . '/' . __function__;
        $cmsConId     = trim($this->request->post('cms_con_id'));
        $supplierName = trim($this->request->post('supplierName'));
        $page         = trim($this->request->post('page'));
        $pagenum      = trim($this->request->post('pagenum'));
        $page         = $page ? $page : 1;
        $pagenum      = $pagenum ? $pagenum : 10;
        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->getSuppliers($page, $pagenum, $supplierName);
        $this->apiLog($apiName, [$cmsConId, $supplierName, $page, $pagenum], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取所有供应商
     * @apiDescription   getSuppliersAll
     * @apiGroup         admin_Suppliers
     * @apiName          getSuppliersAll
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:供应商列表空
     * @apiSuccess (返回) {object_array} data 结果
     * @apiSuccess (data) {String} id 供应商ID
     * @apiSuccess (data) {String} name 名称
     * @apiSampleRequest /admin/suppliers/getsuppliersall
     * @author zyr
     */
    public function getSuppliersAll() {
        $apiName      = classBasename($this) . '/' . __function__;
        $cmsConId     = trim($this->request->post('cms_con_id'));
        $result = $this->app->suppliers->getSuppliersAll();
        $this->apiLog($apiName, [$cmsConId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取供应商详情
     * @apiDescription   getSupplierData
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplierData
     * @apiParam (入参) {String} cms_con_id
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
        $apiName    = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id'));
        $supplierId = trim($this->request->post('supplierId'));
        if (!is_numeric($supplierId)) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->getSupplierData($supplierId);
        $this->apiLog($apiName, [$cmsConId, $supplierId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 新建供应商
     * @apiDescription   addSupplier
     * @apiGroup         admin_Suppliers
     * @apiName          addSupplier
     * @apiParam (入参) {String} cms_con_id
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
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        /* 获取提交参数 */
        $tel   = trim($this->request->post('tel'));
        $name  = trim($this->request->post('name'));
        $title = trim($this->request->post('title'));
        $desc  = trim($this->request->post('desc'));
        $image = trim($this->request->post('image'));

        /* 参数判断 */
        if (!checkMobile($tel)) {
            return ['code' => '3001'];
        }
        if (!$name || !$title || !$desc) {
            return ['code' => '3002'];
        }
        if (!$image) {
            return ['code' => '3003'];
        }
        $result = $this->app->suppliers->addSupplier($tel, $name, $title, $desc, $image);
        $this->apiLog($apiName, [$cmsConId, $tel, $name, $title, $desc, $image], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改供应商
     * @apiDescription   updateSupplier
     * @apiGroup         admin_Suppliers
     * @apiName          updateSupplier
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 供应商ID
     * @apiParam (入参) {String} tel 联系方式
     * @apiParam (入参) {String} name 名称
     * @apiParam (入参) {String} image 图片
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} desc 详情
     * @apiSuccess (返回) {String} code 200:成功  / 3001:手机号码格式错误 / 3002:提交数据不完整 / 3003:供应商ID必须是数字 / 3004:更新失败 / 3005:图片没有上传过 / 3006:供应商id不存在 / 3007:供应商名称不能重复
     * @apiSuccess (data) {Array} data 结果
     * @apiSampleRequest /admin/suppliers/updatesupplier
     * @author rzc
     */
    public function updateSupplier() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $id    = trim($this->request->post('id'));
        $tel   = trim($this->request->post('tel'));
        $name  = trim($this->request->post('name'));
        $title = trim($this->request->post('title'));
        $desc  = trim($this->request->post('desc'));
        $image = trim($this->request->post('image'));
        /* 参数判断 */
        if (!is_numeric($id)) {
            return ['code' => '3003'];
        }
        if (!checkMobile($tel)) {
            return ['code' => '3001'];
        }
        if (!$name || !$title || !$desc) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->editSupplier(intval($id), $tel, $name, $title, $desc, $image);
        $this->apiLog($apiName, [$cmsConId, $id, $tel, $name, $title, $desc, $image], $result['code'], $cmsConId);
        return $result;
    }

    /*
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
//    public function issetSupplier() {
//        /* 参数处理及判断 */
//        $status = trim($this->request->post('status'));
//        $id     = trim($this->request->post('id'));
//        if (!is_numeric($status) || !is_numeric($id)) {
//            return ['code' => '3001'];
//        }
//        $supplier = $this->app->suppliers->getSupplierWhereFile('id', $id);
//        /* 已启用供应商 */
//        if ($status == 1 && $status == $supplier['status']) {
//            return ['code' => '3002'];
//        } /* 已停用供应商 */
//        elseif ($status == 2 && $status == $supplier['status']) {
//            return ['code' => '3003'];
//        }
//        /* 启动事务 */
//        Db::startTrans();
//        try {
//            $this->app->suppliers->updateSupplier(['status' => $status], $id);
//            $this->app->suppliers->updateSupplierFreights($status, $id);
//            /* 提交事务 */
//            Db::commit();
//            return ['code' => '200', 'msg' => '操作成功'];
//        } catch (\Exception $e) {
//            /* 回滚事务 */
//            Db::rollback();
//            return ['code' => '3004', 'msg' => '操作失败'];
//        }
//
//    }

    /**
     * @api              {post} / 获取供应商快递模板列表
     * @apiDescription   getSupplierFreights
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplierFreights
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} supplierId 供应商ID
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3002:供应商ID只能是数字
     * @apiSuccess (data) {object_array} data 结果
     * @apiSuccess (data) {String} supid 供货商id
     * @apiSuccess (data) {String} stype 计价方式1.件数 2.重量 3.体积
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} desc 详情
     * @apiSampleRequest /admin/suppliers/getsupplierfreights
     * @author rzc
     */
    public function getSupplierFreights() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        /* 获取提交参数 */
        $supid = trim($this->request->post('supplierId'));
        /* 判断值 */
        if (!is_numeric($supid)) {
            return ['code' => '3002'];
        }
        /* 获取返回结果 */
        $result = $this->app->suppliers->getSupplierFreights($supid);
        $this->apiLog($apiName, [$cmsConId, $supid], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取供应商快递模板详情
     * @apiDescription   getSupplierFreight
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplierFreight
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} supplierFreightId 供应商快递模板ID
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3002:供应商快递模板ID只能是数字
     * @apiSuccess (data) {String} id ID
     * @apiSuccess (data) {String} supid 供应商ID
     * @apiSuccess (data) {String} stype 计价方式1.件数 2.重量 3.体积
     * @apiSuccess (data) {String} status 1.启用 2.停用
     * @apiSuccess (data) {String} title 标题
     * @apiSuccess (data) {String} desc 详情
     * @apiSampleRequest /admin/suppliers/getSupplierFreight
     * @author rzc
     */
    public function getSupplierFreight() {
        $apiName           = classBasename($this) . '/' . __function__;
        $cmsConId          = trim($this->request->post('cms_con_id')); //操作管理员
        $supplierFreightId = trim($this->request->post('supplierFreightId'));
        if (!is_numeric($supplierFreightId)) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->getSupplierFreight($supplierFreightId);
        $this->apiLog($apiName, [$cmsConId, $supplierFreightId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取供应商快递模板运费列表
     * @apiDescription   getSupplierFreightdetailList
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplierFreightdetailList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} freight_id 供应商快递模板ID
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} pagenum 每页条数
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3002:供应商快递模板ID和页码和每页条数只能是数字
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {Int} freight_id 快递模版ID
     * @apiSuccess (data) {Decimal} price 邮费单价
     * @apiSuccess (data) {Decimal} after_price 续件价格
     * @apiSuccess (data) {Decimal} total_price 包邮价格
     * @apiSuccess (data) {Decimal} unit_price 计价单位包邮价(重量按kg 体积按m³)
     * @apiSampleRequest /admin/suppliers/getSupplierFreightdetailList
     * @author rzc
     */
    public function getSupplierFreightdetailList() {
        $apiName    = classBasename($this) . '/' . __function__;
        $cmsConId   = trim($this->request->post('cms_con_id')); //操作管理员
        $page       = trim($this->request->post('page'));
        $pagenum    = trim($this->request->post('pagenum'));
        $freight_id = trim($this->request->post('freight_id'));
        $page       = $page ? $page : 1;
        $pagenum    = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum) || !is_numeric($freight_id)) {
            return ['code' => '3002'];
        }

        $result = $this->app->suppliers->getSupplierFreightdetailList($freight_id, $page, $pagenum);
        $this->apiLog($apiName, [$cmsConId, $page, $pagenum, $freight_id], $result['code'], $cmsConId);
        return $result;
    }


    /**
     * @api              {post} / 新建供应商快递模板
     * @apiDescription   addSupplierFreight
     * @apiGroup         admin_Suppliers
     * @apiName          addSupplierFreight
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} supplierId 供应商ID
     * @apiParam (入参) {Number} stype 计价方式1.件数 2.重量 3.体积
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} desc 详情
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3001:供应商id必须是数字 / 3002:供应商ID只能是数字 / 3003:标题和详情不能为空
     * @apiSuccess (返回) {String} data 结果
     * @apiSampleRequest /admin/suppliers/addsupplierfreight
     * @author rzc
     */

    public function addSupplierFreight() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $stypeArr   = [1, 2, 3,];
        $supplierId = trim($this->request->post('supplierId'));
        $stype      = trim($this->request->post('stype'));
        $title      = trim($this->request->post('title'));
        $desc       = trim($this->request->post('desc'));
        if (!is_numeric($supplierId)) {
            return ['code' => '3001']; /* 供应商id必须是数字 */
        }
        if (!in_array($stype, $stypeArr)) {
            return ['3002'];
        }
        if (!$title || !$desc) {
            return ['code' => '3003']; /* 标题和详情不能为空 */
        }
        $result = $this->app->suppliers->addSupplierFreight(intval($supplierId), intval($stype), $title, $desc);
        $this->apiLog($apiName, [$cmsConId, $supplierId, $stype, $title, $desc], $result['code'], $cmsConId);
        return $result;

    }

    /**
     * @api              {post} / 修改供应商快递模板
     * @apiDescription   updateSupplierFreight
     * @apiGroup         admin_Suppliers
     * @apiName          updateSupplierFreight
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} supplier_freight_Id 快递模版ID
     * @apiParam (入参) {Number} stype 计价方式1.件数 2.重量 3.体积
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {String} desc 详情
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3001:供应商模版id必须是数字 /3002:计价方式参数有误 / 3003:标题和详情不能为空
     * @apiSuccess (返回) {String} data 结果
     * @apiSampleRequest /admin/suppliers/updateSupplierFreight
     * @author rzc
     */
    public function updateSupplierFreight() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $stypeArr            = [1, 2, 3,];
        $supplier_freight_Id = trim($this->request->post('supplier_freight_Id'));
        $stype               = trim($this->request->post('stype'));
        $title               = trim($this->request->post('title'));
        $desc                = trim($this->request->post('desc'));
        if (!is_numeric($supplier_freight_Id)) {
            return ['code' => '3001']; /* 供应商id必须是数字 */
        }
        if (!in_array($stype, $stypeArr)) {
            return ['3002'];
        }
        if (!$title || !$desc) {
            return ['code' => '3003']; /* 标题和详情不能为空 */
        }
        $result = $this->app->suppliers->updateSupplierFreight(intval($supplier_freight_Id), intval($stype), $title, $desc);
        $this->apiLog($apiName, [$cmsConId, $supplier_freight_Id, $stype, $title, $desc], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 获取供应商快递模板运费详情
     * @apiDescription   getSupplierFreightdetail
     * @apiGroup         admin_Suppliers
     * @apiName          getSupplierFreightdetail
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} sfd_id 快递模版运费详情ID
     * @apiSuccess (返回) {String} code 200:成功  / 3000:查询结果不存在 / 3002:供应商快递模版ID只能是数字
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {Int} freight_id 快递模版ID
     * @apiSuccess (data) {Decimal} price 邮费单价
     * @apiSuccess (data) {Decimal} after_price 续件价格
     * @apiSuccess (data) {Decimal} total_price 包邮价格
     * @apiSuccess (data) {Decimal} unit_price 计价单位包邮价(重量按kg 体积按m³)
     * @apiSampleRequest /admin/suppliers/getSupplierFreightdetail
     * @author rzc
     */
    public function getSupplierFreightdetail() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        $sfd_id   = trim($this->request->post('sfd_id'));
        if (!is_numeric($sfd_id)) {
            return ['code' => '3001']; /* 供应商id和方式必须是数字 */
        }
        $result = $this->app->suppliers->getSupplierFreightdetail(intval($sfd_id));
        $this->apiLog($apiName, [$cmsConId, $sfd_id], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 新增供应商快递模板运费
     * @apiDescription   addSupplierFreightdetail
     * @apiGroup         admin_Suppliers
     * @apiName          addSupplierFreightdetail
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} freight_id 运费模版模版ID
     * @apiParam (入参) {decimal} [price] 邮费单价 默认0
     * @apiParam (入参) {decimal} [after_price] 续件价格 默认0
     * @apiParam (入参) {decimal} [total_price] 包邮价格 默认0
     * @apiParam (入参) {decimal} [unit_price] 计价单位包邮价(重量按kg 体积按m³) 默认0
     * @apiSuccess (返回) {String} code 200:成功 /3001:运费模版Id错误 / 3002:价格只能是数字 / 3003:运费模版不存在
     * @apiSuccess (返回) {String} data 结果
     * @apiSampleRequest /admin/suppliers/addSupplierFreightdetail
     * @author rzc
     */
    public function addSupplierFreightdetail() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $freight_id  = trim($this->request->post('freight_id'));
        $price       = trim($this->request->post('price'));
        $after_price = trim($this->request->post('after_price'));
        $total_price = trim($this->request->post('total_price'));
        $unit_price  = trim($this->request->post('unit_price'));
        if (!is_numeric($freight_id) || $freight_id < 0) {
            return ['code' => '3001'];
        }
        $price       = empty($price) ? 0 : $price;
        $after_price = empty($after_price) ? 0 : $after_price;
        $total_price = empty($total_price) ? 0 : $total_price;
        $unit_price  = empty($unit_price) ? 0 : $unit_price;
        if (!is_numeric($price) || !is_numeric($after_price) || !is_numeric($total_price)) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->addSupplierFreightdetail(intval($freight_id), floatval($price), floatval($after_price), floatval($total_price), floatval($unit_price));
        $this->apiLog($apiName, [$cmsConId, $freight_id, $price, $after_price, $total_price, $unit_price], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 修改供应商快递模板运费
     * @apiDescription   editSupplierFreightdetail
     * @apiGroup         admin_Suppliers
     * @apiName          editSupplierFreightdetail
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} freight_detail_id 运费价格详情ID
     * @apiParam (入参) {decimal} [price] 邮费单价 默认0
     * @apiParam (入参) {decimal} [after_price] 续件价格 默认0
     * @apiParam (入参) {decimal} [total_price] 包邮价格 默认0
     * @apiParam (入参) {decimal} [unit_price] 计价单位包邮价(重量按kg 体积按m³) 默认0
     * @apiSuccess (返回) {String} code 200:成功 /3001:运费模版Id错误 / 3002:价格只能是数字 / 3003:运费详情不存在
     * @apiSuccess (返回) {String} data 结果
     * @apiSampleRequest /admin/suppliers/editsupplierfreightdetail
     * @author rzc
     */
    public function editSupplierFreightdetail() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $freight_detail_id = trim($this->request->post('freight_detail_id'));
        $price             = trim($this->request->post('price'));
        $after_price       = trim($this->request->post('after_price'));
        $total_price       = trim($this->request->post('total_price'));
        $unit_price        = trim($this->request->post('unit_price'));
        if (!is_numeric($freight_detail_id) || $freight_detail_id < 0) {
            return ['code' => '3001'];
        }
        $price       = empty($price) ? 0 : $price;
        $after_price = empty($after_price) ? 0 : $after_price;
        $total_price = empty($total_price) ? 0 : $total_price;
        $unit_price  = empty($unit_price) ? 0 : $unit_price;
        if (!is_numeric($price) || !is_numeric($after_price) || !is_numeric($total_price)) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->editSupplierFreightdetail(intval($freight_detail_id), floatval($price), floatval($after_price), floatval($total_price), floatval($unit_price));
        $this->apiLog($apiName, [$cmsConId, $freight_detail_id, $price, $after_price, $total_price, $unit_price], $result['code'], $cmsConId);
        return $result;
    }


    /**
     * @api              {post} / 更新运费模版和市的价格关联
     * @apiDescription   updateSupplierFreightArea
     * @apiGroup         admin_Suppliers
     * @apiName          updateSupplierFreightArea
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} city_id_str 市id
     * @apiParam (入参) {String} freight_detail_id 快递模版详情id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:运费模版Id必须为数字 / 3002:快递模版详情id参数有误 / 3003:保存失败 / 3004:提交的city_id不是市级id
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSuccess (data) {Number} pid 父级id
     * @apiSampleRequest /admin/suppliers/updatesupplierfreightarea
     * @author zyr
     */
    public function updateSupplierFreightArea() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $cityIdStr       = trim($this->request->post('city_id_str'));
        $freightDetailId = trim($this->request->post('freight_detail_id'));
        if (empty($cityIdStr)) {
            return ['code' => '3001'];//市Id不能为空
        }
        if (!is_numeric($freightDetailId)) {
            return ['code' => '3002'];
        }
        $result = $this->app->suppliers->updateSupplierFreightArea($cityIdStr, intval($freightDetailId));
        $this->apiLog($apiName, [$cmsConId, $cityIdStr, $freightDetailId], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 添加供应商管理后台账号(密码默认111111)
     * @apiDescription   addSupplierAdmin
     * @apiGroup         admin_Suppliers
     * @apiName          addSupplierAdmin
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} mobile  手机号
     * @apiParam (入参) {String} sup_name 登录账号
     * @apiSuccess (返回) {String} code 200:成功 / 3001:账号不能为空 / 3002:手机号格式有误 / 3003:账号名称已存在 / 3004:
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {String} area_name 名称
     * @apiSampleRequest /admin/suppliers/addsupplieradmin
     * @author zyr
     */
    public function addSupplierAdmin() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $supName = trim($this->request->post('sup_name'));
        $mobile  = trim($this->request->post('mobile'));
        if (empty($supName)) {
            return ['code' => '3001'];//账号不能为空
        }
        if(!checkMobile($mobile)){
            return ['code' => '3002'];//手机格式有误
        }
        $result = $this->app->suppliers->addSupplierAdmin($mobile, $supName);
        $this->apiLog($apiName, [$cmsConId, $mobile, $supName], $result['code'], $cmsConId);
        return $result;
    }

    /**
     * @api              {post} / 供应商管理员列表
     * @apiDescription   supplierAdminList
     * @apiGroup         admin_Suppliers
     * @apiName          supplierAdminList
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Int} page  页码
     * @apiParam (入参) {Int} [page_num] 每页条数(默认10)
     * @apiSuccess (返回) {String} code 200:成功 / 3001:page错误
     * @apiSuccess (返回) {Array} data 结果
     * @apiSuccess (data) {Int} id 编号
     * @apiSuccess (data) {String} sup_name 名称
     * @apiSuccess (data) {String} mobile 手机
     * @apiSampleRequest /admin/suppliers/supplieradminlist
     * @author zyr
     */
    public function supplierAdminList() {
        $apiName  = classBasename($this) . '/' . __function__;
        $cmsConId = trim($this->request->post('cms_con_id')); //操作管理员
        if ($this->checkPermissions($cmsConId, $apiName) === false) {
            return ['code' => '3100'];
        }
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        if (!is_numeric($page) || $page < 1) {
            return ['code' => '3001'];//page错误
        }
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 10;
        }
        $page    = intval($page);
        $pageNum = intval($pageNum);
        $result = $this->app->suppliers->supplierAdminList($page, $pageNum);
        $this->apiLog($apiName, [$cmsConId, $page, $pageNum], $result['code'], $cmsConId);
        return $result;
    }
}