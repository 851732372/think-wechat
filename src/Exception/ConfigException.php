<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\Exception;

/**
 * 配置异常
 */
class ConfigException extends WeChatException
{
    /**
     * 构造函数
     */
    public function __construct(string $message = '配置错误', array $data = [])
    {
        parent::__construct($message, 1001, $data);
    }
}
