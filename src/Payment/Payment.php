<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\Payment;

use think\facade\Http;
use Ginger\ThinkWeChat\Exception\ConfigException;
use Ginger\ThinkWeChat\Exception\ApiException;

/**
 * 微信支付
 */
class Payment
{
    protected array $config = [];
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validateConfig();
    }
    
    /**
     * 验证配置
     */
    protected function validateConfig(): void
    {
        if (empty($this->config['mch_id'])) {
            throw new ConfigException('商户号 mch_id 未配置');
        }
        if (empty($this->config['key'])) {
            throw new ConfigException('API密钥 key 未配置');
        }
        if (empty($this->config['appid'])) {
            throw new ConfigException('支付 appid 未配置');
        }
    }
    
    /**
     * 获取配置
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * 统一下单（JSAPI）
     */
    public function unifiedOrder(string $openid, string $outTradeNo, int $totalFee, string $body, array $extra = []): array
    {
        $params = [
            'appid' => $this->getConfig('appid'),
            'mch_id' => $this->getConfig('mch_id'),
            'nonce_str' => $this->generateNonceStr(),
            'body' => $body,
            'out_trade_no' => $outTradeNo,
            'total_fee' => $totalFee,
            'spbill_create_ip' => request()->ip(),
            'notify_url' => $this->getConfig('notify_url'),
            'trade_type' => 'JSAPI',
            'openid' => $openid,
        ];
        
        // 附加参数
        if (isset($extra['attach'])) {
            $params['attach'] = $extra['attach'];
        }
        if (isset($extra['time_expire'])) {
            $params['time_expire'] = $extra['time_expire'];
        }
        
        // 签名
        $params['sign'] = $this->generateSign($params);
        
        // 请求
        $xml = $this->arrayToXml($params);
        $response = Http::post('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        
        $result = $this->xmlToArray($response);
        
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            // 生成前端调起支付参数
            $payParams = [
                'appId' => $this->getConfig('appid'),
                'timeStamp' => (string)time(),
                'nonceStr' => $this->generateNonceStr(),
                'package' => 'prepay_id=' . $result['prepay_id'],
                'signType' => 'MD5',
            ];
            $payParams['paySign'] = $this->generateSign($payParams);
            
            return [
                'success' => true,
                'data' => [
                    'prepay_id' => $result['prepay_id'],
                    'pay_params' => $payParams,
                ]
            ];
        }
        
        return ['success' => false, 'message' => $result['err_code_des'] ?? $result['return_msg'] ?? '下单失败'];
    }
    
    /**
     * 查询订单
     */
    public function queryOrder(string $outTradeNo): array
    {
        $params = [
            'appid' => $this->getConfig('appid'),
            'mch_id' => $this->getConfig('mch_id'),
            'out_trade_no' => $outTradeNo,
            'nonce_str' => $this->generateNonceStr(),
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        $xml = $this->arrayToXml($params);
        $response = Http::post('https://api.mch.weixin.qq.com/pay/orderquery', $xml);
        
        return $this->xmlToArray($response);
    }
    
    /**
     * 关闭订单
     */
    public function closeOrder(string $outTradeNo): array
    {
        $params = [
            'appid' => $this->getConfig('appid'),
            'mch_id' => $this->getConfig('mch_id'),
            'out_trade_no' => $outTradeNo,
            'nonce_str' => $this->generateNonceStr(),
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        $xml = $this->arrayToXml($params);
        $response = Http::post('https://api.mch.weixin.qq.com/pay/closeorder', $xml);
        
        $result = $this->xmlToArray($response);
        
        if ($result['return_code'] === 'SUCCESS') {
            return ['success' => true, 'message' => '关闭成功'];
        }
        
        return ['success' => false, 'message' => $result['return_msg'] ?? '关闭失败'];
    }
    
    /**
     * 申请退款（需要证书）
     */
    public function refund(string $outTradeNo, string $outRefundNo, int $totalFee, int $refundFee, string $reason = ''): array
    {
        $certPath = $this->getConfig('cert_path');
        $keyPath = $this->getConfig('key_path');
        
        if (!$certPath || !$keyPath) {
            return ['success' => false, 'message' => '未配置退款证书'];
        }
        
        if (!file_exists($certPath) || !file_exists($keyPath)) {
            return ['success' => false, 'message' => '证书文件不存在'];
        }
        
        $params = [
            'appid' => $this->getConfig('appid'),
            'mch_id' => $this->getConfig('mch_id'),
            'nonce_str' => $this->generateNonceStr(),
            'out_trade_no' => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'total_fee' => $totalFee,
            'refund_fee' => $refundFee,
        ];
        
        if ($reason) {
            $params['refund_desc'] = $reason;
        }
        
        $params['sign'] = $this->generateSign($params);
        
        $xml = $this->arrayToXml($params);
        
        // 使用证书发送请求
        $result = $this->postWithCert('https://api.mch.weixin.qq.com/secapi/pay/refund', $xml, $certPath, $keyPath);
        
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            return [
                'success' => true,
                'message' => '退款申请成功',
                'data' => [
                    'refund_id' => $result['refund_id'] ?? '',
                    'out_refund_no' => $result['out_refund_no'] ?? '',
                ]
            ];
        }
        
        return ['success' => false, 'message' => $result['err_code_des'] ?? $result['return_msg'] ?? '退款失败'];
    }
    
    /**
     * 查询退款
     */
    public function queryRefund(string $outRefundNo): array
    {
        $params = [
            'appid' => $this->getConfig('appid'),
            'mch_id' => $this->getConfig('mch_id'),
            'out_refund_no' => $outRefundNo,
            'nonce_str' => $this->generateNonceStr(),
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        $xml = $this->arrayToXml($params);
        $response = Http::post('https://api.mch.weixin.qq.com/pay/refundquery', $xml);
        
        $result = $this->xmlToArray($response);
        
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            return ['success' => true, 'data' => $result];
        }
        
        return ['success' => false, 'message' => $result['err_code_des'] ?? '查询失败'];
    }
    
    /**
     * 使用证书发送 POST 请求
     */
    protected function postWithCert(string $url, string $xml, string $certPath, string $keyPath): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $keyPath);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['return_code' => 'FAIL', 'return_msg' => '请求失败: ' . $error];
        }
        
        curl_close($ch);
        
        return $this->xmlToArray($response);
    }
    
    /**
     * 验证支付回调签名
     */
    public function verifyNotify(array $data): bool
    {
        if (!isset($data['sign'])) {
            return false;
        }
        
        $sign = $data['sign'];
        unset($data['sign']);
        
        return $this->generateSign($data) === $sign;
    }
    
    /**
     * 生成随机字符串
     */
    protected function generateNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    /**
     * 生成签名
     */
    protected function generateSign(array $params): string
    {
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && $value !== '' && !is_null($value)) {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string .= 'key=' . $this->getConfig('key');
        return strtoupper(md5($string));
    }
    
    /**
     * 数组转XML
     */
    protected function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }
    
    /**
     * XML转数组
     */
    protected function xmlToArray(string $xml): array
    {
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_decode(json_encode($data), true);
    }
}
