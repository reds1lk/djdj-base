<?php

namespace Djdj\Base\constant;

interface AuthConstant
{
    const STATE = [
        'NOT_TOKEN' => 'NOT_TOKEN',
        'INVALID_TOKEN' => 'INVALID_TOKEN',
        'TOKEN_TIMEOUT' => 'TOKEN_TIMEOUT',
        'BE_REPLACED' => 'BE_REPLACED',
        'KICK_OUT' => 'KICK_OUT',
    ];

    const STATE_LIST = [
        self::STATE['NOT_TOKEN'] => '用户未登录',
        self::STATE['INVALID_TOKEN'] => '无效的Token',
        self::STATE['TOKEN_TIMEOUT'] => '登录已过期',
        self::STATE['BE_REPLACED'] => '账户已在其他地方登录',
        self::STATE['KICK_OUT'] => '用户已被强制下线',
    ];
}