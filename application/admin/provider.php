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
    'index' => app\common\admin\Index::class,
    'suppliers' => app\common\admin\Suppliers::class,
    'category' => app\common\admin\Category::class,
    'spec' => app\common\admin\Spec::class,
    'goods'=>\app\common\admin\Goods::class,
];
