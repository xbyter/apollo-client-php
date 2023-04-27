<?php

namespace Xbyter\ApolloClient;

class ApolloSignature
{

    /**
     * 生成签名字符串
     *
     * @param int $timestamp 13位时间戳
     * @param string $pathWithQuery 请求uri
     * @param string $secret 密钥
     * @return string
     */
    public static function generateSignature(int $timestamp, string $pathWithQuery, string $secret): string
    {
        if (!$timestamp || !$pathWithQuery || !$secret) {
            return '';
        }
        return base64_encode(
            hash_hmac(
                'sha1',
                mb_convert_encoding($timestamp . "\n" . $pathWithQuery, "UTF-8"),
                $secret,
                true
            )
        );
    }
}
