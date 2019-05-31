<?php
namespace app\facade;

use think\Facade;

class DbOfflineActivities extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\offlineactivities\DbOfflineActivities';
    }
}