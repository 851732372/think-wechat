<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\Tests;

use PHPUnit\Framework\TestCase;
use Ginger\ThinkWeChat\WeChat;

/**
 * 基础测试
 */
class WeChatTest extends TestCase
{
    /**
     * 测试实例化
     */
    public function testInstance(): void
    {
        $config = [
            'miniprogram' => [
                'appid' => 'test-appid',
                'secret' => 'test-secret',
            ],
        ];
        
        $wechat = new WeChat($config);
        
        $this->assertInstanceOf(WeChat::class, $wechat);
        $this->assertEquals('test-appid', $wechat->getConfig('miniprogram')['appid']);
    }
    
    /**
     * 测试获取配置
     */
    public function testGetConfig(): void
    {
        $wechat = new WeChat(['key' => 'value']);
        
        $this->assertEquals('value', $wechat->getConfig('key'));
        $this->assertNull($wechat->getConfig('not-exist'));
        $this->assertEquals('default', $wechat->getConfig('not-exist', 'default'));
    }
    
    /**
     * 测试获取小程序实例
     */
    public function testMiniProgram(): void
    {
        $wechat = new WeChat([
            'miniprogram' => ['appid' => 'test', 'secret' => 'test'],
        ]);
        
        $miniProgram = $wechat->miniProgram();
        
        $this->assertInstanceOf(\Ginger\ThinkWeChat\MiniProgram\MiniProgram::class, $miniProgram);
    }
    
    /**
     * 测试获取支付实例
     */
    public function testPayment(): void
    {
        $wechat = new WeChat([
            'payment' => [
                'mch_id' => 'test',
                'key' => 'test',
                'appid' => 'test',
            ],
        ]);
        
        $payment = $wechat->payment();
        
        $this->assertInstanceOf(\Ginger\ThinkWeChat\Payment\Payment::class, $payment);
    }
    
    /**
     * 测试获取公众号实例
     */
    public function testOfficialAccount(): void
    {
        $wechat = new WeChat([
            'official_account' => ['appid' => 'test', 'secret' => 'test'],
        ]);
        
        $official = $wechat->officialAccount();
        
        $this->assertInstanceOf(\Ginger\ThinkWeChat\OfficialAccount\OfficialAccount::class, $official);
    }
}
