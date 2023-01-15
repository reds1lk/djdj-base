<?php

namespace Djdj\Base\middleware;

use Closure;
use Djdj\Base\common\AesUtils;
use Djdj\Base\model\ApiLog;
use think\Request;

/**
 * 请求日志
 */
class AccessLog
{
    public function handle(Request $request, Closure $next)
    {
        if (env('access_log', false)) {
            $startTime = microtime(true);
            $response = $next($request);
            $data = [
                'method' => $request->method(),
                'uri' => $request->pathinfo(),
                'action' => $request->controller() . '.' . $request->action(),
                'header' => $request->header(),
                'params' => $request->app_params,
                'response' => json_decode($response->getContent(), true),
                'duration' => (int)((microtime(true) - $startTime) * 1000),
                'create_time' => date('Y-m-d H:i:s'),
            ];
            //解密响应数据
            if (AesUtils::isEncrypted()) {
                $data['response']['data'] = AesUtils::decrypt($data['response']['data']);
            }
            ApiLog::insert($data);
            return $response;
        }
        return $next($request);
    }
}
