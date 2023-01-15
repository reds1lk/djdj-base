<?php

namespace Djdj\Base\util;

use Djdj\Base\exception\BizException;
use Exception;
use Predis\Client;

class RedisUtils
{
    const LOCK = 'redlock';

    /**
     * 静态调用redis
     */
    public static function select(int $database = null)
    {
        return new Client([
            'scheme' => config('redis.scheme'),
            'host' => config('redis.host'),
            'port' => config('redis.port'),
            'password' => config('redis.password'),
            'database' => $database ?? config('redis.database'),
        ]);
    }

    /**
     * 获取redis缓存,如没有缓存则执行闭包方法并记录缓存
     */
    public static function getOrSet($key, callable $closure)
    {
        $result = self::select()->get($key);
        if ($result) {
            return json_decode($result, true);
        } else {
            $result = $closure();
            self::select()->setex($key, self::randomExpire(), json_encode($result));
            return $result;
        }
    }

    /**
     * 生成随机过期时间
     */
    public static function randomExpire()
    {
        return rand(3600, 7200);
    }

    /**
     * 清除缓存
     */
    public static function delete(...$keys)
    {
        $cacheKeys = [];
        foreach ($keys as $key) {
            if (str_contains($key, '*')) {
                $cacheKeys[] = self::select()->keys($key);
            } else {
                $cacheKeys[] = $key;
            }
        }
        if (!empty($cacheKeys)) {
            self::select()->del($cacheKeys);
        }
    }

    /**
     * 拼接key
     */
    public static function cacheKey(...$keys)
    {
        $delimiter = ':';
        if (empty($keys)) {
            throw new Exception('key cannot be empty');
        }
        $cacheKey = env('app_name', 'default');
        foreach ($keys as $key) {
            if ($key)
                $cacheKey .= $delimiter . $key;
        }
        return $cacheKey;
    }

    /**
     * 获取分布式锁
     */
    public static function lock(...$keys)
    {
        $key = self::cacheKey(self::LOCK, ...$keys);
        $id = md5(uniqid(mt_rand(), true));
        if (self::select()->setnx($key, $id)) {
            self::select()->expire($key, 15);
            return new Redlock($key, $id);
        }
        return null;
    }

    /**
     * 获取分布式锁，获取不到抛出异常
     */
    public static function lockThrowEx(...$keys)
    {
        $lock = self::lock(...$keys);
        if (!$lock) {
            throw new BizException('请求过于频繁');
        }
        return $lock;
    }

    /**
     * 获取分布式锁，获取失败则重试
     */
    public static function lockRetry(...$keys)
    {
        while (true) {
            if ($lock = self::lock(...$keys)) {
                return $lock;
            } else {
                usleep(100);
                self::lockRetry(...$keys);
            }
        }
    }
}
