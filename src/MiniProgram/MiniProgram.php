<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\MiniProgram;

use think\facade\Cache;
use think\facade\Http;
use Ginger\ThinkWeChat\Exception\ConfigException;
use Ginger\ThinkWeChat\Exception\ApiException;

/**
 * 微信小程序
 */
class MiniProgram
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
        if (empty($this->config['appid'])) {
            throw new ConfigException('小程序 appid 未配置');
        }
        if (empty($this->config['secret'])) {
            throw new ConfigException('小程序 secret 未配置');
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
     * 获取缓存存储
     */
    protected function getCacheStore(): \think\contract\CacheHandlerInterface
    {
        $store = $this->getConfig('cache_store', 'file');
        return Cache::store($store);
    }
    
    /**
     * code 换取 openid 和 session_key
     */
    public function code2Session(string $code): array
    {
        try {
            $url = 'https://api.weixin.qq.com/sns/jscode2session';
            $response = Http::get($url, [
                'appid' => $this->getConfig('appid'),
                'secret' => $this->getConfig('secret'),
                'js_code' => $code,
                'grant_type' => 'authorization_code',
            ]);
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException('解析响应失败');
            }
            
            if (isset($data['openid'])) {
                return ['success' => true, 'data' => $data];
            }
            
            throw new ApiException(
                $data['errmsg'] ?? '请求失败',
                $data['errcode'] ?? ''
            );
        } catch (\Exception $e) {
            if ($e instanceof ApiException) {
                throw $e;
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 解密手机号
     */
    public function decryptPhone(string $code, string $encryptedData, string $iv): array
    {
        // 先获取 session
        $sessionResult = $this->code2Session($code);
        if (!$sessionResult['success']) {
            return $sessionResult;
        }
        
        $sessionKey = $sessionResult['data']['session_key'];
        $openid = $sessionResult['data']['openid'];
        $unionid = $sessionResult['data']['unionid'] ?? '';
        
        // 解密
        $phoneData = $this->decryptData($encryptedData, $iv, $sessionKey);
        if (!$phoneData) {
            return ['success' => false, 'message' => '解密失败'];
        }
        
        return [
            'success' => true,
            'data' => [
                'phone' => $phoneData['purePhoneNumber'] ?? '',
                'openid' => $openid,
                'unionid' => $unionid,
                'countryCode' => $phoneData['countryCode'] ?? '86',
            ]
        ];
    }
    
    /**
     * 获取小程序码（永久）
     */
    public function getUnlimitedQRCode(string $scene, array $options = []): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => '获取access_token失败'];
        }
        
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
        
        $data = [
            'scene' => $scene,
            'page' => $options['page'] ?? '',
            'width' => $options['width'] ?? 430,
            'auto_color' => $options['auto_color'] ?? false,
            'line_color' => $options['line_color'] ?? ['r' => 0, 'g' => 0, 'b' => 0],
            'is_hyaline' => $options['is_hyaline'] ?? false,
        ];
        
        $response = Http::post($url, json_encode($data));
        
        // 如果是二进制图片数据
        if (substr($response, 0, 1) !== '{') {
            return ['success' => true, 'data' => ['image' => base64_encode($response)]];
        }
        
        $result = json_decode($response, true);
        return ['success' => false, 'message' => $result['errmsg'] ?? '生成失败'];
    }
    
    /**
     * 发送订阅消息
     */
    public function sendSubscribeMessage(string $openid, string $templateId, array $data, string $page = ''): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => '获取access_token失败'];
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        
        $params = [
            'touser' => $openid,
            'template_id' => $templateId,
            'data' => $data,
        ];
        
        if ($page) {
            $params['page'] = $page;
        }
        
        $response = Http::post($url, json_encode($params));
        $result = json_decode($response, true);
        
        if ($result['errcode'] === 0) {
            return ['success' => true, 'message' => '发送成功'];
        }
        
        return ['success' => false, 'message' => $result['errmsg'] ?? '发送失败'];
    }
    
    /**
     * 获取 AccessToken
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'wechat_miniprogram_access_token';
        $store = $this->getCacheStore();
        
        $token = $store->get($cacheKey);
        if ($token) {
            return $token;
        }
        
        $appid = $this->getConfig('appid');
        $secret = $this->getConfig('secret');
        
        if (!$appid || !$secret) {
            return null;
        }
        
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $response = Http::get($url, [
            'grant_type' => 'client_credential',
            'appid' => $appid,
            'secret' => $secret,
        ]);
        
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            $token = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? 7200;
            $store->set($cacheKey, $token, $expiresIn - 200);
            return $token;
        }
        
        return null;
    }
    
    /**
     * 解密数据
     */
    protected function decryptData(string $encryptedData, string $iv, string $sessionKey): ?array
    {
        $appid = $this->getConfig('appid');
        
        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        
        $result = openssl_decrypt($aesCipher, 'AES-128-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIV);
        
        if (!$result) {
            return null;
        }
        
        $data = json_decode($result, true);
        
        // 验证水印
        if (isset($data['watermark']['appid']) && $data['watermark']['appid'] !== $appid) {
            return null;
        }
        
        return $data;
    }
}
