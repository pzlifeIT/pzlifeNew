<?php

namespace app\common\action\admin;

use app\facade\DbModelMessage;
use think\Db;

class ModelMessage extends CommonIndex {

    /**
     * 添加触发器
     * @param string $title
     * @param number $start_time
     * @param number $stop_time
     * @return array
     * @author rzc
     */
    public function saveTrigger($title, $start_time, $stop_time) {
        $data               = [];
        $data['title']      = $title;
        $data['start_time'] = $start_time;
        $data['stop_time']  = $stop_time;
        $data['status']     = 1;
        $result             = DbModelMessage::saveTrigger($data);
        return ['code' => '200', 'saveid' => $result];
    }

    /**
     * 获取触发器
     * @param number $page
     * @param number $pageNum
     * @return array
     * @author rzc
     */
    public function getTrigger(int $page, int $pageNum, $id = '') {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbModelMessage::getTrigger(['id' => $id], '*', true);
            return ['code' => '200', 'Trigger' => $result];
        } else {
            $result = DbModelMessage::getTrigger([], '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
            if (empty($result)) {
                return ['code' => '3000'];
            }
        }
        if (empty($result)) {
            return ['code' => '3000'];
        }
        $total = DbModelMessage::countTrigger([]);
        return ['code' => '200', 'total' => $total, 'Trigger' => $result];
    }

    /**
     * 审核触发器
     * @param number $id
     * @param number $status
     * @return array
     * @author rzc
     */
    public function auditTrigger($id, $status) {
        $result = DbModelMessage::getTrigger(['id' => $id], 'status', true);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        if ($result['status'] == $status) {
            return ['code' => '3003'];
        }
        DbModelMessage::editTrigger(['status' => $status], $id);
        return ['code' => '200'];
    }

    /**
     * 修改触发器
     * @param number $id
     * @param string $title
     * @param number $start_time
     * @param number $stop_time
     * @return array
     * @author rzc
     */
    public function editTrigger($title, $start_time, $stop_time, $id) {
        $result = DbModelMessage::getTrigger(['id' => $id], 'status', true);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        if ($result['status'] == 2) {
            return ['code' => '3003'];
        }
        $data               = [];
        $data['title']      = $title;
        $data['start_time'] = $start_time;
        $data['stop_time']  = $stop_time;
        $data['status']     = 1;
        $result             = DbModelMessage::editTrigger($data, $id);
        return ['code' => '200', 'saveid' => $result];
    }

    /**
     * 添加模板消息
     * @param number $type
     * @param string $title
     * @param string $template
     * @return array
     * @author rzc
     */
    public function saveMessageTemplate($title, $type, $template) {
        $data             = [];
        $data['type']     = $type;
        $data['title']    = $title;
        $data['template'] = $template;
        $data['status']   = 1;
        $result           = DbModelMessage::saveMessageTemplate($data);
        return ['code' => '200', 'tem_id' => $result];
    }

    /**
     * 修改模板消息
     * @param number $type
     * @param string $title
     * @param string $template
     * @return array
     * @author rzc
     */
    public function editMessageTemplate($title, $type, $template, $id) {
        $result = DbModelMessage::getMessageTemplate(['id' => $id], 'status', true);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        if ($result['status'] == 2) {
            return ['code' => '3003'];
        }
        $data             = [];
        $data['type']     = $type;
        $data['title']    = $title;
        $data['template'] = $template;
        $data['status']   = 1;
        $result           = DbModelMessage::editMessageTemplate($data, $id);
        return ['code' => '200', 'tem_id' => $result];
    }

    /**
     * 审核模板消息
     * @param number $id
     * @param number $status
     * @return array
     * @author rzc
     */
    public function auditMessageTemplate($id, $status) {
        $result = DbModelMessage::getMessageTemplate(['id' => $id], 'status', true);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        if ($result['status'] == $status) {
            return ['code' => '3003'];
        }
        DbModelMessage::editMessageTemplate(['status' => $status], $id);
        return ['code' => '200'];
    }

