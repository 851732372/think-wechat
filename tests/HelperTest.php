<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\Tests;

use PHPUnit\Framework\TestCase;
use Ginger\ThinkWeChat\Utils\Helper;

/**
 * 工具类测试
 */
class HelperTest extends TestCase
{
    /**
     * 测试 XML 转数组
     */
    public function testXmlToArray(): void
    {
        $xml = '<xml><name>test</name><age>18</age></xml>';
        $result = Helper::xmlToArray($xml);
        
        $this->assertEquals(['name' => 'test', 'age' => '18'], $result);
    }
    
    /**
     * 测试数组转 XML
     */
    public function testArrayToXml(): void
    {
        $data = ['name' => 'test', 'age' => 18];
        $xml = Helper::arrayToXml($data);
        
        $this->assertStringContainsString('<name><![CDATA[test]]></name>', $xml);
        $this->assertStringContainsString('<age>18</age>', $xml);
    }
    
    /**
     * 测试生成随机字符串
     */
    public function testGenerateNonceStr(): void
    {
        $str = Helper::generateNonceStr(16);
        
        $this->assertEquals(16, strlen($str));
        $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $str);
    }
    
    /**
     * 测试 HMAC-SHA256
     */
    public function testHmacSha256(): void
    {
        $result = Helper::hmacSha256('data', 'key');
        
        $this->assertEquals(64, strlen($result)); // sha256 hex = 64 chars
        $this->assertEquals(hash_hmac('sha256', 'data', 'key'), $result);
    }
}
