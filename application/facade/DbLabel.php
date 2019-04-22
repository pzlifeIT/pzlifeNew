<?php
namespace app\facade;
use think\Facade;

class DbLabel extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\product\DbLabel';
    }
}