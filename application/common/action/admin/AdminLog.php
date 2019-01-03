<?php

namespace app\common\action\admin;

use app\common\model\LogApi;

class AdminLog {
    public function apiRequestLog($apiName, $code, $stype, $adminName = '') {
        $user = new LogApi();
        $user->save([
            'api_name'    => $apiName,
            'stype'       => $stype,
            'code'        => $code,
            'admin_name'  => $adminName,
        ]);
    }
}