<?php

namespace app\facade;

use think\Facade;

class DbCoupon extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\product\DbCoupon';
    }
}