<?php

namespace app\common\action\admin;

use app\facade\DbModelMessage;
use cache\Phpredis;
use Config;
use think\Db;

class ModelMessage extends CommonIndex {

    private function redisInit() {
        $this->redis = Phpredis::getConn();
//        $this->connect = Db::connect(Config::get('database.db_config'));
    }

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
     * @param number $id
     * @param number $all
     * @return array
     * @author rzc
     */
    public function getTrigger(int $page, int $pageNum, $id = '', $all = '') {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbModelMessage::getTrigger(['id' => $id], '*', true);
            return ['code' => '200', 'Trigger' => $result];
        } else {
            if ($all == 1) {
                $result = DbModelMessage::getTrigger([], '*', false, ['id' => 'desc']);
            } else {
                $result = DbModelMessage::getTrigger([], '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
            }
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
        if ($status == 3) {
            $messagetask = DbModelMessage::getMessageTask(['trigger_id' => $id, 'status' => 2], '*', true);
            if (!empty($messagetask)) {
                return ['code' => '3004', 'msg' => '存在已启用的消息任务，无法停用'];
            }
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
    public function editTrigger($title = '', $start_time = 0, $stop_time = 0, $id) {
        $result = DbModelMessage::getTrigger(['id' => $id], '*', true);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        if ($result['status'] == 2) {
            return ['code' => '3003'];
        }
        if ($start_time && !$stop_time) {
            if (strtotime($result['stop_time']) < $start_time + 900) {
                return ['code' => '3004'];
            }
        }
        if ($stop_time && !$start_time) {
            if ($stop_time < strtotime($result['start_time']) + 900) {
                return ['code' => '3004'];
            }
        }
        $data = [];
        if ($title) {
            $data['title'] = $title;
        }
        if ($start_time) {
            $data['start_time'] = $start_time;
        }
        if ($stop_time) {
            $data['stop_time'] = $stop_time;
        }
        $data['status'] = 1;
        $result         = DbModelMessage::editTrigger($data, $id);
        return ['code' => '200', 'saveid' => $id];
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
            return ['code' => '3005'];
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
        if ($status == 3) {
            $messagetask = DbModelMessage::getMessageTask(['mt_id' => $id, 'status' => 2], '*', true);
            if (!empty($messagetask)) {
                return ['code' => '3004', 'msg' => '存在已启用的消息任务，无法停用'];
            }
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
    public function getMessageTemplate(int $page, int $pageNum, $id = '', $all = '') {
        $offset = ($page - 1) * $pageNum;
        if (!empty($id)) {
            $result = DbModelMessage::getMessageTemplate(['id' => $id], '*', true);
            if (empty($result)) {
                return ['code' => '3000'];
            }
            /*    $template = $result['template'];
            // preg_match_all("/(?<={{)[^}]+/", $template, $matches);
           $template = str_replace(
                    [
                        '{{[order_no]}}',
                        '{{[delivergoods]}}',
                        '{{[nick_name]}}',
                        '{{[money]}}',
                        '{{[goods_name]}}',
                        '{{[goods_num]}}',
                        '{{[bank_card]}}',
                    ],
                    [
                        '订单号xxx',
                        '物流公司XX运单号XXXXX商品XX数量XX',
                        '昵称xxx',
                        '金额XXX',
                        '商品XXX',
                        '数量XXX',
                        '尾号为XXXX账户',
                    ], $template);
            $result['template'] = $template;
             */
            return ['code' => '200', 'Trigger' => $result];
        } else {
            // echo $pageNum;die;
            if ($all == 1) {
                $result = DbModelMessage::getMessageTemplate([], '*', false, ['id' => 'desc']);
            } else {
                $result = DbModelMessage::getMessageTemplate([], '*', false, ['id' => 'desc'], $offset . ',' . $pageNum);
            }
            if (empty($result)) {
                return ['code' => '3000'];
            }
            foreach ($result as $key => $value) {
                $template = $value['template'];
                ///(?<={{)[^}]+/
                // preg_match_all("/\{\{\[[_a-z]+\]\}\}/", $template, $matches);
                $template = str_replace(
                    [
                        '{{[order_no]}}',
                        '{{[delivergoods]}}',
                        '{{[nick_name]}}',
                        '{{[money]}}',
                        '{{[goods_name]}}',
                        '{{[goods_num]}}',
                        '{{[bank_card]}}',
                    ],
                    [
                        '订单号xxx',
                        '物流公司XX运单号XXXXX商品XX数量XX',
                        '昵称xxx',
                        '金额XXX',
                        '商品XXX',
                        '数量XXX',
                        '尾号为XXXX账户',
                    ], $template);
                $result[$key]['template'] = $template;

            }
        }
        $total = DbModelMessage::countMessageTemplate([]);
        return ['code' => '200', 'total' => $total, 'MessageTemplate' => $result];
    }

    /**
     * 消息模板对应文本
     * @return array
     * @author rzc
     */
    public function getMessageTemplateText() {
        $templatetext = [
            [
                'key'   => '{{[order_no]}}',
                'value' => '订单号xxx',
            ],
            [
                'key'   => '{{[delivergoods]}}',
                'value' => '物流公司XX运单号XXXXX商品XX数量XX',
            ],
            [
                'key'   => '{{[nick_name]}}',
                'value' => '昵称XXX',
            ],
            [
                'key'   => '{{[money]}}',
                'value' => '金额XXX',
            ],
            [
                'key'   => '{{[goods_name]}}',
                'value' => '商品XXX',
            ],
            [
                'key'   => '{{[goods_num]}}',
                'value' => '数量XXX',
            ],
            [
                'key'   => '{{[bank_card]}}',
                'value' => '尾号为XXXX账户',
            ],
        ];
        return ['code' => '200', 'templatetext' => $templatetext];
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
        if (!in_array($wtype, [4, 5])) {
            if ($type == 1) {
                $has_type = '1,2,3,4,5';
            } else {
                $has_type = '1,' . $type;
            }
            $has_message_task = DbModelMessage::getMessageTask([['wtype', '=', 1], ['status', '=', 2], ['type', 'in', $has_type]], '*', true);
            if (!empty($has_message_task)) {
                return ['code' => '3006', 'msg' => '存在已启用的同类模板任务'];
            }
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
        if (!in_array($wtype, [4, 5])) {
            if ($type == 1) {
                $has_type = '1,2,3,4,5';
            } else {
                $has_type = '1,' . $type;
            }
            $has_message_task = DbModelMessage::getMessageTask([['wtype', '=', $wtype], ['status', '=', 2], ['type', 'in', $has_type], ['id', '<>', $MessageTask_id]], '*', true);
            if (!empty($has_message_task)) {
                return ['code' => '3008', 'msg' => '存在已启用的同类模板任务'];
            }
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
    public function auditMessageTask(int $id, int $status) {
        $result = DbModelMessage::getMessageTask(['id' => $id], '*', true);
        if (empty($result)) {
            return ['code' => '3000'];
        }
        if ($result['status'] == $status) {
            return ['code' => '3003'];
        }
        if ($status == 2) {
            $redisListKey = Config::get('redisKey.modelmessage.redisMarketingActivity');
            $this->redis->rPush($redisListKey, $id);
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