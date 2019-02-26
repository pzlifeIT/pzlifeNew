<?php
return [
    'cart'  => [
        'redisCartUserKey' => 'index:cart:user:',//用户购物车信息
    ],
    'order' => [
        'redisOrderBonus' => 'index:order:bonus:list:',//计算分利的订单队列的key(普通商品)
        'redisMemberOrder' => 'index:member:order:list:',//购买会员成功支付的订单队列
        'redisMemberShare' => 'index:member:share:list:',//购买会员成功支付的上级分享者获利提示队列
    ],
];