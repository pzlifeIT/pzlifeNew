<?php

namespace app\index\controller;

use app\index\MyController;

class Order extends MyController {
    protected $beforeActionList = [
        'isLogin',//所有方法的前置操作
//        'isLogin' => ['except' => ''],//除去getFirstCate其他方法都进行second前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];


}