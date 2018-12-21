<?php

namespace app\console\com;

use app\console\Pzlife;
use Env;
use think\Db;
use Config;

class User extends Pzlife {
    public function api($params) {
        
        $member = "SELECT * FROM pre_member ORDER BY uid ASC LIMIT 1 ";
        echo '<pre>';
        var_dump( Config::get('database.db_config') );
        echo '</pre>';
        exit;
        
        $res = Db::connect(Config::get('database.db_config'))->query($member);
        
        while ($value = Db::query($member)) {
            echo '<pre>';
            var_dump( $value );
            echo '</pre>';
            exit;
            
        }
    }
}