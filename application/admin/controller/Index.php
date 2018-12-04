<?php

namespace app\admin\controller;

use app\facade\Test;
use Config;
use Env;
use think\Controller;


class Index extends Controller {

    public function index() {
        $res = Config::get();
        print_r($res);
        die;
        return json_encode($res);
    }

    public function hello($name = '') {
        return 'admin hello '.$name;
    }
}