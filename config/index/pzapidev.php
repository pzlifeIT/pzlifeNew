<?php
/**
 * pzapidev 老项目数据库
 */
return [
    // 数据库类型
    'type'            => 'mysql',
    // 服务器地址
    'hostname'        => Env::get('pzapidev.hostname', '127.0.0.1'),
    // 数据库名
    'database'        => Env::get('pzapidev.database', ''),
    // 用户名
    'username'        => Env::get('pzapidev.username', ''),
    // 密码
    'password'        => Env::get('pzapidev.password', ''),
    // 数据库连接端口
    'hostport'    => '',
    // 数据库连接参数
    'params'      => [],
    // 数据库编码默认采用utf8
    'charset'     => 'utf8',
    // 数据库表前缀
    'prefix'      => 'pre_',
];