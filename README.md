# ThinkPHP 微信开发工具包

ThinkPHP 8 微信开发工具包，支持微信小程序、微信支付、公众号等功能。

## 安装

```bash
composer require 851732372/think-wechat
```

## 配置

```bash
# 发布配置文件
php think vendor:publish --package 851732372/think-wechat
```

### 环境变量配置

```bash
# .env

# 微信小程序
WECHAT_MINI_APPID=wx1234567890abcdef
WECHAT_MINI_SECRET=your-secret

# 微信支付
WECHAT_PAY_MCHID=1234567890
WECHAT_PAY_KEY=your-payment-key
WECHAT_PAY_APPID=wx1234567890abcdef
WECHAT_PAY_CERT=/path/to/cert.pem
WECHAT_PAY_CERT_KEY=/path/to/key.pem
WECHAT_PAY_NOTIFY=https://your-domain.com/api/notify

# 微信公众号
WECHAT_OFFICIAL_APPID=wx1234567890abcdef
WECHAT_OFFICIAL_SECRET=your-secret
WECHAT_OFFICIAL_TOKEN=your-token
WECHAT_OFFICIAL_AES_KEY=your-aes-key
```

## 使用

### 基础用法

```php
use Ginger\ThinkWeChat\WeChat;

// 从配置文件加载
$wechat = new WeChat(config('wechat'));

// 或手动配置
$wechat = new WeChat([
    'miniprogram' => [
        'appid' => 'wx123...',
        'secret' => 'secret...',
    ],
    'payment' => [
        'mch_id' => '123456',
        'key' => 'key...',
        'appid' => 'wx123...',
    ],
]);
```

### 微信小程序

```php
// 获取实例
$miniProgram = $wechat->miniProgram();

// code 换取 openid
$result = $miniProgram->code2Session('wx-code');
// 返回：['success' => true, 'data' => ['openid' => '...', 'session_key' => '...']]

// 解密手机号
$result = $miniProgram->decryptPhone('code', 'encryptedData', 'iv');
// 返回：['success' => true, 'data' => ['phone' => '13800138000', 'openid' => '...']]

// 获取小程序码
$result = $miniProgram->getUnlimitedQRCode('scene=123', ['page' => 'pages/index']);

// 发送订阅消息
$miniProgram->sendSubscribeMessage('openid', 'template_id', [
    'name1' => ['value' => '商品名称'],
    'amount2' => ['value' => '99.99元'],
]);
```

### 微信支付

```php
// 获取实例
$payment = $wechat->payment();

// 统一下单
$result = $payment->unifiedOrder(
    'openid',
    'ORDER_20240101_001',
    9900, // 金额：分
    '商品描述'
);
// 返回：['success' => true, 'data' => ['prepay_id' => '...', 'pay_params' => [...]]]

// 查询订单
$payment->queryOrder('ORDER_20240101_001');

// 关闭订单
$payment->closeOrder('ORDER_20240101_001');

// 验证支付回调
$payment->verifyNotify($notifyData);
```

### 微信公众号

```php
// 获取实例
$official = $wechat->officialAccount();

// 获取网页授权 URL
$url = $official->getOauthUrl('https://your-domain.com/callback', 'snsapi_userinfo');

// 通过 code 获取用户信息
$result = $official->getUserInfoByCode('code');

// 发送模板消息
$official->sendTemplateMessage('openid', 'template_id', 'url', [
    'keyword1' => ['value' => '内容1', 'color' => '#173177'],
    'keyword2' => ['value' => '内容2'],
]);

// 发送客服消息
$official->sendCustomMessage('openid', 'text', ['content' => '您好']);

// 获取用户信息
$official->getUserInfo('openid');
```

## License

MIT
