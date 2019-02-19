<?php
return [
    'cart'  => [
        'redisCartUserKey' => 'index:cart:user:',//用户购物车信息
    ],
    'order' => [
        'redisOrderBonus' => 'index:order:bonus:list:',//计算分利的订单队列的key(普通商品)
    ],
];