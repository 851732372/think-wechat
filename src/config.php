<?php

return [
    // 微信小程序
    'miniprogram' => [
        'appid'  => env('WECHAT_MINI_APPID', ''),
        'secret' => env('WECHAT_MINI_SECRET', ''),
    ],
    
    // 微信支付
    'payment' => [
        'mch_id'     => env('WECHAT_PAY_MCHID', ''),      // 商户号
        'key'        => env('WECHAT_PAY_KEY', ''),         // API密钥
        'appid'      => env('WECHAT_PAY_APPID', ''),       // 绑定公众号/小程序的appid
        'cert_path'  => env('WECHAT_PAY_CERT', ''),        // 证书路径
        'key_path'   => env('WECHAT_PAY_CERT_KEY', ''),    // 证书密钥路径
        'notify_url' => env('WECHAT_PAY_NOTIFY', ''),      // 支付回调地址
    ],
    
    // 微信公众号
    'official_account' => [
        'appid'   => env('WECHAT_OFFICIAL_APPID', ''),
        'secret'  => env('WECHAT_OFFICIAL_SECRET', ''),
        'token'   => env('WECHAT_OFFICIAL_TOKEN', ''),     // 消息服务token
        'aes_key' => env('WECHAT_OFFICIAL_AES_KEY', ''),   // 消息加密key
    ],
    
    // 通用配置
    'cache_store' => 'redis',
];
