<?php

declare(strict_types=1);

namespace Ginger\ThinkWeChat\Utils;

/**
 * 微信工具类
 */
class Helper
{
    /**
     * XML 转数组
     */
    public static function xmlToArray(string $xml): array
    {
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_decode(json_encode($data), true);
    }
    
    /**
     * 数组转 XML
     */
    public static function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }
    
    /**
     * 生成随机字符串
     */
    public static function generateNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    /**
     * HMAC-SHA256 签名
     */
    public static function hmacSha256(string $data, string $key): string
    {
        return hash_hmac('sha256', $data, $key);
    }
    
    /**
     * 解密 PKCS#7 填充
     */
    public static function pkcs7Unpad(string $data): string
    {
        $pad = ord(substr($data, -1));
        if ($pad < 1 || $pad > 32) {
            return $data;
        }
        return substr($data, 0, -$pad);
    }
}
