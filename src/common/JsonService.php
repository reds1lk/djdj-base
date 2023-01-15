<?php

namespace Djdj\Base\common;

class JsonService
{
    public static function result($code, $msg = '', $data = [], $count = 0)
    {
        return (new JsonResponse($code, $msg, $data))->json();
    }

    public static function success($msg, $data = [])
    {
        if (is_array($msg)) {
            $data = $msg;
            $msg = 'ok';
        }
        return self::result(1, $msg, $data);
    }

    public static function successful($msg = 'ok', $data = [], $status = 1)
    {
        if (!is_string($msg)) {
            $data = $msg;
            $msg = 'ok';
        }
        return self::result($status, $msg, $data);
    }

    public static function status($status, $msg, $result = [])
    {
        $status = strtoupper($status);
        if (is_array($msg)) {
            $result = $msg;
            $msg = 'ok';
        }
        return self::result(1, $msg, compact('status', 'result'));
    }

    public static function fail($msg, $data = [], $code = 0)
    {
        if (is_array($msg)) {
            $data = $msg;
            $msg = 'fail';
        }
        return self::result($code, $msg, $data);
    }

    public static function failTrace($msg, $trace = [], $code = 0)
    {
        return (new JsonResponse($code, $msg, null, $trace))->setShowTrace(true)->json();
    }
}