<?php

namespace app\facade;

use think\Facade;

class DbSup extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\other\DbSup';
    }
}