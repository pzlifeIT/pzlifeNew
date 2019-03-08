<?php
return [
    'cart'  => [
        'redisCartUserKey' => 'index:cart:user:',//用户购物车信息
    ],
    'order' => [
        'redisOrderBonus'  => 'index:order:bonus:list:',//计算分利的订单队列的key(普通商品)
        'redisMemberOrder' => 'index:member:order:list:',//购买会员成功支付的订单队列
        'redisMemberShare' => 'index:member:share:list:',//购买会员成功支付的上级分享者获利提示队列
    ],
    'user'  => [
        'redisKey'           => 'index:user:',
        'redisConIdTime'     => 'index:user:conId:expiration',//conId到期时间的zadd
        'redisConIdUid'      => 'index:user:conId:uid',//conId和uid的hSet
        'redisUserNextLevel' => 'index:user:nextLevel:uid:',//用户关系下的所有关系网uid列表
    ],
    'index' => [
        'redisIndexShow' => 'index:index:show',
    ],
];