<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\Exception;

/**
 * API 请求异常
 */
class ApiException extends WeChatException
{
    /**
     * 微信错误代码
     */
    protected string $errCode = '';
    
    /**
     * 构造函数
     */
    public function __construct(string $message = 'API请求失败', string $errCode = '', array $data = [])
    {
        parent::__construct($message, 2001, $data);
        $this->errCode = $errCode;
    }
    
    /**
     * 获取微信错误代码
     */
    public function getErrCode(): string
    {
        return $this->errCode;
    }
}
