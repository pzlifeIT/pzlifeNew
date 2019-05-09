<?php

namespace app\common\db\modelmessage;

use app\common\model\MessageTask;
use app\common\model\MessageTemplate;
use app\common\model\Trigger;

class DbModelMessage {

    /**
     * @param $obj
     * @param bool $row
     * @param array $orderBy
     * @param string $limit
     * @return mixed
     * @author rzc
     */
    private function getResult($obj, $row = false, $orderBy = '', $limit = '') {
        if (!empty($orderBy)) {
            $obj = $obj->order($orderBy);
        }
        if (!empty($limit)) {
            $obj = $obj->limit($limit);
        }
        if ($row === true) {
            $obj = $obj->findOrEmpty();
        } else {
            $obj = $obj->select();
        }
        return $obj->toArray();
    }

    /**
     * 添加触发器
     * @param array $data
     * @author rzc
     */
    public function saveTrigger($data) {
        $Trigger = new Trigger;
        $Trigger->save($data);
        return $Trigger->id;
    }

    /**
     * 修改触发器
     * @param array $data
     * @author rzc
     */
    public function editTrigger($data, $id) {
        $Trigger = new Trigger;
        return $Trigger->save($data, ['id' => $id]);
    }

    /**
     * 获取触发器
     * @param array $where
     * @param bool $field
     * @param string $row
     * @param array $orderBy
     * @param string $limit
     * @return mixed
     * @author rzc
     */
    public function getTrigger($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = Trigger::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * @param array $where
     * @return mixed
     * @author rzc
     */
    public function countTrigger($where) {
        return Trigger::where($where)->count();
    }

    /**
     * 添加消息模板
     * @param array $data
     * @author rzc
     */
    public function saveMessageTemplate($data) {
        $MessageTemplate = new MessageTemplate;
        $MessageTemplate->save($data);
        return $MessageTemplate->id;
    }

    /**
     * 修改消息模板
     * @param array $data
     * @author rzc
     */
    public function editMessageTemplate($data, $id) {
        $MessageTemplate = new MessageTemplate;
        return $MessageTemplate->save($data, ['id' => $id]);
    }

    /**
     * 获取消息模板
     * @param array $where
     * @param bool $field
     * @param string $row
     * @param array $orderBy
     * @param string $limit
     * @return mixed
     * @author rzc
     */
    public function getMessageTemplate($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = MessageTemplate::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 消息模板计数
     * @param array $where
     * @return mixed
     * @author rzc
     */
    public function countMessageTemplate($where) {
        return MessageTemplate::where($where)->count();
    }

    /**
     * 添加消息任务
     * @param array $data
     * @author rzc
     */
    public function saveMessageTask($data) {
        $MessageTask = new MessageTask;
        $MessageTask->save($data);
        return $MessageTask->id;
    }

    /**
     * 修改消息任务
     * @param array $data
     * @param number $id
     * @author rzc
     */
    public function editMessageTask($data, $id) {
        $MessageTask = new MessageTask;
        $MessageTask->save($data, ['id' => $id]);
        return $MessageTask->id;
    }

    /**
     * 获取消息任务
     * @param array $where
     * @param bool $field
     * @param string $row
     * @param array $orderBy
     * @param string $limit
     * @return mixed
     * @author rzc
     */
    public function getMessageTask($where, $field, $row = false, $orderBy = '', $limit = '') {
        $obj = MessageTask::field($field)->where($where);
        return $this->getResult($obj, $row, $orderBy, $limit);
    }

    /**
     * 消息任务计数
     * @param array $where
     * @return mixed
     * @author rzc
     */
    public function countMessageTask($where) {
        return MessageTask::where($where)->count();
    }
}