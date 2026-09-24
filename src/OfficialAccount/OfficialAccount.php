<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\OfficialAccount;

use think\facade\Cache;
use think\facade\Http;
use Ginger\ThinkWeChat\Exception\ConfigException;
use Ginger\ThinkWeChat\Exception\ApiException;

/**
 * 微信公众号
 */
class OfficialAccount
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
            throw new ConfigException('公众号 appid 未配置');
        }
        if (empty($this->config['secret'])) {
            throw new ConfigException('公众号 secret 未配置');
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
     * 获取 AccessToken
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'wechat_official_access_token';
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
     * 获取网页授权 URL
     */
    public function getOauthUrl(string $redirectUri, string $scope = 'snsapi_userinfo', string $state = ''): string
    {
        $appid = $this->getConfig('appid');
        
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $params = [
            'appid' => $appid,
            'redirect_uri' => urlencode($redirectUri),
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state,
        ];
        
        return $url . '?' . http_build_query($params) . '#wechat_redirect';
    }
    
    /**
     * 通过 code 获取用户信息
     */
    public function getUserInfoByCode(string $code): array
    {
        $appid = $this->getConfig('appid');
        $secret = $this->getConfig('secret');
        
        // 获取 access_token 和 openid
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $response = Http::get($url, [
            'appid' => $appid,
            'secret' => $secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            return ['success' => false, 'message' => $data['errmsg'] ?? '获取失败'];
        }
        
        // 获取用户信息
        $userUrl = 'https://api.weixin.qq.com/sns/userinfo';
        $userResponse = Http::get($userUrl, [
            'access_token' => $data['access_token'],
            'openid' => $data['openid'],
            'lang' => 'zh_CN',
        ]);
        
        $userData = json_decode($userResponse, true);
        
        if (isset($userData['openid'])) {
            return ['success' => true, 'data' => $userData];
        }
        
        return ['success' => false, 'message' => $userData['errmsg'] ?? '获取用户信息失败'];
    }
    
    /**
     * 发送模板消息
     */
    public function sendTemplateMessage(string $openid, string $templateId, string $url, array $data, array $miniProgram = []): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => '获取access_token失败'];
        }
        
        $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";
        
        $params = [
            'touser' => $openid,
            'template_id' => $templateId,
            'url' => $url,
            'data' => $data,
        ];
        
        if (!empty($miniProgram)) {
            $params['miniprogram'] = $miniProgram;
        }
        
        $response = Http::post($apiUrl, json_encode($params));
        $result = json_decode($response, true);
        
        if ($result['errcode'] === 0) {
            return ['success' => true, 'message' => '发送成功'];
        }
        
        return ['success' => false, 'message' => $result['errmsg'] ?? '发送失败'];
    }
    
    /**
     * 发送客服消息
     */
    public function sendCustomMessage(string $openid, string $msgType, array $content): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => '获取access_token失败'];
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$accessToken}";
        
        $params = [
            'touser' => $openid,
            'msgtype' => $msgType,
            $msgType => $content,
        ];
        
        $response = Http::post($url, json_encode($params, JSON_UNESCAPED_UNICODE));
        $result = json_decode($response, true);
        
        if ($result['errcode'] === 0) {
            return ['success' => true, 'message' => '发送成功'];
        }
        
        return ['success' => false, 'message' => $result['errmsg'] ?? '发送失败'];
    }
    
    /**
     * 获取用户列表
     */
    public function getUserList(string $nextOpenid = ''): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => '获取access_token失败'];
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token={$accessToken}";
        if ($nextOpenid) {
            $url .= "&next_openid={$nextOpenid}";
        }
        
        $response = Http::get($url);
        $data = json_decode($response, true);
        
        if (isset($data['data'])) {
            return ['success' => true, 'data' => $data];
        }
        
        return ['success' => false, 'message' => $data['errmsg'] ?? '获取失败'];
    }
    
    /**
     * 获取用户信息
     */
    public function getUserInfo(string $openid): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => '获取access_token失败'];
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$accessToken}&openid={$openid}&lang=zh_CN";
        
        $response = Http::get($url);
        $data = json_decode($response, true);
        
        if (isset($data['openid'])) {
            return ['success' => true, 'data' => $data];
        }
        
        return ['success' => false, 'message' => $data['errmsg'] ?? '获取失败'];
    }
    
    /**
     * 验证服务器配置（消息服务器）
     */
    public function verifyServer(string $signature, string $timestamp, string $nonce, string $token): bool
    {
        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        
        return $tmpStr === $signature;
    }
}
