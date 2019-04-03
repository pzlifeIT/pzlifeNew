<?php

namespace app\common\action\admin;

use app\common\model\LogApi;

class AdminLog extends CommonIndex {
    public function apiRequestLog($apiName, $param, $code, $cmsConId) {
        $adminId = $this->getUidByConId($cmsConId);
        $user    = new LogApi();
        $user->save([
            'api_name' => $apiName,
            'param'    => json_encode($param),
            'stype'    => 2,
            'code'     => $code,
            'admin_id' => $adminId,
        ]);
    }
}