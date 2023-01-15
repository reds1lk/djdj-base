<?php

namespace Djdj\Base\common;

use Djdj\Base\constant\AuthConstant;
use Djdj\Base\exception\AuthException;
use Djdj\Base\exception\BizException;
use think\exception\Handle;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class GlobalExceptionHandle extends Handle
{

    public function render($request, Throwable $e): Response
    {
        $response = new JsonResponse();
        if ($e instanceof AuthException) {
            $response->setCode(401);
            $response->setMsg(AuthConstant::STATE_LIST[$e->state]);
        } elseif ($e instanceof ValidateException) {
            $response->setCode(0);
            if (is_array($e->getError())) {
                $response->setMsg('参数错误');
                $response->setData($e->getError());
            } else {
                $response->setMsg($e->getError());
            }
        } elseif ($e instanceof BizException) {
            $response->setCode(0);
            $response->setMsg($e->getMessage());
        } else {
            $response->setCode(500);
            $response->setMsg(config('app.app_debug') ? $e->getMessage() : '服务器异常');
        }
        if (config('app.app_debug')) {
            $response->setTrace($e->getTrace());
            $response->setShowTrace(true);
        }
        return $response->json();
    }
}
