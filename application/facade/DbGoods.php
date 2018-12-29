<?php

namespace app\facade;

use think\Facade;

class DbGoods extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\product\DbGoods';
    }
}