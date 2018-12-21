<?php

namespace app\console\controller;

use app\console\Pzlife;
use Env;
use Config;

class User extends Pzlife {
    public function api($params) {
        var_dump(Config::get('database.db_config'));
        die;
    }
}