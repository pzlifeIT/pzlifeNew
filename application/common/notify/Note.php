<?php

namespace app\common\notify;

use third\AliSms;

/**
 * 短信发送
 * @package app\common\notify
 */
class Note {
    public function sendSms($phone, $code) {
        $sendRes = AliSms::send($phone, $code, 4);
        if ($sendRes) {
            return ['code' => 200];
        }
        return ['code' => 3000];
    }

    public function getSms($phone, $date) {
        $result = AliSms::querySendDetails($phone, $date);
        $result = $result->SmsSendDetailDTOs->SmsSendDetailDTO;
        return ['code' => 200, 'data' => $result];
    }
}