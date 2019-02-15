<?php
namespace app\facade;

use think\Facade;

class DbOrder extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\order\DbOrder';
    }
}