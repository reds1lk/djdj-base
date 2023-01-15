<?php

namespace Djdj\Base\common;

use Djdj\Base\util\IpUtils;

/**
 * 加密解密类
 */
class AesUtils
{
    private static $isdebug = 'imdewang';
    private static $key = 'd2hvaXNkZXdhbmc=';
    public static $appoint = 'dXJkZXdhbmc=dXJkZXdhbmc=';

    /**
     * 是否加密
     */
    public static function isEncrypted()
    {
        return !(request()->param('isdebug') == AesUtils::$isdebug . date('YmdH') || in_array(IpUtils::realIp(), config('encrypt.whitelist', [])));
    }

    /* 加密 */
    public static function encrypt($data)
    {
        return openssl_encrypt(json_encode($data), 'AES-128-ECB', self::$key);
    }

    /* 解密 */
    public static function decrypt($data)
    {
        return json_decode(openssl_decrypt($data, 'AES-128-ECB', self::$key), true);
    }
}
