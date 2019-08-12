<?php

namespace app\facade;

use think\Facade;

class DbAudios extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\product\DbAudios';
    }
}