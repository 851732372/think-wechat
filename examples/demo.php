<?php

/**
 * 使用示例
 */

require __DIR__ . '/../vendor/autoload.php';

use Ginger\ThinkWeChat\WeChat;

// ==================== 配置 ====================
$config = [
    // 微信小程序
    'miniprogram' => [
        'appid'  => 'wx1234567890abcdef',
        'secret' => 'your-secret',
    ],
    
    // 微信支付
    'payment' => [
        'mch_id'     => '1234567890',
        'key'        => 'your-payment-key',
        'appid'      => 'wx1234567890abcdef',
        'cert_path'  => '/path/to/cert.pem',
        'key_path'   => '/path/to/key.pem',
        'notify_url' => 'https://your-domain.com/api/notify',
    ],
    
    // 微信公众号
    'official_account' => [
        'appid'   => 'wx1234567890abcdef',
        'secret'  => 'your-secret',
        'token'   => 'your-token',
        'aes_key' => 'your-aes-key',
    ],
    
    // 通用配置
    'cache_store' => 'redis',
];

$wechat = new WeChat($config);


// ==================== 微信小程序示例 ====================

// 1. code 换取 openid
$code = 'wx-login-code';
$result = $wechat->miniProgram()->code2Session($code);
if ($result['success']) {
    $openid = $result['data']['openid'];
    $sessionKey = $result['data']['session_key'];
}

// 2. 解密手机号（需要 wx.login 和 getPhoneNumber 的 code）
$loginCode = 'wx-login-code';
$encryptedData = 'encrypted-phone-data';
$iv = 'iv-string';
$result = $wechat->miniProgram()->decryptPhone($loginCode, $encryptedData, $iv);
if ($result['success']) {
    $phone = $result['data']['phone'];
    $openid = $result['data']['openid'];
}

// 3. 获取小程序码
$result = $wechat->miniProgram()->getUnlimitedQRCode('scene=123', [
    'page' => 'pages/index',
    'width' => 430,
]);

// 4. 发送订阅消息
$wechat->miniProgram()->sendSubscribeMessage('openid', 'template-id', [
    'thing1' => ['value' => '商品名称'],
    'amount2' => ['value' => '99.99元'],
    'thing3' => ['value' => '2024-01-01 10:00:00'],
]);


// ==================== 微信支付示例 ====================

// 1. 统一下单
$openid = 'user-openid';
$outTradeNo = 'ORDER_' . time();
$totalFee = 9900; // 金额：分
$body = '商品描述';

$result = $wechat->payment()->unifiedOrder($openid, $outTradeNo, $totalFee, $body);
if ($result['success']) {
    $prepayId = $result['data']['prepay_id'];
    $payParams = $result['data']['pay_params']; // 前端调起支付参数
}

// 2. 查询订单
$result = $wechat->payment()->queryOrder($outTradeNo);

// 3. 关闭订单
$result = $wechat->payment()->closeOrder($outTradeNo);

// 4. 验证支付回调签名
$notifyData = $_POST; // 微信支付回调数据
$isValid = $wechat->payment()->verifyNotify($notifyData);


// ==================== 微信公众号示例 ====================

// 1. 获取网页授权 URL
$redirectUri = 'https://your-domain.com/callback';
$oauthUrl = $wechat->officialAccount()->getOauthUrl($redirectUri, 'snsapi_userinfo', 'state123');
// 跳转用户到 $oauthUrl

// 2. 通过 code 获取用户信息
$code = $_GET['code'] ?? '';
$result = $wechat->officialAccount()->getUserInfoByCode($code);
if ($result['success']) {
    $userInfo = $result['data'];
    $openid = $userInfo['openid'];
    $nickname = $userInfo['nickname'];
    $headimgurl = $userInfo['headimgurl'];
}

// 3. 发送模板消息
$wechat->officialAccount()->sendTemplateMessage(
    'openid',
    'template-id',
    'https://your-domain.com/detail',
    [
        'keyword1' => ['value' => '张三', 'color' => '#173177'],
        'keyword2' => ['value' => '99.99元'],
        'keyword3' => ['value' => '2024-01-01 10:00:00'],
    ],
    [
        'appid' => 'wx1234567890abcdef',
        'pagepath' => 'pages/index',
    ]
);

// 4. 发送客服消息
$wechat->officialAccount()->sendCustomMessage('openid', 'text', ['content' => '您好！']);
$wechat->officialAccount()->sendCustomMessage('openid', 'image', ['media_id' => 'media-id']);

// 5. 获取用户信息
$result = $wechat->officialAccount()->getUserInfo('openid');
