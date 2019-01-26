<?php
return [
    'timeout' => 3,
    //用户密码加密方式
    'cipher_algo'            => 'sha3-256',

    'weixin_miniprogram_appid' => Env::get('weixin.weixin_miniprogram_appid'),
    'weixin_miniprogram_appsecret' =>Env::get('weixin.weixin_miniprogram_appsecret'),

//    'weixin_appid' => 'wxeead1475c05cde84',
//    'weixin_appsecret' =>'e688545400add6d33a2ee7321a904999',

];