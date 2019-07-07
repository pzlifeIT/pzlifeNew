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
    'user'              => app\common\action\index\User::class,
    'category'          => \app\common\action\index\Category::class,
    'goods'             => \app\common\action\index\Goods::class,
    'cart'              => \app\common\action\index\Cart::class,
//    'collect'  => \app\common\action\index\Collect::class,
    'order'             => app\common\action\index\Order::class,
    'rights'            => app\common\action\index\Rights::class,
    'recommend'         => app\common\action\index\Recommend::class,
    'upload'            => app\common\action\index\Upload::class,
    'shopmanage'        => app\common\action\index\Shopmanage::class,
    'offlineactivities' => app\common\action\index\OfflineActivities::class,
    'indexLog'          => app\common\action\index\IndexLog::class,
    'wap'               => app\common\action\index\Wap::class,
    'wechattweets'      => app\common\action\index\WechatTweets::class,
];
