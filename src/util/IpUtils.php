<?php

namespace Djdj\Base\util;

class IpUtils
{
    public static function realIp()
    {
        $ip = request()->server('REMOTE_ADDR', '');
        $proxyIpHeader = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'];
        foreach ($proxyIpHeader as $header) {
            $tempIP = request()->server($header);
            if (empty($tempIP)) {
                continue;
            }
            $tempIP = trim(explode(',', $tempIP)[0]);
            if (request()->isValidIP($tempIP)) {
                $ip = $tempIP;
                break;
            }
        }
        if (!request()->isValidIP($ip)) {
            $ip = '0.0.0.0';
        }
        return $ip;
    }
}