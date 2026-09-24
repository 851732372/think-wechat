<?php

/**
 * ThinkPHP 中使用示例
 */

namespace app\controller;

use app\BaseController;
use Ginger\ThinkWeChat\WeChat;

class WechatExample extends BaseController
{
    /**
     * 微信小程序登录
     */
    public function miniLogin()
    {
        $code = $this->request->post('code');
        $encryptedData = $this->request->post('encrypted_data');
        $iv = $this->request->post('iv');
        
        $wechat = new WeChat(config('wechat'));
        $result = $wechat->miniProgram()->decryptPhone($code, $encryptedData, $iv);
        
        if (!$result['success']) {
            return json(['code' => 400, 'message' => $result['message']], 400);
        }
        
        $phone = $result['data']['phone'];
        $openid = $result['data']['openid'];
        
        // 这里处理你的登录逻辑：查找用户、生成 Token 等
        // $user = User::findByPhone($phone);
        // ...
        
        return json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'phone' => $phone,
                'openid' => $openid,
            ]
        ]);
    }
    
    /**
     * 微信支付
     */
    public function pay()
    {
        $openid = $this->request->post('openid');
        $orderNo = 'ORDER_' . time();
        $amount = 9900; // 分
        
        $wechat = new WeChat(config('wechat'));
        $result = $wechat->payment()->unifiedOrder($openid, $orderNo, $amount, '商品购买');
        
        if (!$result['success']) {
            return json(['code' => 400, 'message' => $result['message']], 400);
        }
        
        return json([
            'code' => 200,
            'data' => $result['data']['pay_params']
        ]);
    }
    
    /**
     * 微信支付回调
     */
    public function payNotify()
    {
        $xml = file_get_contents('php://input');
        $data = \Ginger\ThinkWeChat\Utils\Helper::xmlToArray($xml);
        
        $wechat = new WeChat(config('wechat'));
        
        if (!$wechat->payment()->verifyNotify($data)) {
            return '<xml><return_code><![CDATA[FAIL]]></return_code></xml>';
        }
        
        // 处理订单支付成功逻辑
        $outTradeNo = $data['out_trade_no'];
        // ...
        
        return '<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>';
    }
    
    /**
     * 公众号授权回调
     */
    public function oauthCallback()
    {
        $code = $this->request->get('code');
        
        $wechat = new WeChat(config('wechat'));
        $result = $wechat->officialAccount()->getUserInfoByCode($code);
        
        if (!$result['success']) {
            return '授权失败：' . $result['message'];
        }
        
        $userInfo = $result['data'];
        
        // 处理用户登录逻辑
        // ...
        
        return '欢迎 ' . $userInfo['nickname'];
    }
}
