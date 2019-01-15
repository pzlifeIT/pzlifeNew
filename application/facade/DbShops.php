<?php
namespace app\facade;

use think\Facade;

class DbShops extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\shop\DbShops';
    }
}