<?php

namespace app\console\com;

use app\console\Pzlife;
use Env;

class User extends Pzlife {
    public function api($params) {
        echo $params;
    }
}