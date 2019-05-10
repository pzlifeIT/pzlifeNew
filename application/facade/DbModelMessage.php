<?php
namespace app\facade;

use think\Facade;

class DbModelMessage extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\modelmessage\DbModelMessage';
    }
}