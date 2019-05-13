<?php

namespace app\admin\controller;

use app\admin\AdminController;
use think\Controller;

class ModelMessage extends AdminController {
    protected $beforeActionList = [
        'isLogin', //所有方法的前置操作
        //        'isLogin' => ['except' => 'login'],//除去login其他方法都进行isLogin前置操作
        //        'three'   => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 添加触发器
     * @apiDescription   addTrigger
     * @apiGroup         admin_ModelMessage
     * @apiName          addTrigger
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} title 标题
     * @apiParam (入参) {Number} [start_time] 开始时间 不传默认为当前时间
     * @apiParam (入参) {Number} stop_time 结束时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户列表空 / 3001:title空 / 3002:时间格式错误 / 3003:结束时间不能小于开始时间
     * @apiSampleRequest /admin/ModelMessage/addTrigger
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function addTrigger() {
        $title      = trim($this->request->post('title'));
        $start_time = trim($this->request->post('start_time'));
        $stop_time  = trim($this->request->post('stop_time'));
        if (empty($title)) {
            return ['code' => '3001'];
        }
        $preg = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)?$/';
        if (empty($start_time)) {
            $start_time = time();
        } else {
            if (preg_match($preg, $start_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $start_time = strtotime($start_time);
        }
        if (empty($stop_time)) {
            return ['code' => '3002'];
        } else {
            if (preg_match($preg, $stop_time, $parts2)) {
                if (checkdate($parts2[2], $parts2[3], $parts2[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $stop_time = strtotime($stop_time);
        }
        if ($stop_time < $start_time) {
            return ['code' => '3003'];
        }
        $result = $this->app->modelmessage->saveTrigger($title, $start_time, $stop_time);
        return $result;
    }

    /**
     * @api              {post} / 获取触发器
     * @apiDescription   getTrigger
     * @apiGroup         admin_ModelMessage
     * @apiName          getTrigger
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [page] 页码 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiParam (入参) {Number} [id] 查看详情传入详情ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:数据为空空 / 3001:page或者pageNum格式错误 / 3002:id格式错误
     * @apiSuccess (返回) {String} Trigger 数组
     * @apiSuccess (Trigger) {String} id
     * @apiSuccess (Trigger) {String} title 标题
     * @apiSuccess (Trigger) {String} status 状态  1:待审核 2:启用 3:停用
     * @apiSuccess (Trigger) {String} start_time  开始时间
     * @apiSuccess (Trigger) {String} stop_time  停止时间
     * @apiSuccess (Trigger) {String} create_time  创建时间
     * @apiSampleRequest /admin/ModelMessage/getTrigger
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     *  "total": 1,
     * "Trigger": [
     * {
     *      "id": 1,
     *      "title": "测试",
     *      "status": 1,
     *      "start_time": 2019-05-08 12:10:35,
     *      "stop_time": 2019-05-08 00:00:00,
     *      "create_time": "2019-05-08 12:10:35",
     *      "update_time": "2019-05-08 12:10:35",
     *      "delete_time": null
    }
    ]
     * ]
     * @author rzc
     */
    public function getTrigger() {
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        $id      = trim($this->request->post('id'));
        $page    = empty($page) ? 1 : $page;
        $pageNum = empty($pageNum) ? 10 : $pageNum;
        if (!is_numeric($page) || strpos($page, ".") !== false || !is_numeric($pageNum) || strpos($pageNum, ".") !== false) {
            return ['code' => '3001'];
        }
        if ($page < 1 || $pageNum < 1) {
            return ['code' => '3001'];
        }
        if (!empty($id)) {
            if (!is_numeric($id) || strpos($id, ".") !== false) {
                return ['code' => '3002'];
            }
        }
        $result = $this->app->modelmessage->getTrigger($page, $pageNum, $id);
        return $result;
    }

    /**
     * @api              {post} / 审核触发器
     * @apiDescription   auditTrigger
     * @apiGroup         admin_ModelMessage
     * @apiName          auditTrigger
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 触发器id
     * @apiParam (入参) {Number} status 审核状态 2:启用 3:停用
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未查询到该信息 / 3001:状态码为空 / 3002:id格式错误 / 3003:该信息已进行过审核
     * @apiSampleRequest /admin/ModelMessage/auditTrigger
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function auditTrigger() {
        $status = trim($this->request->post('status'));
        $id     = trim($this->request->post('id'));
        if (!in_array($status, [2, 3])) {
            return ['code' => '3001'];
        }
        if (!is_numeric($id) || strpos($id, ".") !== false) {
            return ['code' => '3002'];
        }
        $result = $this->app->modelmessage->auditTrigger($id, $status);
        return $result;
    }

    /**
     * @api              {post} / 修改触发器
     * @apiDescription   editTrigger
     * @apiGroup         admin_ModelMessage
     * @apiName          editTrigger
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id 触发器id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {Number} [start_time] 开始时间 不传默认为当前时间
     * @apiParam (入参) {Number} stop_time 结束时间
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未查询到该信息 / 3001:状态码为空 / 3002:id格式错误 / 3003:该信息已进行过审核,无法修改 / 3004:结束时间不能小于开始时间
     * @apiSampleRequest /admin/ModelMessage/editTrigger
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function editTrigger() {
        $title      = trim($this->request->post('title'));
        $start_time = trim($this->request->post('start_time'));
        $stop_time  = trim($this->request->post('stop_time'));
        $id         = trim($this->request->post('id'));
        if (!is_numeric($id) || strpos($id, ".") !== false) {
            return ['code' => '3002'];
        }
        if (empty($title)) {
            return ['code' => '3001'];
        }
        $preg = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)?$/';
        if (empty($start_time)) {
            $start_time = time();
        } else {
            if (preg_match($preg, $start_time, $parts1)) {
                if (checkdate($parts1[2], $parts1[3], $parts1[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $start_time = strtotime($start_time);
        }
        if (empty($stop_time)) {
            return ['code' => '3002'];
        } else {
            if (preg_match($preg, $stop_time, $parts2)) {
                if (checkdate($parts2[2], $parts2[3], $parts2[1]) == false) {
                    return ['code' => '3002'];
                }
            } else {
                return ['code' => '3002'];
            }
            $stop_time = strtotime($stop_time);
        }
        if ($stop_time < $start_time) {
            return ['code' => '3004'];
        }
        $result = $this->app->modelmessage->editTrigger($title, $start_time, $stop_time, $id);
        return $result;
    }

    /**
     * @api              {post} / 添加消息模板
     * @apiDescription   saveMessageTemplate
     * @apiGroup         admin_ModelMessage
     * @apiName          saveMessageTemplate
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {Number} type 类型  1:短短信 2:长短信 3:彩信
     * @apiParam (入参) {String} template 发送内容模板
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未查询到该信息 / 3001:title为空 / 3002:type参数错误 / 3003:template为空 / 3004:结束时间不能小于开始时间
     * @apiSampleRequest /admin/ModelMessage/saveMessageTemplate
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function saveMessageTemplate() {
        $title    = trim($this->request->post('title'));
        $type     = trim($this->request->post('type'));
        $template = trim($this->request->post('template'));
        $type     = empty($type) ? 1 : $type;
        if (empty($title)) {
            return ['code' => '3001'];
        }
        if (!in_array($type, [1, 2, 3])) {
            return ['code' => '3002'];
        }
        if (empty($template)) {
            return ['code' => '3003'];
        }
        $result = $this->app->modelmessage->saveMessageTemplate($title, $type, $template);
        return $result;
    }

    /**
     * @api              {post} / 审核消息模板
     * @apiDescription   auditMessageTemplate
     * @apiGroup         admin_ModelMessage
     * @apiName          auditMessageTemplate
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} id
     * @apiParam (入参) {Number} status 审核状态  2:启用 3:停用
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未查询到该信息 / 3001:status参数错误 / 3002:id参数错误 / 3003:template为空 /
     * @apiSampleRequest /admin/ModelMessage/auditMessageTemplate
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function auditMessageTemplate() {
        $id     = trim($this->request->post('id'));
        $status = trim($this->request->post('status'));
        if (!is_numeric($id) || strpos($id, ".") !== false) {
            return ['code' => '3002'];
        }
        if (!is_numeric($status) || strpos($status, ".") !== false) {
            return ['code' => '3002'];
        }
        if (!in_array($status, [2, 3])) {
            return ['code' => '3001'];
        }
        $result = $this->app->modelmessage->auditMessageTemplate($id, $status);
        return $result;
    }

    /**
     * @api              {post} / 获取消息模板
     * @apiDescription   getMessageTemplate
     * @apiGroup         admin_ModelMessage
     * @apiName          getMessageTemplate
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [page] 页码 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiParam (入参) {Number} [id] 查看详情传入详情ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未查询到该信息 / 3001:pageNum或者page参数错误 / 3002:id参数错误 / 3003:template为空 /
     * @apiSampleRequest /admin/ModelMessage/getMessageTemplate
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function getMessageTemplate() {
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        $id      = trim($this->request->post('id'));
        $page    = empty($page) ? 1 : $page;
        $pageNum = empty($pageNum) ? 10 : $pageNum;
        if (!is_numeric($page) || strpos($page, ".") !== false || !is_numeric($pageNum) || strpos($pageNum, ".") !== false) {
            return ['code' => '3001'];
        }
        if ($page < 1 || $pageNum < 1) {
            return ['code' => '3001'];
        }
        if (!empty($id)) {
            if (!is_numeric($id) || strpos($id, ".") !== false) {
                return ['code' => '3002'];
            }
        }
        $result = $this->app->modelmessage->getMessageTemplate($page, $pageNum, $id);
        return $result;
    }
    /**
     * @api              {post} / 修改消息模板信息
     * @apiDescription   editMessageTemplate
     * @apiGroup         admin_ModelMessage
     * @apiName          editMessageTemplate
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {Number} type 类型  1:短短信 2:长短信 3:彩信
     * @apiParam (入参) {String} template 发送内容模板
     * @apiParam (入参) {Number} id 需修改模板的ID
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未查询到该信息 / 3001:title为空 / 3002:type参数错误 / 3003:template为空 / 3004:结束时间不能小于开始时间
     * @apiSampleRequest /admin/ModelMessage/editMessageTemplate
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function editMessageTemplate() {
        $title    = trim($this->request->post('title'));
        $type     = trim($this->request->post('type'));
        $template = trim($this->request->post('template'));
        $id       = trim($this->request->post('id'));
        $type     = empty($type) ? 1 : $type;
        if (empty($title)) {
            return ['code' => '3001'];
        }
        if (!is_numeric($id) || strpos($id, ".") !== false) {
            return ['code' => '3002'];
        }

        if (!in_array($type, [1, 2, 3])) {
            return ['code' => '3002'];
        }
        if (empty($template)) {
            return ['code' => '3003'];
        }
        $result = $this->app->modelmessage->editMessageTemplate($title, $type, $template, $id);
        return $result;
    }

    /**
     * @api              {post} / 添加消息任务
     * @apiDescription   saveMessageTask
     * @apiGroup         admin_ModelMessage
     * @apiName          saveMessageTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {Number} type 发送人群  1:所有会员 2:普通会员 3:钻石会员 4:创业店主 5:合伙人
     * @apiParam (入参) {String} wtype 任务类型 1.订单发货 2.订单退款 3.未付款订单提醒 4.营销类活动 5.定时任务 6.生日祝福 7.提现到账
     * @apiParam (入参) {Number} mt_id 短信模板id
     * @apiParam (入参) {Number} trigger_id 触发器id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未查询到该信息 / 3001:title为空 / 3002:mt_id 或者trigger_id 参数错误 / 3003:wtype错误 / 3004:该短信模板未启用或者不存在 / 3005:该触发器未启用或者不存在
     * @apiSampleRequest /admin/ModelMessage/saveMessageTask
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function saveMessageTask() {
        $title      = trim($this->request->post('title'));
        $type       = trim($this->request->post('type'));
        $wtype      = trim($this->request->post('wtype'));
        $mt_id      = trim($this->request->post('mt_id'));
        $trigger_id = trim($this->request->post('trigger_id'));
        $type       = empty($type) ? 1 : $type;
        if (empty($title)) {
            return ['code' => '3001'];
        }
        if (!is_numeric($mt_id) || strpos($mt_id, ".") !== false || !is_numeric($trigger_id) || strpos($trigger_id, ".") !== false) {
            return ['code' => '3002'];
        }
        if (!in_array($type, [1, 2, 3, 4, 5])) {
            return ['code' => '3002'];
        }
        if (!in_array($wtype, [1, 2, 3, 4, 5, 6, 7])) {
            return ['code' => '3003'];
        }
        $result = $this->app->modelmessage->saveMessageTask($title, $type, $wtype, $mt_id, $trigger_id);
        return $result;
    }

    /**
     * @api              {post} / 修改消息任务
     * @apiDescription   editMessageTask
     * @apiGroup         admin_ModelMessage
     * @apiName          editMessageTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {String} title 标题
     * @apiParam (入参) {Number} type 发送人群  1:所有会员 2:普通会员 3:钻石会员 4:创业店主 5:合伙人
     * @apiParam (入参) {String} wtype 任务类型 1.订单发货 2.订单退款 3.未付款订单提醒 4.营销类活动 5.定时任务 6.生日祝福 7.提现到账
     * @apiParam (入参) {Number} mt_id 短信模板id
     * @apiParam (入参) {Number} trigger_id 触发器id
     * @apiParam (入参) {Number} MessageTask_id 消息任务id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未查询到该信息 / 3001:title为空 / 3002:mt_id 或者trigger_id 参数错误 / 3003:wtype错误 / 3004:该短信模板未启用或者不存在 / 3005:该触发器未启用或者不存在 / 3006:MessageTask_id参数错误 / 3007:已启用的消息任务无法修改
     * @apiSampleRequest /admin/ModelMessage/editMessageTask
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function editMessageTask() {
        $title          = trim($this->request->post('title'));
        $type           = trim($this->request->post('type'));
        $wtype          = trim($this->request->post('wtype'));
        $mt_id          = trim($this->request->post('mt_id'));
        $trigger_id     = trim($this->request->post('trigger_id'));
        $MessageTask_id = trim($this->request->post('MessageTask_id'));
        $type           = empty($type) ? 1 : $type;
        if (empty($title)) {
            return ['code' => '3001'];
        }
        if (!is_numeric($MessageTask_id) || strpos($MessageTask_id, ".") !== false) {
            return ['code' => ''];
        }
        if (!is_numeric($mt_id) || strpos($mt_id, ".") !== false || !is_numeric($trigger_id) || strpos($trigger_id, ".") !== false) {
            return ['code' => '3002'];
        }
        if (!in_array($type, [1, 2, 3, 4, 5])) {
            return ['code' => '3002'];
        }
        if (!in_array($wtype, [1, 2, 3, 4, 5, 6, 7])) {
            return ['code' => '3003'];
        }
        $result = $this->app->modelmessage->editMessageTask($title, intval($type), intval($wtype), intval($mt_id), intval($trigger_id), intval($MessageTask_id));
        return $result;
    }

    /**
     * @api              {post} / 停/启用消息任务
     * @apiDescription   auditMessageTask
     * @apiGroup         admin_ModelMessage
     * @apiName          auditMessageTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} status 2:启用 3:停用
     * @apiParam (入参) {Number} id 查看详情传入详情ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:pageNum或者page参数错误 / 3002:id参数错误 /
     * @apiSampleRequest /admin/ModelMessage/auditMessageTask
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function auditMessageTask() {
        $id     = trim($this->request->post('id'));
        $status = trim($this->request->post('status'));
        if (!is_numeric($id) || strpos($id, ".") !== false) {
            return ['code' => '3002'];
        }
        if (!is_numeric($status) || strpos($status, ".") !== false) {
            return ['code' => '3002'];
        }
        if (!in_array($status, [2, 3])) {
            return ['code' => '3001'];
        }
        $result = $this->app->modelmessage->auditMessageTask(intval($id), intval($status));
        return $result;
    }

    /**
     * @api              {post} / 获取消息任务
     * @apiDescription   getMessageTask
     * @apiGroup         admin_ModelMessage
     * @apiName          getMessageTask
     * @apiParam (入参) {String} cms_con_id
     * @apiParam (入参) {Number} [page] 页码 默认1
     * @apiParam (入参) {Number} [page_num] 每页数量 默认10
     * @apiParam (入参) {Number} [id] 查看详情传入详情ID
     * @apiSuccess (返回) {String} code 200:成功 / 3001:pageNum或者page参数错误 / 3002:id参数错误 /
     * @apiSampleRequest /admin/ModelMessage/getMessageTask
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function getMessageTask() {
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        $id      = trim($this->request->post('id'));
        $page    = empty($page) ? 1 : $page;
        $pageNum = empty($pageNum) ? 10 : $pageNum;
        if (!is_numeric($page) || strpos($page, ".") !== false || !is_numeric($pageNum) || strpos($pageNum, ".") !== false) {
            return ['code' => '3001'];
        }
        if ($page < 1 || $pageNum < 1) {
            return ['code' => '3001'];
        }
        if (!empty($id)) {
            if (!is_numeric($id) || strpos($id, ".") !== false) {
                return ['code' => '3002'];
            }
            if ($id < 1) {
                return ['code' => '3002'];
            }
        }
        $result = $this->app->modelmessage->getMessageTask(intval($page), intval($pageNum), intval($id));
        return $result;
    }

    /**
     * @api              {post} / 消息模板对应文本接口
     * @apiDescription   getMessageTemplateText
     * @apiGroup         admin_ModelMessage
     * @apiName          getMessageTemplateText
     * @apiParam (入参) {String} cms_con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3001:pageNum或者page参数错误 / 3002:id参数错误 /
     * @apiSampleRequest /admin/ModelMessage/getMessageTemplateText
     * @apiParamExample (data) {Array} 返回用户列表
     * [
     * "code":"200",返回code码
     * ]
     * @author rzc
     */
    public function getMessageTemplateText() {
        $result = $this->app->modelmessage->getMessageTemplateText();
        return $result;
    }
}
