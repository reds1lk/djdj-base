<?php

namespace Djdj\Base\middleware;

use Closure;
use Djdj\Base\common\AesUtils;
use Djdj\Base\exception\BizException;
use think\Request;

/**
 * 验签
 */
class CheckSign
{
    public function handle(Request $request, Closure $next)
    {
        $request->app_params = (object)[];
        if (AesUtils::isEncrypted()) {
            $params = AesUtils::decrypt($request->param('signs'));
            if (!isset($data['the_wang'])) {
                throw new BizException('缺少那个呢');
            }
            if ($data['the_wang'] != AesUtils::$appoint) {
                throw new BizException('非法请求');
            }
        } else {
            $params = $request->param();
        }
        foreach ($params as $k => $v) {
            if (is_null($v) || (is_string($v) && trim($v) == '')) {
                continue;
            }
            $request->$k = $v;
            $request->app_params->$k = $v;
        }
        return $next($request);
    }
}
