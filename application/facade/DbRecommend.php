<?php

namespace app\facade;

use think\Facade;

class DbRecommend extends Facade {
    protected static function getFacadeClass() {
        return 'app\common\db\other\DbRecommend';
    }
}