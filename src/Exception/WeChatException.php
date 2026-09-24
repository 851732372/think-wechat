<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\Exception;

use Exception;

/**
 * 微信异常基类
 */
class WeChatException extends Exception
{
    /**
     * 错误数据
     */
    protected array $data = [];
    
    /**
     * 构造函数
     */
    public function __construct(string $message = '', int $code = 0, array $data = [], ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }
    
    /**
     * 获取错误数据
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * 设置错误数据
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
}
