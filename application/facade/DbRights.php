<?php
namespace app\facade;

use think\Facade;

class DbRights extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\rights\DbRights';
    }
}