    /**
     * 获取模板消息
     * @param number $id
     * @param number $page
     * @param number $pageNum
     * @return array
     * @author rzc
     */
    public function getMessageTemplate(int $page, int $pageNum, $id = '') {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbModelMessage::getMessageTemplate(['id' => $id], '*', true);
            if (empty($result)) {
                return ['code' => '3000'];
            }
            return ['code' => '200', 'Trigger' => $result];
        } else {
            // echo $pageNum;die;
            $result = DbModelMessage::getMessageTemplate([], '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
            if (empty($result)) {
                return ['code' => '3000'];
            }
        }
        $total = DbModelMessage::countMessageTemplate([]);
        return ['code' => '200', 'total' => $total, 'MessageTemplate' => $result];
    }

    /**
     * 添加消息任务
     * @param string $title
     * @param number $type
     * @param number $wtype
     * @param number $mt_id
     * @param number $trigger_id
     * @return array
     * @author rzc
     */
    public function saveMessageTask($title, int $type, int $wtype, int $mt_id, int $trigger_id) {
        $message_template = DbModelMessage::getMessageTemplate(['id' => $mt_id, 'status' => 2], '*', true);
        if (empty($message_template)) {
            return ['code' => '3004'];
        }
        $trigger = DbModelMessage::getMessageTemplate(['id' => $trigger_id, 'status' => 2], '*', true);
        if (empty($trigger)) {
            return ['code' => '3005'];
        }
        $data               = [];
        $data['title']      = $title;
        $data['type']       = $type;
        $data['wtype']      = $wtype;
        $data['mt_id']      = $mt_id;
        $data['trigger_id'] = $trigger_id;
        $data['status']     = 1;
        $result             = DbModelMessage::saveMessageTask($data);
        return ['code' => '200', 'mtask_id' => $result];
    }

    /**
     * 修改消息任务
     * @param string $title
     * @param number $type
     * @param number $wtype
     * @param number $mt_id
     * @param number $trigger_id
     * @param number $MessageTask_id
     * @return array
     * @author rzc
     */
    public function editMessageTask($title, int $type, int $wtype, int $mt_id, int $trigger_id, int $MessageTask_id) {
        $message_template = DbModelMessage::getMessageTemplate(['id' => $mt_id, 'status' => 2], '*', true);
        if (empty($message_template)) {
            return ['code' => '3004'];
        }
        $trigger = DbModelMessage::getMessageTemplate(['id' => $trigger_id, 'status' => 2], '*', true);
        if (empty($trigger)) {
            return ['code' => '3005'];
        }
        $messagetask = DbModelMessage::getMessageTask(['id' => $MessageTask_id], '*', true);
        if (empty($messagetask)) {
            return ['code' => '3000'];
        }
        if ($messagetask['status'] == 2) {
            return ['code' => '3007'];
        }
        $data               = [];
        $data['title']      = $title;
        $data['type']       = $type;
        $data['wtype']      = $wtype;
        $data['mt_id']      = $mt_id;
        $data['trigger_id'] = $trigger_id;
        $data['status']     = 1;
        $result             = DbModelMessage::editMessageTask($data, $MessageTask_id);
        return ['code' => '200', 'mtask_id' => $result];
    }

    /**
     * 停启用消息任务
     * @param number $id
     * @param number $status
     * @return array
     * @author rzc
     */
    public function auditMessageTask(int $id, int $status){
        $result = DbModelMessage::getMessageTask(['id' => $id], 'status', true);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        if ($result['status'] == $status) {
            return ['code' => '3003'];
        }
        DbModelMessage::editMessageTask(['status' => $status], $id);
        return ['code' => '200'];
    }

    /**
     * 获取消息任务
     * @param number $page
     * @param number $pageNum
     * @param number $id
     * @return array
     * @author rzc
     */
    public function getMessageTask(int $page, int $pageNum, int $id = 0) {
        $offset = ($page - 1) * $pageNum;
        if ($id) {
            $result = DbModelMessage::getMessageTask(['id' => $id], '*', true);
            return ['code' => '200', 'messagetask' => $result];
        }
        $result = DbModelMessage::getMessageTask([], '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
        $total  = DbModelMessage::countMessageTask([]);
        return ['code' => '200', 'total' => $total, 'messagetask' => $result];

    }
}