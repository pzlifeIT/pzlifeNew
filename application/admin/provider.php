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
    'provinces'         => app\common\action\admin\Provinces::class,
    'suppliers'         => app\common\action\admin\Suppliers::class,
    'category'          => app\common\action\admin\Category::class,
    'spec'              => app\common\action\admin\Spec::class,
    'goods'             => app\common\action\admin\Goods::class,
//    'dividend'  => app\common\action\index\Dividend::class,
    'adminLog'          => app\common\action\admin\AdminLog::class,
    'subject'           => app\common\action\admin\Subject::class,
    'user'              => app\common\action\admin\User::class,
    'upload'            => app\common\action\admin\Upload::class,
    'admin'             => app\common\action\admin\Admin::class,
    'rights'            => app\common\action\admin\Rights::class,
    'order'             => app\common\action\admin\Order::class,
    'recommend'         => app\common\action\admin\Recommend::class,
    'label'             => app\common\action\admin\Label::class,
    'modelmessage' => app\common\action\admin\ModelMessage::class,
    'offlineactivities' => app\common\action\admin\OfflineActivities::class,
];
