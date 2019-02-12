<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用容器绑定定义
return [
    'user'     => app\common\action\index\User::class,
    'category' => \app\common\action\index\Category::class,
    'goods' => \app\common\action\index\Goods::class,
    'cart' => \app\common\action\index\Cart::class,
    'order' => \app\common\action\index\Order::class,
//    'collect'  => \app\common\action\index\Collect::class,
    'order'     => app\common\action\index\Order::class
];
