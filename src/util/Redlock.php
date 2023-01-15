<?php

namespace Djdj\Base\util;

class Redlock
{

    private string $key;
    private string $id;

    public function __construct($key, $id)
    {
        $this->key = $key;
        $this->id = $id;
    }

    /**
     * 解锁,对比锁的值是否与自身相等,防止超时后解锁其他线程的锁
     */
    public function unlock()
    {
        $redis = RedisUtils::select();
        if ($this->id == $redis->get($this->key)) {
            $redis->del($this->key);
        }
    }
}
