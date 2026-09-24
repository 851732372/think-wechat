<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat;

use Ginger\ThinkWeChat\MiniProgram\MiniProgram;
use Ginger\ThinkWeChat\Payment\Payment;
use Ginger\ThinkWeChat\OfficialAccount\OfficialAccount;
use Ginger\ThinkWeChat\Exception\ConfigException;

/**
 * 微信主类
 */
class WeChat
{
    /**
     * 配置
     */
    protected array $config = [];
    
    /**
     * 实例缓存
     */
    protected static array $instances = [];
    
    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    /**
     * 获取配置
     */
    public function getConfig(string $key = '', mixed $default = null): mixed
    {
        if (empty($key)) {
            return $this->config;
        }
        return $this->config[$key] ?? $default;
    }
    
    /**
     * 微信小程序
     * @throws ConfigException
     */
    public function miniProgram(): MiniProgram
    {
        $config = array_merge(
            $this->config['miniprogram'] ?? [],
            ['cache_store' => $this->config['cache_store'] ?? 'file']
        );
        return new MiniProgram($config);
    }
    
    /**
     * 微信支付
     * @throws ConfigException
     */
    public function payment(): Payment
    {
        $config = array_merge(
            $this->config['payment'] ?? [],
            ['cache_store' => $this->config['cache_store'] ?? 'file']
        );
        return new Payment($config);
    }
    
    /**
     * 微信公众号
     * @throws ConfigException
     */
    public function officialAccount(): OfficialAccount
    {
        $config = array_merge(
            $this->config['official_account'] ?? [],
            ['cache_store' => $this->config['cache_store'] ?? 'file']
        );
        return new OfficialAccount($config);
    }
}
